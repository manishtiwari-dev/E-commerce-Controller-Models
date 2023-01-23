<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Services\OrderService;
use Modules\Ecommerce\Models\OrderAddress;
use Modules\Ecommerce\Models\OrderItems;
use App\Models\User;
use Modules\CRM\Models\CRMCustomer;





class Order extends Model
{
    use HasFactory;

  
    protected $primaryKey = 'order_id';

    public $timestamps = false;
    protected $guarded = ['order_id'];

    public function address(){
        return $this->hasMany(OrderAddress::class, 'order_id','order_id');
    }

    public function item(){
        return $this->hasMany(OrderItems::class, 'order_id','order_id');
    }



    public function user(){
        return $this->belongsTo(CRMCustomer::class, 'customer_id','customer_id');
    }


    //service
    public static function placeOrder($request){
        return OrderService::placeOrder($request);
    }


     public function getTable()
    {
        return config('dbtable.web_orders');
    }


    

}
