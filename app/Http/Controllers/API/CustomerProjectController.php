<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\CustomerProject;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Validations\CustomerProject as Validator;

class CustomerProjectController extends Controller
{
    public function getCustomerProjects(Request $request)
    {
        try {
            $customerProjects = CustomerProject::with('mobile_user_access_right') -> select('id', 'customer_id', 'code', 'name', 'contractor_name', 'type', 'start_date', 'end_date') -> where([
                ["customer_id", $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
                ['start_date', '<=', Carbon::now()],
                ['end_date', '>=', Carbon::now()],
                ]) -> when(!$request -> is_user_admin, function ($query) use($request) {
                    $query -> whereIn('id', $request -> project_ids);
                });
                if ($request->search) {
                    $customerProjects->where(function ($query) use ($request) {
                        $query->where('name', 'LIKE', '%' . $request->search . '%')
                            // ->orWhere('code', 'LIKE', '%' . $request->search . '%')
                            ->orWhere('contractor_name', 'LIKE', '%' . $request->search . '%')
                            ->orWhereHas('address', function ($subQuery) use ($request) {
                                $subQuery->where('address', 'LIKE', '%' . $request->search . '%');
                            });
                    });
                }
            $customerProjects = $customerProjects -> with('address') -> get();
            return array(
                'message' => __("message.records_returned_successfully", ['static' => __("static.projects")]),
                'data' => array(
                    'projects' => $customerProjects
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getSingleCustomerProject(Request $request, String $id)
    {
        try {
            $customerProject = CustomerProject::with('mobile_user_access_right')->select('id', 'customer_id', 'code', 'name', 'contractor_name', 'type', 'start_date', 'end_date') -> with('address') -> where([
                ['id', $id],
                ['status', ConstantHelper::ACTIVE],
                ['customer_id', $request -> customer_id]
            ]) -> when(!$request -> is_user_admin, function ($query) use($request) {
                $query -> whereIn('id', $request -> project_ids);
            }) -> with(['products' => function ($query) use ($request) {
                if ($request -> search) {
                    $query -> whereHas('product', function ($subQuery) use ($request) {
                        $subQuery -> where('name', 'LIKE', '%'. $request -> search . '%') -> orWhere('code', 'LIKE', '%'. $request -> search . '%');
                    });
                }
            }]) -> first();
            if (isset($customerProject)) {
                foreach ($customerProject -> products as &$product) {
                    $product -> is_ordered = $product -> is_ordered();
                    $product -> makeHidden(['product']);
                }
                $customerProject -> group_company_name = $customerProject -> group_company_name();
                return array(
                    'message' => __("message.records_returned_successfully", ['static' => __("static.project")]),
                    'data' => array(
                        'project' => $customerProject
                    )
                );
            }
             else {
                throw new ApiGenericException("Project Not Found");
             }
            
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getActiveProjectsCount(Request $request)
    {
        try {
            $activeProjects = CustomerProject::where([
                ["customer_id", $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
                ['start_date', '<=', Carbon::now()],
                ['end_date', '>=', Carbon::now()],
            ]) -> when(!$request -> is_user_admin, function ($query) use($request) {
                $query -> whereIn('id', $request -> project_ids);
            }) -> get();
            $activeProjectsCount = 0;
            if (isset($activeProjects) && count($activeProjects) > 0) {
                $activeProjectsCount = count($activeProjects);
            }
            return array(
                'message' => 'Projects count retrieved successfully',
                'data' => array(
                    'projects_count' => $activeProjectsCount,
                    'project_id' => $activeProjectsCount == 1 ? $activeProjects -> first() -> id : null,
                    'project_data' => $activeProjectsCount == 1 ? $activeProjects -> first() : null,
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
