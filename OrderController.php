<?php
namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;

use Modules\Ecommerce\Models\Order;


class OrderController extends Controller
{
    public $page = 'orders';
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
        
        /*Fetching subscriber data*/ 
        $orders_query = Order::with('user');
        //  $orders_query = ApiHelper::attach_query_permission_filter($orders_query, $api_token, $this->page, $this->pageview);

        $dateFormat= ApiHelper::dateFormat();

        /*Checking if search data is not empty*/
        if(!empty($search))
            $orders_query = $orders_query
        ->where("order_id","LIKE", "%{$search}%")
        ->orWhere("order_number","LIKE", "%{$search}%");

        /* order by sorting */
        if(!empty($sortBy) && !empty($orderBy))
            $orders_query = $orders_query->orderBy($sortBy,$orderBy);
        else
            $orders_query = $orders_query->orderBy('order_id','DESC');

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;

        $orders_count = $orders_query->count();

        $orders_list = $orders_query->skip($skip)->take($perPage)->get();
        
        /*Binding data into a variable*/
        $res = [
            'data'=>$orders_list,
            'current_page'=>$current_page,
            'total_records'=>$orders_count,
            'total_page'=>ceil((int)$orders_count/(int)$perPage),
            'per_page'=>$perPage,
            'date_format'=>$dateFormat
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');

        
    }


    public function orderDetails(Request $request)
    {
       $api_token = $request->api_token;

       $res=Order::with('item','address','user','item.product.productdescription')->where('order_number',$request->order_number)->first();

       
     return ApiHelper::JSON_RESPONSE(true,$res,''); 



 }


 public function changeStatus(Request $request)
 {

    $api_token = $request->api_token;
    $order_update_data=$request->except(['api_token','order_id']);
    $data = Order::where('order_id', $request->order_id)->update($order_update_data);
    if($data)
        return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_STATUS_UPDATE');
    else
        return ApiHelper::JSON_RESPONSE(false,[],'ERROR_STATUS_UPDATE');

}


}
