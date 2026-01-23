<?php

namespace Database\Seeders;

use App\Models\StructuralReference;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StructuralReferencesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StructuralReference::insert([
            ['group_company_id' => 1,'name' => 'Raft'],
            ['group_company_id' => 1,'name' => 'Column']
        ]);
    }
}
