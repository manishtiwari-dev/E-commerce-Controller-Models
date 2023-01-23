<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Ecommerce\Models\Reviews;
use Modules\Ecommerce\Models\ProductDescription;
use Illuminate\Http\Request;
use App\Models\Language;

use ApiHelper;



class ReviewsController extends Controller
{
    public $page = 'reviews';
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
        $reviews_query = Reviews::with('product','product.productdescription');


               // return ApiHelper::JSON_RESPONSE(true,$reviews_query,'');

        /*Checking if search data is not empty*/
        if(!empty($search))
            $reviews_query = $reviews_query
                ->where("customers_name","LIKE", "%{$search}%")
                ->orWhere("customers_email","LIKE", "%{$search}%")
                ->orWhere("reviews_title", "LIKE", "%{$search}%")
                ->orWhere("reviews_text", "LIKE", "%{$search}%");                
                
        /* order by sorting */
        if(!empty($sortBy) && !empty($orderBy))
            $reviews_query = $reviews_query->orderBy($sortBy,$orderBy);
        else
            $reviews_query = $reviews_query->orderBy('reviews_id','DESC');

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;

        $reviews_count = $reviews_query->count();

        $reviews_list = $reviews_query->skip($skip)->take($perPage)->get();
         
         
        $reviews_list = $reviews_list->map(function($data) use ($language)  {
            
            $productname = '';

            if(!empty($data->product)){
              if(!empty($data->product->productdescription)){
                 $proName = $data->product->productdescription()->where('languages_id', ApiHelper::getLangid($language))->first();
                    $productname = ($proName == null) ? '' : $proName->products_name;
                }
            }

            $data->products_name = $productname;
            return $data;
        });
        
        
        
         /*Binding data into a variable*/
        $res = [
            'data'=>$reviews_list,
            'current_page'=>$current_page,
            'total_records'=>$reviews_count,
            'total_page'=>ceil((int)$reviews_count/(int)$perPage),
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



       $validator = Validator::make($request->all(),[
            'customers_name' => 'required',
            // 'customers_email' => 'required',
            // 'reviews_title' => 'required',
            // 'reviews_text' => 'required',   
 
        ],[
            'customers_name.required'=>'CUSTOMER_NAME_REQUIRED',
          ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        
        $prdopval = Reviews::create([
        'products_id' => $request->products_id,
        'customers_name' => $request->customers_name,
        'customers_email' => $request->customers_email,
        'reviews_title' => $request->reviews_title,
        'reviews_text' => $request->reviews_text,
        'reviews_read' => 0,
        'quality_rating' => $request->quality_rating,
        'price_rating' => $request->price_rating,
        // 'status' => $request->status,
        
        ]);



        if ($prdopval) {
            return ApiHelper::JSON_RESPONSE(true, $prdopval, 'SUCCESS_REVIEWS_CREATED');
        } else {
            return ApiHelper::JSON_RESPONSE(false,'', 'ERROR_REVIEWS_CREATED');
        }
    }


 
    
     public function create(Request $request){
        $api_token = $request->api_token;
         $language = $request->language;

        $product_list = ProductDescription::select('products_name as label','products_id as value')->where('languages_id', ApiHelper::getLangid($language))->get();


        $res = [
            'product_list'=>$product_list,
        ];

        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
    
   
   
    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $reviews_id = $request->reviews_id;
        $sub_data = Reviews::find($reviews_id);
        $sub_data->status = $request->status;         
        $sub_data->save();
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }


}
