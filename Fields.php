<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fields extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'fields_id';

    protected $fillable = [
        'fieldsgroup_id',
        'field_name',
        'field_label',
        'field_type',
        'field_placeholder',
        'field_options',
        'field_value',
        'field_required',
        'sort_order',
    ];

    public function getTable(){
        return config('dbtable.ecm_fields');
    }
    

}
