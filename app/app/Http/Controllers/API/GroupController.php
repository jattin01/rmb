<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ImageCollectionHelper;
use App\Http\Controllers\Controller;
use App\Models\Group;
use Exception;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function addImageIcon(Request $request, String $id)
    {
        try {
            $group = Group::find($id);
            if ($group -> hasMedia(ImageCollectionHelper::GROUP_IMAGE_COLLECTION)) {
                $group -> clearMediaCollection(ImageCollectionHelper::GROUP_IMAGE_COLLECTION);
            }
            $group->addMediaFromRequest('icon')->toMediaCollection(ImageCollectionHelper::GROUP_IMAGE_COLLECTION);
            return array(
                'message' => 'Done'
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
