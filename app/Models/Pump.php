<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pump extends Model
{
    use HasFactory;

    protected $fillable = [
            'group_company_id',
            'pump_name',
            'type',
            'description',
            'operator_id',
            'pump_capacity',
            'status'
    ];

    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class, 'group_company_id', 'id');
    }
    public function operator()
    {
        return $this -> belongsTo(Driver::class, 'operator_id', 'id');
    }
    public function pump_occupancy()
    {
        return $this -> hasOne(PumpOccupancy::class);
    }
}
