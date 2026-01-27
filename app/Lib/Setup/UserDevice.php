<?php

namespace App\Lib\Setup;

use App\Models\UserDevice as ModelsUserDevice;
use Illuminate\Http\Request;
use Auth;

class UserDevice
{

    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function store()
    {
    
        $userDevice = new ModelsUserDevice();
        $userDevice->device_id = $this->request->device_id;
        $userDevice->device_name = $this->request->device_name;
        if(auth()->check()) {
          
            $userDevice->user_id  = Auth::user()->id;
        }
        $userDevice->save();

        return $userDevice;
    }
}