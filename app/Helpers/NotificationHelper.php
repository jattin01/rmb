<?php

namespace App\Helpers;
use App\Models\Customer;
use App\Models\CustomerTeamMember;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class NotificationHelper
{
    public static function sendToCustomers(int $customerId, int $projectId, string $messageBody, string $messageTitle) : void
    {
        $customer = Customer::find($customerId);
        if (isset($customer)) {
            $customerTeamMembers = CustomerTeamMember::where('customer_id', $customer -> id) -> where('status', ConstantHelper::ACTIVE) -> where(function ($whereQuery) use($projectId) {
                $whereQuery -> whereHas('access_rights', function ($query) use($projectId) {
                    $query -> where('customer_project_id', $projectId);
                }) -> orWhere('is_admin', 1);
            })  -> with('user') -> get();
            foreach ($customerTeamMembers as $customerTeamMember) {
                if (isset($customerTeamMember -> user -> device_token)) {
                    CommonHelper::sendNotification($customerTeamMember -> user -> device_token, $messageTitle, $messageBody);
                }
            }
        }
    }

}
