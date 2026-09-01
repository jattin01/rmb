<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;

    protected $fillable = ['group_company_id','type', 'batching_creation_time','temperature_creation_time','description', 'status'];

    public function product(){
        return $this->hasOne(Product::class, 'product_type_id');
    }

    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class, 'group_company_id', 'id');
    }
}
