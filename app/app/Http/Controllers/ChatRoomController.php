<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Models\ChatRoom;
use App\Models\CustomerProject;
use Exception;
use Illuminate\Http\Request;
use View;

class ChatRoomController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $groupCompanyIds = $user->access_rights->pluck('group_company_id');
            $projectIds = CustomerProject::select('id', 'name', 'customer_id')->whereHas('customer', function ($query) use ($groupCompanyIds) {
                $query->whereHas('group_companies', function ($subQuery) use ($groupCompanyIds) {
                    $subQuery->whereIn('group_company_id', $groupCompanyIds);
                });
            })->get()->pluck('id');
            $rooms = ChatRoom::select('id', 'project_id', 'entity_id', 'entity_type', 'status')->whereIn('project_id', $projectIds)->get();
            $html = View::make('layouts.auth.partials.chat_list', ['rooms' => $rooms])->render();
            return response()->json([
                'html' => $html
            ]);
        } catch (Exception $ex) {
            throw new ApiGenericException($ex->getMessage());
        }
    }
    public function getRoomIds(Request $request)
    {
        try {
            $user = auth()->user();
            $groupCompanyIds = $user->access_rights->pluck('group_company_id');
            $projectIds = CustomerProject::select('id', 'name', 'customer_id')->whereHas('customer', function ($query) use ($groupCompanyIds) {
                $query->whereHas('group_companies', function ($subQuery) use ($groupCompanyIds) {
                    $subQuery->whereIn('id', $groupCompanyIds);
                });
            })->get()->pluck('id');
            $rooms = ChatRoom::select('id', 'project_id', 'entity_id', 'entity_type', 'status')->whereIn('project_id', $projectIds)->get();
            return response()->json([
                'rooms' => $rooms
            ]);
        } catch (Exception $ex) {
            throw new ApiGenericException($ex->getMessage());
        }
    }

    public function getChatRoomDetails(Request $request, String $roomId)
    {
        try {
            $room = ChatRoom::find($roomId);
            $html = View::make('layouts.auth.partials.chat', ['room' => $room])->render();
            return response()->json([
                'html' => $html
            ]);
        } catch (Exception $ex) {
            throw new ApiGenericException($ex->getMessage());
        }
    }

    public function getDriverChatroom( $project_id)
    {

        $room = ChatRoom::with(['entity','project'])->where('project_id', $project_id)->where('entity_type', 'Driver')->get();


        return response()->json([

            'data' => $room
        ]);
    }
}
