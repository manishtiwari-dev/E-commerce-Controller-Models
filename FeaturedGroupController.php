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





class FeaturedGroupController extends Controller
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
        
        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $language = $request->language;
        
        $data_query = FeaturedGroup::with('feature_product','feature_product.product');
        
        if(!empty($search))
            
             $data_query = $data_query->where("group_name","LIKE", "%{$search}%")->orWhere("group_title", "LIKE", "%{$search}%");

            /* order by sorting */
        if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('featured_group_id','DESC');
        }

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;
        
        $user_count = $data_query->count();

        $data_list = $data_query->skip($skip)->take($perPage)->get();

        if(!empty($data_list)){
            $data_list->map(function($data){
                $data->productCount = $data->feature_product()->count();
                return $data;
            });
        }
        
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
   
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
   

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

     public function store(Request $request)
     {

        // Validate user page access
        $api_token = $request->api_token;
          if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        

        return ApiHelper::JSON_RESPONSE(false,$request->only('attributes'),'PAGE_ACCESS_DENIED');
        
         //validation check 
        $rules = [
           
            'group_name'=>'required',
            'group_title'=>'required',
        //    'sort_order'=>'required',

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        }



        // store category ,
        $saveData = $request->only(['group_name','group_title']);
        $data =  FeaturedGroup::max('sort_order');
        $sort_order=$data+1;
            
        //  return ApiHelper::JSON_RESPONSE(true,   $sort_order,'SUCCESS_STATUS_UPDATE');
           $saveData['sort_order'] =$sort_order;


        $cat = FeaturedGroup::create($saveData);

        if($cat){
            return ApiHelper::JSON_RESPONSE(true,$cat,'SUCCESS_FEATURED_GROUP_ADD');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FEATURED_GROUP_ADD');
        }


     }


     public function edit(Request $request)
     {
        $api_token = $request->api_token;
       $data_list = FeaturedGroup::where('featured_group_id',$request->featured_group_id)->first();
        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

     }

    
    public function update(Request $request)
    {

      // Validate user page access
        $api_token = $request->api_token;
          if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
        

        return ApiHelper::JSON_RESPONSE(false,$request->only('attributes'),'PAGE_ACCESS_DENIED');
        
        $featured_group_id = $request->featured_group_id;


        //validation check 
        $rules = [
            'group_name'=>'required',
            'group_title'=>'required',
            'sort_order'=>'required',

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) 
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        

        // store fieldsgroup
        $saveData = $request->only(['group_name','group_title','sort_order','status']); 
         FeaturedGroup::where('featured_group_id', $featured_group_id)->update($saveData);

       
        if($saveData){
            return ApiHelper::JSON_RESPONSE(true,$saveData,'SUCCESS_FEATURED_GROUP_UPDATE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FEATURED_GROUP_UPDATE');
        }

    }

   

    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $featured_group_id = $request->featured_group_id;
        /*
        if(!ApiHelper::is_page_access($api_token,$this->BrandDelete)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        */
        $status = FeaturedGroup::where('featured_group_id',$featured_group_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_FEATURE_GROUP_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FEATURE_GROUP_DELETE');
        }
    }


    public function changeStatus(Request $request){      
        $api_token = $request->api_token;
        $infoData = FeaturedGroup::find($request->featured_group_id);
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true,$infoData,'SUCCESS_STATUS_UPDATE');
    }


}
