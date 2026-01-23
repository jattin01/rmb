<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerProjectMixDesignExport implements FromCollection, WithHeadings
{
    protected $projects;

    public function __construct($projects)
    {

        $this->projects = $projects;
    }

    public function collection()
    {
        return $this->projects->map(function($project) {
            return [
                $project->customer_project?->customer?->name ?? 'N/A',
                $project->customer_project->name ?? 'N/A',
                $project->product->code,
                $project->product->name,
                $project->product->product_type->type,
                $project->total_quantity,
                $project->ordered_quantity,
                $project->remaining_quantity,
                // $project->service_company_location->site_name,

                $project->status
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Customer',
            'Project Name',
           ' Mix Code',
           ' Mix Name',
           ' Mix Type',
           ' Total Qty',
           ' Utilized Qty',
           ' Remaining Qty',
            'Status'
        ];
    }
}

