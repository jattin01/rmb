<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Group Seeder
        DB::table('groups')->delete();
        
        $data = [
           [
            'id' => 1,
            'code' => 'RMB',
            'name' => 'RMB Readymix Concrete',
           ],
       ];

       DB::table('groups')->insert($data);
    }
}
