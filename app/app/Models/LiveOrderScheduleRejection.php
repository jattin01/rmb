<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class LiveOrderScheduleRejection extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'trip_id',
        'reject_reason_id',
        'activity',
        'remarks',
        'quantity'
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

            // static::deleting(function ($model) {
            //     $user = \Auth::user();
            //     $model->deleted_by = $user->id;
            // });
        }   
    }
}
