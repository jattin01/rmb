<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'username',
        'user_type',
        'country_id',
        'name',
        'email',
        'password',
        'mobile_no',
        'role_id',
        'user_type',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['profile_icon'];

    public function getProfileIconAttribute()
    {
        $media =  $this->getMedia(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE)->first() ?-> getFullUrl();
        return $media;
    }

    public function setPasswordAttribute($password)
    {
        if (!is_null($password)) {
            $this->attributes['password'] = ($password);
        }
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_users');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole()
    {
        $user = auth()->user();
        $role = RoleUser::whereHas(
            'role'
        )->whereUserId($user->id)->with('role')->first();
          
        if ($role) {
            return $role;
        }
        return false;
    }
    public function associated_customers()
    {
        return $this -> hasMany(Customer::class, 'user_type_id', 'id');
    }
    public function customer_companies()
    {
        return $this->hasMany(Customer::class, 'user_id', 'id') -> with('group_company');
    }
    public function driver_company()
    {
        return $this->hasMany(Driver::class, 'user_id', 'id');
    }
    public function driver()
    {
        return $this->hasOne(Driver::class, 'user_id', 'id') -> with('transit_mixers');
    }
    public function user_group_companies()
    {
        return $this -> hasMany(UserGroupCompany::class);
    }

    public function access_rights()
    {
        return $this -> hasMany(UserAccessRight::class);
    }

    public function group()
    {
        return $this -> belongsTo(Group::class);
    }
}
