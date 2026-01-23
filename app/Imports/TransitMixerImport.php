<?php

namespace App\Imports;

use App\Helpers\ConstantHelper;
use App\Models\Driver;
use App\Models\Role;
use App\Models\TransitMixer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TransitMixerImport implements ToModel, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        $role = Role::where('name', ConstantHelper::USER_TYPE_DRIVER)->where('access_level', ConstantHelper::ACCESS_LEVEL_SYSTEM)->where('status', ConstantHelper::ACTIVE)->first();
                if($role){
                    $userRoleId = $role->id;
                }
                $user = User::create([
                    'group_id' => 1,
                    'name' => $row['driver_name'],
                    'username' => $row['email'],
                    'email' => $row['email'],
                    'password' => bcrypt($row['phone_no']),
                    'mobile_no' => $row['phone_no'],
                    'role_id' => $userRoleId ?? null,
                    'user_type' => ConstantHelper::USER_TYPE_DRIVER,
                    'status' => ConstantHelper::ACTIVE
                ]);
                $driver = Driver::create([
                    'user_id' => $user -> id,
                    'group_company_id' => 1,
                    'code' => $row['driver_code'],
                    'employee_code' => $row['driver_code'],
                    'name' => $row['driver_name'],
                    'email_id' => $row['email'],
                    'username' => $row['email'],
                    'user_role' => "driver",
                    'phone' => $row['phone_no'],
                    'license_no' => $row['license_number'],
                    'license_expiry' => date('Y-m-d', strtotime($row['expiry_date'])),
                    'status' => ConstantHelper::ACTIVE
                ]);

        return new TransitMixer([
            'group_company_id' => 1,
            'truck_name' => $row['asset_code'],
            'description' => $row['vehicle_number'],
            'registration_no' => $row['license_number'],
            'registration_expiry' => date('Y-m-d', strtotime($row['expiry_date'])),
            // 'driver_code' => $row['driver_code'],
            // 'driver_name' => $row['driver_name'],
            'truck_capacity' => 8,
            'loading_time' => 10
        ]);
    }

    public function chunkSize(): int
    {
        return 100; // Adjust this value based on your needs
    }
}
