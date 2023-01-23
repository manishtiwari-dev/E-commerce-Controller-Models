<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCategoryDescription extends Model
{
    use HasFactory;

    protected $primaryKey = "categories_description_id";

    protected $fillable = [
        'categories_id',
        'languages_id',
        'categories_name',
        'categories_description',
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.biz_categories_description');
    }

    public function category(){
        return $this->belongsTo(BusinessCategory::class,'categories_id','categories_id');
    }

    public function seo(){
        return $this->hasOne(SeoMeta::class, 'reference_id','categories_description_id');
    }



}
