<?php
namespace App\Helpers;

use App\Models\CompanyLocation;
use App\Models\CustomerProjectSite;
use App\Models\LiveOrder;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Support\Collection;

class FirestoreHelper
{
    protected $firestore;

    public function __construct()
    {
        $this->firestore = app('firebase.firestore');
    }

    //Notifications setup for live order
    public function createBulkDocuments(Collection $documents)
    {
        $collection = $this->firestore->database()->collection(ConstantHelper::LIVE_ORDER_TRIPS_FIRESTORE_COLLECTION);

        $oldDocuments = $collection->documents();
        foreach ($oldDocuments as $document) {
            $document->reference()->delete();
        }

        $batch = $this->firestore->database()->batch();
        foreach ($documents as $document) {
            $docRef = $collection->newDocument();
            $batch->set($docRef, $document);
        }
        $batch->commit();
    }

    //Function to create or update driver locations firestore collection for live tracking
    public function createOrUpdateDriverLocations(array $document, LiveOrder $liveOrder, String $collectionName, String $subCollectionName)
    {
        $documentReference = $this->firestore->database()->collection($collectionName)->document($subCollectionName);
        $snapshot = $documentReference -> snapshot();
        //Order has been started , only add a new driver
        if ($snapshot -> exists())
        {
            $driverSubCollection = $documentReference -> collection('drivers');
            $driverId = $document['driver_id'];
            $driverSubCollection -> document($driverId) -> set($document);
        } 
        //Order has not started , first trip - so create complete document
        else 
        {
            $endLocation = CustomerProjectSite::find($liveOrder -> site_id);
            $startLocation = CompanyLocation::find($liveOrder -> company_location_id);
            $documentReference -> set([
                'start_point' => array(
                    'latitude' => $startLocation -> latitude,
                    'longitude' => $startLocation -> longitude,
                ),
                'end_point' => array(
                    'latitude' => $endLocation -> latitude,
                    'longitude' => $endLocation -> longitude,
                ),
            ]);
            $driverSubCollection = $documentReference -> collection('drivers');
            $driverId = $document['driver_id'];
            $driverSubCollection -> document($driverId) -> set($document);
        }

    }

    //Function to update current activity of driver along with map visibility on customer
    public function updateCurrentActivity(String $collectionName, String $documentId, String $subCollectionName, int $subDocumentId, String $currentActivity, bool $isShowOnMap)
    {
        //Get the main collection
        $documentReference = $this->firestore->database()->collection($collectionName)->document($documentId);
        $snapshot = $documentReference->snapshot();
        if ($snapshot -> exists())
        {
            //If main collection exists , check for driver sub collection
            $driverSubCollection = $documentReference -> collection($subCollectionName);
            $subDocumentReference = $driverSubCollection -> document($subDocumentId);
            $subDocumentSnapshot = $subDocumentReference -> snapshot();
            if ($subDocumentSnapshot -> exists())
            {
                //If driver sub collection exists, update current activity
                $subDocumentReference -> update([
                    ['path' => 'current_activity', 'value' => $currentActivity],
                    ['path' => 'is_show_on_map', 'value' => $isShowOnMap],
                ]);
            }
        }
    }

    //Function to remove the driver after fully completing the trip
    public function removeDriver(String $collectionName, String $documentId, String $subCollectionName, int $subDocumentId)
    {
        //Get the main collection
        $documentReference = $this->firestore->database()->collection($collectionName)->document($documentId);
        $snapshot = $documentReference->snapshot();
        if ($snapshot -> exists())
        {
            //If main collection exists , check for driver sub collection
            $driverSubCollection = $documentReference -> collection($subCollectionName);
            $subDocumentReference = $driverSubCollection -> document($subDocumentId);
            $subDocumentSnapshot = $subDocumentReference -> snapshot();
            if ($subDocumentSnapshot -> exists())
            {
                //If driver sub collection exists, delete it
                $subDocumentReference -> delete();
            }
        }
    }
}
