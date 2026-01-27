<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GroupCompany;
use Illuminate\Http\Request;

class GroupCompanyController extends Controller
{
    public function addIcon(Request $request, String $id)
    {
        $groupCompany = GroupCompany::find($id);
        $groupCompany->addMediaFromRequest('icon')->toMediaCollection('icon');
        return array(
            'message' => 'Done'
        );

    }
}
