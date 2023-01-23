<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDescription extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'products_description_id';

    protected $fillable = [
        'products_id',
        'languages_id',
        'products_name',
        'products_description',
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.ecm_products_description');
    }

    public function seo(){
        return $this->hasOne(SeoMeta::class, 'reference_id','products_description_id')->where('page_type',2);
    }


}
