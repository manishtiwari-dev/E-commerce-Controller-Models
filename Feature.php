<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Models\FeaturedProduct;


class Feature extends Model
{
    use HasFactory;

    protected $primaryKey = "feature_id";

    protected $fillable = [
        'feature_title',
        'feature_subtitle',
        'feature_icon',
        ''
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.web_feature');
    }

   
   

}
