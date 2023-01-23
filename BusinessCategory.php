<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecommerce\Models\CategoryDescription;

class BusinessCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = "categories_id";

    protected $fillable = [
        'categories_slug',
        'categories_image',
        'categories_icon',
        'parent_id',
        'is_featured',
        'sort_order',
        'status'
    ];

    public function getTable(){
        return config('dbtable.biz_categories');
    }
    
    public function categorydescription(){
        return $this->hasMany(BusinessCategoryDescription::class, 'categories_id', 'categories_id');
    }

     public function options(){
         return $this->belongsToMany(ListingOptions::class,config('dbtable.ecm_categories_to_options'),'categories_id','products_options_id');
     }
     
}
