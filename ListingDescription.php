<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingDescription extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'listing_description_id';

    protected $fillable = [
        'listing_id',
        'languages_id',
        'listing_name',
        'listing_description',
    ];

    public $timestamps = false;

    public function getTable(){
        return config('dbtable.biz_listing_description');
    }

    public function seo(){
        return $this->hasOne(SeoMeta::class, 'reference_id','listing_description_id');
    }


}
