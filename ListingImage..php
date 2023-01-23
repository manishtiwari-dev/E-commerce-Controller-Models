<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingImage extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'listing_id';

    protected $guarded = [
      'listing_id'
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.biz_listing_to_images');
    }


}
