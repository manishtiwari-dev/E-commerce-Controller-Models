<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptions extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'products_options_id';

    protected $guarded = [
        'products_options_id',
        
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.ecm_products_options');
    }

    public function products_options_values(){

        return $this->hasMany(ProductsOptionsValues::class,'products_options_id','products_options_id');
    }

    public static function product_options_with_value($products_options_id=""){
        if(empty($products_options_id)){

            $product_options = Self::all()->map(function($options){
                $options->products_options_values = $options->products_options_values;
                return $options;
            });
            return $product_options;

        }else{
            
            $product_options = Self::find($products_options_id);
            if($product_options !== null)
                $product_options->products_options_values = $product_options->products_options_values;
            
            return $product_options;

        }
    }



}
