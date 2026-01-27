<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerProjectTeamMemberExport implements FromCollection, WithHeadings
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
                $project->customer ?-> name?? 'N/A',
                $project->name ?? 'N/A',
                $project->email,
                $project->username,
                $project->phone_no,
                $project->is_admin ? 'Yes' : 'No',
                $project->status
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Customer',
            'Name',
           'Email',
           'User Name',
           'Mobile No',
           'Admin',
            'Status'
        ];
    }
}

