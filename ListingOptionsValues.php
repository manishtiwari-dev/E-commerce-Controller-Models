<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Models\ListingOptions;

class ListingOptionsValues extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'listing_options_values_id';
    

    protected $fillable = [
       'listing_options_id',
       'listing_options_values_name',
    ];
    public $timestamps=false;

    public function getTable(){
        return config('dbtable.biz_listing_options_values');
    }

    public function product_options(){
        return $this->belongsTo(ListingOptions::class, 'listing_options_id', 'listing_options_id');
    }


}
