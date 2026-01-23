<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Order extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'published_by',
        'group_company_id',
        'order_no',
        'customer',
        'project',
        'site',
        'site_id',
        'mix_code',
        'quantity',
        'delivery_date',
        'interval',
        'interval_deviation',
        'pouring_time',
        'pump',
        'pump_qty',
        'location',
        'company_location_id',
        'travel_to_site',
        'return_to_plant',
        'deviation',
        'start_time',
        'end_time',
        'deviation_reason',
        'order_status',
        'has_customer_confirmed',
        'approval_status',
        'remarks',
        'customer_confirm_remarks',
        'customer_confirmed_on',
        'customer_confirmation_by',
        'structural_reference_id',
        'customer_id',
        'project_id',
        'cust_product_id',
        'is_technician_required',
        'is_temp_required',
        'in_cart'
    ];

    protected $hidden = ['deleted_at'];

    protected $appends = ['structural_reference'];

    // public function setDeliveryDateAttribute($value)
    // {
    //     $userTimezone = auth() -> user() -> timezone;
    //     $this->attributes['delivery_date'] = Carbon::parse($value, $userTimezone) -> setTimezone(config('app.timezone'));
    // }

    // public function getDeliveryDateAttribute($value)
    // {
    //     $userTimezone = auth() -> user() -> timezone;
    //     return Carbon::parse($value) -> setTimezone($userTimezone);
    // }

    public function structural_reference()
    {
        return $this -> belongsTo(StructuralReference::class);
    }

    public function customer_site()
    {
        return $this -> belongsTo(CustomerProjectSite::class, 'site_id', 'id');
    }

    public function getStructuralReferenceAttribute()
    {
        return $this -> structural_reference() ?-> first() ?-> name ?? "";
    }

    public function customer_product()
    {
        return $this -> belongsTo(CustomerProduct::class, 'cust_product_id', 'id');
    }

    public function get_order_confirmation_documents()
    {

        if ($this->getMedia(ConstantHelper::CUST_CONFIRMATION_DOC_COLLECTION_NAME)->isEmpty()) {
            return [];
        } else {
            $media =  $this->getMedia(ConstantHelper::CUST_CONFIRMATION_DOC_COLLECTION_NAME)->all();
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

    public function scopeByCompanyScheduleDate($query, int $group_company_id, string $shift_start, string $shift_end)
    {
        return $query -> where("group_company_id", $group_company_id) -> where('in_cart', 0) -> whereBetween("delivery_date",  [$shift_start, $shift_end]);
    }

    public function schedule()
    {
        return $this->hasMany(OrderSchedule::class, 'order_id', 'id')
            ->select('order_id', 'group_company_id', 'schedule_date', 'order_no', 'location', 'trip', 'mix_code', 'batching_plant', 'batching_plant_id', 'transit_mixer', 'transit_mixer_id',
            'pump','pump_trip', 'pump_id', 'batching_qty',
            'loading_time AS planned_loading_time', 'loading_start AS planned_loading_start', 'loading_end AS planned_loading_end',
            'qc_time AS planned_qc_time', 'qc_start AS planned_qc_start', 'qc_end AS planned_qc_end',
            'travel_time AS planned_travel_time', 'travel_start AS planned_travel_start', 'travel_end AS planned_travel_end',
            'insp_time AS planned_insp_time', 'insp_start AS planned_insp_start', 'insp_end AS planned_insp_end',
            'pouring_time AS planned_pouring_time', 'pouring_start AS planned_pouring_start', 'pouring_end AS planned_pouring_end',
            'cleaning_time AS planned_cleaning_time', 'cleaning_start AS planned_cleaning_start', 'cleaning_end AS planned_cleaning_end',
            'return_time AS planned_return_time', 'return_start AS planned_return_start', 'return_end AS planned_return_end',
            'delivery_start AS planned_delivery_start', 'deviation AS planned_deviation', 'deviation_reason')
            ->orderBy('loading_start');
    }

    public function pump_schedule()
    {
        return $this -> hasMany(OrderPumpSchedule::class, 'order_id', 'id') -> select('order_id', 'group_company_id',
        'schedule_date', 'order_no', 'location', 'trip', 'mix_code', 'pump', 'pump_id', 'batching_qty',
        'qc_time AS planned_qc_time', 'qc_start AS planned_qc_start', 'qc_end AS planned_qc_end',
        'travel_time AS planned_travel_time', 'travel_start AS planned_travel_start', 'travel_end AS planned_travel_end',
        'insp_time AS planned_insp_time', 'insp_start AS planned_insp_start', 'insp_end AS planned_insp_end',
        'pouring_time AS planned_pouring_time', 'pouring_start AS planned_pouring_start', 'pouring_end AS planned_pouring_end',
        'cleaning_time AS planned_cleaning_time', 'cleaning_start AS planned_cleaning_start', 'cleaning_end AS planned_cleaning_end',
        'return_time AS planned_return_time', 'return_start AS planned_return_start', 'return_end AS planned_return_end',
        'delivery_start AS planned_delivery_start') -> orderBy("qc_start");
    }

    public function order_temp_control()
    {
        return $this -> hasMany(OrderTempControl::class)-> select('temp', 'quantity AS qty', 'order_id') -> where('status', ConstantHelper::ACTIVE);;
    }

    public function order_temp_control_display()
    {
        $tempControl = $this -> order_temp_control() -> get();
        $formattedString = "";
        foreach ($tempControl as $key => $tempCtrl) {
            $formattedString .= $tempCtrl -> qty . " CUM / " . $tempCtrl -> temp . "ºC" . ($key === count($tempControl) - 1 ? "" : ", ");
        }
        return $formattedString == "" ? ConstantHelper::NOT_REQUIRED_LABEL : $formattedString;
    }

    public function order_cube_moulds()
    {
        return $this -> hasMany(OrderCubeMould::class) -> select('mould_size', 'quantity AS qty', 'order_id') -> where('status', ConstantHelper::ACTIVE);
    }

    public function order_cube_mould_display()
    {
        $cubeMoulds = $this -> order_cube_moulds() -> get();
        $formattedString = "";
        foreach ($cubeMoulds as $key => $cubeMould) {
            $formattedString .= $cubeMould -> qty . ($key === count($cubeMoulds) - 1 ? "" : ", ");
        }
        return $formattedString == "" ? ConstantHelper::NOT_REQUIRED_LABEL : $formattedString;
    }

    public function order_pumps()
    {
        return $this -> hasMany(OrderPump::class)-> select('type', 'quantity AS qty', 'capacity AS pump_size', 'pipe_size', 'order_id') -> where('status', ConstantHelper::ACTIVE);
    }

    public function order_pumps_display()
    {
        $pumps = $this -> order_pumps() -> get();
        $formattedString = "";
        foreach ($pumps as $key => $pump) {
            $formattedString .= $pump -> type . " - " . $pump -> pump_size . " X " . $pump -> qty . ($pump -> pipe_size ? ( " (" . $pump -> pipe_size . " Pipes)") : "") . ($key === count($pumps) - 1 ? "" : ", ");
        }
        return $formattedString == "" ? ConstantHelper::NOT_REQUIRED_LABEL : $formattedString;
    }

    public function project()
    {
        return $this -> belongsTo(CustomerProject::class, 'project_id', 'id');
    }

    public function group_company()
    {
        return $this -> belongsTo(GroupCompany::class, 'group_company_id', 'id');
    }
    public function company_location()
    {
        return $this -> belongsTo(CompanyLocation::class, 'company_location_id', 'id');
    }
    public function customer_company()
    {
        return $this -> belongsTo(Customer::class, 'customer_id', 'id');
    }
    public function group_company_name()
    {
        $value = $this -> group_company() -> first() ?-> comp_name;
        $this -> makeHidden('group_company');
        return $value;
    }
    public function company_location_name()
    {
        $value = $this -> company_location() -> first() ?-> location;
        $this -> makeHidden('company_location');
        return $value;
    }
    public function customer_company_name()
    {
        $value = $this -> customer_company() -> first() ?-> name;
        $this -> makeHidden('customer_company');
        return $value;
    }

    public function approvals()
    {
        return $this -> hasMany(OrderApproval::class);
    }

    public function customer_approval_user()
    {
        return $this -> belongsTo(User::class, 'customer_confirmation_by', 'id');
    }

    public function mobile_user_access_right()
    {
        $relation = $this -> hasOne(CustomerTeamMemberAccessRight::class, 'customer_project_id','project_id') -> where('customer_team_member_id', request() -> team_member_id) -> where('status', ConstantHelper::ACTIVE) -> select('id', 'customer_team_member_id', 'customer_project_id', 'order_view', 'order_create', 'order_edit', 'order_cancel', 'chat');
        if (request() -> is_user_admin) {
            return $relation -> withDefault(ConstantHelper::ADMIN_DEFAULT_ACCESS_RIGHT_OBJECT);
        } else {
            return $relation;
        }
    }

}
