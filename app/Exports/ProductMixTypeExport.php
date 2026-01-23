<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductMixTypeExport implements FromCollection, WithHeadings
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
                $project->type,
                $project->description ,
                $project->status
            ];
        });
    }


    public function headings(): array
    {
        return [
            'Company',
           'Mix Type',
            'Description',
            'Status'
        ];
    }
}



