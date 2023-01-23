<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFeature extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'product_feature_id';

    protected $guarded = [
      'product_feature_id'
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.ecm_product_feature');
    }


}
