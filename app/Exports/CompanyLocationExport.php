<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompanyLocationExport implements FromCollection, WithHeadings
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
                $project->location,
                $project->site_name,
                $project->contact_person ,
                $project->phone ,
                $project->email,
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
            'Contact Person',
            'Mobile',
            'Email Address',
            'Status'
        ];
    }
}




