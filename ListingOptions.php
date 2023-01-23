<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingOptions extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'listing_options_id';

    protected $fillable = [
        'listing_options_name',
        
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.biz_listing_options');
    }

     public function products_options_values(){

         return $this->hasMany(ListingOptionsValues::class,'listing_options_id','listing_options_id');
    }

     public static function product_options_with_value($listing_options_id=""){
        if(empty($listing_options_id)){

             $product_options = Self::all()->map(function($options){
                 $options->products_options_values = $options->products_options_values;
                return $options;
             });
            return $product_options;

         }else{
            
            $product_options = Self::find($listing_options_id);
             if($product_options !== null)
                 $product_options->products_options_values = $product_options->products_options_values;
            
             return $product_options;

         }
     }


}
