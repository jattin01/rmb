<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_company_id',
        'order_no',
        'og_order_id',
        'customer',
        'project',
        'site',
        'site_id',
        'mix_code',
        'quantity',
        'delivered_quantity',
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
        'planned_deviation',
        'actual_deviation',
        'planned_start_time',
        'actual_start_time',
        'planned_end_time',
        'actual_end_time',
        'deviation_reason',
        'structural_reference_id',
        'customer_id',
        'project_id',
        'cust_product_id',
        'is_technician_required'
    ];


    protected $appends = [
        'structural_reference',
        'remaining_qty',
        'next_loading',
        'order_start_time',
    ];

    public function structural_reference()
    {
        return $this -> belongsTo(StructuralReference::class);
    }

    public function getRemainingQtyAttribute()
    {
        return $this -> quantity;
    }

    public function getOrderStartTimeAttribute()
    {
        $total_time = (((int) ConstantHelper::LOADING_TIME) + ConstantHelper::QC_TIME + ((int) $this -> travel_to_site) + ConstantHelper::INSP_TIME);
        return Carbon::parse($this -> delivery_date) ->copy()->subMinutes($total_time);
    }

    public function getNextLoadingAttribute()
    {
        $total_time = (((int) ConstantHelper::LOADING_TIME) + ConstantHelper::QC_TIME + ((int) $this -> travel_to_site) + ConstantHelper::INSP_TIME);
        return Carbon::parse($this -> delivery_date) ->copy()->subMinutes($total_time);
    }

    public function getStructuralReferenceAttribute()
    {
        return $this -> structural_reference() ?-> first() ?-> name ?? "";
    }

    protected $hidden = ['deleted_at'];

    public function schedule()
    {
        return $this -> hasMany(LiveOrderSchedule::class, 'order_id', 'id') -> orderBy("planned_loading_start");
    }

    public function pump_schedule()
    {
        return $this -> hasMany(LiveOrderPumpSchedule::class, 'order_id', 'id') -> orderBy("planned_qc_start");;
    }
    public function scopeByCompanyScheduleDate($query, int $group_company_id, string $shift_start, string $shift_end)
    {
        return $query -> where("group_company_id", $group_company_id) -> whereBetween("delivery_date",  [$shift_start, $shift_end]);
    }

    public function customer_product()
    {
        return $this -> belongsTo(CustomerProduct::class, 'cust_product_id', 'id');
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

    public function order_temp_control_display()
    {
        $tempControl = $this -> order_temp_control() -> get();
        $formattedString = "";
        foreach ($tempControl as $key => $tempCtrl) {
            $formattedString .= $tempCtrl -> qty . " CUM / " . $tempCtrl -> temp . "ºC" . ($key === count($tempControl) - 1 ? "" : ", ");
        }
        return $formattedString == "" ? ConstantHelper::NOT_REQUIRED_LABEL : $formattedString;
    }

    public function project()
    {
        return $this -> belongsTo(CustomerProject::class, 'project_id', 'id');
    }

    public function project_detail()
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

    public function customer_site()
    {
        return $this -> belongsTo(CustomerProjectSite::class, 'site_id', 'id');
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

    public function approvals()
    {
        return $this -> hasMany(OrderApproval::class,'order_id', 'og_order_id');
    }
    public function order()
    {
        return $this -> belongsTo(Order::class, 'og_order_id');
    }
}
