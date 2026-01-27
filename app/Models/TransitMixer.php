<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class TransitMixer extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'group_id',
        'group_company_id',
        'truck_name',
        'description',
        'registration_no',
        'registration_expiry',
        'truck_capacity',
        'loading_time'
    ];
   
    protected $hidden = ['deleted_at'];

    protected $appends = ['image_url'];

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

    protected function getImageUrlAttribute()
    {
        $media =  $this->getMedia(ConstantHelper::TRANSIT_MIXER_IMG_COLLECTION)->first() ?-> getFullUrl();
        $this -> makeHidden('media');
        return $media;
    }

    public function driverDetail()
    {
        return $this -> belongsTo(Driver::class, 'driver_id', 'id');
    }
    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class, 'group_company_id', 'id');
    }
    public function group_companies()
    {
        return $this -> hasMany(TransitMixerCompany::class, 'transit_mixer_id', 'id');
    }
    public function drivers()
    {
        return $this -> hasMany(DriverTransitMixer::class);
    }
    public function occupancy()
    {
        return $this -> hasOne(TransitMixerOccupancy::class);
    }
}

