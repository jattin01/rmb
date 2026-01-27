<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchingPlant extends Model
{
    use HasFactory;

    protected $fillable = ['group_company_id', 'company_location_id','plant_name', 'long_name', 'capacity','status'];

    protected $hidden = ['deleted_at'];

    public static function boot()
    {
        parent::boot();
        if(\Auth::check()) {

            static::creating(function ($model) {
                $user = \Auth::user();
                $model->created_by = $user->id;
            });

            static::updating(function ($model) {
                $user = \Auth::user();
                $model->updated_by = $user->id;
            });

            static::deleting(function ($model) {
                $user = \Auth::user();
                $model->deleted_by = $user->id;
            });
        }
    }

    public function company_location()
    {
        return $this -> belongsTo(CompanyLocation::class, 'company_location_id', 'id');
    }

    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class, 'group_company_id', 'id');
    }
    public function Batching_plant_occupancy()
    {
        return $this -> hasOne(BatchingPlantOccupancy::class, 'batching_plant_id', 'id');
    }



}
