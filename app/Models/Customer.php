<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Customer extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'group_id',
        'group_company_id',
        'code',
        'name',
        'type',
        'contact_person',
        'email_id',
        'country_code_id',
        'mobile_no',
        'status'
    ];
   
    protected $hidden = ['deleted_at'];

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

    public function projects()
    {
        return $this -> hasMany(CustomerProject::class);
    }

    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class) -> select('id', 'comp_name', 'comp_code');
    }

    public function addresses() {
        return $this->morphMany(Address::class, 'model');
    }

    public function address() {
        return $this->morphOne(Address::class, 'model');
    }

    public function group_companies()
    {
        return $this -> hasMany(CustomerGroupCompany::class);
    }

    public function contact_person_details()
    {
        return $this -> hasOne(CustomerTeamMember::class) -> where('is_contact_person', 1);
    }
}
