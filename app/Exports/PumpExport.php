<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PumpExport implements FromCollection, WithHeadings
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
                $project->pump_name,
                $project->type,
                $project->pump_capacity ,
                $project->description ,
                $project->status
            ];
        });
    }


    public function headings(): array
    {
        return [
            'Company',
            'Name',
           'Type',
           'Capacity',
           'Description',
            'Status'
        ];
    }
}



