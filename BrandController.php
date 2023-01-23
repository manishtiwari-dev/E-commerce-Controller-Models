<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use Illuminate\Support\Str;
use Validator;
use Auth;
use Modules\Ecommerce\Models\Brand;
class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    // public $BrandView = 'brand-view';
    // public $BrandManage = 'brand-manage';
    // public $BrandDelete = 'brand-delete';

    public $page = 'brand';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    public function index(Request $request){

        // Validate user page access
        $api_token = $request->api_token;

        // if(!ApiHelper::is_page_access($api_token, $this->BrandView)){
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        // }
        
         if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
         return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?$request->perPage:10;
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        $data_query = Brand::query();

          // attaching query filter by permission(all, added,owned,both)
        $data_query = ApiHelper::attach_query_permission_filter($data_query, $api_token, $this->page, $this->pageview);


        // search
        if(!empty($search))
            $data_query = $data_query->where("brand_name","LIKE", "%{$search}%");

        /* order by sorting */
        if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('brand_id','ASC');
        }

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;     // apply page logic

        $data_count = $data_query->count(); // get total count

        $data_list = $data_query->skip($skip)->take($perPage)->get(); 
        
        $data_list = $data_list->map(function($data)   {
            $data->status = ($data->status == 1) ? "active":"deactive";  
            $data->brand_icon = ApiHelper::getFullImageUrl($data->brand_icon, 'index-list');   
            return $data;
        });

        $res = [
            'data'=>$data_list,
            'current_page'=>$current_page,
            'total_records'=>$data_count,
            'total_page'=>ceil((int)$data_count/(int)$perPage),
            'per_page'=>$perPage
        ];

        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }

    public function store(Request $request)
    {
        $api_token = $request->api_token;

       /* if(!ApiHelper::is_page_access($api_token,$this->BrandManage))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        */
         
          if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
         return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');


          //validation check 
        $validator = Validator::make($request->all(),[
            'brand_icon'=> 'required',
          
        ],
        [
            
        'brand_icon.required'=>'BRAND_ICON_REQUIRED',

        ]



    );
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());


      

        $user_id = ApiHelper::get_adminid_from_token($api_token);
        $Insert = $request->only('brand_name','brand_icon',);
        //$Insert['created_by'] = $user_id;
        $Insert['brand_slug'] = Str::slug($request->brand_name);


        ApiHelper::image_upload_with_crop($api_token, $Insert['brand_icon'], 1, 'brand', '', false);


    //  ApiHelper::image_upload_with_crop($api_token,$Insert['brand_icon'], 4, 'brand');


        $res = Brand::create($Insert);
        if($res)
            return ApiHelper::JSON_RESPONSE(true,$res->brand_id,'SUCCESS_BRAND_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_BRAND_ADD');

    }

    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        /*
        if(!ApiHelper::is_page_access($api_token,$this->BrandManage)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        */
        $data_list = Brand::where('brand_id',$request->brand_id)->first();

        $data_list->img = ApiHelper::getFullImageUrl($data_list->brand_icon, 'index-list');

        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

    }

    public function update(Request $request)
    {
        $api_token = $request->api_token;
        $brand_id = $request->brand_id;
        /*
        if(!ApiHelper::is_page_access($api_token,$this->BrandManage)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        */

         if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
         return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');



             //validation check 
        $validator = Validator::make($request->all(),[
            'brand_icon'=> 'required',
          
        ],
        [
            
        'brand_icon.required'=>'BRAND_ICON_REQUIRED',

        ]



    );
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());



        $user_id = Auth::id();

     

        $user_id = ApiHelper::get_adminid_from_token($api_token);

        $Insert = $request->only('brand_name','brand_icon','status');

        $Insert['brand_slug'] = Str::slug($request->brand_name);

        ApiHelper::image_upload_with_crop($api_token, $Insert['brand_icon'], 1, 'brand', '', false);

    //   ApiHelper::image_upload_with_crop($api_token,$Insert['brand_icon'], 4, 'brand');
        $res = Brand::where('brand_id',$brand_id)->update($Insert);
        //dd($res);
        if($res)
            return ApiHelper::JSON_RESPONSE(true,$res,'SUCCESS_BRAND_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_BRAND_UPDATE');
    }

    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $brand_id = $request->brand_id;
        /*
        if(!ApiHelper::is_page_access($api_token,$this->BrandDelete)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        */
        $status = Brand::where('brand_id',$brand_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_BRAND_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_BRAND_DELETE');
        }
    }

     public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
         $infoData = Brand::where('brand_id',$request->brand_id)->first();
         $infoData->status = ($infoData->status == 0) ? 1 : 0;
         $infoData->save();
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_STATUS_UPDATE');
    }

}
