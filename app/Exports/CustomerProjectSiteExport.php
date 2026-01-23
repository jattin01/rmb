<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerProjectSiteExport implements FromCollection, WithHeadings
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
                $project->project->customer->name ?? 'N/A',
                $project->project->name,
                $project->name,
                $project->address,
                $project->service_company_location->site_name,

                $project->status
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Customer',
            'Project Name',
            'Site Name',
            'Site Address',
            'Service Location',
            'Status'
        ];
    }
}

