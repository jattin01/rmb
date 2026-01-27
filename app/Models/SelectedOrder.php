<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SelectedOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'og_order_id',
        'group_company_id',
        'order_no',
        'customer',
        'project',
        'site',
        'mix_code',
        'quantity',
        'delivered_quantity',
        'delivery_date',
        'interval',
        'pouring_time',
        'pump',
        'pump_qty',
        'location',
        'travel_to_site',
        'return_to_plant',
        'interval_deviation',
        'site_id',
        'order_id',
        'selected',
        'time',
        'priority',
        'flexibility',
        'is_temp_required',
        ];

    protected $appends = [
        // 'expected_start_time',
        // 'order_start_time',
        'batched_qty',
        'remaining_qty',
        'next_loading',
        'max_interval',
        'assigned_batching_plant'
    ];


    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            
            $qc_time = GlobalSetting::where('group_company_id', $model->group_company_id)->value('batching_quality_inspection') ?? ConstantHelper::QC_TIME;

            $insp_time = GlobalSetting::where('group_company_id', $model->group_company_id)->value('site_quality_inspection') ?? ConstantHelper::INSP_TIME;

            $loading_time = $model->customer_product ?->product ?->product_type ?->batching_creation_time ?? ConstantHelper::LOADING_TIME;

            $total_time = $loading_time + $qc_time + ((int) $model -> travel_to_site) + $insp_time;
         
            $time =  Carbon::parse($model -> delivery_date) ->copy()->subMinutes($total_time);

            $model->order_start_time = $time;
        });
    }

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

    // public function getExpectedStartTimeAttribute()
    // {
    //     return GeneralHelper::get_order_start_time($this -> group_company_id, $this -> order_no);
    // }

    public function structural_reference_details()
    {
        return $this -> hasOne(StructuralReference::class, 'id', 'structural_reference_id');
    }

    public function schedule()
    {
        return $this -> hasMany(SelectedOrderSchedule::class, 'order_id', 'id') 
            -> leftJoin("transit_mixers", function ($query) {
            $query -> on("transit_mixers.truck_name", "=", "selected_order_schedules.transit_mixer")
            ->whereColumn("transit_mixers.group_company_id", "=", "selected_order_schedules.group_company_id");
        }) ->leftJoin("batching_plants", function ($query) {
            $query->on("batching_plants.plant_name", "=", "selected_order_schedules.batching_plant")
                  ->whereColumn("batching_plants.group_company_id", "=", "selected_order_schedules.group_company_id");
        }) -> select("batching_plants.capacity", "batching_plants.avg_mixer_capacity", "transit_mixers.truck_capacity", "selected_order_schedules.schedule_date", "selected_order_schedules.order_no", "selected_order_schedules.order_id",
            "selected_order_schedules.location", "selected_order_schedules.trip", "selected_order_schedules.batching_qty", "selected_order_schedules.transit_mixer",
            "selected_order_schedules.batching_plant","selected_order_schedules.qc_time", "selected_order_schedules.qc_start", "selected_order_schedules.qc_end",
            "selected_order_schedules.loading_time", "selected_order_schedules.loading_start", "selected_order_schedules.loading_end",
            "selected_order_schedules.travel_time", "selected_order_schedules.travel_start", "selected_order_schedules.travel_end",
            "selected_order_schedules.insp_time", "selected_order_schedules.insp_start", "selected_order_schedules.insp_end",
            "selected_order_schedules.pouring_time", "selected_order_schedules.pouring_start", "selected_order_schedules.pouring_end",
            "selected_order_schedules.cleaning_time", "selected_order_schedules.cleaning_start", "selected_order_schedules.cleaning_end",
            "selected_order_schedules.return_time", "selected_order_schedules.return_start", "selected_order_schedules.return_end", "selected_order_schedules.deviation",
            "selected_order_schedules.id") -> orderBy("loading_start");
    }

    public function pump_schedule()
    {
        return $this -> hasMany(SelectedOrderPumpSchedule::class, 'order_id', 'id') -> orderBy("qc_start");;
    }

    public function getBatchedQtyAttribute()
    {
        return 0;
    }

    public function getAssignedBatchingPlantAttribute()
    {
        return null;
    }
    public function getRemainingQtyAttribute()
    {
        return $this -> quantity;
    }

    // public function sgetOrderStartTimeAttribute($model)
    // {

    //     $qc_time = GlobalSetting::where('group_company_id', $this->group_company_id)->value('batching_quality_inspection') ?? ConstantHelper::QC_TIME;

    //     $insp_time = GlobalSetting::where('group_company_id', $this->group_company_id)->value('site_quality_inspection') ?? ConstantHelper::INSP_TIME;

    //     $loading_time = $this->order->customer_product ?->product ?->product_type ?->batching_creation_time ?? ConstantHelper::LOADING_TIME;

    //     $total_time = $loading_time + $qc_time + ((int) $this -> travel_to_site) + $insp_time;
     
    //     $model-> Carbon::parse($this -> delivery_date) ->copy()->subMinutes($total_time);
    // }

    public function getMaxIntervalAttribute()
    {
        $interval = $this -> pouring_time;
        if ($this -> interval > $this -> pouring_time) {
            $interval = $this -> interval;
        }
        return $interval;
    }

    public function getNextLoadingAttribute()
    {
        $total_time = (((int) ConstantHelper::LOADING_TIME) + ConstantHelper::QC_TIME + ((int) $this -> travel_to_site) + ConstantHelper::INSP_TIME);
        return Carbon::parse($this -> delivery_date) ->copy()->subMinutes($total_time);
    }

    public function scopeByUserCompanyScheduleDate($query, int $group_company_id, int $user_id, string $shift_start, string $shift_end)
    {
        return $query -> where("group_company_id", $group_company_id) -> where("user_id", $user_id)
        -> whereBetween("delivery_date",  [$shift_start, $shift_end]);
    }

    public function order_pumps()
    {
        return $this -> hasMany(OrderPump::class, 'order_id', 'og_order_id')-> select('type', 'quantity AS qty', 'capacity AS pump_size', 'pipe_size', 'order_id') -> where('status', ConstantHelper::ACTIVE);
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

    public function order_cube_moulds()
    {
        return $this -> hasMany(OrderCubeMould::class, 'order_id', 'og_order_id') -> select('mould_size', 'quantity AS qty', 'order_id') -> where('status', ConstantHelper::ACTIVE);
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

    public function order_temp_control()
    {
        return $this -> hasMany(OrderTempControl::class, 'order_id', 'og_order_id')-> select('temp', 'quantity AS qty', 'order_id') -> where('status', ConstantHelper::ACTIVE);;
    }

    public function customer_company()
    {
        return $this -> belongsTo(Customer::class, 'customer_id', 'id');
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

    public function customer_product()
    {
        return $this -> belongsTo(CustomerProduct::class, 'cust_product_id');
    }


}
