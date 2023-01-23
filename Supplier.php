<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Country;


class Supplier extends Model
{
    use HasFactory;

    protected $primaryKey = 'supplier_id';
    
    protected $fillable = [
        'supplier_name',
        'supplier_address',
        'supplier_city',
        'supplier_state',
        'supplier_country',
        'status',
        
       
    ];

    public function getTable(){
        return config('dbtable.ecm_supplier');
    }


    public function country()
    {

        return $this->belongsTo(Country::class,'supplier_country', 'countries_id');
    }


}
