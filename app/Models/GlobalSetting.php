<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'batching_quality_inspection',
        'mixture_chute_cleaning',
        'site_quality_inspection',
        'chute_cleaning_site',
        'transite_mixture_cleaning',
        'company_location_id',
        'group_company_id'
    ];


    public function company_location()
    {
        return $this -> belongsTo(CompanyLocation::class, 'company_location_id', 'id');
    }

    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class, 'group_company_id', 'id');
    }

    }

