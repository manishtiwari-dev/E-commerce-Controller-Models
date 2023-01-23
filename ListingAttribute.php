<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Models\ListingOptions;
use Modules\Ecommerce\Models\ListingOptionsValues;

class ListingAttribute extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'listing_attributes_id';

    protected $fillable = [
        'listing_id',
        'options_id',
        'options_values_id',
        'options_values_price',
        'is_default',
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.biz_listing_attributes');
    }

    public function productOptions(){
        return $this->belongsTo(ListingOptions::class, 'options_id', 'listing_options_id');
    }

    public function productOptionsValue(){
        return $this->belongsTo(ListingOptionsValues::class, 'options_values_id', 'listing_options_values_id');
    }

}
