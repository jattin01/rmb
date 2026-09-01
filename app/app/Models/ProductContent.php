<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductContent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['product_id', 'content', 'quality', 'status'];
   
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

    public function product(){
        return $this->belongsTo(Product::class);
    }
}
