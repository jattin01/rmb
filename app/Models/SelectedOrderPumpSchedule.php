<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;



class SelectedOrderPumpSchedule extends Model
{
    use HasFactory;

   protected $guarded = [];
   
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
     protected static function booted()
    {
        static::updating(function ($schedule) {
            // Check if insp_start is changing
            if ($schedule->isDirty('insp_start')) {
                $old = $schedule->getOriginal('insp_start');
                $new = $schedule->insp_start;

                Log::info("insp_start is changing for id {$schedule->id}", [
                    'old' => $old,
                    'new' => $new,
                    'time' => now()
                ]);
            }

            // Optional: Track other datetime fields
            $datetimeFields = [
                'travel_start', 'travel_end', 'qc_start', 'qc_end',
                'insp_end', 'install_start', 'install_end'
            ];

            foreach ($datetimeFields as $field) {
                if ($schedule->isDirty($field)) {
                    Log::info("$field is changing for order {$schedule->order_no}", [
                        'old' => $schedule->getOriginal($field),
                        'new' => $schedule->$field,
                        'time' => now()
                    ]);
                }
            }
        });
    }
   
}
