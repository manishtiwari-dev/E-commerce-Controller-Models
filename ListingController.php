<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use Validator;
use Illuminate\Support\Str;
use App\Models\Language;
use Modules\Ecommerce\Models\Category;
use Modules\Ecommerce\Models\CategoryDescription;
use Modules\Ecommerce\Models\Fields;
use Modules\Ecommerce\Models\Listing;
use Modules\Ecommerce\Models\ListingOptions;
use Modules\Ecommerce\Models\ListingOptionsValues;
use Modules\Ecommerce\Models\ListingAttribute;
use Modules\Ecommerce\Models\ListingDescription;
use Modules\Ecommerce\Models\ListingFeature;

use Illuminate\Support\Facades\Storage;
use Modules\Ecommerce\Models\Supplier;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\SeoMeta;
use DB;
use Illuminate\Database\Eloquent\Builder;




class ListingController extends Controller
{
    
    public $page = 'products';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        
        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();

        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $catId = $request->catId;

        $language = $request->language;
        
        $data_query = Listing::with('products_to_categories','products_to_categories.categorydescription');
        
        if(!empty($search))
            // $data_query = $data_query->where("name","LIKE", "%{$search}%")->orWhere("email", "LIKE", "%{$search}%");

            /* order by sorting */
        if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('listing_id','DESC');
        }

        if(!empty($catId)){
            if(!empty($data_query->products_to_categories))
            {
                
            $data_query =$data_query->whereHas('products_to_categories', function (Builder $query) use($catId) {
              $query->where('categories_id',$catId);
             });

        }
    }

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;
        
        $user_count = $data_query->count();

        $data_list = $data_query->skip($skip)->take($perPage)->get();

        $data_list = $data_list->map(function($data) use ($language)  {

            $cate = $data->productdescription()->where('languages_id', ApiHelper::getLangid($language))->first();

            $data->listing_name = ($cate == null) ? '' : $cate->listing_name;
            $data->listing_description = ($cate == null) ? '' : $cate->listing_description;
            $data->status = ($data->status == 1) ? "active":"deactive";
            $data->listing_image = ApiHelper::getFullImageUrl($data->listing_image);
              
           $catname = '';

             if(!empty($data->products_to_categories)){
              if(!empty($data->products_to_categories->categorydescription)){

                 $cname = $data->products_to_categories->categorydescription()->first();
                
                    $catname = ($cname == null) ? '' : $cname->categories_name;
                }
            }

            $data->categories_name = $catname;
      
            return $data;
        });

       
        
        
        $res = [
            'data'=>$data_list,
            'current_page'=>$current_page,
            'total_records'=>$user_count,
            'total_page'=>ceil((int)$user_count/(int)$perPage),
            'per_page'=>$perPage
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     * use this function to get all helper data for product create. 
     */
    public function create(Request $request)
    {
        $language = $request->language;

        $res = [];

        $categoryItem = array();

        $cat = Category::where('parent_id',0)->get();

        foreach ($cat as $key => $cat) {
            $cate = $cat->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
            array_push($categoryItem, 
                [
                    "value"=>$cat->categories_id, 
                    "label"=>($cate == null) ? '' : $cate->categories_name
                ]);
            // sub category import

            $sub_category = Category::where('parent_id',$cat->categories_id)->get();
            if(sizeof($sub_category) >  0){
               
               foreach ($sub_category as $key => $sub) {
                   $subcat = $sub->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
                    
                    array_push($categoryItem, [ 
                        "value"=>$sub->categories_id, 
                        "label"=>($subcat == null) ? '' : '--'.$subcat->categories_name
                    ]);

               

                 //sub sub category import
             $sub_sub_category = Category::where('parent_id',$sub->categories_id)->get();
             if(sizeof($sub_sub_category) >  0){

               foreach($sub_sub_category as $key =>$sub_cat){
                $subcate = $sub_cat->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();

                   array_push($categoryItem, [ 
                        "value"=>$sub_cat->categories_id, 
                        "label"=>($subcate == null) ? '' : '---'.$subcate->categories_name
                    ]);

               }

             }

            }
        }

        }

             


        $res['category'] = $categoryItem;
        
       
              $res['language'] = ApiHelper::otherSupportLang();

        // $res['product_attribute'] = ListingOptions::product_options_with_value();
        // $res['supplier'] = Supplier::select('supplier_id as value','supplier_name as label')->get();
        // $res['brand'] = Brand::all();
        
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
   public function store(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        $alldata = $request->all();

// dd([0]["name"]);
        // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        

        // return ApiHelper::JSON_RESPONSE(false,$request->only('attributes'),'PAGE_ACCESS_DENIED');
        


        // validation check 
        // $rules = [
        //     'categories_name.*' => 'required|string',
        //     'sort_order' => 'required',
        // ];
        // $validator = Validator::make($request->all(), $rules);
        // if ($validator->fails()) {
        //     return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        // }

        try {
       
              DB::beginTransaction(); // begin transaction

        $saveData = $request->only(['video_url','status','listing_image','listing_tag',]);


        $product = Listing::create($saveData);

            // image and gallery store

            if($request->has("listing_image") && !empty($request->listing_image))
                ApiHelper::image_upload_with_crop($api_token,$request->listing_image, 1, $product->listing_id);

            if($request->has('gallery_ids')){

                $insData = [];

                if (sizeof($request->gallery_ids)) {
                    foreach ($request->gallery_ids as $key => $gallery) {
                        ApiHelper::image_upload_with_crop($api_token,$gallery, 1, $product->listing_id,'gallery');
                        array_push($insData,[
                            'listing_id'=>$product->listing_id,
                            'images_id'=>$gallery
                        ]);
                    }
                    $product->products_to_gallery()->attach($insData);
                }
            }
            

            // store cat details
            foreach (ApiHelper::otherSupportLang() as $key => $value) {

                $name = "listing_name_".$value->languages_id;
                $desc = "listing_description_".$value->languages_id;
                $meta_title = "seometa_title_".$value->languages_id;
                $meta_desc = "seometa_desc_".$value->languages_id;
            
                // if($value->languages_code == 'en'){
                //     $Product = Product::find($product->product_id);
                //     $Product->product_slug = Str::slug($request->$name);
                //     $Product->save();
                // }

                 $desc = ListingDescription::create([
                'listing_id'=>$product->listing_id,
                'languages_id'=>$value->languages_id,
                'listing_name'=>$request->$name,
                'listing_description'=>$request->$desc,
            ]);   
            
                
                if(!empty($request->$meta_title) || !empty($request->$meta_desc)){
                    SeoMeta::create([
                        'page_type'=>2,
                        'reference_id'=>$desc->listing_description_id,
                        'language_id'=>$value->languages_id,
                        'seometa_title'=>$request->$meta_title,
                        'seometa_desc'=>$request->$meta_desc, 
                    ]);
                }
            }

            // attach category to product
            if(sizeof($request->categories_id)){
                // for product to category
                $productToCategory = [];
                foreach ($request->categories_id as $key => $catid) {
                    $productToCategory[$key]['categories_id'] = $catid;
                    $productToCategory[$key]['listing_id'] = $product->listing_id;
                } 
                $product->products_to_categories()->attach($productToCategory);
                
                // attching  product_option to category so that we can fetch option selected in category filter
                foreach ($request->categories_id as $key => $catid) {
                    $optionToCategory = [];

                    if($request->has('productAttribute')){
                        if(!empty($request->productAttribute) && sizeof($request->productAttribute) > 0 ){
                            foreach ($request->productAttribute as $key => $attributes) {
                                $optionToCategory[$key]['categories_id'] = $catid;
                                $optionToCategory[$key]['listing_options_id'] = $attributes['options_id'];
                            }
                        }
                    }


                    // if($request->has('product_brand_id') && !empty($request->product_brand_id))
                    //     $brandToCategory = ['categories_id'=>$catid,'brand_id'=>$request->product_brand_id];
                    

                    $category = Category::find($catid);
                    $category->options()->sync($optionToCategory);  // attach option to category
                  //  $category->brands()->sync($brandToCategory);  // attach brand  to category
                } 
                
            }

            // // attch suppliere
            // if(!empty($request->supplier_ids)){
            //     if(sizeof($request->supplier_ids)){
            //         $supplier_info = [];
            //         foreach ($request->supplier_ids as $key => $catid) {
            //             $supplier_info[$key]['supplier_id'] = $catid;
            //             $supplier_info[$key]['product_id'] = $product->product_id;
            //         } 
            //         $product->products_to_supplier()->attach($supplier_info);
            //     }
            // }


            // // attach product type field values
            // $field_values = [];
            // $product_type = ProductType::product_type_with_fields($request->product_type_id);
            // if(isset($product_type->fields_group) && !empty($product_type->fields_group)){
            //     foreach ($product_type->fields_group as $fields_group_key => $fields_group) {   // looping fieldgroup
                    
            //         if(isset($fields_group->fields) && !empty($fields_group->fields)){
            //             foreach ($fields_group->fields as $fields_key => $fields) {     //looping all field inside 
                            
            //                 $fild_name = $fields->field_name;
            //                 array_push($field_values,[
            //                     'product_id'=>$product->product_id,
            //                     'fieldsgroup_id'=>$fields_group->fieldsgroup_id,
            //                     'fields_id'=>$fields->fields_id,
            //                     'field_name'=>$fild_name,
            //                     'field_value'=>$request->$fild_name
            //                 ]);                    
            //             }                
            //         }

            //     }

            // }

            // $product->products_type_field_value()->attach($field_values);
        
           $attributesArr = [];

            // attact attribute to products
            if($request->has('attributes')){

                if(!empty($request['attributes'])){
                        
                    foreach ($request['attributes'] as $key => $attr) {

                        $options_id = $attr['id'];
                        // array_push($attributesArr, $attr);

                        if(!empty($attr['options']) && sizeof($attr['options']) > 0 ){


                            foreach ($attr['options'] as $key => $options) {
                                
                                if ($options['name']) {
                                    ListingAttribute::create([
                                        'options_id'=>$options_id,
                                        'options_values_id'=>$options['id'],
                                        'options_values_price'=>$options['price'],
                                        'listing_id' => $product->listing_id
                                    ]);

                                }

                            }

                        }
                        
                    }
                }

             }

            // // return ApiHelper::JSON_RESPONSE(false,$attributesArr,'');
            // if($request->has('bulkPrice') && sizeof($request->bulkPrice)){
            //     foreach ($request->bulkPrice as $key => $bulkPrice) {
            //         if(!empty($bulkPrice['product_qty']) && !empty($bulkPrice['product_price'])){
            //             ProductPrice::create([
            //                 'product_id'=>$product->product_id,
            //                 'product_qty'=>$bulkPrice['product_qty'],
            //                 'product_price'=>$bulkPrice['product_price']
            //             ]);
            //         }
            //     }
            // }


            
            // attach pro featured to product
            if($request->has('proFeature') && sizeof($request->proFeature) > 0 ){

                foreach ($request->proFeature as $key => $proFeature) {
                    ListingFeature::create([
                        'listing_id'=>$product->listing_id,
                        'feature_title'=>$proFeature['feature_key'],
                        'feature_value'=>$proFeature['feature_key_value']
                    ]);
                }

            }
       
             DB::commit();       // db commit

            return ApiHelper::JSON_RESPONSE(true,$product,'SUCCESS_PRODUCT_ADD');

        } catch (\Throwable $th) {
            \Log::error($th->getMessage());
            DB::rollback();     // db rollback
            return ApiHelper::JSON_RESPONSE(false,[], $th->getMessage());
        }

    }


   //  /**
   //   * Display the specified resource.
   //   *
   //   * @param  int  $id
   //   * @return \Illuminate\Http\Response
   //   */
   //  public function show($id)
   //  {
   //      //
   //  }

   //  /**
   //   * Show the form for editing the specified resource.
   //   *
   //   * @param  int  $id
   //   * @return \Illuminate\Http\Response
   //   */



   //  /**
   //   * Update the specified resource in storage.
   //   *
   //   * @param  \Illuminate\Http\Request  $request
   //   * @param  int  $id
   //   * @return \Illuminate\Http\Response
   //   */
   public function update(Request $request)
   {

       // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');

       // Validate user page access
       $api_token = $request->api_token;

       // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))

       $listing_id = $request->listing_id;

       // store product 
       $saveData = $request->only(['video_url','status','listing_image','listing_tag',]);

     //  $saveData['product_slug'] = "";

       $saveData['created_by'] = ApiHelper::get_user_id_from_token($api_token);
       $saveData['updated_by'] = ApiHelper::get_user_id_from_token($api_token);

       // image and gallery store
       
     try {
  
         DB::beginTransaction(); // begin transaction

         if($request->has("listing_image") && !empty($request->listing_image))
         ApiHelper::image_upload_with_crop($api_token,$request->listing_image, 1, $listing_id);


         Listing::where('listing_id', $listing_id)->update($saveData);
     
        $product = Listing::find($listing_id);
          
       // if($request->has('gallery_ids') && !empty($request->gallery_ids)){

       //     $insData = [];

       //     if (sizeof($request->gallery_ids)) {
               
       //         // insert gallery image
       //         $product->products_to_gallery()->detach();

       //         foreach ($request->gallery_ids as $key => $gallery) {
       //             ApiHelper::image_upload_with_crop($api_token,$gallery, 1, $listing_id,'gallery');
       //             array_push($insData,[
       //                 'listing_id'=>$listing_id,
       //                 'images_id'=>$gallery
       //             ]);
       //         }
       //         $product->products_to_gallery()->attach($insData);
       //     }
       // }

       // store cat details
       foreach (ApiHelper::otherSupportLang() as $key => $value) {

          
        $name = "listing_name_".$value->languages_id;
        $desc = "listing_description_".$value->languages_id;
        $meta_title = "seometa_title_".$value->languages_id;
        $meta_desc = "seometa_desc_".$value->languages_id;
    
        //    if($value->languages_code == 'en'){
        //        $Product = Product::find($product->product_id);
        //        $Product->product_slug = Str::slug($request->$name);
        //        $Product->save();
        //    }

           $desc = ListingDescription::updateOrCreate([
               'listing_id'=>$product->listing_id,
               'languages_id'=>$value->languages_id,
           ],[
               'listing_id'=>$product->listing_id,
               'languages_id'=>$value->languages_id,
               'listing_name'=>$request->$name,
               'listing_description'=>$request->$desc,
           ]);   

            if(!empty($request->$meta_title) || !empty($request->$meta_desc)){
               SeoMeta::updateOrCreate(
                   [
                       'page_type'=>2,
                       'reference_id'=>$desc->listing_description_id,
                       'language_id'=>$value->languages_id],
                   [
                       'seometa_title'=>$request->$meta_title,
                       'seometa_desc'=>$request->$meta_desc, 
                   ]
               );
           }

       }

       // deattach category
       $product->products_to_categories()->detach();

       // attach category to product
       if(sizeof($request->categories_id)){
           $productToCategory = [];
           foreach ($request->categories_id as $key => $catid) {
               $productToCategory[$key]['categories_id'] = $catid;
               $productToCategory[$key]['listing_id'] = $product->listing_id;
           } 
           $product->products_to_categories()->attach($productToCategory);
       }

    //      // attch suppliere
    //    $product->products_to_supplier()->detach();

    //    if(!empty($request->supplier_ids)){
    //        if(sizeof($request->supplier_ids)){
    //            $supplier_info = [];
    //            foreach ($request->supplier_ids as $key => $catid) {
    //                $supplier_info[$key]['supplier_id'] = $catid;
    //                $supplier_info[$key]['product_id'] = $product->product_id;
    //            } 
    //            $product->products_to_supplier()->attach($supplier_info);
    //        }
    //    }

    //    // attach product type field values
    //    $field_values = [];
    //    $product_type = ProductType::product_type_with_fields($request->product_type_id);
    //    if(isset($product_type->fields_group) && !empty($product_type->fields_group)){
    //        foreach ($product_type->fields_group as $fields_group_key => $fields_group) {   // looping fieldgroup
               
    //            if(isset($fields_group->fields) && !empty($fields_group->fields)){
    //                foreach ($fields_group->fields as $fields_key => $fields) {     //looping all field inside 
                       
    //                    $fild_name = $fields->field_name;
    //                    array_push($field_values,[
    //                        'product_id'=>$product->product_id,
    //                        'fieldsgroup_id'=>$fields_group->fieldsgroup_id,
    //                        'fields_id'=>$fields->fields_id,
    //                        'field_name'=>$fild_name,
    //                        'field_value'=>$request->$fild_name
    //                    ]);                    
    //                }                
    //            }

    //        }

    //    }

    //    //old dettach 
    //    $product->products_type_field_value()->detach();

    //    // new attach
    //    $product->products_type_field_value()->attach($field_values);
       

    //    // attact attribute to PRODUCT
    //     $attributesArr = [];

        
    //     ProductAttribute::where('products_id', $product->product_id)->delete();    // DETACH ATTRIBUTE OLD

    //        // attact attribute to products
    //        if($request->has('attributes')){

    //            if(!empty($request['attributes'])){
                       
    //                foreach ($request['attributes'] as $key => $attr) {

    //                    $options_id = $attr['id'];
    //                    // array_push($attributesArr, $attr);

    //                    if(!empty($attr['options']) && sizeof($attr['options']) > 0 ){


    //                        foreach ($attr['options'] as $key => $options) {
                               
    //                            if ($options['name']) {
    //                                ProductAttribute::create([
    //                                    'options_id'=>$options_id,
    //                                    'options_values_id'=>$options['id'],
    //                                    'options_values_price'=>$options['price'],
    //                                    'products_id' => $product->product_id
    //                                ]);

    //                            }

    //                        }

    //                    }
                       
    //                }
    //            }

    //        }

    //        // return ApiHelper::JSON_RESPONSE(false,$attributesArr,'');
    //        ProductPrice::where('product_id', $product->product_id)->delete();

    //        if($request->has('bulkPrice') && sizeof($request->bulkPrice)){
    //            foreach ($request->bulkPrice as $key => $bulkPrice) {
    //                if(!empty($bulkPrice['product_qty']) && !empty($bulkPrice['product_price'])){
    //                    ProductPrice::create([
    //                        'product_id'=>$product->product_id,
    //                        'product_qty'=>$bulkPrice['product_qty'],
    //                        'product_price'=>$bulkPrice['product_price']
    //                    ]);
    //                }
    //            }
    //        }


           
           // attach pro featured to product
           ListingFeature::where('listing_id', $product->listing_id)->delete();

           if($request->has('proFeature') && sizeof($request->proFeature) > 0 ){

               foreach ($request->proFeature as $key => $proFeature) {
                   if(!empty($proFeature['feature_key']) && !empty($proFeature['feature_key_value'])){
                    ListingFeature::create([
                           'listing_id'=>$product->listing_id,
                           'feature_title'=>$proFeature['feature_key'],
                           'feature_value'=>$proFeature['feature_key_value']
                       ]);
                   }
               }

           }


       
            DB::commit();       // db commit

           return ApiHelper::JSON_RESPONSE(true,$product,'SUCCESS_PRODUCT_UPDATE');

       } catch (\Throwable $th) {
           \Log::error($th->getMessage());
           DB::rollback();     // db rollback
           return ApiHelper::JSON_RESPONSE(false,[], $th->getMessage());
       }
   }

   /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
   public function destroy($id)
   {
       //
   }


   public function changeStatus(Request $request){
       $api_token = $request->api_token;
       $statusId = $request->statusId;

       $infoData = Listing::find($request->update_id);
       $infoData->status = $statusId;

       $infoData->save();
       return ApiHelper::JSON_RESPONSE(true,$infoData,'SUCCESS_STATUS_UPDATE');

   }

  
}
