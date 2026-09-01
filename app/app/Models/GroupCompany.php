<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class GroupCompany extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'group_companies';
    protected $fillable = [];

    protected $hidden = ['deleted_at'];
    protected $appends = ['banner_images', 'icon_url'];

    public function getBannerImagesAttribute()
    {

        if ($this->getMedia('banner_images')->isEmpty()) {
            return [];
        } else {
            $media =  $this->getMedia('banner_images')->all();
            $files = [];
            foreach ($media as $file) {
                $file->full_url = $file->getFullUrl();
                $files[] = $file;
            }
            return $files;
        }
    }

    protected function getIconUrlAttribute()
    {
        $media =  $this->getMedia('icon')->first() ?-> getFullUrl();
        return $media;
    }

    public function group()
    {
        return $this -> belongsTo(Group::class, 'group_id', 'id');
    }

    public function company_locations()
    {
        return $this -> hasMany(CompanyLocation::class);
    }
    
}