<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;
use App\Helpers\ConstantHelper;

class CustomerProject extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'customer_id',
        'code',
        'name',
        'contractor_name',
        'type',
        'start_date',
        'end_date',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
   
    protected $hidden = ['deleted_at'];
    protected $appends = ['progress', 'total_cum', 'ordered_cum', 'image', 'image_url', 'default_group_company_id'];

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

    public function getProgressAttribute()
    {
        $all_products = $this -> products() -> get();
        $ordered = 0;
        $total = 0;
        foreach ($all_products as $product) {
            $ordered += $product -> ordered_quantity;
            $total += $product -> total_quantity;
        }
        if ($total > 0) {
            return min(round(($ordered/$total) * 100), 100);
        } else {
            return 0;
        }
    }

    public function group_company_name()
    {
        return $this -> customer() -> first() -> group_company() -> first() -> comp_name ?? "";
    }

    public function customer()
    {
        return $this -> belongsTo(Customer::class);
    }

    public function orders()
    {
        return $this -> hasMany(Order::class, 'project_id');
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable', 'model_type', 'model_id') -> select('id', 'model_id', 'model_type', 'latitude', 'longitude', 'address');
    }

    public function products()
    {
        return $this -> hasMany(CustomerProduct::class, 'project_id', 'id') -> select('id', 'project_id', 'product_id', 'total_quantity', 'ordered_quantity', 'status') -> orderByRaw('ordered_quantity >= total_quantity') -> orderBy('id', 'asc');
    }
    public function sites()
    {
        return $this -> hasMany(CustomerProjectSite::class, 'cust_project_id');
    }
    public function getTotalCumAttribute()
    {
        $all_products = $this -> products() -> get();
        $value = 0;
        if($all_products){
            foreach ($all_products as $product) {
                $value += $product -> total_quantity;
            }
        }
        return $value;
    }
    public function getOrderedCumAttribute()
    {
        $all_products = $this -> products() -> get();
        $value = 0;
        if( $all_products){
            foreach ($all_products as $product) {
                $value += $product -> ordered_quantity;
            }
        }
        return $value;
    }

    protected function getImageUrlAttribute()
    {
        $media =  $this->getMedia(ConstantHelper::CUST_PROJECT_IMG_COLLECTION)->first() ?-> getFullUrl();
        $this -> makeHidden('media');
        return $media;
    }

    public function defaultSite()
    {
        return $this -> hasOne(CustomerProjectSite::class, 'cust_project_id', 'id') -> where('is_default', 1);
    }

    public function getDefaultGroupCompanyIdAttribute()
    {
        $this -> makeHidden('defaultSite');
        return $this -> defaultSite ?-> service_group_company_id;
    }

    public function mobile_user_access_right()
    {
        $relation = $this -> hasOne(CustomerTeamMemberAccessRight::class, 'customer_project_id','id') -> where('customer_team_member_id', request() -> team_member_id) -> where('status', ConstantHelper::ACTIVE) -> select('id', 'customer_team_member_id', 'customer_project_id', 'order_view', 'order_create', 'order_edit', 'order_cancel', 'chat');
        if (request() -> is_user_admin) {
            return $relation -> withDefault(ConstantHelper::ADMIN_DEFAULT_ACCESS_RIGHT_OBJECT);
        } else {
            return $relation;
        }    
    }
}
