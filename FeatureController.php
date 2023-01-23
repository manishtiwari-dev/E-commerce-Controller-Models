<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use Illuminate\Support\Str;
use Validator;
use Auth;
use Modules\Ecommerce\Models\Feature;
class FeatureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public $page = 'web_feature';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';

    public function index(Request $request){

         // Validate user page access
         $api_token = $request->api_token;
         if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
       

       return ApiHelper::JSON_RESPONSE(false,$request->only('attributes'),'PAGE_ACCESS_DENIED');
       


        $data_list = Feature::all();
        $data_list = $data_list->map(function($data)   {

        
           $data->status = ($data->status == 1) ? "active":"deactive";
            $data->feature_icon = ApiHelper::getFullImageUrl($data->feature_icon);


            return $data;
        });

        $res = [
            'data_list'=> $data_list
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');    }

    public function store(Request $request)
    {
      
    // Validate user page access
        $api_token = $request->api_token;
          if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        

        return ApiHelper::JSON_RESPONSE(false,$request->only('attributes'),'PAGE_ACCESS_DENIED');
        

        $validator = Validator::make($request->all(),[
            'feature_title' => 'required',
            'feature_subtitle' => 'required',
            'feature_icon' => 'required',
            
        ],[
            'feature_title.required'=>'FEATURED_TITLE_REQUIRED',
            'feature_icon.required'=>'FEATURED_ICON_REQUIRED',
            'feature_subtitle.required'=>'FEATURED_SUBTITLE_REQUIRED',
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        

       // $user_id = ApiHelper::get_adminid_from_token($api_token);
        $Insert = $request->only('feature_title','feature_icon','status','feature_subtitle');
        //$Insert['created_by'] = $user_id;

        //ApiHelper::image_upload_with_crop($api_token,$Insert['feature_icon'], 4, 'feature');
    
        ApiHelper::image_upload_with_crop($api_token, $Insert['feature_icon'], 1, 'assest','', false);

        $res = Feature::create($Insert);
        if($res)
            return ApiHelper::JSON_RESPONSE(true,$res->feature_id,'SUCCESS_FEATURE_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FEATURE_ADD');

    }

    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        /*
        if(!ApiHelper::is_page_access($api_token,$this->BrandManage)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        */
        $data_list = Feature::where('feature_id',$request->feature_id)->first();
    
        //   $data_list->feature_icon = ApiHelper::getFullImageUrl($data_list->feature_icon, 'index-list');

        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

    }


    public function update(Request $request)
    {
        $api_token = $request->api_token;
        $feature_id = $request->feature_id;
         // Validate user page access
     
         if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
       
       return ApiHelper::JSON_RESPONSE(false,$request->only('attributes'),'PAGE_ACCESS_DENIED');
       

        $user_id = Auth::id();

        $validator = Validator::make($request->all(),[
            'feature_title' => 'required',
            'feature_subtitle' => 'required',
            'feature_icon' => 'required',
            'status' => 'required',
        ],[
            'feature_title.required'=>'FEATURED_TITLE_REQUIRED',
            'feature_icon.required'=>'FEATURED_ICON_REQUIRED',
            'feature_subtitle.required'=>'FEATURED_SUBTITLE_REQUIRED',
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        

        $user_id = ApiHelper::get_adminid_from_token($api_token);

        $Insert = $request->only('feature_title','feature_icon','status','feature_subtitle');

        if($Insert['feature_icon'] != '')
        // ApiHelper::image_upload_with_crop($api_token, $web_feature_img, 1, 'industry/feature', '', false);

        ApiHelper::image_upload_with_crop($api_token, $Insert['feature_icon'], 1, 'assest','', false);

     //  ApiHelper::image_upload_with_crop($api_token,$Insert['feature_icon'], 4, 'feature');
         Feature::where('feature_id',$feature_id)->update($Insert);
        //dd($res);
        if($Insert)
            return ApiHelper::JSON_RESPONSE(true,$Insert,'SUCCESS_FEATURE_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FEATURE_UPDATE');
    }

    public function destroy(Request $request)
    {
        
    }

     public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
         $infoData = Feature::where('feature_id',$request->feature_id)->first();
         $infoData->status = ($infoData->status == 0) ? 1 : 0;
         $infoData->save();
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_STATUS_UPDATE');
    }

}
