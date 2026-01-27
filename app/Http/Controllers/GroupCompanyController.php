<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Models\CompanyLocation;
use DB;
use Exception;
use Illuminate\Http\Request;

class GroupCompanyController extends Controller
{
    public function getUserCompanyLocations(Request $request, String $groupCompanyId)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $location_ids = $user -> access_rights -> pluck('location_id');
            $groupCompanyLocations = CompanyLocation::select('id AS value', DB::raw("CONCAT(site_name, ' - ', location) AS label")) -> where([
                ['group_company_id', $groupCompanyId]
            ]) -> whereIn('group_company_id', $group_company_ids) -> whereIn('id', $location_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            return array(
                'message' => __('message.records_returned_successfully', ['static' => __('static.location')]),
                'data' => array(
                    'company_locations' => $groupCompanyLocations,
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
