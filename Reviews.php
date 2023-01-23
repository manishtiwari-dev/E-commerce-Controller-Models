<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Models\Product;


class Reviews extends Model
{
    use HasFactory;

    protected $primaryKey = "reviews_id";

    public $timestamps = false;

    protected $guarded=[
     
     'reviews_id',


    ];
     
    
    public function getTable()
    {
        return config('dbtable.web_reviews');
    }
   
    public function product(){
        return $this->belongsTo(Product::class, 'products_id', 'product_id');
    }
    

}
