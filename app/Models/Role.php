<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;


    public static function boot()
    {
        parent::boot();
        if(auth()->check()) {

            static::creating(function ($model) {
                $user = auth()->user();
                $model->created_by = $user->id;
            });

            static::updating(function ($model) {
                $user = auth()->user();
                $model->updated_by = $user->id;
            });

            static::deleting(function ($model) {
                $user = auth()->user();
                $model->deleted_by = $user->id;
            });
        }
    }


    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function childs()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_users');
    }
}
