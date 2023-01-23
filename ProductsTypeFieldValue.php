<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsTypeFieldValue extends Model
{
    use HasFactory;
    
    // protected $primaryKey = 'product_id';
    

    protected $fillable = [
       'fieldsgroup_id',
       'fields_id',
       'field_name',
       'field_value',
       'sort_order',
    ];
    public $timestamps=false;

    public function getTable(){
        return config('dbtable.ecm_products_type_field_value');
    }


}
