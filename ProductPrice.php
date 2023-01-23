<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'product_price_id';

    protected $guarded = [
      'product_price_id'
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.ecm_products_price');
    }


}
