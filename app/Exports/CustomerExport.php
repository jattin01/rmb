<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class CustomerExport implements FromCollection, WithHeadings
{
    protected $customers;

    public function __construct($customers)
    {
        $this->customers = $customers;
    }

    public function collection()
    {
        // Customize the export data, including the company name
        return $this->customers->map(function ($customer) {
            $companies ='';
            foreach ($customer->group_companies as $key=> $comp) {
                if($key==0){

                    $companies.=$comp->company->comp_name;
                }else{
                    $companies.= ','.$comp->company->comp_name;
                }

            }
            return [
                $customer->code,
                $customer->name,
                $customer->contact_person,
                $customer->mobile_no,
                $customer->email_id,
                $customer->status,
                $companies
            ];
        });
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Contact Person', 'Mobile No', 'Email ID', 'Status', 'Company'];
    }
}
