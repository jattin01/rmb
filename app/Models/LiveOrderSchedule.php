<?php

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveOrderSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
         // "order_no",
         "pump",
         "location",
         "trip",
         "mix_code",
         "batching_plant",
         "transit_mixer",
         "batching_qty",
         "expected_loading_time",
         "expected_loading_start",
         "expected_loading_end",
         "expected_qc_time",
         "expected_qc_start",
         "expected_qc_end",
         "expected_travel_time",
         "expected_travel_start",
         "expected_travel_end",
         "expected_insp_time",
         "expected_insp_start",
         "expected_insp_end",
         "expected_pouring_time",
         "expected_pouring_start",
         "expected_pouring_end",
         "expected_cleaning_time",
         "expected_cleaning_start",
         "expected_cleaning_end",
         "expected_return_time",
         "expected_return_start",
         "expected_return_end",
         "expected_delivery_start",
         // "deviation"
    ];

    public function order()
    {
        return $this -> belongsTo(LiveOrder::class);
    }
    public function transit_mixer_detail()
    {
        return $this -> hasOne(TransitMixer::class, 'id', 'transit_mixer_id');
    }
    public function pump_detail()
    {
        return $this -> hasOne(Pump::class, 'id', 'pump_id');
    }
    public function driver_details()
    {
        return $this -> hasOne(Driver::class, 'id', 'driver_id');
    }
    public function batching_plant_detail()
    {
        return $this -> hasOne(BatchingPlant::class, 'id', 'batching_plant_id');
    }
    public function transit_mixer_location()
    {
        return $this -> hasOne(TransitMixerLiveLocation::class, 'transit_mixer_id', 'transit_mixer_id');
    }

    public function getCurrentActivity()
    {
        $currentActivity = "";

        $currentTrip = $this -> getAttributes();

        $notStartedCondition = $currentTrip['actual_loading_start'] === null;
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

    public function getCurrentActivityPercentage()
    {
        $currentActivity = $this -> getCurrentActivity();
        $currentTime = Carbon::now() -> addHours(5) -> addMinutes(30);
        $actualStartTime = "";
        $currentTrip = $this -> getAttributes();
        $percentage= 0;


        switch ($currentActivity) {
            case ConstantHelper::BATCHING:
                $actualStartTime = $currentTrip['actual_loading_start'];
                break;

            case ConstantHelper::INTERNAL_QC:
                $percentage= 14;

                $actualStartTime = $currentTrip['actual_qc_start'];
                break;

            case ConstantHelper::ON_SITE_TRAVEL:
                $percentage= 28;

                $actualStartTime = $currentTrip['actual_travel_start'];
                break;

            case ConstantHelper::ON_SITE_INSP:
                $percentage= 42;

                $actualStartTime = $currentTrip['actual_insp_start'];
                break;

            case ConstantHelper::POURING:
                $percentage= 56;

                $actualStartTime = $currentTrip['actual_pouring_start'];
                break;

            case ConstantHelper::CLEAN_ON_SITE:
                $percentage= 70;

                $actualStartTime = $currentTrip['actual_cleaning_start'];
                break;

            case ConstantHelper::RETURN:
                $percentage= 84;

                $actualStartTime = $currentTrip['actual_return_start'];
                break;

            default:
                break;
        }

        $plannedTime =
            ($currentTrip['actual_loading_start'] ? Carbon::parse($actualStartTime) -> diffInMinutes($currentTime)  : $currentTrip['planned_loading_time']) +
            ($currentTrip['actual_qc_start'] ? Carbon::parse($actualStartTime) -> diffInMinutes($currentTime) : $currentTrip['planned_qc_time']) +
            ($currentTrip['actual_travel_start'] ? Carbon::parse($actualStartTime) -> diffInMinutes($currentTime) : $currentTrip['planned_travel_time']) +
            ($currentTrip['actual_insp_start'] ? Carbon::parse($actualStartTime) -> diffInMinutes($currentTime) : $currentTrip['planned_insp_time']) +
            ($currentTrip['actual_pouring_start'] ? Carbon::parse($actualStartTime) -> diffInMinutes($currentTime) : $currentTrip['planned_pouring_time']) +
            ($currentTrip['actual_cleaning_start'] ? Carbon::parse($actualStartTime) -> diffInMinutes($currentTime) : $currentTrip['planned_cleaning_time']) +
            ($currentTrip['actual_return_start'] ? Carbon::parse($actualStartTime) -> diffInMinutes($currentTime) : $currentTrip['planned_return_time'])
        ;

        // if ($actualStartTime && $plannedTime) {
        //     $elapsedTime = Carbon::parse($actualStartTime) -> diffInMinutes($currentTime);
        //     $percentage = round(($elapsedTime / $plannedTime) * 100, 0);
        //     return min(100,$percentage);
        // } else {
        //     return 0;
        // }
        return $percentage;



    }

    public function rejections()
    {
        return $this -> hasMany(LiveOrderScheduleRejection::class, 'trip_id') -> whereIn('activity', [ConstantHelper::INTERNAL_QC, ConstantHelper::ON_SITE_INSP]);
    }
    public function reports()
    {
        return $this -> hasMany(LiveOrderScheduleReport::class, 'trip_id');
    }
}
