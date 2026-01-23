<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class OrderApproval extends Model implements HasMedia
{
    use HasFactory, SoftDeletes,InteractsWithMedia;

    protected $fillable = [
        'order_id',
        'approved_by',
        'approval_status',
        'reset',
        'remarks'
    ];
   
    protected $hidden = ['deleted_at'];

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

    public function user()
    {
        return $this -> belongsTo(User::class, 'approved_by', 'id');
    }

    public function documents()
    {
        if ($this->getMedia('order_approval_docs')->isEmpty()) {
            return [];
        } else {
            $media =  $this->getMedia('order_approval_docs')->all();
            $files = [];
            foreach ($media as $file) {
                array_push($files, [
                    'file_url' => $file -> getFullUrl(),
                    'file_name' => $file -> file_name,
                    'file_mime_type' => $file -> mime_type
                ]);
            }
            return $files;
        }
    }
}
