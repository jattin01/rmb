<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class OrderExport implements FromCollection, WithHeadings

{
    protected $projects;

    public function __construct($projects)
    {

        $this->projects = $projects;
    }

    public function collection()
    {
        return $this->projects->map(function ($project) {
            return [
                $project->order_no,
                $project->customer_company?->contact_person . ' - ' . $project->customer_company?->name,
                $project->group_company?->comp_name,
                Carbon::parse($project->delivery_date)->format('d F, Y'),
                Carbon::parse($project->delivery_date)->format('h:i A'),
                $project->interval,
                $project->project,
                $project->site,
                $project->customer_product?->product_name,
                $project->customer_product?->product_code,
                $project->quantity,
                $project->structural_reference,
                $project->is_technician_required ? 'Yes' : 'No',
                $project->order_temp_control_display(),
                $project->order_pumps_display(),
                $project->order_cube_mould_display(),
                $project->has_customer_confirmed
                ? 'Ready for Casting'
                : 'Pending',
                $project->approval_status,
            ];
        });
    }



    public function headings(): array
    {
        return [

            'Order No',
            'Customer',
            'Company',
            'Delivery Date',
            'Time',
            'Interval',
            'Project',
            'Site Location',
            'Mix',
            'Mix Code',
            'Qty (CUM)',
            'Structure',
            'Technician',
            'Temp Control',
            'Pumps',
            'Cube Mould',
            'Site Status',
            'Order Status'

        ];
    }
}
