<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalSetup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_company_id',
        'location_id',
        'transaction_type',
        'approval_level_users',
        'status'
    ];

    protected $table = 'approval_setup';
   
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

    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class, 'group_company_id', 'id');
    }

    public function location()
    {
        return $this -> belongsTo(CompanyLocation::class, 'location_id', 'id');
    }

    public function levels()
    {
        return $this -> hasMany(ApprovalSetupLevel::class, 'approval_setup_id', 'id');
    }
}
