<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExport implements FromCollection, WithHeadings
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
                $project->username,
                $project->name,
                $project->mobile_no ,
                $project->email ,
                $project->role?->name ,
                $project->status
            ];
        });
    }


    public function headings(): array
    {
        return [
            'User Name',
            'Name',
            'Mobile',
            'Email Address',
            'Role',
            'Status'
        ];
    }
}



