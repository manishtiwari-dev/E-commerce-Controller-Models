<?php
namespace Modules\Ecommerce\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\CategoryDescription;
use Modules\Ecommerce\Models\Category;
use Modules\Ecommerce\Models\FieldsGroup;
use Modules\Ecommerce\Models\ListingOptions;
use Modules\Ecommerce\Models\ListingOptionsValues;
use App\Models\Language;
use ApiHelper;

use Illuminate\Support\Facades\Storage;

use Modules\Ecommerce\Models\Fields;


class ListingOptionsController extends Controller
{

    public $page = 'fieldsgroup';
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
        $perPage = !empty($request->perPage)?$request->perPage:10;
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $language = $request->language;
        
        $data_query = ListingOptions::with('products_options_values');
        
        
        if(!empty($search))
            // $data_query = $data_query->where("name","LIKE", "%{$search}%")->orWhere("email", "LIKE", "%{$search}%");

            /* order by sorting */
        if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('listing_options_id','ASC');
        }

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;
        
        $user_count = $data_query->count();

        $data_list = $data_query->get();
        
     
        return ApiHelper::JSON_RESPONSE(true,$data_list,'');
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function store(Request $request)
    // {
    //     // Validate user page access
    //     $api_token = $request->api_token;

    //     // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
    //     //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        


    //     //validation check 
    //     $rules = [
    //         'products_options_name' => 'required|string',
    //     ];
    //     $validator = Validator::make($request->all(), $rules);
    //     if ($validator->fails()) {
    //         return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
    //     }



    //     // store Fields 
    //     $saveData = $request->only(['products_options_name']);
    //     // $saveData['parent_id'] = ($request->category_type == 'parent') ? $request->main_category : $request->sub_category;
        
        

    //     $cat = ProductOptions::create($saveData);

       



    //     if($cat){
    //         return ApiHelper::JSON_RESPONSE(true,$cat,'SUCCESS_PRODUCT_OPTION_ADD');
    //     }else{
    //         return ApiHelper::JSON_RESPONSE(false,[],'ERROR_PRODUCT_OPTION_ADD');
    //     }
    // }

    // /**
    //  * Show the form for editing the specified resource.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function edit(Request $request)
    // {
    //     $response = ProductOptions::find($request->products_options_id);
    //     return ApiHelper::JSON_RESPONSE(true,$response,'');

    // }

    // *
    //  * Update the specified resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
     
    // public function update(Request $request)
    // {
    //     // Validate user page access
    //     $api_token = $request->api_token;
    //     $products_options_id = $request->products_options_id;

    //     // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
    //     //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        


    //     //validation check 
    //     $rules = [
    //         'products_options_id' => 'required',
    //         'products_options_name' => 'required',
            
    //     ];
    //     $validator = Validator::make($request->all(), $rules);
    //     if ($validator->fails()) 
    //         return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        

    //     // store fieldsgroup
    //     $saveData = $request->only(['products_options_name']); 
    //     $fldg = ProductOptions::where('products_options_id', $products_options_id)->update($saveData);

       
    //     if($fldg){
    //         return ApiHelper::JSON_RESPONSE(true,$saveData,'SUCCESS_PRODUCT_OPTION_UPDATE');
    //     }else{
    //         return ApiHelper::JSON_RESPONSE(false,[],'ERROR_PRODUCT_OPTION_UPDATE');
    //     }
    // }


    // public function changeStatus(Request $request){
    //     $api_token = $request->api_token;
    //     $infoData = ProductOptions::find($request->products_options_id);
    //     $infoData->status = ($infoData->status == 0) ? 1 : 0;
    //     $infoData->save();
    //     return ApiHelper::JSON_RESPONSE(true,$infoData,'SUCCESS_STATUS_UPDATE');

    // }
}
