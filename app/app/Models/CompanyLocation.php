<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['group_company_id','location', 'site_name', 'contact_person', 'email', 'phone', 'country', 'province', 'status'];
   
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

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable', 'model_type', 'model_id') -> select('id', 'model_id', 'model_type', 'latitude', 'longitude', 'address');
    }
    public function batchingPlants(){
        return $this->hasMany(BatchingPlant::class, 'company_location_id');
    }
    public function group_company(){
        return $this->belongsTo(GroupCompany::class, 'group_company_id', 'id');
    }
}
