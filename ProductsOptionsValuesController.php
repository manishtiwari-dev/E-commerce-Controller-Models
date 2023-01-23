<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\ProductsOptionsValues;
use Modules\Ecommerce\Models\ProductOptions;

use ApiHelper;

class ProductsOptionsValuesController extends Controller
{
    public $page = 'product_option';
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



        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();

        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        //$language = $request->language;

        $data_query = ProductsOptionsValues::select('products_options_values_id as value_id', 'products_options_id as option_id', 'products_options_values_name as values_name');

        //if(!empty($search))
        // $data_query = $data_query->where("name","LIKE", "%{$search}%")->orWhere("email", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $data_query = $data_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $data_query = $data_query->orderBy('products_options_values_id', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $user_count = $data_query->count();

        $data_list = $data_query->get();


        return ApiHelper::JSON_RESPONSE(true, $data_list, '');
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



        //validation check 
        $rules = [
            'products_options_values_name' => 'required|string',
            'products_options_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        $products_options_id = $request->products_options_id;
        $products_options_values_name = $request->products_options_values_name;
        $productoptvalarray = explode(",", $products_options_values_name);
        // store Fields 
        //$saveData = $request->only(['sort_order','fieldsgroup_name','fieldsgroup_description']);
        // $saveData['parent_id'] = ($request->category_type == 'parent') ? $request->main_category : $request->sub_category;
        if (!empty($productoptvalarray)) {
            foreach ($productoptvalarray as $value) {
                $prdopval = ProductsOptionsValues::create([
                    'products_options_id' => $products_options_id,
                    'products_options_values_name' => $value,

                ]);
            }
        }

        if ($prdopval) {
            return ApiHelper::JSON_RESPONSE(true, $productoptvalarray, 'SUCCESS_OPTIONS_VALUES_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_OPTIONS_VALUES_ADD');
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
        $response = ProductsOptionsValues::where('products_options_values_id',$request->products_options_values_id)->first();
        return ApiHelper::JSON_RESPONSE(true, $response, '');
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
        $products_options_values_id = $request->products_options_values_id;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        


        //validation check 
        $rules = [
           // 'products_options_id' => 'required',
            'products_options_values_name' => 'required',
            
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) 
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        

        // store fieldsgroup
        $saveData = $request->only(['products_options_values_name','products_options_id']); 
         ProductsOptionsValues::where('products_options_values_id', $products_options_values_id)->update($saveData);

       
        if($saveData){
            return ApiHelper::JSON_RESPONSE(true,$saveData,'SUCCESS_OPTION_VALUES_UPDATE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_OPTION_VALUES_UPDATE');
        } 
         

    }


    public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
        $infoData=ProductsOptionsValues::find($request->products_options_values_id);
        if(!empty($infoData)){
      //  $infoData = ProductsOptionsValues::where('products_options_values_id',$request->products_options_values_id)->get();
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();
        }
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'STATUS_UPDATED');
    }



    
    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $products_options_values_id = $request->products_options_values_id;
        $sort_order=$request->sort_order;
        $infoData =  ProductsOptionsValues::find($products_options_values_id);
        if(empty($infoData)){
            $infoData = new ProductsOptionsValues();
            $infoData->products_options_values_id=$products_options_values_id;
            $infoData->sort_order =$sort_order;
            $infoData->status =1;

            $infoData->save();
        
        }else{
            $infoData->sort_order = $sort_order;
            $infoData->save();
        }
       
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_SORT_ORDER_UPDATE');
    }    

    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $products_options_values_id = $request->products_options_values_id;
        /*
        if(!ApiHelper::is_page_access($api_token,$this->BrandDelete)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        */
        $status = ProductsOptionsValues::where('products_options_values_id',$products_options_values_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_PRODUCT_OPTION_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_OPTION_PRODUCT_DELETE');
        }
    }

    
    public function view(Request $request)
    {
       $api_token = $request->api_token;

       $res=ProductsOptionsValues::where('products_options_id',$request->products_options_id)->get();

       
     return ApiHelper::JSON_RESPONSE(true,$res,''); 



 }

}
