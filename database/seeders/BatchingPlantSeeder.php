<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BatchingPlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('batching_plants')->delete();

        $data = [
           [
            'group_company_id' => '1',
            'company_location_id' => '1',
            'plant_name' => 'JA01',
            'long_name' => 'JA01',
            'capacity' => '100',
           ],
           [
            'group_company_id' => '1',
            'company_location_id' => '2',
            'plant_name' => 'AQ01',
            'long_name' => 'AQ01',
            'capacity' => '100',
           ],
           [
            'group_company_id' => '1',
            'company_location_id' => '1',
            'plant_name' => 'JA02',
            'long_name' => 'JA02',
            'capacity' => '150',
           ],
       ];

       DB::table('batching_plants')->insert($data);
    }
}
