<?php

namespace App\Helpers\V2;

use App\Helpers\ConstantHelper;
use App\Models\TransitMixerRestriction;
use Carbon\Carbon;

class TransitMixerRestrictionHelper {
    public function getRestrictions(int $company_id, string $schedule_date, string $shift_start) : array
    {
        $restrictions = TransitMixerRestriction::select("restriction_start", "restriction_end")
            ->where("group_company_id", $company_id)->first();

        $restriction_start = null;
        $restriction_end = null;

        if (isset($restrictions)) {
            $restriction_date = Carbon::parse($schedule_date . ' ' . $restrictions -> restriction_start) -> lt(Carbon::parse($shift_start)) ? Carbon::parse($schedule_date) -> copy() -> addDay() -> toDateString() : $schedule_date;
            $restriction_start = Carbon::parse($restriction_date . " " . $restrictions->restriction_start)->format(ConstantHelper::SQL_DATE_TIME);
            $restriction_end = Carbon::parse($restriction_date . " " . $restrictions->restriction_end)->lt(Carbon::parse($restriction_date . " " . $restrictions->restriction_start)) ?
                Carbon::parse($restriction_date . " " . $restrictions->restriction_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME)
                : Carbon::parse($restriction_date . " " . $restrictions->restriction_end)->format(ConstantHelper::SQL_DATE_TIME);
        }
        return array(
            'restriction_start' => $restriction_start,
            'restriction_end' => $restriction_end,
        );
    }
}