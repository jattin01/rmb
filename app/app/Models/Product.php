<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Helpers\ConstantHelper;

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = ['group_company_id', 'product_type_id', 'code', 'name', 'density', 'usage' ,'batching_creation_time','temperature_creation_time','status'];
    protected $appends = ['image'];


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

    public function getImageAttribute()
    {

        if ($this->getMedia('image')->isEmpty()) {
            return [];
        } else {
            $media =  $this->getMedia('image')->all();
            $files = [];
            foreach ($media as $file) {
                $file->full_url = $file->getFullUrl();
                $files[] = $file;
            }
            return $files;
        }
    }

    public function product_type()
    {
        return $this -> belongsTo(ProductType::class) -> select('id', 'type', 'batching_creation_time','temperature_creation_time','description');
    }

    public function productContents(){
        return $this->hasMany(ProductContent::class);
    }
    public function group_company()
    {
        return $this->belongsTo(GroupCompany::class, 'group_company_id', 'id');
    }

    public function structuralReferences()
{
    return $this->hasMany(ProductStructuralReference::class, 'product_id','id');
}
}
