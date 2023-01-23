<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldsGroup extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'fieldsgroup_id';

    protected $fillable = [
        'fieldsgroup_name',
        'fieldsgroup_description',
        'sort_order',
        'status',
    ];

    public function getTable(){
        return config('dbtable.ecm_fieldsgroup');
    }

    public function fields(){
        return $this->hasMany(Fields::class,'fieldsgroup_id','fieldsgroup_id');
    }


}
