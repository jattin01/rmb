<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectedOrderSchedule extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $hidden = ['deleted_at'];

    public static function boot()
    {
        parent::boot();
        if (\Auth::check()) {

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
    public function mixer()
    {
        return $this->belongsTo(TransitMixer::class, 'transit_mixer', 'truck_name');
    }
    public function order()
    {
        return $this->belongsTo(SelectedOrder::class, 'order_id');
    }
    public function pump()
    {
        return $this->hasOne(
            SelectedOrderPumpSchedule::class,
            'order_id',
            'order_id'
        )->where('pouring_start', $this->pouring_start);
    }
}
