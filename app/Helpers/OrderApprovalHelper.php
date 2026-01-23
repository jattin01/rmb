<?php

namespace App\Helpers;

use App\Models\ApprovalSetup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as DatabaseRowCollection;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class OrderApprovalHelper
{
    public static function canApproveVerticalApproval(int $currentUserApprovalLevel, int $currentUserId, DatabaseRowCollection $approvalLevels, DatabaseRowCollection $approvedStatuses, ApprovalSetup $approvalSetup): bool
    {
        //Check if levels less than the current exists 
        $previousApprovalLevels = $approvalLevels->filter(function ($level) use ($currentUserApprovalLevel) {
            return ($level->level_no < $currentUserApprovalLevel);
        });
        //Retrieve all the user Ids wit level
        $userIdWithLevels = [];
        foreach ($previousApprovalLevels as $prevLevel) {
            $userIdWithLevels[] = [
                'user_id' => $prevLevel->user_id,
                'level_no' => $prevLevel->level_no
            ];
        }
        //Preivous approval exists (Now check if they have been approved or not)
        if (count($userIdWithLevels) > 0) {
            $canApprove = false;
            $canApprovePrevLevel = null;
            foreach ($userIdWithLevels as $userIdLevel) {
                if ($userIdLevel['user_id'] !== $currentUserId) {
                    $currentApprovalStatus = $approvedStatuses->firstWhere('approved_by', $userIdLevel['user_id']);
                    if ($approvalSetup->approval_level_users === "All") {
                        if (isset($currentApprovalStatus)) {
                            $canApprove = true;
                        } else {
                            $canApprove = false;
                        }
                    } else {
                        if ($userIdLevel['level_no'] != $canApprovePrevLevel) {
                            $canApprove = false;
                        } else {
                            if ($canApprove)
                                continue;
                        }
                        if (isset($currentApprovalStatus)) {
                            $canApprove = true;
                        } else {
                            $canApprove = false;
                        }
                    }
                }
            }
            return $canApprove;
        } else { //No previous level exists , direct approval
            return true;
        }
    }

    public static function canApprove(int $currentUserApprovalLevel, int $currentUserId, DatabaseRowCollection $approvalLevels, DatabaseRowCollection $approvedStatuses): bool
    {
        //Check if levels less than the current exists 
        $previousApprovalLevels = $approvalLevels->filter(function ($level) use ($currentUserApprovalLevel) {
            return ($level->level_no < $currentUserApprovalLevel);
        });
        //Preivous approval exists (Now check if they have been approved or not)
        if (count($previousApprovalLevels) > 0) {
            $canApprove = false;
            foreach ($previousApprovalLevels as $approvalLevel) {
                if ($approvalLevel -> user_id !== $currentUserId) {
                    $currentApprovalStatus = $approvedStatuses->firstWhere('approved_by', $approvalLevel -> user_id);
                    if (isset($currentApprovalStatus)) {
                        $canApprove = true;
                    } else {
                        $canApprove = false;
                    }
                }
            }
            return $canApprove;
        } else { //No previous level exists , direct approval
            return true;
        }
    }
}