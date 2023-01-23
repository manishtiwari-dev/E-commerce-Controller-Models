<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecommerce\Models\CategoryDescription;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\CategoryOption;


class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = "categories_id";

    // protected $fillable = [
    //     'categories_slug',
    //     'categories_image',
    //     'categories_icon',
    //     'parent_id',
    //     'is_featured',
    //     'sort_order',
    //     'status'
    // ];
    protected $guarded = ['categories_id'];

    public function getTable(){
        return config('dbtable.ecm_categories');
    }
    
    public function categorydescription(){
        return $this->hasMany(CategoryDescription::class, 'categories_id', 'categories_id');
    }

    public function options(){
        return $this->belongsToMany(CategoryOption::class,config('dbtable.ecm_categories_to_options'),'categories_id','products_options_id');
    }
   
    public function brands(){
        return $this->belongsToMany(Brand::class,config('dbtable.ecm_categories_to_brands'),'categories_id','brand_id');
    }
}
