<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use App\Helpers\ImageCollectionHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Group extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'code',
        'name',
        'status'
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

    public function getImageUrlAttribute()
    {
        $media =  $this->getMedia(ImageCollectionHelper::GROUP_IMAGE_COLLECTION)->first() ?-> getFullUrl();
        return $media;
    }
}
