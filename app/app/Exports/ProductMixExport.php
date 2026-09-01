<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductMixExport implements FromCollection, WithHeadings
{
    protected $projects;

    public function __construct($projects)
    {

        $this->projects = $projects;
    }

    public function collection()
    {
        return $this->projects->map(function ($project) {
            // Create a comma-separated list of structural names
            $structuralNames = $project->structuralReferences->map(function ($reference) {
                return $reference->structural->name ?? 'N/A';
            })->implode(', ');

            // Return the desired structure
            return [
                $project->group_company?->comp_name ?? 'N/A',
                $structuralNames,
                $project->code,
                $project->name,
                $project->product_type->type ?? 'N/A',
                $project->density ?? 'N/A',
                $project->usage ?? 'N/A',
                $project->status ?? 'N/A',
            ];
        });
    }



    public function headings(): array
    {
        return [
            'Company',
            'Structure',
            'Mix Code',
            'Mix Name',
            'Mix Type',
            'Density',
            'Usage',
            'Status'
        ];
    }
}



