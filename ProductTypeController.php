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
use Modules\Ecommerce\Models\ProductType;
use Modules\Ecommerce\Models\ProductsTypeFieldValue;
use Modules\Ecommerce\Models\Fields;
use Modules\Ecommerce\Models\FieldsGroup;


class ProductTypeController extends Controller
{

    public $page = 'product_type';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request){
        $api_token = $request->api_token;

        
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        $product_type_list = ProductType::all();
        $product_type_list = $product_type_list->map(function($product_type){
            
            // $product_type->fields_group = $product_type->fields_group;
            
            if(!empty($product_type->fields_group)){
                $product_type->fields_group = $product_type->fields_group->map(function($fields_group){
                    $fields_group->fields = $fields_group->fields;
                    return $fields_group;
                });
            }


            return $product_type;
        });

        return ApiHelper::JSON_RESPONSE(true,$product_type_list,'');
    } 

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        
        // validation check 
        $rules = [
            'product_type_name' => 'required|string',
           
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        }
        $product_type_key = Str::slug($request->product_type_name, '-');
        // store Producttypes
        $saveData = $request->only(['product_type_name','product_type_key']);
        // $saveData['parent_id'] = ($request->category_type == 'parent') ? $request->main_category : $request->sub_category;

        $product_type = ProductType::create($saveData);

        $fieldsgrouparray=[];
        if($request->has('fields_group'))
        {
            $fields_group=$request->fields_group;
            $data=explode(',',$fields_group);
            //var_dump($data);
            if(!empty($data))
            {
                foreach($data as $key=>$value)
                {
                    $fieldsgrouparray[$key]['product_type_id'] = $product_type->product_type_id;
                    $fieldsgrouparray[$key]['fieldsgroup_id'] = $value;

                    // array_push($fieldsgrouparray,[$key]['product_type_id'],[$key]['fieldsgroup_id']);
                }
            }
        }
        //dd($fieldsgrouparray);
        
        $product_type->fields_group()->attach($fieldsgrouparray);

        if($product_type){
            return ApiHelper::JSON_RESPONSE(true,$fieldsgrouparray,'SUCCESS_PRODUCT_TYPE_ADD');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_PRODUCT_TYPE_ADD');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $response = ProductType::find($request->product_type_id);
        if($response != null)
            $response->fields_group = !empty($response->fields_group) ? $response->fields_group : [];

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
        // Validate user page access
        $api_token = $request->api_token;
        $product_type_id = $request->product_type_id;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        


        // validation check 
        $rules = [
            'product_type_id' => 'required',
           'product_type_name' => 'required',
            //'product_type_key'=>'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) 
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        
            $product_type_key = Str::slug($request->product_type_name, '-');
        // store ProductTypes 
        $saveData = $request->only(['product_type_name','status','product_type_key']);
        
        ProductType::where('product_type_id', $product_type_id)->update($saveData);
        
        $product_type = ProductType::find($product_type_id);  
        $product_type->fields_group()->detach();

        $fieldsgrouparray=[];
        if($request->has('fields_group'))
        {
            $fields_group=$request->fields_group;
            $data=explode(',',$fields_group);
            //var_dump($data);
            if(!empty($data))
            {
                foreach($data as $key=>$value)
                {
                    $fieldsgrouparray[$key]['product_type_id'] = $product_type->product_type_id;
                    $fieldsgrouparray[$key]['fieldsgroup_id'] = $value;

                    // array_push($fieldsgrouparray,[$key]['product_type_id'],[$key]['fieldsgroup_id']);
                }
            }
        }
        //dd($fieldsgrouparray);
        
        $product_type->fields_group()->attach($fieldsgrouparray);

        
        if($product_type){
            return ApiHelper::JSON_RESPONSE(true,$fieldsgrouparray,'SUCCESS_PRODUCT_TYPE_UPDATE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_PRODUCT_TYPE_UPDATE');
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
        $infoData = FieldsGroup::find($request->fieldsgroup_id);
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true,$infoData,'SUCCESS_STATUS_UPDATE');

    }

  

    public function sortOrder(Request $request){

        $api_token = $request->api_token;
    
        if($request->type == "fieldsGroup")
            $infoData = FieldsGroup::find($request->update_id);
        else
            $infoData = Fields::find($request->update_id);
    
        $infoData->sort_order = (int)$request->sort_order;
        $res = $infoData->save();
    
       
        return ApiHelper::JSON_RESPONSE(true,$res,'SUCCESS_SORT_ORDER_UPDATE');
    
    }

}
