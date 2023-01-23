<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Models\FeaturedProduct;


class FeaturedGroup extends Model
{
    use HasFactory;

    protected $primaryKey = "featured_group_id";

    protected $fillable = [
        'group_name',
        'group_title',
        'sort_order',
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.ecm_featured_group');
    }

   
   public function feature_product()
   {

    return $this->hasMany(FeaturedProduct::class,'featured_group_id');
   }


}
