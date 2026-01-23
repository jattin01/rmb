<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('group_companies')->delete();

        $data = [
           [
            'group_id' => '1',
            'comp_code' => 'RMB DUBAI',
            'comp_name' => 'RMB DUBAI',
            'working_hrs_s' => '8:00:00',
            'working_hrs_e' => '7:59:00',
           ],
       ];

       DB::table('group_companies')->insert($data);
    }
}
