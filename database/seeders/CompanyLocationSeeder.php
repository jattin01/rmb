<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('company_locations')->delete();

        $data = [
           [
            'id' => 1,
            'group_company_id' => '1',
            'location' => 'JEBEL ALI',
            'site_name' => 'Business Bay',
            'distance' => '20',
            'time_1to6' => '15',
            'time_6to9' => '30',
            'time_9to12' => '40',
            'time_12to15' => '30',
            'time_15to18' => '40',
            'time_18to21' => '30',
            'time_21to1' => '15',
           ],
           [
            'id' => 2,
            'group_company_id' => '1',
            'location' => 'AL QUSAIS',
            'site_name' => 'Al Qusais',
            'distance' => '10',
            'time_1to6' => '10',
            'time_6to9' => '20',
            'time_9to12' => '25',
            'time_12to15' => '20',
            'time_15to18' => '25',
            'time_18to21' => '20',
            'time_21to1' => '10',
           ],
           [
            'id' => 3,
            'group_company_id' => '1',
            'location' => 'AL NAHDA',
            'site_name' => 'Al Nahda',
            'distance' => '25',
            'time_1to6' => '20',
            'time_6to9' => '40',
            'time_9to12' => '45',
            'time_12to15' => '30',
            'time_15to18' => '40',
            'time_18to21' => '30',
            'time_21to1' => '20',
           ],
           [
            'id' => 4,
            'group_company_id' => '1',
            'location' => 'DOWN TOWN',
            'site_name' => 'Down town',
            'distance' => '15',
            'time_1to6' => '15',
            'time_6to9' => '25',
            'time_9to12' => '30',
            'time_12to15' => '25',
            'time_15to18' => '30',
            'time_18to21' => '25',
            'time_21to1' => '15',
           ],
       ];

       DB::table('company_locations')->insert($data);
    }
}
