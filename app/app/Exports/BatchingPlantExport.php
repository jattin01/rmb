<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BatchingPlantExport implements FromCollection, WithHeadings
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
                $project->group_company ?-> comp_name?? 'N/A',
                $project->plant_name ?? 'N/A',
                $project->long_name,
                $project->company_location ?-> location,
                $project->capacity,
                $project->description,
                $project->status
            ];
        });
    }




    public function headings(): array
    {
        return [
            'Company',
            'Code',
           'Name',
           'Location',
           'Capacity',
           'Description',
            'Status'
        ];
    }
}

