<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProjectSite extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cust_project_id','name', 'address', 'latitude', 'longitude', 'is_default', 'company_location_id'
    ];
   
    protected $hidden = ['deleted_at'];
    protected $appends = ['service_group_company_id'];

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

    public function project()
    {
        return $this -> belongsTo(CustomerProject::class, 'cust_project_id', 'id');
    }

    public function service_company_location()
    {
        return $this -> hasOne(CompanyLocation::class, 'id', 'company_location_id');
    }
    public function getServiceGroupCompanyIdAttribute()
    {
        $this -> makeHidden('service_company_location');
        return $this -> service_company_location?-> group_company ?-> id;
    }
}
