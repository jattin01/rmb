<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\CustomerProjectSiteExport;
use App\Helpers\ConstantHelper;
use App\Models\CompanyLocation;
use App\Models\Customer;
use App\Models\CustomerProduct;
use App\Models\CustomerProject;
use App\Models\CustomerProjectSite;
use App\Models\GroupCompany;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\CustomerProjectSite as Validator;
use Maatwebsite\Excel\Facades\Excel;

class CustomerProjectSiteController extends Controller
{
    public function getAllList(Request $request, String $projectId)
    {
        try {
            $sites = CustomerProjectSite::select('id AS value', 'name AS label', 'company_location_id')->where([
                ['status', ConstantHelper::ACTIVE],
                ['cust_project_id', $projectId]
            ])->whereIn('company_location_id', auth()->user()->access_rights->pluck('location_id')->toArray())->get();
            $mixCodes = CustomerProduct::where([
                ['status', ConstantHelper::ACTIVE],
                ['project_id', $projectId]
            ]);
            if ($request->structure_id) {
                $mixCodes = $mixCodes->whereHas('product', function ($query) use ($request) {
                    $query->wherehas('structuralReferences', function ($subQuery) use ($request) {
                        $subQuery->where('structure_reference_id', $request->structure_id);
                    });
                })->get();
            } else {
                $mixCodes = $mixCodes->get();
            }
            foreach ($mixCodes as $mixCode) {
                $mixCode->label = $mixCode->product_code;
                $mixCode->value = $mixCode->id;
            }
            return array(
                'data' => array(
                    'project_sites' => $sites,
                    'mix_codes' => $mixCodes
                )
            );
        } catch (Exception $ex) {
            throw new ApiGenericException($ex->getMessage());
        }
    }

    public function getDetails(Request $request, String $id)
    {
        try {
            $details = CustomerProjectSite::with('service_company_location.group_company')->find($id);
            return array(
                'data' => array(
                    'site' => $details
                )
            );
        } catch (Exception $ex) {
            throw new ApiGenericException($ex->getMessage());
        }
    }

    public function index(Request $request)
    {
        try {
            $search = $request->search;
            $user = auth()->user();
            $locationIds = $user->access_rights->pluck('location_id');


            $project = CustomerProject::where('id', $request->project_id)
                ->whereHas('customer', function ($query) use ($user) {
                    $query->where('group_id', $user->group_id);
                })
                ->first();

            $customerCompanyIds = $project?->customer?->group_companies?->pluck('group_company_id')
                ?? Customer::find($request->customer_id)?->group_companies?->pluck('group_company_id');


            // $sites = CustomerProjectSite::where('cust_project_id', $request->project_id)
            $sites = CustomerProjectSite::when($request->project_id, function ($conditionalQuery) use($request) {
                $conditionalQuery -> where('cust_project_id', $request->project_id);
            }) -> whereHas('project', function ($projectQuery) use($request) {

                $projectQuery -> whereHas('customer', function($custQuery) use($request) {
                    $custQuery -> when($request -> customer_id, function ($mainQuery) use($request) {
                        $mainQuery-> where('id', $request -> customer_id);
                    });
                });
            })
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('name', 'LIKE', '%' . $search . '%')
                            ->orWhere('address', 'LIKE', '%' . $search . '%');
                    })
                        ->orWhereHas('project', function ($query2) use ($search) {
                            $query2->where('name', 'LIKE', '%' . $search . '%')->orWhereHas('customer', function ($custQuery) use ($search) {
                                $custQuery->where('name', 'LIKE', '%' . $search . '%');
                            });
                        });
                })

                ->orderByDesc('created_at');
            if (isset($request->name)) {
                $sites = $sites->where('name', $request->name);
            }
            if (isset($request->company_location_id)) {
                $sites = $sites->where('company_location_id', $request->company_location_id);
            }


            if (!$project) {
                $sites->whereHas('project', function ($query) use ($search) {
                    $query->whereHas('customer', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'LIKE', '%' . $search . '%');
                    });
                });
            }


            $sites = $sites->paginate(ConstantHelper::PAGINATE)
                ->appends(['search' => $search, 'customer_id' => $project?->customer?->id, 'project_id' => $project?->id]);

            // Retrieve active group companies
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label')
                ->whereIn('id', $customerCompanyIds ?? [])
                ->where('status', ConstantHelper::ACTIVE)
                ->get();

            // Retrieve company locations based on the first group company
            $companyLocations = collect([]);
            $firstGroupCompany = $groupCompanies->first();

            if ($firstGroupCompany) {
                $companyLocations = CompanyLocation::select('id AS value', 'site_name AS label')
                    ->whereIn('id', $locationIds)
                    ->where('group_company_id', $firstGroupCompany->value)
                    ->where('status', ConstantHelper::ACTIVE)
                    ->get();
            }

            // dd($sites);
            return view('components.customers.projects.sites.index', [
                'sites' => $sites,
                'groupCompanies' => $groupCompanies,
                'locations' => $companyLocations,
                'search' => $search
            ]);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }



    public function store(Request $request)
    {
        $validator = (new Validator($request))->store();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            // Update Site Address
            if ($request->siteId) {
                $site = CustomerProjectSite::find($request->siteId);
                $site->cust_project_id = $request->project_id;
                $site->company_location_id = $request->company_location_id;
                $site->name = $request->site_name;
                $site->is_default = isset($defaultSiteEnabled) ? 0 : 1;
                $site->address = $request->site_address;
                $site->latitude = $request->latitude;
                $site->longitude = $request->longitude;
                $site->status = $request->input('site_status', 'Inactive');
                $site->save();
            } else {
                // Save Site Address
                $site = new CustomerProjectSite();
                $site->cust_project_id = $request->project_id;
                $site->company_location_id = $request->company_location_id;
                $site->name = $request->site_name;
                $site->is_default = isset($defaultSiteEnabled) ? 0 : 1;
                $site->address = $request->site_address;
                $site->latitude = $request->latitude;
                $site->longitude = $request->longitude;
                $site->status = $request->input('site_status', 'Inactive');
                $site->save();
            }
            return [
                "status" => 200,
                "data" => $site,
                "message" => __('message.records_saved_successfully', ['static' => __('static.project_site')])
            ];
        } catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function edit(Request $request)
    {
        try {
            if ($request->siteId) {
                $siteDetail = CustomerProjectSite::with('service_company_location')->where('id', $request->siteId)->first();
                if ($siteDetail) {
                    return $siteDetail;
                }
            }
            return false;
        } catch (Exception $ex) {
            throw new ApiGenericException($ex->getMessage());
        }
    }


    public function export(Request $request)
    {
        
        $sites = CustomerProjectSite::when($request->project_id, function ($conditionalQuery) use($request) {
            $conditionalQuery -> where('cust_project_id', $request->project_id);
        }) -> whereHas('project', function ($projectQuery) use($request) {

            $projectQuery -> whereHas('customer', function($custQuery) use($request) {
                $custQuery -> when($request -> customer_id, function ($mainQuery) use($request) {
                    $mainQuery-> where('id', $request -> customer_id);
                });
            });
        })
        ->orderByDesc('created_at')->get();

        // Export using an Excel export class
        return Excel::download(new CustomerProjectSiteExport($sites), 'CustomerProjectSite.xlsx');
    }
}
