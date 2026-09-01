<?php

namespace App\Http\Controllers\API;

use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\TransitMixer;
use Illuminate\Http\Request;

class TransitMixerController extends Controller
{
    public function addIcon(Request $request, String $id)
    {
            $transitMixer = TransitMixer::find($id);
            if ($transitMixer -> hasMedia(ConstantHelper::TRANSIT_MIXER_IMG_COLLECTION)) {
                $transitMixer -> clearMediaCollection(ConstantHelper::TRANSIT_MIXER_IMG_COLLECTION);
            }
            $transitMixer -> addMediaFromRequest('image') -> toMediaCollection(ConstantHelper::TRANSIT_MIXER_IMG_COLLECTION);
            return array(
                'message' => 'Image Updated successfully'
            );
    }
}
