<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use ApiHelper;
use Modules\Ecommerce\Models\Supplier;
use App\Models\Country;


class SupplierController extends Controller
{


    public $page = 'supplier';
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

       $data_query = Supplier::select('supplier_id', 'supplier_name', 'supplier_address','supplier_city','supplier_state','supplier_country','status');

       // attaching query filter by permission(all, added,owned,both)
        $data_query = ApiHelper::attach_query_permission_filter($data_query, $api_token, $this->page, $this->pageview);


       //$data_query=Supplier::all();

       


       if (!empty($search)) {
        $data_query = $data_query->where("supplier_name", "LIKE", "%{$search}%")->orWhere("supplier_address", "LIKE", "%{$search}%")->orWhere("supplier_city", "LIKE", "%{$search}%")->orWhere("supplier_state", "LIKE", "%{$search}%")->orWhereHas('country', function ($data_query) use ($search) {
                $data_query->where("countries_name", "LIKE", "%{$search}%");
            });
       }


        // if(!empty($search))
        // $data_query = $data_query->where("supplier_name","LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $data_query = $data_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $data_query = $data_query->orderBy('supplier_id', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;
        $user_count = $data_query->count();

        $data_list = $data_query->skip($skip)->take($perPage)->get();

        $data_list = $data_list->map(function($data)   {
            $data->status = ($data->status == 1) ? "active":"deactive";     
            return $data;
        });

        $res = [
            'data'=>$data_list,
            'current_page'=>$current_page,
            'total_records'=>$user_count,
            'total_page'=>ceil((int)$user_count/(int)$perPage),
            'per_page'=>$perPage
        ];

        return ApiHelper::JSON_RESPONSE(true,$res, '');
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
            'supplier_name' => 'required|string',
            'supplier_address' => 'required',
            'supplier_city' => 'required|string',
            'supplier_state' => 'required|string',
            'supplier_country' => 'required|int',
        
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false,'', $validator->messages());
        }
        $prdopval = Supplier::create([
        'supplier_name' => $request->supplier_name,
        'supplier_address' => $request->supplier_address,
        'supplier_city' => $request->supplier_city,
        'supplier_state' => $request->supplier_state,
        'supplier_country' => $request->supplier_country,
        // 'status' => $request->status,
        
        ]);

        /*
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
        */

        if ($prdopval) {
            return ApiHelper::JSON_RESPONSE(true, $prdopval, 'SUPPLIER_CREATED');
        } else {
            return ApiHelper::JSON_RESPONSE(false,'', 'SOME_ISSUE');
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
        $api_token = $request->api_token;

        $response = Supplier::find($request->supplier_id);
        if (!empty($response)) {
           
         $response->country=$response->country;
              
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
        // Validate user page access
        $api_token = $request->api_token;
        

         if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');



        //validation check 
        $rules = [
            'supplier_name' => 'required|string',
            'supplier_address' => 'required',
            'supplier_city' => 'required|string',
            'supplier_state' => 'required|string',
            'supplier_country' => 'required|int',
            'status' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,'', $validator->messages());

        
        /*
        // store fieldsgroup
        //$saveData = $request->only(['sort_order','fieldsgroup_name','status','fieldsgroup_description']); 
         $products_options_id = $request->products_options_id;
        $products_options_values_name = $request->products_options_values_name;
        $productoptvalarray = explode(",", $products_options_values_name);
        ProductsOptionsValues::where('products_options_id',$products_options_id )->delete();;
        //$post->delete();

        // store Fields 
        //$saveData = $request->only(['sort_order','fieldsgroup_name','fieldsgroup_description']);
        // $saveData['parent_id'] = ($request->category_type == 'parent') ? $request->main_category : $request->sub_category;

        foreach ($productoptvalarray as $value) {
            $prdopval = ProductsOptionsValues::create([
                'products_options_id' => $products_options_id,
                'products_options_values_name' => $value,

            ]);
        }
        */

        //$fldg = ProductsOptionsValues::where('products_options_id', $products_options_id)->update($prdopval);
      
            $prdopval = Supplier::where('supplier_id',$request->supplier_id)
              ->update(['supplier_name' => $request->supplier_name,
              'supplier_address' => $request->supplier_address,
              'supplier_city' => $request->supplier_city,
              'supplier_state' => $request->supplier_state,
              'supplier_country' => $request->supplier_country,
              'status' => $request->status,]);

        
        if ($prdopval) {
            return ApiHelper::JSON_RESPONSE(true, $prdopval,'SUPPLIER_UPDATED');
        } else {
            return ApiHelper::JSON_RESPONSE(false,'', 'SOME_ISSUE');
        }
    }


     public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
         $infoData = Supplier::where('supplier_id',$request->supplier_id)->first();
         $infoData->status = ($infoData->status == 0) ? 1 : 0;
         $infoData->save();
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'STATUS_UPDATED');
    }



    public function create()
    {
      
        $countrydata = Country::all();
     
        $res = [
            
            'countrydata' => $countrydata,
           
        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }
}
