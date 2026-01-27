<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StructuralReference extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_company_id',
        'name',
        'pouring_time_wo_pump',
        'pouring_time_w_pump',
        'status'
    ];

    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class) -> select('id', 'comp_name', 'comp_code');
    }
    }

