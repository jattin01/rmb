<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class ApprovalSetpExport implements FromCollection, WithHeadings
{
    protected $projects;

    public function __construct($projects)
    {

        $this->projects = $projects;
    }

    public function collection()
    {
        return $this->projects->map(function ($project) {
            return [
                $project->group_company?->comp_name ?? 'N/A',
                $project->location?->site_name ?? 'N/A',
                $project->levels->count() . ' Levels',
                $project->status ?? 'N/A',
            ];
        });
    }




    public function headings(): array
    {
        return ['Company'	,'Location'	,'Levels',	'Status'];
    }
}
