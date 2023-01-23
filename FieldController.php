<?php
namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


use Modules\Ecommerce\Models\CategoryDescription;
use Modules\Ecommerce\Models\Category;

use App\Models\Language;
use ApiHelper;

use Illuminate\Support\Facades\Storage;

use Modules\Ecommerce\Models\Fields;


class FieldController extends Controller
{

    public $page = 'Fields';
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

        // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        


        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();

        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $language = $request->language;
        
        $data_query = Fields::select('fieldsgroup_id as label','field_name as name');
        
        if(!empty($search))
            // $data_query = $data_query->where("name","LIKE", "%{$search}%")->orWhere("email", "LIKE", "%{$search}%");

            /* order by sorting */
        if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('fields_id','ASC');
        }

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;
        
        $user_count = $data_query->count();

        $data_list = $data_query->skip($skip)->take($perPage)->get();
        
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        


        //validation check 
        $rules = [
            'fieldsgroup_id' => 'required',
            'field_name'=>'required',
            'field_label'=>'required',
            'field_type'=>'required',
            'field_placeholder'=>'required',
            'field_required'=>'required',
            'sort_order'=>'required',

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        }



        // store category 
        $saveData = $request->only(['fieldsgroup_id','field_name','field_label','field_type','field_placeholder','field_options',
        'field_value','field_required','sort_order']);
     

        $cat = Fields::create($saveData);

        if($cat){
            return ApiHelper::JSON_RESPONSE(true,$cat,'SUCCESS_FIELD_ADD');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FIELD_ADD');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $response = Fields::find($request->fields_id);

        if($response != null)
            $response->fields_id = $response->fields_id;

        return ApiHelper::JSON_RESPONSE(true,$response,'');

    }

 


    public function update(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        $fieldsgroup_id = $request->fields_id;


        // store category 
        $saveData = $request->only(['fieldsgroup_id','field_name','field_label','field_type','field_placeholder','field_options','field_value','field_required','sort_order']);
       

        
        $cat = Fields::where('fields_id', $fieldsgroup_id)->update($saveData);

       
           

        
        if($cat){
            return ApiHelper::JSON_RESPONSE(true,$saveData,'SUCCESS_FIELD_UPDATE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FIELD_UPDATE');
        }

    }


    public function changeStatus(Request $request){
        $api_token = $request->api_token;
        $infoData = Fields::find($request->fields_id);
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true,$infoData,'SUCCESS_STATUS_UPDATE');

    }
}
