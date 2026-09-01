<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\GroupCompany;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class GroupCompanyHelper
{
    public static function getShiftTime(int $id, string $shiftDate) : array
    {
        //Find group company
        $company_shift = GroupCompany::find($id);
        $location_start_time = Carbon::parse($shiftDate . ' ' . ConstantHelper::GROUP_COMP_START_TIME);
        $location_end_time = Carbon::parse($shiftDate . ' ' . ConstantHelper::GROUP_COMP_END_TIME) -> copy() -> addDay();
        //Group company found
        if (isset($company_shift)) {
                $location_start_time = Carbon::parse($shiftDate . ' ' .$company_shift -> working_hrs_s);
                $location_end_time_1 = Carbon::parse($shiftDate . ' ' .$company_shift -> working_hrs_e);
                $location_end_time = Carbon::parse($location_start_time) -> gt(Carbon::parse($location_end_time_1)) ? 
                    $location_end_time_1 -> copy() -> addDay() : 
                    $location_start_time -> copy() -> setTimeFromTimeString(ConstantHelper::DAY_END_TIME);
        }
        //Return start and end time
        return array(
            'start_time' => $location_start_time,
            'end_time' => $location_end_time
        );
    }
}
