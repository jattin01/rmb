<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerTeamMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'username',
        'user_id',
        'customer_id',
        'name',
        'email',
        'phone_no',
        'is_admin',
        'is_contact_person'
    ];
   
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

    public function customer()
    {
        return $this -> belongsTo(Customer::class);
    }

    public function access_rights()
    {
        return $this -> hasMany(CustomerTeamMemberAccessRight::class);
    }
    public function access_rights_mobile()
    {
        return $this -> hasMany(CustomerTeamMemberAccessRight::class) -> where('status', ConstantHelper::ACTIVE) -> select('id', 'customer_team_member_id', 'customer_project_id', 'order_view', 'order_create', 'order_edit', 'chat');
    }
    public function user()
    {
        return $this -> belongsTo(User::class);
    }
}
