<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DriverExport implements FromCollection, WithHeadings
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
                $project->employee_code,
                $project->name ,
                $project->phone,
                $project->email_id,
                $project->username,
                $project->status,
            ];
        });
    }


    public function headings(): array
    {
        return [
            'Company',
            'Employee Code	',
            'Name',
            'Mobile',
            'Email Address	',
            'Username',
            'Status'
        ];
    }
}



