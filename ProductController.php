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
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductFeature;
use Modules\Ecommerce\Models\ProductPrice;
use Modules\Ecommerce\Models\ProductType;
use Modules\Ecommerce\Models\ProductOptions;
use Modules\Ecommerce\Models\CategoryOption;
use Modules\Ecommerce\Models\CategoryBrand;
use Modules\Ecommerce\Models\ProductsOptionsValues;
use Modules\Ecommerce\Models\ProductAttribute;
use Modules\Ecommerce\Models\ProductDescription;
use Modules\Ecommerce\Models\ProductsTypeFieldValue;
use Illuminate\Support\Facades\Storage;
use Modules\Ecommerce\Models\Supplier;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\SeoMeta;
use Modules\CRM\Models\CRMSettingTax;
use Modules\CRM\Models\CRMSettingTaxGroup;

use DB;
use Illuminate\Database\Eloquent\Builder;




class ProductController extends Controller
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
        //$perPage = ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $catId = $request->categories_id;


        $language = $request->language;
        
        $data_query = Product::with('products_to_categories','products_to_categories.categorydescription')->where('source',1);
        
        if(!empty($search))
        {
            $data_query=$data_query->where("product_model","LIKE", "%{$search}%")
            ->orWhereHas('productdescription',function ($data_query)use($search)
            {
            $data_query->where("products_name","LIKE", "%{$search}%");
            });
        }
 


        // if(!empty($search))
        //      $data_query = $data_query->where("name","LIKE", "%{$search}%")->orWhere("email", "LIKE", "%{$search}%");

            /* order by sorting */
        

        if(!empty($catId)){
          
                
            $data_query =$data_query->whereHas('products_to_categories', function (Builder $query) use($catId) {
            $query->where('ecm_products_to_categories.categories_id',$catId);
            });

        


        //    // $categories_id= config('dbtable.ecm_products_to_categories');

        //     $data_query =$data_query->whereHas('products_to_categories', function (Builder $query) use($catId) {
        //     $query->where('products_to_categories.categories_id',$catId);
        //     });

            
        }
    

    if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('product_id','DESC');
        }


        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;
        
        $user_count = $data_query->count();

        if( $user_count == 0)
        {
            $data_list = $data_query->get();

        }
        else{

            $data_list = $data_query->skip($skip)->take($perPage)->get();
 
        }

     //   $data_list = $data_query->skip($skip)->take($perPage)->get();

        $data_list = $data_list->map(function($data) use ($language)  {

            $cate = $data->productdescription()->where('languages_id', ApiHelper::getLangid($language))->first();

            $data->products_name = ($cate == null) ? '' : $cate->products_name;
            $data->products_description = ($cate == null) ? '' : $cate->products_description;

            $data->status = ($data->status == 1) ? "active":"deactive";
            $data->product_image = ApiHelper::getFullImageUrl($data->product_image);
              
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

        $cName = '';

        if($request->has('categories_id')){        
            //getting category Name
             $catName=CategoryDescription::where('categories_id',$request->categories_id )->where('languages_id', ApiHelper::getLangid($language))->first();
             $cName = !empty($catName) ? $catName->categories_name : '';
        }
        $res = [
            'data'=>$data_list,
            'current_page'=>$current_page,
            'total_records'=>$user_count,
            'cat_name'=>$cName,
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

        $cat = Category::where('parent_id',0)->where('source',1)->get();

        foreach ($cat as $key => $cat) {
            $cate = $cat->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
            array_push($categoryItem, 
                [
                    "value"=>$cat->categories_id, 
                    "label"=>($cate == null) ? '' : $cate->categories_name
                ]);
            // sub category import

            $sub_category = Category::where('parent_id',$cat->categories_id)->where('source',1)->get();
            if(sizeof($sub_category) >  0){
               
               foreach ($sub_category as $key => $sub) {
                   $subcat = $sub->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
                    
                    array_push($categoryItem, [ 
                        "value"=>$sub->categories_id, 
                        "label"=>($subcat == null) ? '' : '--'.$subcat->categories_name
                    ]);

               

                 //sub sub category import
             $sub_sub_category = Category::where('parent_id',$sub->categories_id)->where('source',1)->get();
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

        $crmTaxGroup = CRMSettingTaxGroup::with('tax_info')->get();

        $tax_array =[];
             
          foreach ($crmTaxGroup as $key => $taxGroup) {
            
            $tax_percent = [];
            $tax_list = $taxGroup->tax_info;
            if(!empty($tax_list)){
                foreach ($tax_list as $key => $tax) {
                    array_push($tax_percent, $tax->tax_percent);
                }
            }

            array_push($tax_array,[
                'tax_id'=>$taxGroup->tax_group_id,
                'tax_name'=>$taxGroup->tax_group_name,
                'tax_percantage'=>$tax_percent,
                ]
            );
        }
        

        $res['all_category'] = $categoryItem;
        
        $res['product_type'] = ProductType::all()->map(function($product_type){
            if(!empty($product_type->fields_group)){
                $product_type->fields_group = $product_type->fields_group->map(function($fields_group){
                    $fields_group->fields = $fields_group->fields;
                    return $fields_group;
                });
            }
            return $product_type;
        });
        
        $res['tax_type']=ApiHelper::taxType();
        $res['enable_tax']=ApiHelper::enableTax();
        $res['language'] = ApiHelper::allSupportLang();
        $res['product_attribute'] = ProductOptions::product_options_with_value();
        $res['supplier'] = Supplier::select('supplier_id as value','supplier_name as label')->get();
        $res['brand'] = Brand::all();
        $res['crmtax'] = CRMSettingTax::all();
        $res['taxGroup']=  $tax_array;
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
        

            //validation

            foreach ( ApiHelper::allSupportLang() as $key => $value) {

                                                
                //validation check 
                $validator = Validator::make($request->all(),[
                    'products_name_'. $value->languages_id=> 'required',
                    'products_description_'. $value->languages_id=> 'required',
                  
                ],
                [
                    
                'products_name_'.$value->languages_id.'.required'=>$value->languages_name.'_'.'PRODUCTS_NAME_REQUIRED',

                'products_description_'.$value->languages_id.'.required'=>$value->languages_name.'_'.'PRODUCTS_DECRIPTION_REQUIRED',

                ]



            );
                if ($validator->fails())
                    return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
                
            }

        try {
       
              DB::beginTransaction(); // begin transaction

            // store category 
            $saveData =  $request->only(['product_model','product_condition','product_sku','product_video_url','product_condition','product_type_id','stock_qty','product_price_type','stock_price','product_external_url','profit_percent','sale_price','product_tags','discount_type','discount_amount','product_brand_id','product_image','shipping_charge','max_order_qty','min_order_qty','manage_stock','video_provider','product_delivery_time','expire_at', 'product_live_date','tax_id','max_sale_price']);

         
            $max_sortOrder =  Product::max('sort_order');
            $sort_order=$max_sortOrder+1;
               
              //  return ApiHelper::JSON_RESPONSE(true,   $sort_order,'SUCCESS_STATUS_UPDATE');
           $saveData['sort_order'] =$sort_order;
             
        


            $saveData['product_slug'] = "";

            $saveData['created_by'] = ApiHelper::get_user_id_from_token($api_token);
            $saveData['updated_by'] = ApiHelper::get_user_id_from_token($api_token);


            $product = Product::create($saveData);

            // image and gallery store

            if($request->has("product_image") && !empty($request->product_image))
              //  ApiHelper::image_upload_with_crop($api_token,$request->product_image, 2, $product->product_id);
              ApiHelper::image_upload_with_crop($api_token, $request->product_image, 2, $product->product_id,'', true);

            if($request->has('gallery_ids')){

                $insData = [];

                if (sizeof($request->gallery_ids)) {
                    foreach ($request->gallery_ids as $key => $gallery) {
                        ApiHelper::image_upload_with_crop($api_token, $gallery, 2, $product->product_id,'gallery', true);

                     //   ApiHelper::image_upload_with_crop($api_token,$gallery, 2, $product->product_id,'gallery');
                        array_push($insData,[
                            'product_id'=>$product->product_id,
                            'images_id'=>$gallery
                        ]);
                    }
                    $product->products_to_gallery()->attach($insData);
                }
            }
            

            // store cat details
            foreach (ApiHelper::allSupportLang() as $key => $value) {

                $name ="products_name_".$value->languages_id;
                $desc = "products_description_".$value->languages_id;
                $meta_title = "seometa_title_".$value->languages_id;
                $meta_desc = "seometa_desc_".$value->languages_id;
                
                if($value->languages_code == 'en'){
                    $Product = Product::find($product->product_id);
                    $Product->product_slug = Str::slug($request->$name);
                    $Product->save();
                }


                $desc = ProductDescription::create([
                    'products_id'=>$product->product_id,
                    'languages_id'=>$value->languages_id,
                    'products_name'=>$request->$name,
                    'products_description'=>$request->$desc,
                ]);   
                
                if(!empty($request->$meta_title) || !empty($request->$meta_desc)){
                    SeoMeta::create([
                        'page_type'=>2,
                        'reference_id'=>$desc->products_description_id,
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
                    $productToCategory[$key]['products_id'] = $product->product_id;
                } 
                $product->products_to_categories()->attach($productToCategory);
                
                // attching  product_option to category so that we can fetch option selected in category filter
                foreach ($request->categories_id as $key => $catid) {
                    $optionToCategory = [];
                    $brandToCategory = [];

                    if($product->has('productAttribute')){
                        if(!empty( $product->productAttribute) && sizeof( $product->productAttribute) > 0 ){
                            foreach ( $product->productAttribute as $key => $attributes) {
                                $optionToCategory[$key]['categories_id'] = $catid;
                                $optionToCategory[$key]['products_options_id'] = $attributes['options_id'];
                                CategoryOption::updateOrCreate([
                                    'categories_id' => $catid,
                                    'products_options_id' => $attributes['options_id']
                                ]);

                            }
                        }
                    }

             
                if($request->has('product_brand_id') && !empty($request->product_brand_id)){
                    $brandToCategory = ['categories_id'=>$catid,'brand_id'=>$request->product_brand_id];
                    CategoryBrand::updateOrCreate($brandToCategory);
                }

                 
                } 
                
            }

            // attch suppliere
            if(!empty($request->supplier_ids)){
                if(sizeof($request->supplier_ids)){
                    $supplier_info = [];
                    foreach ($request->supplier_ids as $key => $catid) {
                        $supplier_info[$key]['supplier_id'] = $catid;
                        $supplier_info[$key]['product_id'] = $product->product_id;
                    } 
                    $product->products_to_supplier()->attach($supplier_info);
                }
            }


            // attach product type field values
            $field_values = [];
            $product_type = ProductType::product_type_with_fields($request->product_type_id);
            if(isset($product_type->fields_group) && !empty($product_type->fields_group)){
                foreach ($product_type->fields_group as $fields_group_key => $fields_group) {   // looping fieldgroup
                    
                    if(isset($fields_group->fields) && !empty($fields_group->fields)){
                        foreach ($fields_group->fields as $fields_key => $fields) {     //looping all field inside 
                            
                            $fild_name = $fields->field_name;
                            array_push($field_values,[
                                'product_id'=>$product->product_id,
                                'fieldsgroup_id'=>$fields_group->fieldsgroup_id,
                                'fields_id'=>$fields->fields_id,
                                'field_name'=>$fild_name,
                                'field_value'=>$request->$fild_name
                            ]);                    
                        }                
                    }

                }

            }

            $product->products_type_field_value()->attach($field_values);
        
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
                                    ProductAttribute::create([
                                        'options_id'=>$options_id,
                                        'options_values_id'=>$options['id'],
                                        'options_values_price'=>$options['price'],
                                        'products_id' => $product->product_id
                                    ]);

                                }

                            }

                        }
                        
                    }
                }

            }

            // return ApiHelper::JSON_RESPONSE(false,$attributesArr,'');
            if($request->has('bulkPrice') && sizeof($request->bulkPrice)){
                foreach ($request->bulkPrice as $key => $bulkPrice) {
                    if(!empty($bulkPrice['product_qty']) && !empty($bulkPrice['stock_price'])){
                        ProductPrice::create([
                            'product_id'=>$product->product_id,
                            'product_qty'=>$bulkPrice['product_qty'],
                            'stock_price'=>$bulkPrice['stock_price'],
                            'profit_percent'=>$bulkPrice['profit_percent'],
                            'max_sale_price'=>$bulkPrice['max_sale_price'],
                            'discount_percent'=>$bulkPrice['discount_percent'],
                            'sale_price'=>$bulkPrice['sale_price'],
                        ]);
                    }
                }
            }


            
            // attach pro featured to product
            if($request->has('proFeature') && sizeof($request->proFeature) > 0 ){
                foreach ($request->proFeature as $key => $proFeature) {
                    if(!empty($proFeature['feature_key']) && !empty($proFeature['feature_key_value'])){
                        ProductFeature::create([
                            'product_id'=>$product->product_id,
                            'feature_title'=>$proFeature['feature_key'],
                            'feature_value'=>$proFeature['feature_key_value']
                        ]);
                    }
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        
        $response = Product::with('productdescription','productAttribute','productdescription.seo', 'productFeature', 'productPrice')->find($request->product_id);
        if($response !== null){
            // $response->product_status = ($response->product_status == 1) ? "active":"deactive"; 
            $response->product_image = ApiHelper::getFullImageUrl($response->product_image);
            $response->productdescription_with_lang = $response->productdescription_with_lang;

            // create category tfor table
            $products_to_categories = $response->products_to_categories;
            $selected_category = [];
            if(!empty($products_to_categories)){
                foreach ($products_to_categories as $key => $cat) {
                    
                    $label_res = $cat->categorydescription()->where('categories_id', $cat->categories_id)->first();
                    $label = ($label_res !== null) ? $label_res->categories_name : '';

                    array_push($selected_category,[
                        "label"=>$label,
                        "value"=>$cat->categories_id
                    ]);        
                }
            }
            $response->selected_category = $selected_category;

            // prductType with fieldgroup
            $products_id = $response->product_id;
            $product_type = ProductType::product_type_with_fields($response->product_type_id);
            
            if(isset($product_type->fields_group)){
                $product_type = $product_type->fields_group->map(function($fields_group) use ($products_id){

                    $fields_group->fields = $fields_group->fields->map(function($fields) use ($products_id) {
                        
                        $res = ProductsTypeFieldValue::where('product_id', $products_id)->where('fields_id',$fields->fields_id)->first();
                        $fied_value = ($res !== null ) ? $res->field_value : '';

                        $fields->show_value = $fied_value;
                        return $fields;
                    });

                    return $fields_group;

                });

            }

            $response->product_type = $product_type;

            // $response->product_attribute = ProductOptions::product_options_with_value();

            $productAttribute = ProductAttribute::where('products_id', $response->product_id)->get();
            if(!empty($productAttribute)){

                foreach ($productAttribute as $key => $attribute) {
                    
                    $productAttribute[$key]['option_list'] = ProductOptions::all();
                    $productAttribute[$key]['option_value_list'] = ProductsOptionsValues::where('products_options_id', $attribute->options_id)->get();
                }
            }

            $response->productAttribute = $productAttribute;

            $response->products_type_field_value = $response->products_type_field_value;

            $response->products_to_supplier = $response->products_to_supplier;

            $selected_products_to_supplier = [];
            $selected_products_to_supplier_id = [];

            if(!empty($response->products_to_supplier )){
                foreach ($response->products_to_supplier  as $key => $supp){

                    array_push($selected_products_to_supplier_id,$supp->supplier_id);
                    array_push($selected_products_to_supplier,[
                        "label"=>$supp->supplier_name,
                        "value"=>$supp->supplier_id
                    ]);        
                }
                
            }
            $response->selected_supplier = $selected_products_to_supplier;
            $response->selected_supplier_id = $selected_products_to_supplier_id;
            $response->products_to_seo = $response->products_to_seo;
            
            $gallery_ids = [];
            $gallery_image = [];

            $gallery = $response->products_to_gallery;

            if(!empty($gallery)){
                foreach ($gallery as $key => $gal) {
                    array_push($gallery_ids,$gal->images_id);
                    array_push($gallery_image,ApiHelper::getFullImageUrl($gal->images_id)); 
                }
            }
            $response->gallery_ids = $gallery_ids;
            $response->gallery_image = $gallery_image;


        }


        
        if($request->has('product_id')){        
            //getting category Name
             $catName=ProductDescription::where('products_id',$request->product_id )->first();
             $cName = !empty($catName) ? $catName->products_name : '';
             $response->proName =  $cName;

        }


        return ApiHelper::JSON_RESPONSE(true,$response,'');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {


        $response = Product::with('productdescription','productAttribute', 'productFeature', 'productdescription.seo','productPrice')->find($request->product_id);
        if (!empty($response)){
            //$response->product_status = ($response->product_status == 1) ? "active":"deactive"; 
      //      $response->product_image = ApiHelper::getFullImageUrl($response->product_image);
            $response->productdescription_with_lang = $response->productdescription_with_lang;

        //   //seo data
        //     if (!empty($response->productdescription)) {
        //         $response->productdescription->map(function ($description) {

        //             $seoInfo = SeoMeta::select('seometa_title', 'seometa_desc')->where([
        //                 'page_type' => 2,
        //                 'reference_id' => $description->products_id,
        //                 'language_id' => 1,
        //             ])->first();

        //             if(empty($seoInfo)){
        //                 $seoInfo['seometa_title']='';
        //                 $seoInfo['seometa_desc']='';
        //             }

        //             $description->seo = (object)$seoInfo;

        //             return $description;

        //         });
        //     }



            // create category tfor table
            $products_to_categories = $response->products_to_categories;
            $selected_category = [];
            if(!empty($products_to_categories)){
                foreach ($products_to_categories as $key => $cat) {
                    
                    $label_res = $cat->categorydescription()->where('categories_id', $cat->categories_id)->first();
                    $label = ($label_res !== null) ? $label_res->categories_name : '';

                    array_push($selected_category,[
                        "label"=>$label,
                        "value"=>$cat->categories_id
                    ]);        
                }
            }
            $response->selected_category = $selected_category;

            // prductType with fieldgroup
            $products_id = $response->product_id;
            $product_type = ProductType::product_type_with_fields($response->product_type_id);
            
            if(isset($product_type->fields_group)){
                $product_type = $product_type->fields_group->map(function($fields_group) use ($products_id){

                    $fields_group->fields = $fields_group->fields->map(function($fields) use ($products_id) {
                        
                        $res = ProductsTypeFieldValue::where('product_id', $products_id)->where('fields_id',$fields->fields_id)->first();
                        $fied_value = ($res !== null ) ? $res->field_value : '';

                        $fields->show_value = $fied_value;
                        return $fields;
                    });

                    return $fields_group;

                });

            }

            $response->product_type = $product_type;

            $response->products_type_field_value = $response->products_type_field_value;

            $response->products_to_supplier = $response->products_to_supplier;

            $selected_products_to_supplier = [];
            $selected_products_to_supplier_id = [];

            if(!empty($response->products_to_supplier )){
                foreach ($response->products_to_supplier  as $key => $supp){

                    array_push($selected_products_to_supplier_id,$supp->supplier_id);
                    array_push($selected_products_to_supplier,[
                        "label"=>$supp->supplier_name,
                        "value"=>$supp->supplier_id
                    ]);        
                }
                
            }
            $response->selected_supplier = $selected_products_to_supplier;
            $response->selected_supplier_id = $selected_products_to_supplier_id;
            $response->products_to_seo = $response->products_to_seo;

          
            $gallery_ids = [];
            $gallery_image = [];

            $gallery = $response->products_to_gallery;

            if(!empty($gallery)){
                foreach ($gallery as $key => $gal) {
                    array_push($gallery_ids,$gal->images_id);
                    array_push($gallery_image,ApiHelper::getFullImageUrl($gal->images_id)); 
                }
            }
            $response->gallery_ids = $gallery_ids;
            $response->gallery_image = $gallery_image;



        }

        return ApiHelper::JSON_RESPONSE(true,$response,'');

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   public function update(Request $request)
    {

        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');

        // Validate user page access
        $api_token = $request->api_token;

        // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))

           //validation

           foreach ( ApiHelper::allSupportLang() as $key => $value) {

                                                
            //validation check 
            $validator = Validator::make($request->all(),[
                'products_name_'. $value->languages_id=> 'required',
                'products_description_'. $value->languages_id=> 'required',
              
            ],
            [
                
            'products_name_'.$value->languages_id.'.required'=>$value->languages_name.'_'.'PRODUCTS_NAME_REQUIRED',

            'products_description_'.$value->languages_id.'.required'=>$value->languages_name.'_'.'PRODUCTS_DECRIPTION_REQUIRED',

            ]



        );
            if ($validator->fails())
                return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
            
        }

        $products_id = $request->product_id;

        // store product 
        $saveData = $request->only(['product_model','product_condition','product_sku','product_video_url','sort_order','product_status','product_condition','product_type_id','stock_qty','product_price_type','stock_price','product_external_url','profit_percent','sale_price','max_sale_price','product_tags','discount_type','discount_amount','product_brand_id','shipping_charge','max_order_qty','min_order_qty','manage_stock','video_provider','product_delivery_time','expire_at', 'product_live_date','tax_id']);

        $saveData['product_slug'] = "";

        //$saveData['created_by'] = ApiHelper::get_user_id_from_token($api_token);
        $saveData['updated_by'] = ApiHelper::get_user_id_from_token($api_token);

        // image and gallery store
    
        
        
      try {
   
          DB::beginTransaction(); // begin transaction


          
        if($request->has("product_sale_price_show") && !empty($request->product_sale_price_show))
        $saveData['sale_price']=$request->product_sale_price_show;

        Product::where('product_id', $products_id)->update($saveData);


        if($request->has("product_image") && !empty($request->product_image)){
            ApiHelper::image_upload_with_crop($api_token, $request->product_image, 2, $products_id, '', false);



            Product::where('product_id', $products_id)->update(['product_image'=> $request->product_image ]);

        }

        
        $product = Product::find($products_id);

        if($request->has('gallery_ids') && !empty($request->gallery_ids)){

            $insData = [];

            if (sizeof($request->gallery_ids)) {
                
                // insert gallery image
                $product->products_to_gallery()->detach();

                foreach ($request->gallery_ids as $key => $gallery) {
                    ApiHelper::image_upload_with_crop($api_token, $gallery, 2, $products_id, 'gallery', false);
                //    ApiHelper::image_upload_with_crop($api_token,$gallery, 1, $products_id,'gallery');
                    array_push($insData,[
                        'product_id'=>$products_id,
                        'images_id'=>$gallery
                    ]);
                }
                $product->products_to_gallery()->attach($insData);
            }
        }

        // store cat details
        foreach (ApiHelper::allSupportLang() as $key => $value) {

            $name = "products_name_".$value->languages_id;
            $desc = "products_description_".$value->languages_id;
            $meta_title = "seometa_title_".$value->languages_id;
            $meta_desc = "seometa_desc_".$value->languages_id;
            
            if($value->languages_code == 'en'){
                $Product = Product::find($product->product_id);
                $Product->product_slug = Str::slug($request->$name);
                $Product->save();
            }

            $desc = ProductDescription::updateOrCreate([
                'products_id'=>$product->product_id,
                'languages_id'=>$value->languages_id,
            ],[
                'products_id'=>$product->product_id,
                'languages_id'=>$value->languages_id,
                'products_name'=>$request->$name,
                'products_description'=>$request->$desc,
            ]);   

             if(!empty($request->$meta_title) || !empty($request->$meta_desc)){
                SeoMeta::updateOrCreate(
                    [
                        'page_type'=>2,
                        'reference_id'=>$desc->products_description_id,
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
                $productToCategory[$key]['products_id'] = $product->product_id;
            } 
            $product->products_to_categories()->attach($productToCategory);

            // attching  product_option to category so that we can fetch option selected in category filter
            foreach ($request->categories_id as $key => $catid) {
                $optionToCategory = [];
                $brandToCategory = [];

             

                // return ApiHelper::JSON_RESPONSE(true, $optionToCategory,'');
                if($product->has('productAttribute')){
                    if(!empty( $product->productAttribute) && sizeof( $product->productAttribute) > 0 ){
                        foreach ( $product->productAttribute as $key => $attributes) {
                            $optionToCategory[$key]['categories_id'] = $catid;
                            $optionToCategory[$key]['products_options_id'] = $attributes['options_id'];
                            CategoryOption::updateOrCreate([
                                'categories_id' => $catid,
                                'products_options_id' => $attributes['options_id']
                            ]);
                        }
                    }
                }
          //      $optionToCategory = array_map("unserialize", array_unique(array_map("serialize", $optionToCategory)));

                if($request->has('product_brand_id') && !empty($request->product_brand_id)){
                    $brandToCategory = ['categories_id'=>$catid,'brand_id'=>$request->product_brand_id];
                    CategoryBrand::updateOrCreate($brandToCategory);
                }
                    
                    

                //$category = Category::find($catid);
                //  $category->options()->syncWithoutDetaching($optionToCategory);
                // $category->brands()->syncWithoutDetaching( $brandToCategory);  // attach brand  to category
               

            } 



        }

          // attch suppliere
        $product->products_to_supplier()->detach();

        if(!empty($request->supplier_ids)){
            if(sizeof($request->supplier_ids)){
                $supplier_info = [];
                foreach ($request->supplier_ids as $key => $catid) {
                    $supplier_info[$key]['supplier_id'] = $catid;
                    $supplier_info[$key]['product_id'] = $product->product_id;
                } 
                $product->products_to_supplier()->attach($supplier_info);
            }
        }

        // attach product type field values
        $field_values = [];
        $product_type = ProductType::product_type_with_fields($request->product_type_id);
        if(isset($product_type->fields_group) && !empty($product_type->fields_group)){
            foreach ($product_type->fields_group as $fields_group_key => $fields_group) {   // looping fieldgroup
                
                if(isset($fields_group->fields) && !empty($fields_group->fields)){
                    foreach ($fields_group->fields as $fields_key => $fields) {     //looping all field inside 
                        
                        $fild_name = $fields->field_name;
                        array_push($field_values,[
                            'product_id'=>$product->product_id,
                            'fieldsgroup_id'=>$fields_group->fieldsgroup_id,
                            'fields_id'=>$fields->fields_id,
                            'field_name'=>$fild_name,
                            'field_value'=>$request->$fild_name
                        ]);                    
                    }                
                }

            }

        }

        //old dettach 
        $product->products_type_field_value()->detach();

        // new attach
        $product->products_type_field_value()->attach($field_values);
        

        // attact attribute to PRODUCT
         $attributesArr = [];

         
         ProductAttribute::where('products_id', $product->product_id)->delete();    // DETACH ATTRIBUTE OLD

            // attact attribute to products
            if($request->has('attributes')){

                if(!empty($request['attributes'])){
                        
                    foreach ($request['attributes'] as $key => $attr) {

                        $options_id = $attr['id'];
                        // array_push($attributesArr, $attr);

                        if(!empty($attr['options']) && sizeof($attr['options']) > 0 ){


                            foreach ($attr['options'] as $key => $options) {
                                
                                if ($options['name']) {
                                    ProductAttribute::create([
                                        'options_id'=>$options_id,
                                        'options_values_id'=>$options['id'],
                                        'options_values_price'=>$options['price'],
                                        'products_id' => $product->product_id
                                    ]);

                                }

                            }

                        }
                        
                    }
                }

            }

            // return ApiHelper::JSON_RESPONSE(false,$attributesArr,'');
        //    ProductPrice::where('product_id', $product->product_id)->delete();

            if($request->has('bulkPrice') && sizeof($request->bulkPrice)){
                foreach ($request->bulkPrice as $key => $bulkPrice) {
                    if(!empty($bulkPrice['product_qty']) && !empty($bulkPrice['stock_price'])){
                        ProductPrice::updateOrCreate([
                            'product_id'=>$product->product_id,],[
                            'product_qty'=>$bulkPrice['product_qty'],
                            'stock_price'=>$bulkPrice['stock_price'],
                            'profit_percent'=>$bulkPrice['profit_percent'],
                            'max_sale_price'=>$bulkPrice['max_sale_price'],
                            'discount_percent'=>$bulkPrice['discount_percent'],
                            'sale_price'=>$bulkPrice['sale_price'],
                        ]);
                    }
                }
            }


           
            // attach pro featured to product
            ProductFeature::where('product_id', $product->product_id)->delete();

            if($request->has('proFeature') && sizeof($request->proFeature) > 0 ){

                foreach ($request->proFeature as $key => $proFeature) {
                    if(!empty($proFeature['feature_key']) && !empty($proFeature['feature_key_value'])){
                        ProductFeature::create([
                            'product_id'=>$product->product_id,
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

        $infoData = Product::find($request->update_id);
        $infoData->product_status = $statusId;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true,$infoData,'SUCCESS_STATUS_UPDATE');

    }

    
}
