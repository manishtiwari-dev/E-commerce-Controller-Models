<?php


namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class CategoryBrand extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    public $timestamps = false;
    protected $guarded = ['id'];

   
    public function getTable()
    {
        return config('dbtable.ecm_categories_to_brands');
    }
    public function user(){
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
