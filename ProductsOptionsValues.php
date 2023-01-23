<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Models\ProductOptions;

class ProductsOptionsValues extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'products_options_values_id';
    

    protected $fillable = [
       'products_options_id',
       'products_options_values_name',
       'status',
       'sort_order',
    ];
    public $timestamps=false;

    public function getTable(){
        return config('dbtable.ecm_products_options_values');
    }

    public function product_options(){
        return $this->belongsTo(ProductOptions::class, 'products_options_id', 'products_options_id');
    }


}
