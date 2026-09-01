<?php

namespace App\Imports;

use Illuminate\Support\Carbon;

use App\Exceptions\OrderImportException;
use App\Models\CompanyLocation;
use App\Models\Customer;
use App\Models\CustomerProject;
use App\Models\GroupCompany;
use App\Models\StructuralReference;
use App\Models\OrderPump;
use App\Models\OrderCubeMould;
use App\Models\OrderTempControl;
use App\Models\Product;
use App\Models\CustomerProduct;
use App\Models\CustomerProjectSite;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\ValidationException;

class OrderImport implements ToModel, WithHeadingRow
{
    private $group_company_id = null;

    public function __construct($group_company_id)
    {
        $this->group_company_id = $group_company_id;
    }

    public function model(array $row)
    {
// dd($row);
	    $rowNumber = $row['sno'] ?? 'unknown';
	    //echo $row['delivery_date'];
        // Set up validation with row-specific messages
        $validator = Validator::make($row, $this->rules(), $this->getCustomMessages($rowNumber));

        if ($validator->fails()) {

            $errorMessage = $validator->errors()->first();
            throw new OrderImportException("Error : {$errorMessage}");
        }

        // Get customer and project information based on name
        $customer = Customer::where('name', $row['customer'])->first();
        $project = CustomerProject::where('name', $row['project'])->first();
        $mixCodeProduct = Product::where('code', $row['mix_code'])->first();
        $customerProduct = CustomerProduct::where('product_id',$mixCodeProduct->id)
                                        ->where('customer_id',$customer->id)
                                        ->where('project_id','=',$project->id)
                                        ->first();
        $mixCode = $customerProduct->mix_code;
        $customerProductId = $customerProduct->id;
        $groupCompany = GroupCompany::where('comp_code','=',$row['comp_code'])->first();
        $groupCompanyId = $groupCompany?->id;

        $companyLocation =CompanyLocation::where('group_company_id','=',$groupCompanyId)->first();
        $projectSite =CustomerProjectSite::where('name','=',$row['site_location'])->first();


        $structuralReference = StructuralReference::where('name','=',$row['structure'])
        ->where('group_company_id',$groupCompanyId)
        ->first();

        // Generate a unique order number based on user ID and previous orders
        $previousOrders = Order::withTrashed()->where([
            ['group_company_id', $this->group_company_id],
            ['customer_id', $customer->id],
        ])->count();

        $newOrderNo = auth()->user()->id . $groupCompanyId . $customer->id . $previousOrders;

        // Create and return a new Order
        $order = Order::create([
            'group_company_id' => $groupCompanyId,
            'order_no' => $newOrderNo,
            'customer' => $row['customer'],
            'customer_id' => $customer->id,
            'project' => $row['project'],
            'project_id' => $project->id,
            'site' => $row['site_location'],
            'site_id' => $projectSite->id,
            'company_location_id' => $companyLocation->id,
            'structural_reference_id' => $structuralReference?->id,
            'mix_code' => $mixCode,
            'cust_product_id' => $customerProductId,
            'quantity' => $row['qty'],
            'pump_qty' => $row['pump_qty'] ?? 0,
            'delivery_date' => date('Y-m-d H:i:s', strtotime($row['delivery_date'])),
            'interval' => $row['interval'],
            'location' => $row['site_location'],
            'temp_control' => $row['temp_control'],
            'technician' => ($row['technician'] == 'yes') ? 1 : 0,

        ]);

        if($row['temp_control']){
            $tempControl = explode(',', $row['temp_control']);
            foreach ($tempControl as $temperature) {
                $temp = explode('-',$temperature);
                $tempVal = $temp[0];
                $tempQty = $temp[1];
                OrderTempControl::create([
                    'order_id' => $order->id,
                    'quantity' => $tempQty,
                    'temp' => $tempVal,
                    'status' => 'Active'
                ]);
            }
        }

        if ($row['pump']) {
            $pumpRequired = explode(',', $row['pump']);
            foreach ($pumpRequired as $pump) {
                $pumpDetail = explode('-', $pump);
                $pumpType = $pumpDetail[0];
                $pumpSize = eval('return ' . $pumpDetail[1] . ';'); // Safely evaluate multiplication
                $pumpQuantity = $pumpDetail[2];
                $pipeSize = $pumpDetail[3] ?? 0;

                OrderPump::create([
                    'order_id' => $order->id,
                    'capacity' => $pumpSize,
                    'type' => $pumpType,
                    'quantity' => $pumpQuantity ?? 0,
                    'pipe_size' => $pipeSize ?? null,
                ]);
            }
        }

        OrderCubeMould::create([
            'order_id' => $order -> id,
            'mould_size' => null,
            'quantity' => $row['cube_mould']
        ]);
        OrderTempControl::create([
            'order_id' => $order -> id,
            'quantity' => $row['quantity']?? 0,
            'temp' => $row['temp']?? 0,
        ]);

        return $order;
    }

    public function rules(): array
    {
        return [
            'sno' => 'required|numeric',
            'customer' => 'required|string|exists:customers,name',
            'project' => 'required|string|exists:customer_projects,name',
            'site_location' => 'required|string|exists:customer_project_sites,name',
            'structure' => 'required|string|exists:structural_references,name',
            'comp_code' => 'required|string',
            'mix_code' => 'required|string|exists:products,code',
            'qty' => 'required|numeric|min:1',
            // 'pump_qty' => 'nullable|numeric|integer',
            'delivery_date' => 'required|string',
            'delivery_date' => 'required',
            'interval' => 'required|numeric|integer',
            'temp_control' => 'required',
            'cube_mould' => 'required|numeric',
            'technician' => 'required|string',
            // 'pump_size' => 'required|numeric',
            // 'pump_type' => 'required|string'
            'pump' => 'required'
        ];
    }

    private function getCustomMessages($rowNumber): array
    {
        return [
            'sno.required' => "Serial Number 'SNo' field is required .",
            'sno.numeric' => "Serial Number 'SNo' field must be numeric .",
            'customer.required' => "Customer field is required at Row {$rowNumber}.",
            'customer.exists' => "Customer does not exist in our records at Row {$rowNumber}.",
            'project.required' => "Project field is required at Row {$rowNumber}.",
            'project.exists' => "Project is invalid at Row {$rowNumber}.",
            'site_location.required' => "Site Location field is required at Row {$rowNumber}.",
	        'structure.required' => "Structure field is required at Row {$rowNumber}.",
	        'structure.exists' => "Structure does not exist in our records at Row {$rowNumber}.",
            'comp_code.required' => "Comp Code field is required at Row {$rowNumber}.",
            'mix_code.required' => "Mix Code field is required at Row {$rowNumber}.",
            'mix_code.exists' => "Mix Code does not exist in our records at Row {$rowNumber}.",
            'qty.required' => "Quantity is required and must be at least 1 at Row {$rowNumber}.",
            'qty.min' => "Quantity must be a positive number at Row {$rowNumber}.",
            'pump_qty.numeric' => "Pump Quantity must be numeric at Row {$rowNumber}.",
            'pump_qty.integer' => "Pump Quantity must be an integer at Row {$rowNumber}.",
            'delivery_date.required' => "Delivery Date is required at Row {$rowNumber}.",
            'interval.required' => "Interval field is required at Row {$rowNumber}.",
            'interval.numeric' => "Interval must be numeric at Row {$rowNumber}.",
            'interval.integer' => "Interval must be an integer at Row {$rowNumber}.",
            'temp_control.required' => "Temperature Control field is required at Row {$rowNumber}.",
            'temp_control.numeric' => "Temperature Control must be numeric at Row {$rowNumber}.",
            'cube_mould.required' => "Cube Mould field is required at Row {$rowNumber}.",
            'cube_mould.numeric' => "Cube Mould must be numeric at Row {$rowNumber}.",
            'technician.required' => "Technician field is required at Row {$rowNumber}.",
            'technician.string' => "Technician field must be a string at Row {$rowNumber}.",
            'pump_size.required' => "Pump Size field is required at Row {$rowNumber}.",
            'pump_size.numeric' => "Pump Size must be numeric at Row {$rowNumber}.",
            'pump.required' => "Pump field is required at Row {$rowNumber}.",
            'pump_type.string' => "Pump Type must be a string at Row {$rowNumber}."
        ];
    }

    public function chunkSize(): int
    {
        return 100; // Set this to your desired chunk size for import processing
    }
}
