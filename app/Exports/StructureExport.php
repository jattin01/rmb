<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StructureExport implements FromCollection, WithHeadings
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
                $project->name,
                $project->group_company ?-> comp_name?? 'N/A',

                $project->status,
            ];
        });
    }


    public function headings(): array
    {
        return [
            'Name',
            'Company',

            'Status'
        ];
    }
}



