<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerProjectsExport implements FromCollection, WithHeadings
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
                $project->customer->name ?? 'N/A',
                $project->code,
                $project->name,
                $project->type,
                $project->contractor_name,
                $project->start_date->format('Y-m-d'),
                $project->end_date ? $project->end_date->format('Y-m-d') : 'N/A',
                $project->status
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Customer',
            'Project Code',
            'Project Name',
            'Type',
            'Contractor',
            'Started on',
            'ETC',
            'Status'
        ];
    }
}

