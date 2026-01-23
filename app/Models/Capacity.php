<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capacity extends Model
{
    use HasFactory;

    protected $table = "capacities";

    protected $fillable = [
            'value',
            'uom',
            'status'
    ];


}
