<?php

namespace App\Imports;

use App\Helpers\ConstantHelper;
use App\Models\Pump;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PumpImport implements ToModel, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        return new Pump([
            'group_company_id' => 1,
            'pump_name' => $row['pump_code'],
            'description' => $row['pump_desc'],
            'pump_capacity' => 42
        ]);
    }

    public function chunkSize(): int
    {
        return 100; // Adjust this value based on your needs
    }
}
