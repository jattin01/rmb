<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\CustomerProjectSiteHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use App\Models\CustomerProjectSite;

use Illuminate\Validation\Validator as ValidationValidator;

class Order
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function import(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'excel_file' => [
                    'required',
                    'file',
                    'mimes:xls,xlsx',
                    'max:' . ConstantHelper::MAX_FILE_SIZE
                ],
                // 'group_company_id' => [
                //     'required',
                //     'numeric',
                //     'integer',
                //     'exists:group_companies,id'
                // ]
            ]
        );

        return $validator;
    }
    public function ordersOverview(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ]
            ]
        );

        return $validator;
    }
    public function importTransitMixers(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'excel_file' => [
                    'required',
                    'file',
                    'mimes:xls,xlsx',
                    'max:' . ConstantHelper::MAX_FILE_SIZE
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ]
            ]
        );

        return $validator;
    }
    public function importDrivers(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'excel_file' => [
                    'required',
                    'file',
                    'mimes:xls,xlsx',
                    'max:' . ConstantHelper::MAX_FILE_SIZE
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ]
            ]
        );

        return $validator;
    }
    public function scheduleStepTwo(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'company_id' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'schedule_date' => [
                    'required',
                    'date'
                ]
            ]
        );

        return $validator;
    }
    public function scheduleStepThree(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'company_id' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'schedule_date' => [
                    'required',
                    'date'
                ]
            ]
        );

        return $validator;
    }
    public function generateSchedule(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'company_id' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'schedule_date' => [
                    'required',
                    'date'
                ],
                'transit_mixers' => [
                    'required',
                    'array'
                ],
                'transit_mixers.*' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'pumps' => [
                    // 'required',
                    'array'
                ],
                'pumps.*' => [
                    // 'required',
                    'numeric',
                    'integer'
                ],
                'batching_plants' => [
                    'required',
                    'array'
                ],
                'batching_plants.*' => [
                    'required',
                    'numeric',
                    'integer'
                ],
            ]
        );

        return $validator;
    }
    public function updateSelectedOrders(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'orders' => [
                    'required',
                    'array',
                    'max:250',
                    'min:1'
                ],
                'company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'schedule_date' => [
                    'required',
                    'date'
                ],
                'orders.*.order_id' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'orders.*.priority' => [
                    'nullable',
                    'numeric',
                    'integer',
                    'max:9999'
                ],
                'orders.*.interval' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'orders.*.travel_to_site' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'orders.*.return_to_plant' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'orders.*.time' => [
                    'required',
                    'string'
                ],
                'interval_deviation' => [
                    'required',
                    'integer',
                    'numeric'
                ]
            ]
        );

        return $validator;
    }
    public function updateOrder(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'order_id' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'delivery_date' => [
                    'required',
                    'date',
                ],
                'interval' => [
                    'required',
                    'numeric',
                    'integer',
                    'max:9999'
                ],
                'interval_deviation' => [
                    'required',
                    'numeric',
                    'integer',
                    'max:9999'
                ],
            ]
        );

        return $validator;
    }
    public function orderScheduleView(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'company_id' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'schedule_date' => [
                    'required',
                    'date'
                ]
            ]
        );

        return $validator;
    }
    public function liveScheduleView(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'company_id' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'schedule_date' => [
                    'required',
                    'date'
                ],
                'current_time' => [
                    'required',
                    'string'
                ]
            ]
        );

        return $validator;
    }
    public function resetOrders(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'company_id' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'schedule_date' => [
                    'required',
                    'date'
                ]
            ]
        );

        return $validator;
    }
    public function publishOrders(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'schedule_date' => [
                    'required',
                    'date',
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ]
            ]
        );

        return $validator;
    }
    //API
    public function getCustomerProjects(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'customer_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'filter_type' => [
                    'required',
                    'string',
                    Rule::in(ConstantHelper::ORDER_FILTERS)
                ]
            ]
        );

        return $validator;
    }
    public function getCustomerProjectOrderDetails(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'filter_type' => [
                    'required',
                    'string',
                    Rule::in(ConstantHelper::ORDER_FILTERS)
                ]
            ]
        );

        return $validator;
    }
    public function updateProductQuantity(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'type' => [
                    'required',
                    'string',
                    Rule::in('ADD', "SUB")
                ]
            ]
        );

        return $validator;
    }
    public function confirmCustomerOrder(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'customer_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'site_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customer_project_sites,id'
                ],
                'id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customer_products,id'
                ],
                'is_tech_required' => [
                    'boolean'
                ],
                'quantity' => [
                    'numeric',
                    'required'
                ],
                'order_id' => [
                    'numeric',
                    'integer',
                    'exists:orders,id'
                ],
                'structural_reference_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:structural_references,id'
                ],
                'delivery_date' => [
                    'required',
                    'date',
                    'after_or_equal:today'
                ],
                'delivery_time' => [
                    'required',
                    'date_format:H:i'
                ],
                'interval' => [
                    'required',
                    'integer',
                    'numeric',
                    'min:0',
                    'max:40'
                ],
                'temp_control' => [
                    // 'required',
                    'array'
                ],
                'temp_control.*.temp' => [
                    'required',
                    'numeric'
                ],
                'temp_control.*.qty' => [
                    'required',
                    'numeric'
                ],
                'pump_req' => [
                    // 'required',
                    'array'
                ],
                'pump_req.*.pump_size' => [
                    'required',
                    'numeric'
                ],
                'pump_req.*.qty' => [
                    'required',
                    'numeric'
                ],
                'pump_req.*.type' => [
                    'required',
                    'string'
                ],
                'pump_req.*.pipe_size' => [
                    'nullable',
                    'numeric'
                ],
                'cube_mould_req' => [
                    // 'required',
                    'array'
                ],
                // 'cube_mould_req.*.mould_size' => [
                //     'required',
                //     'numeric'
                // ],
                'cube_mould_req.*.qty' => [
                    'required',
                    'numeric'
                ],
                'remarks' => [
                    'string',
                    'max:255'
                ]
            ]
        );

        return $validator;
    }
    public function createOrderAdmin(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'customer_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customers,id'
                ],
                'project_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customer_projects,id'




                ],
                'site_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customer_project_sites,id'
                ],
                // 'company_location_id' => [
                //     'required',
                //     'numeric',
                //     'integer',
                //     'exists:company_locations,id'
                // ],
                'id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customer_products,id'
                ],
                'is_tech_required' => [
                    'string'
                ],
                'is_temp_required' => [
                    'string'
                ],
                'is_pump_required' => [
                    'string'
                ],
                'is_cube_mould_required' => [
                    'string'
                ],
                'quantity' => [
                    'numeric',
                    'required'
                ],
                'order_id' => [
                    'numeric',
                    'integer',
                    'exists:orders,id'
                ],
                'structural_reference_id' => [
                    'required',
                    // 'numeric',
                    // 'integer',
                    'exists:structural_references,id'
                ],
                // 'delivery_date' => [
                //     'required',
                //     'date',
                //     'after_or_equal:today'
                // ],
                'delivery_time' => [
                    'required',
                    'date_format:H:i'
                ],
                'interval' => [
                    'nullable',
                    'integer',
                    'numeric',
                    'min:1',
                    'max:40'
                ],
                'temp_values' => [
                    'required',
                    'array'
                ],
                'temp_values.*' => [
                    'nullable',
                    'numeric'
                ],
                // 'temp_qty' => [
                //     'required',
                //     'array'
                // ],
                'temp_qty.*' => [
                    'nullable',
                    'numeric'
                ],
                'pump_types' => [
                    'required',
                    'array'
                ],
                'pump_types.*' => [
                    'nullable',
                    'string'
                ],
                'pump_sizes' => [
                    'required',
                    'array'
                ],
                'pump_sizes.*' => [
                    'nullable',
                    'numeric'
                ],
                'no_of_pumps' => [
                    'required',
                    'array'
                ],
                'no_of_pumps.*' => [
                    'nullable',
                    'numeric'
                ],
                'no_of_pipes' => [
                    'nullable',
                    'array'
                ],
                'no_of_pipes.*' => [
                    'nullable',
                    'numeric'
                ],
                'cube_mould_req_quantity' => [
                    'nullable',
                    'numeric'
                ],
                'remarks' => [
                    'string',
                    'max:255'
                ]
                ],[
                    'interval.min'=>'The interval field  greater than 0 min.',
                    'interval.max'=>'The interval field less than 40 mins.',

                ]
        );

        $validator -> sometimes(['temp_values.*'], 'required|numeric', function (Fluent $input) {
            return $input -> is_temp_required == "on";
        });

        $validator -> sometimes(['cube_mould_req_quantity'], 'required|numeric', function (Fluent $input) {
            return $input -> is_cube_mould_required == "on";
        });

        $validator -> sometimes([ 'no_of_pumps.*', 'pump_sizes.*'], 'required|numeric', function (Fluent $input) {
            return $input -> is_pump_required == "on";
        });

        $validator -> sometimes(['pump_types.*'], 'required|string', function (Fluent $input) {
            return $input -> is_pump_required == "on";
        });
        $projectSite = CustomerProjectSite::find($this ->request -> site_id);
        if (!isset($projectSite))
        {
            $validator->errors()->add('checkdistance', 'Project not found');
        }else{
            $company_location_id = $projectSite -> service_company_location -> id;
            $travelToSiteDistance =CustomerProjectSiteHelper::assignDistance( $company_location_id , $this ->request->site_id,'site');

               if((isset($travelToSiteDistance['rows'][0]['elements'][0]['distance']['value'])) &&($travelToSiteDistance['rows'][0]['elements'][0]['distance']['value']<=300000))

               {


           }
           else
           {

            // DB::rollback();
            $validator->errors()->add('checkdistance', 'Customer site out of reach');
            // dd($validator->messages());

        }
        }


        return $validator;
    }
    public function updateSiteStatus(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'order_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:orders,id'
                ],
                'remarks' => [
                    'string',
                ],
                'documents' => [
                    'array',
                    'max:5'
                ],
                'documents.*' => [
                    'nullable',
                    'file',
                    'mimes:jpeg,png,jpg,gif,svg,bmp,tiff,webp,doc,docx,xls,xlsx,pdf',
                    'max:2048'
                ]
            ]
        );

        $validator -> sometimes(['temp_qty.*', 'temp_values.*'], 'required|numeric', function (Fluent $input) {
            $input -> is_temp_required == "on";
        });

        $validator -> sometimes([''], 'required|numeric', function (Fluent $input) {
            $input -> is_cube_mould_required == "on";
        });

        $validator -> sometimes(['no_of_pipes.*', 'no_of_pumps.*', 'pump_sizes.*'], 'required|numeric', function (Fluent $input) {
            $input -> is_pump_required == "on";
        });

        $validator -> sometimes(['pump_types.*'], 'required|numeric', function (Fluent $input) {
            $input -> is_pump_required == "on";
        });

        return $validator;
    }
    public function markAsApprove(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'order_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:orders,id'
                ],
                'remarks' => [
                    'string',
                ],
                'docs' => [
                    'array',
                    'max:5'
                ],
                'docs.*' => [
                    'nullable',
                    'file',
                    'mimes:jpeg,png,jpg,gif,svg,bmp,tiff,webp,doc,docx,xls,xlsx,pdf',
                    'max:2048'
                ],
                'approval_status' => [
                    'required',
                    'string',
                    Rule::in(['Approved', 'Rejected', 'Sent Back'])
                ]
            ]
        );

        $validator -> sometimes(['temp_qty.*', 'temp_values.*'], 'required|numeric', function (Fluent $input) {
            $input -> is_temp_required == "on";
        });

        $validator -> sometimes([''], 'required|numeric', function (Fluent $input) {
            $input -> is_cube_mould_required == "on";
        });

        $validator -> sometimes(['no_of_pipes.*', 'no_of_pumps.*', 'pump_sizes.*'], 'required|numeric', function (Fluent $input) {
            $input -> is_pump_required == "on";
        });

        $validator -> sometimes(['pump_types.*'], 'required|numeric', function (Fluent $input) {
            $input -> is_pump_required == "on";
        });

        return $validator;
    }

    public function addConfirmationToOrder(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:orders,id'
                ],
                'remarks' => [
                    'string',
                ],
                'documents' => [
                    'array',
                    'max:5'
                ],
                'documents.*' => [
                    'required',
                    'file',
                    'mimes:jpeg,png,jpg,gif,svg,bmp,tiff,webp,doc,docx,xls,xlsx,pdf',
                    'max:2048'
                ]
            ]
        );

        return $validator;
    }
    public function getTimeSlotsStatus(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'schedule_date' => [
                    'required',
                    'date',
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:group_companies,id'
                ]
            ]
        );

        return $validator;
    }
}
