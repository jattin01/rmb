<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'super.admin@antfast.com',
                'password' => 'AntFast@12345',
                'mobile_no' => '9876543210'
        ]);
        User::factory()->create([
                'name' => 'Admin One',
                'email' => 'super.admin_01@antfast.com',
                'password' => 'AntFast@12345',
                'mobile_no' => '9876543211'
        ]);
        User::factory()->create([
                'name' => 'Admin Two',
                'email' => 'super.admin_02@antfast.com',
                'password' => 'AntFast@12345',
                'mobile_no' => '9876543212'
        ]);
    }
}
