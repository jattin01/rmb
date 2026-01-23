<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveOrderPumpSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_company_id',
        'schedule_date',
        'order_no',
        'pump',
        'location',
        'trip',
        'mix_code',
        'batching_qty',
        'expected_qc_time',
        'expected_qc_start',
        'expected_qc_end',
        'expected_travel_time',
        'expected_travel_start',
        'expected_travel_end',
        'expected_insp_time',
        'expected_insp_start',
        'expected_insp_end',
        'expected_pouring_time',
        'expected_pouring_start',
        'expected_pouring_end',
        'expected_cleaning_time',
        'expected_cleaning_start',
        'expected_cleaning_end',
        'expected_return_time',
        'expected_return_start',
        'expected_return_end',
        'expected_delivery_start'
    ];


    public function order()
    {
        return $this -> belongsTo(LiveOrder::class);
    }

    public function getCurrentActivity()
    {
        $currentActivity = "";

        $currentTrip = $this -> getAttributes();

        $notStartedCondition = isset($currentTrip['actual_loading_start']);
        $batchingCondition = isset($currentTrip['actual_loading_start']) && $currentTrip['actual_loading_end'] === null;
        $internalQCCondition = isset($currentTrip['actual_qc_start']) && $currentTrip['actual_qc_end'] === null;
        $travelCondition = isset($currentTrip['actual_travel_start']) && $currentTrip['actual_travel_end'] === null;
        $inspCondition = isset($currentTrip['actual_insp_start']) && $currentTrip['actual_insp_end'] === null;
        $pouringCondition = isset($currentTrip['actual_pouring_start']) && $currentTrip['actual_pouring_end'] === null;
        $cleaningCondition = isset($currentTrip['actual_cleaning_start']) && $currentTrip['actual_cleaning_end'] === null;
        $returnCondition = isset($currentTrip['actual_return_start']) && $currentTrip['actual_return_end'] === null;
        $completedCondition = isset($currentTrip['actual_return_end']);

        if ($notStartedCondition) {
            $currentActivity = "Trip Not Started Yet";
        }
        if ($batchingCondition) {
            $currentActivity = ConstantHelper::BATCHING;
        }
        if ($internalQCCondition) {
            $currentActivity = ConstantHelper::INTERNAL_QC;
        }
        if ($travelCondition) {
            $currentActivity = ConstantHelper::ON_SITE_TRAVEL;
        }
        if ($inspCondition) {
            $currentActivity = ConstantHelper::ON_SITE_INSP;
        }
        if ($pouringCondition) {
            $currentActivity = ConstantHelper::POURING;
        }
        if ($cleaningCondition) {
            $currentActivity = ConstantHelper::CLEAN_ON_SITE;
        }
        if ($returnCondition) {
            $currentActivity = ConstantHelper::RETURN;
        }
        if ($completedCondition) {
            $currentActivity = "Trip Completed";
        }
        return $currentActivity;
    }
}
