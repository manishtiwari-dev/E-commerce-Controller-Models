<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use Validator;
use Illuminate\Support\Str;
use App\Models\Language;
use Modules\Ecommerce\Models\FeaturedGroup;
use Modules\Ecommerce\Models\FeaturedProduct;
use Modules\Ecommerce\Models\Product;






class FeaturedProductController extends Controller
{
    
    public $page = 'feature_product';
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
        
        $featured_group_id=$request->featured_group_id;
 
        $language = $request->language;

       

        $productItem = array();   
        $data_list = FeaturedGroup::with('feature_product','feature_product.product.productdescription')->where('featured_group_id',$featured_group_id)->first();
        
    
      //  $product=Product::with('productdescription')->get();
      $product=Product::with('productdescription')->Where('product_status', '1')->paginate(1500);
        foreach ($product as $key => $prod) {
            $productlist = $prod->productdescription()->where('languages_id',  ApiHelper::getLangid($language))->first();
            if(!empty($productlist))

            array_push($productItem, 
                [
                    "value"=>$prod->product_id, 
                    "label"=>$productlist->products_name.'('.$prod->product_sku.')', 
                ]);   

        }    


        
        $res = [
            'group_details'=>$data_list,
            'product_list'=>$productItem,
            
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }

    

     public function store(Request $request)
     {

        // Validate user page access
        $api_token = $request->api_token;
           // Validate user page access
           $api_token = $request->api_token;
           if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
         
 
         return ApiHelper::JSON_RESPONSE(false,$request->only('attributes'),'PAGE_ACCESS_DENIED');
         
        $product_id=explode(",",$request->products_id);
         //validation check 
        $rules = [
           
            'featured_group_id'=>'required',
            'products_id'=>'required',
            
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        }



        // store category 
        foreach($product_id as $key=>$value)
        {
          $cat=FeaturedProduct::updateOrCreate([
                'products_id'=>$value,
                'featured_group_id'=>$request->featured_group_id
          ],[]);

        }

        if($cat){
            return ApiHelper::JSON_RESPONSE(true,$cat,'SUCCESS_FEATURED_PRODUCT_ADD');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FEATURED_PRODUCT_ADD');
        }


     }


     public function edit(Request $request)
     {
        $api_token = $request->api_token;
       
        $response = FeaturedProduct::with('product')->find($request->featured_products_id);
        return ApiHelper::JSON_RESPONSE(true,$response,'');

     }

    
    public function update(Request $request)
    {

      // Validate user page access
        $api_token = $request->api_token;
           // Validate user page access
           $api_token = $request->api_token;
           if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
         
 
         return ApiHelper::JSON_RESPONSE(false,$request->only('attributes'),'PAGE_ACCESS_DENIED');
         
     //   $featured_products_id = $request->featured_products_id;


        //validation check 
        $rules = [
            'featured_group_id'=>'required',
            'products_id'=>'required',
            
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) 
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        

        // store fieldsgroup
        $saveData = $request->only(['featured_group_id','products_id']); 
        $data =FeaturedProduct::where('featured_products_id', $request->featured_products_id)->update($saveData);


       
        if($saveData){
            return ApiHelper::JSON_RESPONSE(true,$saveData,'SUCCESS_FEATURED_PRODUCT_UPDATE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FEATURED_PRODUCT_UPDATE');
        }

    }

   
    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $featured_products_id = $request->featured_products_id;
        /*
        if(!ApiHelper::is_page_access($api_token,$this->BrandDelete)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        */
        $status = FeaturedProduct::where('featured_products_id',$featured_products_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_FEATURE_PRODUCT_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FEATURE_PRODUCT_DELETE');
        }
    }


    public function changeStatus(Request $request){      
        $api_token = $request->api_token;
        $infoData = FeaturedProduct::find($request->featured_products_id);
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true,$infoData,'SUCCESS_STATUS_UPDATE');
    }

}
