<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;

class OrderPumpSchedule extends Model

{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'order_pump_schedules';
}
