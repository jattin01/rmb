<?php

namespace App\Helpers;

use Carbon\Carbon;
use Http;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CommonHelper
{
    public static function groupBy(array $array, $groupByKey)
    {
        return array_reduce($array, function ($result, $item) use ($groupByKey) {
            $key = $item[$groupByKey];
            if (!isset($result[$key])) {
                $result[$key] = [];
            }
            $result[$key][] = $item;
            return $result;
        }, []);
    }

    public static function searchAndUpdateArray($arrayOfArrays, $searchCriteria, $updateValues) 
    {
        $match = false;
        foreach ($arrayOfArrays as &$innerArray) {
            foreach ($searchCriteria as $key => $value) {
                if (!isset($innerArray[$key]) || $innerArray[$key] !== $value) {
                    $match = false;
                    break;
                } else {
                    $match = true;
                }
            }
            if ($match) {
                // Update the matched array with new values
                foreach ($updateValues as $key => $value) {
                    $innerArray[$key] = is_array($value) ? $innerArray[$key] + Carbon::parse($innerArray['pouring_end']) -> diffInMinutes(Carbon::parse($value['value'])) : $value;
                }
                break;
            }
        }
        return ['data' => $arrayOfArrays, 'match' => $match];
    }

    public static function searchAndUpdateArrayReSch($arrayOfArrays, $searchCriteria, $updateValues) 
    {
        $match = false;
        foreach ($arrayOfArrays as &$innerArray) {
            foreach ($searchCriteria as $key => $value) {
                if (!isset($innerArray[$key]) || $innerArray[$key] !== $value) {
                    $match = false;
                    break;
                } else {
                    $match = true;
                }
            }
            if ($match) {
                // Update the matched array with new values
                foreach ($updateValues as $key => $value) {
                    $innerArray[$key] = is_array($value) ? $innerArray[$key] + Carbon::parse($innerArray['expected_pouring_end']) -> diffInMinutes(Carbon::parse($value['value'])) : $value;
                }
                break;
            }
        }
        return ['data' => $arrayOfArrays, 'match' => $match];
    }
    public static function sortByDate($a , $b, $key) 
    {
        return $a[$key] <=> $b[$key];
    }

    public static function divideTimeEqually(string $startTime, string $endTime, string $scheduleDate): array
    {
        // Parse start and end times
        $startDateTime = Carbon::parse($scheduleDate . " " . $startTime);
        $endDateTime = Carbon::parse($scheduleDate . " " .  $endTime);
        $endDateTime->addDay();

        $totalSlots = ConstantHelper::SCHEDULE_GRAPH_SLOT_HOURS;

        // Calculate the total time difference in minutes
        $totalMinutes = $endDateTime->diffInMinutes($startDateTime);

        // Calculate the duration for each slot
        $slotDuration = $totalMinutes / $totalSlots;

        // Initialize an array to store the slot start and end times
        $slots = [];

        // Calculate and store each slot start and end time
        for ($i = 0; $i < $totalSlots; $i++) {
            $slotStart = $startDateTime->copy()->addMinutes($i * $slotDuration);
            $slotEnd = $startDateTime->copy()->addMinutes(($i + 1) * $slotDuration);

            // Store the slot start and end times in the array
            $slots[] = [
                'start_time' => $slotStart->format('h A'),
                'end_time' => $slotEnd->format('h A'),
                'start_time_date' => $slotEnd->format('Y-m-d h A'),
                'end_time_date' => $slotEnd->format('Y-m-d h A'),
            ];
        }

        return $slots;
    }

    public static function generateOtp() : int
    {
        return rand(1234, 9999);
    }

    public static function getBackgroundColorForChatUser() {
        // Step 1: Convert the name to a numerical value
        $randomColorIndex = rand(0, count(ConstantHelper::CHAT_USER_COLORS));
        return isset(ConstantHelper::CHAT_USER_COLORS[$randomColorIndex]) ? ConstantHelper::CHAT_USER_COLORS[$randomColorIndex] : "";
    }

    public static function getNameInititals(string $name) : string
    {
	    return '';
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper($word[0]);
        }
        return $initials;
    }

    public static function getMinutesToHumanDiff(int $minutes) : string
    {
        if ($minutes == 0) {
            return "";
        } else if ($minutes < 60) {
            // Less than 60 minutes, return the minutes
            return $minutes . ' min' . ($minutes > 1 ? 's' : '');
        } else if ($minutes < 1440) {
            // Between 61 minutes and 1440 (i.e., less than a day), return hours and minutes
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return $hours . ' hr' . ($hours > 1 ? 's' : '') . 
                   ($remainingMinutes ? ' ' . $remainingMinutes . ' min' . ($remainingMinutes > 1 ? 's' : '') : '');
        }  else {
            // More than or equal to 1440 minutes (i.e., 1 day or more), return days, hours, and minutes
            $days = floor($minutes / 1440);
            $remainingMinutes = $minutes % 1440;
            $hours = floor($remainingMinutes / 60);
            $minutesLeft = $remainingMinutes % 60;
            return $days . ' day' . ($days > 1 ? 's' : '') . 
                ($hours ? ' ' . $hours . ' hr' . ($hours > 1 ? 's' : '') : '') . 
                ($minutesLeft ? ' ' . $minutesLeft . ' min' . ($minutesLeft > 1 ? 's' : '') : '');
        }
    }

    public static function getDeviationStringType(int $deviation)
    {
        if ($deviation < 0) {
            return "Early";
        } else if ($deviation == 0) {
            return "";
        } else {
            return "Delay";
        }
    }

    public static function sendNotification(string $fcmToken, string $title, string $body) : void
    {
        $apiURL = config('app.fcm_base_url') . '/v1/projects/' . config('app.firebase_project_id') . '/messages:send';
        $authToken = FirebaseHelper::getOauthAccessToken();
        // Notification payload
        $data = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'body' => $body,
                    'title' => $title
                ]
            ],
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $authToken, // Set FCM Server Key
            'Content-Type'  => 'application/json',  // Send as JSON
        ])->post($apiURL, $data);

        dd($response -> json());
    }
    
}
