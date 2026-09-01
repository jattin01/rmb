<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_company_id',
        'code',
        'employee_code',
        'name',
        'email_id',
        'username',
        'user_role',
        'country_id',
        'phone',
        'license_no',
        'license_expiry',
        'status'
    ];

    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class) -> select('id', 'comp_name', 'comp_code');
    }

    public function transit_mixers()
    {
        return $this -> hasMany(DriverTransitMixer::class, 'driver_id', 'id') -> with('transit_mixer');
    }
    public function user()
    {
        return $this -> belongsTo(User::class);
    }
}
