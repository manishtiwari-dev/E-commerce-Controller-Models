<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Ecommerce\Models\Coupon;

use Illuminate\Http\Request;
use App\Models\Language;

use ApiHelper;



class CouponController extends Controller
{
    public $page = 'coupon';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    public function index(Request $request){

      
       //Validate user page access
        $api_token = $request->api_token;
     
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        

        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();

        $search = $request->search;
        $sortBy = $request->sortBy;
        $orderBy = $request->orderBy;
        $language = $request->language;

        /*Fetching subscriber data*/ 
      //  $reviews_query = Reviews::with('product','product.productdescription');
        $coupun_query = Coupon::query();


               // return ApiHelper::JSON_RESPONSE(true,$reviews_query,'');

        /*Checking if search data is not empty*/
        if(!empty($search))
            $coupun_query = $coupun_query
                ->where("coupon_name","LIKE", "%{$search}%");
                // ->orWhere("coupon_code","LIKE", "%{$search}%");

        /* order by sorting */
        if(!empty($sortBy) && !empty($orderBy))
            $coupun_query = $coupun_query->orderBy($sortBy,$orderBy);
        else
            $coupun_query = $coupun_query->orderBy('coupon_id','DESC');

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;

        $coupun_count = $coupun_query->count();

        $coupan_list = $coupun_query->skip($skip)->take($perPage)->get();
         
         
        // $reviews_list = $reviews_list->map(function($data) use ($language)  {
            
        //     $productname = '';

        //     if(!empty($data->product)){
        //       if(!empty($data->product->productdescription)){
        //          $proName = $data->product->productdescription()->where('languages_id', ApiHelper::getLangid($language))->first();
        //             $productname = ($proName == null) ? '' : $proName->products_name;
        //         }
        //     }

        //     $data->products_name = $productname;
        //     return $data;
        // });
        
        
        
         /*Binding data into a variable*/
        $res = [
            'data'=>$coupan_list,
            'current_page'=>$current_page,
            'total_records'=>$coupun_count,
            'total_page'=>ceil((int)$coupun_count/(int)$perPage),
            'per_page'=>$perPage,
        ];
      
        return ApiHelper::JSON_RESPONSE(true,$res,'');


    }


     public function store(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
         return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');



    //    $validator = Validator::make($request->all(),[
    //         'customers_name' => 'required',
    //         'customers_email' => 'required',
    //         'reviews_title' => 'required',
    //         'reviews_text' => 'required',   
 
    //     ],[
    //         'customers_name.required'=>'CUSTOMER_NAME_REQUIRED',
    //         'customers_email.required'=>'CUSTOMER_EMAIL_REQUIRED',
    //         'reviews_title.required'=>'REVIEWS_TITLE_REQUIRED',
    //         'reviews_text.required'=>'REVIEWS_TEXT_REQUIRED',
 

    //     ]);
    //     if ($validator->fails())
    //         return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());


        
        $coupans = Coupon::create([
     
        'coupon_name' => $request->coupon_name,
        'coupon_code' => $request->coupon_code,
        'coupon_limit' => $request->coupon_limit,
        'active_at' => $request->active_at,
        'coupon_type' => 1,
        'expire_at' => $request->expire_at,
        'discount' => $request->discount,
        'discount_type'=>$request->discount_type,
        'minimum_purchase' =>$request->minimum_purchase,
        'maximum_discount' =>$request->maximum_discount,
        'first_purchase_only'=>$request->first_purchase_only,
        'multiple_use'=>$request->multiple_use,

        // 'status' => $request->status,
        
        ]);



        if ($coupans) {
            return ApiHelper::JSON_RESPONSE(true, $coupans, 'SUCCESS_COUPUN_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false,'', 'ERROR_COUPUN_ADD');
        }
    }

    public function update(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;


         if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        //validation check 
        // $rules = [
        //     'source_id' => 'required',
        //     'industry_id' => 'required',
        //    // 'status_id' => 'required',
        //     'priority' => 'required',
        //     'company_name' => 'required|string',
        //     'website' => 'required|string',
        //     'address' => 'required|string',
        //     'city' => 'required|string',
        //     'state' => 'required|string',
        //     'zipcode' => 'required',
        //     'countries_id' => 'required',
        //     'phone' => 'required',
        //     'folllow_up' => 'required',
        //     //'followup_schedule' => 'required',
        // ];
        // $validator = Validator::make($request->all(), $rules);

        // if ($validator->fails())
        //     return ApiHelper::JSON_RESPONSE(false, '', $validator->messages());


    
           $coupon_id = $request->coupon_id;

     
            $arra = [
                            
                'coupon_name' => $request->coupon_name,
                'coupon_code' => $request->coupon_code,
                'coupon_limit' => $request->coupon_limit,
                'active_at' => $request->active_at,
                'coupon_type' => 1,
                'expire_at' => $request->expire_at,
                'discount' => $request->discount,
                'discount_type'=>$request->discount_type,
                'minimum_purchase' =>$request->minimum_purchase,
                'maximum_discount' =>$request->maximum_discount,
                'first_purchase_only'=>$request->first_purchase_only,
                'multiple_use'=>$request->multiple_use,

                'status' => $request->status,
                        ];
                        
         $coupuns = Coupon::where('coupon_id', $coupon_id)->update($arra);
            
    

        if ($coupuns) {
            return ApiHelper::JSON_RESPONSE(true,[], 'SUCCESS_COUPUN_UPDATE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, '', 'ERROR_COUPUN_UPDATE');
        }
    }


    // public function update(Request $request)
    // {
    //     $api_token = $request->api_token;
    //     $coupon_id = $request->coupon_id;

     
    //     /*
    //     if(!ApiHelper::is_page_access($api_token,$this->BrandManage)){
    //         return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
    //     }
    //     */

    //      if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
    //      return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');



    //     $Insert = $request->only('coupon_name','coupon_code','status','multiple_use','first_purchase_only','maximum_discount','minimum_purchase',);
    //    ApiHelper::image_upload_with_crop($api_token,$Insert['brand_icon'], 4, 'brand');
    //     $res = Brand::where('brand_id',$brand_id)->update($Insert);
    //     //dd($res);
    //     if($res)
    //         return ApiHelper::JSON_RESPONSE(true,$res,'SUCCESS_BRAND_UPDATE');
    //     else
    //         return ApiHelper::JSON_RESPONSE(false,[],'ERROR_BRAND_UPDATE');
    // }

   
   
    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $coupon_id = $request->coupon_id;
        $infoData = Coupon::find($coupon_id);
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();


        
        return ApiHelper::JSON_RESPONSE(true,$infoData,'SUCCESS_STATUS_UPDATE');
    }


    public function view(Request $request)
    {
        $response = Coupon::find($request->coupon_id);


     

        return ApiHelper::JSON_RESPONSE(true, $response, '');
    }


    public function edit(Request $request)
    {
        $response = Coupon::find($request->coupon_id);


     

        return ApiHelper::JSON_RESPONSE(true, $response, '');
    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $coupon_id = $request->coupon_id;
        /*
        if(!ApiHelper::is_page_access($api_token,$this->BrandDelete)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        */
        $status = Coupon::where('coupon_id',$coupon_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_COUPUN_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_COUPUN_DELETE');
        }
    }




}
