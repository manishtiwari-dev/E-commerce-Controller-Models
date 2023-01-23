<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingFeature extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'listing_feature_id';

    protected $guarded = [
      'listing_feature_id'
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.biz_listing_feature');
    }


}
