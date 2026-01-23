<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchingPlantAvailability extends Model
{
    use HasFactory;
    protected $table = "batching_plant_availability";

    protected $fillable = [
        'group_company_id',
        'location',
        'plant_name',
        'plant_capacity',
        'free_from',
        'free_upto',
        'user_id',
        'reason'
    ];
}
