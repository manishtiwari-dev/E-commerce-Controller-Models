<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    use HasFactory;
    //protected $table = 'web_order_address';
    protected $primaryKey = 'order_address_id';
    protected $guarded = ['order_address_id'];


      public function getTable()
    {
        return config('dbtable.web_order_address');
    }
    
    
}
