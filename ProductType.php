<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'product_type_id';

    protected $fillable = [
        'product_type_name',
        'product_type_key',
        'status',
    ];

    public function getTable(){
        return config('dbtable.ecm_product_type');
    }

    public function fields_group(){
        return $this->belongsToMany(FieldsGroup::class,config('dbtable.ecm_product_type_to_fieldsgroup'),'product_type_id','fieldsgroup_id');
    }

    public static function product_type_with_fields($product_type_id=''){

        if(!empty($product_type_id)){

            $product_type_list = Self::find($product_type_id);
            if(!empty($product_type_list->fields_group)){
                $product_type_list->fields_group = $product_type_list->fields_group->map(function($fields_group){
                    $fields_group->fields = $fields_group->fields;
                    return $fields_group;
                });
            }else
                $product_type_list->fields_group = [];
           
            return $product_type_list;
        
        }else{
            $product_type_list = Self::all()->map(function($product_type){
                if(!empty($product_type->fields_group)){
                    $product_type->fields_group = $product_type->fields_group->map(function($fields_group){
                        $fields_group->fields = $fields_group->fields;
                        return $fields_group;
                    });
                }
                return $product_type;
            });

            return $product_type_list;
        }
    }



}
