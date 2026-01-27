<?php

namespace App\Helpers;

use App\Models\LiveOrderSchedule;
use Carbon\Carbon;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class LiveScheduleHelper
{
    public static function updateTripOnDriverUpdate(String $activityType, string $actualActivityTime, string $plannedActivityTime, LiveOrderSchedule $trip) : void
    {
        $deviation = 0;
        $deviation = Carbon::parse($plannedActivityTime) -> diffInMinutes(Carbon::parse($actualActivityTime), false);

        switch ($activityType) {
            case ConstantHelper::BATCHING:
                if ($deviation > 0) {

                    $trip -> planned_qc_start = (Carbon::parse($trip -> planned_qc_start) -> addMinutes($deviation));
                    $trip -> planned_qc_end = (Carbon::parse($trip -> planned_qc_end) -> addMinutes($deviation));
                    $trip -> planned_qc_time = Carbon::parse($trip -> planned_qc_end) -> diffInMinutes(Carbon::parse($trip -> planned_qc_start));

                    $trip -> planned_travel_start = (Carbon::parse($trip -> planned_travel_start) -> addMinutes($deviation));
                    $trip -> planned_travel_end = (Carbon::parse($trip -> planned_travel_end) -> addMinutes($deviation));
                    $trip -> planned_travel_time = Carbon::parse($trip -> planned_travel_end) -> diffInMinutes(Carbon::parse($trip -> planned_travel_start));

                    $trip -> planned_insp_start = (Carbon::parse($trip -> planned_insp_start) -> addMinutes($deviation));
                    $trip -> planned_insp_end = (Carbon::parse($trip -> planned_insp_end) -> addMinutes($deviation));
                    $trip -> planned_insp_time = Carbon::parse($trip -> planned_insp_end) -> diffInMinutes(Carbon::parse($trip -> planned_insp_start));

                    $trip -> planned_pouring_start = (Carbon::parse($trip -> planned_pouring_start) -> addMinutes($deviation));
                    $trip -> planned_pouring_end = (Carbon::parse($trip -> planned_pouring_end) -> addMinutes($deviation));
                    $trip -> planned_pouring_time = Carbon::parse($trip -> planned_pouring_end) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> addMinutes($deviation));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> addMinutes($deviation));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> addMinutes($deviation));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> addMinutes($deviation));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));

                } else if ($deviation < 0) {
                    $trip -> planned_qc_start = (Carbon::parse($trip -> planned_qc_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_qc_end = (Carbon::parse($trip -> planned_qc_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_qc_time = Carbon::parse($trip -> planned_qc_end) -> diffInMinutes(Carbon::parse($trip -> planned_qc_start));

                    $trip -> planned_travel_start = (Carbon::parse($trip -> planned_travel_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_travel_end = (Carbon::parse($trip -> planned_travel_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_travel_time = Carbon::parse($trip -> planned_travel_end) -> diffInMinutes(Carbon::parse($trip -> planned_travel_start));

                    $trip -> planned_insp_start = (Carbon::parse($trip -> planned_insp_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_insp_end = (Carbon::parse($trip -> planned_insp_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_insp_time = Carbon::parse($trip -> planned_insp_end) -> diffInMinutes(Carbon::parse($trip -> planned_insp_start));

                    $trip -> planned_pouring_start = (Carbon::parse($trip -> planned_pouring_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_pouring_end = (Carbon::parse($trip -> planned_pouring_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_pouring_time = Carbon::parse($trip -> planned_pouring_end) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));

                }
                break;
            case ConstantHelper::INTERNAL_QC:
                if ($deviation > 0) {

                    $trip -> planned_travel_start = (Carbon::parse($trip -> planned_travel_start) -> addMinutes($deviation));
                    $trip -> planned_travel_end = (Carbon::parse($trip -> planned_travel_end) -> addMinutes($deviation));
                    $trip -> planned_travel_time = Carbon::parse($trip -> planned_travel_end) -> diffInMinutes(Carbon::parse($trip -> planned_travel_start));

                    $trip -> planned_insp_start = (Carbon::parse($trip -> planned_insp_start) -> addMinutes($deviation));
                    $trip -> planned_insp_end = (Carbon::parse($trip -> planned_insp_end) -> addMinutes($deviation));
                    $trip -> planned_insp_time = Carbon::parse($trip -> planned_insp_end) -> diffInMinutes(Carbon::parse($trip -> planned_insp_start));

                    $trip -> planned_pouring_start = (Carbon::parse($trip -> planned_pouring_start) -> addMinutes($deviation));
                    $trip -> planned_pouring_end = (Carbon::parse($trip -> planned_pouring_end) -> addMinutes($deviation));
                    $trip -> planned_pouring_time = Carbon::parse($trip -> planned_pouring_end) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> addMinutes($deviation));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> addMinutes($deviation));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> addMinutes($deviation));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> addMinutes($deviation));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));


                } else if ($deviation < 0) {

                    $trip -> planned_travel_start = (Carbon::parse($trip -> planned_travel_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_travel_end = (Carbon::parse($trip -> planned_travel_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_travel_time = Carbon::parse($trip -> planned_travel_end) -> diffInMinutes(Carbon::parse($trip -> planned_travel_start));

                    $trip -> planned_insp_start = (Carbon::parse($trip -> planned_insp_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_insp_end = (Carbon::parse($trip -> planned_insp_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_insp_time = Carbon::parse($trip -> planned_insp_end) -> diffInMinutes(Carbon::parse($trip -> planned_insp_start));

                    $trip -> planned_pouring_start = (Carbon::parse($trip -> planned_pouring_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_pouring_end = (Carbon::parse($trip -> planned_pouring_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_pouring_time = Carbon::parse($trip -> planned_pouring_end) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));

                }
                break;
            case ConstantHelper::ON_SITE_TRAVEL:
                if ($deviation > 0) {

                    $trip -> planned_insp_start = (Carbon::parse($trip -> planned_insp_start) -> addMinutes($deviation));
                    $trip -> planned_insp_end = (Carbon::parse($trip -> planned_insp_end) -> addMinutes($deviation));
                    $trip -> planned_insp_time = Carbon::parse($trip -> planned_insp_end) -> diffInMinutes(Carbon::parse($trip -> planned_insp_start));

                    $trip -> planned_pouring_start = (Carbon::parse($trip -> planned_pouring_start) -> addMinutes($deviation));
                    $trip -> planned_pouring_end = (Carbon::parse($trip -> planned_pouring_end) -> addMinutes($deviation));
                    $trip -> planned_pouring_time = Carbon::parse($trip -> planned_pouring_end) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> addMinutes($deviation));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> addMinutes($deviation));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> addMinutes($deviation));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> addMinutes($deviation));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));

                } else if ($deviation < 0) {

                    $trip -> planned_insp_start = (Carbon::parse($trip -> planned_insp_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_insp_end = (Carbon::parse($trip -> planned_insp_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_insp_time = Carbon::parse($trip -> planned_insp_end) -> diffInMinutes(Carbon::parse($trip -> planned_insp_start));

                    $trip -> planned_pouring_start = (Carbon::parse($trip -> planned_pouring_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_pouring_end = (Carbon::parse($trip -> planned_pouring_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_pouring_time = Carbon::parse($trip -> planned_pouring_end) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));

                }
                break;
            case ConstantHelper::ON_SITE_INSP:
                if ($deviation > 0) {

                    $trip -> planned_pouring_start = (Carbon::parse($trip -> planned_pouring_start) -> addMinutes($deviation));
                    $trip -> planned_pouring_end = (Carbon::parse($trip -> planned_pouring_end) -> addMinutes($deviation));
                    $trip -> planned_pouring_time = Carbon::parse($trip -> planned_pouring_end) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> addMinutes($deviation));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> addMinutes($deviation));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> addMinutes($deviation));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> addMinutes($deviation));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));

                } else if ($deviation < 0) {

                    $trip -> planned_pouring_start = (Carbon::parse($trip -> planned_pouring_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_pouring_end = (Carbon::parse($trip -> planned_pouring_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_pouring_time = Carbon::parse($trip -> planned_pouring_end) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));
                }
                break;
            case ConstantHelper::POURING:
                if ($deviation > 0) {

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> addMinutes($deviation));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> addMinutes($deviation));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> addMinutes($deviation));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> addMinutes($deviation));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));

                } else if ($deviation < 0) {

                    $trip -> planned_cleaning_start = (Carbon::parse($trip -> planned_cleaning_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_end = (Carbon::parse($trip -> planned_cleaning_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_cleaning_time = Carbon::parse($trip -> planned_cleaning_end) -> diffInMinutes(Carbon::parse($trip -> planned_cleaning_start));

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));
                }
                break;
            case ConstantHelper::CLEAN_ON_SITE:
                if ($deviation > 0) {

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> addMinutes($deviation));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> addMinutes($deviation));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));

                } else if ($deviation < 0) {

                    $trip -> planned_return_start = (Carbon::parse($trip -> planned_return_start) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_end = (Carbon::parse($trip -> planned_return_end) -> subMinutes(abs($deviation)));
                    $trip -> planned_return_time = Carbon::parse($trip -> planned_return_end) -> diffInMinutes(Carbon::parse($trip -> planned_return_start));
                }
                break;
            
            default:
               
                break;
        }
        $trip -> save();
    }
}
