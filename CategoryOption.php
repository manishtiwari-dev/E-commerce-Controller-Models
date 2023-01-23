<?php


namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class CategoryOption extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    public $timestamps = false;
    protected $guarded = ['id'];

   
    public function getTable()
    {
        return config('dbtable.ecm_categories_to_options');
    }
    public function user(){
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
