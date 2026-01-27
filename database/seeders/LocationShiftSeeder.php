<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('location_shifts')->delete();

        $data = [
           [
            'group_company_id' => '1',
            'company_location_id' => '1',
            'shift_start' => '8:00:00',
            'shift_end' => '7:59:00',
           ],
           [
            'group_company_id' => '1',
            'company_location_id' => '2',
            'shift_start' => '8:00:00',
            'shift_end' => '7:59:00',
           ],
       ];

       DB::table('location_shifts')->insert($data);
    }
}
