<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatRoom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'entity_id',
        'entity_type',
        'status'
    ];

    protected $hidden = ['deleted_at'];
    protected $appends = ['firebase_doc_id'];

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

    public function project()
    {
        return $this -> belongsTo(CustomerProject::class, 'project_id', 'id');
    }

    public function entity()
    {
        $type = $this -> getAttribute('entity_type');
        if ($type === ConstantHelper::USER_TYPE_CUST) {
            return $this -> belongsTo(Customer::class, 'entity_id', 'id');
        } else {
            return $this -> belongsTo(Driver::class, 'entity_id', 'id');
        // } else {
        //     return null;
        }
    }



    public function firebaseDocumentId()
    {
        $project_id = $this -> getAttribute('project_id');
        $entity_id = $this -> getAttribute('entity_id');
        $entity_type = $this -> getAttribute('entity_type');
        return $project_id . "_" . $entity_type . "_" . $entity_id;
    }

    public function getFirebaseDocIdAttribute (){
return $this->firebaseDocumentId();

    }
}
