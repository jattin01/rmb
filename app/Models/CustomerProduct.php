<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProduct extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id','project_id', 'product_id', 'total_quantity', 'ordered_quantity'];

    protected $appends = ['product_name', 'product_code', 'mix_code', 'remaining_quantity'];

    
    public function product()
    {
        return $this -> belongsTo(Product::class) -> select('id' ,'product_type_id', 'code', 'name', 'usage', 'density_per_cum', 'cement_per_cum', 'flyash_per_cum', 'water_per_cum', 'sand_per_cum', 'aggregate_10mm_per_cum', 'aggregate_20mm_per_cum', 'admixture_per_cum', 'batching_creation_time', 'temperature_creation_time') -> with('product_type');
    }

    public function getProductNameAttribute()
    {
        return $this -> product() -> first() ?-> name;
    }
    public function getProductCodeAttribute()
    {
        return $this -> product() -> first() ?-> code;
    }
    public function getMixCodeAttribute()
    {
        return $this -> product() -> first() ?-> product_type ?-> type;
    }

    public function getRemainingQuantityAttribute()
    {
        if (isset($this -> attributes['total_quantity']) && isset($this -> attributes['ordered_quantity'])) {
            $rem_qty =  ($this->attributes['total_quantity'] - $this->attributes['ordered_quantity']);
            if ($rem_qty < 0) {
                return 0;
            } else {
                return $rem_qty;
            }
        } else {
            return 0;
        }
        
    }

    public function customer_project()
    {
        return $this -> belongsTo(CustomerProject::class, 'project_id', 'id');
    }

    public function order()
    {
        return $this -> hasOne(Order::class, 'cust_product_id', 'id') -> select('id', 'is_technician_required', 'structural_reference_id', 'order_no', 'cust_product_id', 'quantity', 'delivery_date', 'interval', 'site_id', 'remarks') -> where([['status', ConstantHelper::ACTIVE], ['in_cart', 1]]);
    }

    public function is_ordered()
    {
        $order = $this -> order() -> first();
        if (isset($order)) {
            return true;
        } else {
            return false;
        }
    }
}
