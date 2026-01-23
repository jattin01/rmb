<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this -> call(GroupSeeder::class);
        $this -> call(GroupCompanySeeder::class);
        $this -> call(CompanyLocationSeeder::class);
        $this -> call(LocationShiftSeeder::class);
        $this -> call(BatchingPlantSeeder::class);
        $this -> call(UserSeeder::class);
    }
}
