<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class SeoMeta extends Model
{
    use HasFactory;

    protected $primaryKey = 'seometa_id';
    
    protected $fillable = [
        'page_type',
        'reference_id',
        'page_key',
        'language_id',
        'seometa_title',
        'seometa_desc', 
    ];

    public function getTable(){
        return config('dbtable.web_seometa');
    }


}
