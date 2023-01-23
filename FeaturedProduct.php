<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Models\FeaturedGroup;
use Modules\Ecommerce\Models\Product;



class FeaturedProduct extends Model
{
    use HasFactory;

    protected $primaryKey = "featured_products_id";

    protected $fillable = [
        'featured_group_id',
        'products_id',
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.ecm_featured_products');
    }

   
    public function group()
    {
        return $this->belongsTo(FeaturedGroup::class,'featured_group_id','featured_group_id');
    }   


    public function product()
    {
        return $this->belongsTo(Product::class,'products_id','product_id');
    }


}
