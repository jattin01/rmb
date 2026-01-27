<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSchedule extends Model
{
    use HasFactory;

    public function order()
    {
        return $this -> belongsTo(Order::class);
    }
    public function transit_mixer_detail()
    {
        return $this -> hasOne(TransitMixer::class, 'id', 'transit_mixer_id');
    }
    public function batching_plant_detail()
    {
        return $this -> hasOne(BatchingPlant::class, 'id', 'batching_plant_id');
    }
}
