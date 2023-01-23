<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'coupon_id';
   
    protected $guarded = ['coupon_id'];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.ecm_coupon');
    }
    

}
