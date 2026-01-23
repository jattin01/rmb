

<div class="modal fade filter-modal order-model" id="liveTripTrackDetails" tabindex="-1" role="dialog"
aria-labelledby="exampleModalLabel" aria-hidden="true">
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header border-0">
            <h5 class="modal-title" id="exampleModalLabel"></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <img src="img/filter-close.svg" alt=""> close
            </button>
        </div>
        <div class="modal-body">
            @if ($trip)
            <div class="filter-contentbox">
                <h6>Trip Details</h6>
            </div>


            {{-- <div class="order-map mt-3">
                <iframe class="order-mapinner"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14025.378893509162!2d77.39042522978897!3d28.49927446945861!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390ce8609722a14b%3A0x31318b8199ad8290!2sSector%20135%2C%20Noida%2C%20Uttar%20Pradesh!5e0!3m2!1sen!2sin!4v1697536963022!5m2!1sen!2sin"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                <div class="fastest-routebox">
                    <span> 30 min (32 km) </span> Fastest route now due to traffic conditions
                </div>
            </div> --}}
                 <div class="order-map mt-3" id="map">

                </div>

            <div class="order-details mt-4">
                <div class="row">
                    <div class="col-md-6 mb-sm-0 mb-2">
                        <div class="order-detailscontent">
                            <h1>Order Details</h1>

                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <p>Order No</p>
                                    <h6>{{ $trip->order->order_no ?? 'N/A' }}</h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p>Quantity</p>
                                    <h6>{{$trip->order->quantity ?? 'N/A'}}</h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p>Batching Plant</p>
                                    <h6>{{$trip->batching_plant_detail->plant_name ?? 'N/A'}}</h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p>location</p>
                                    <h6>{{$trip->order->location ?? 'N/A'}}</h6>
                                </div>
                            </div>

                            <div class="row mt-3 align-items-center">
                                <div class="col-md-4 col-4">
                                    <div class="trip-userbox">
                                        <img src="{{$trip?->driver_details?->user?->profile_icon}}" alt="">
                                    </div>
                                </div>
                                <div class="col-md-8 col-8 pl-0">
                                    <h3>{{$trip->driver_details?->name}}</h3>
                                    <h4>{{$trip->transit_mixer_detail?->registration_no}}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">

                        <div class="customer-infotext ">
                            <div class="customer-infotextinner pb-sm-5">
                                <h6>Customer Info</h6>

                                <div class="customer-timeline mb-sm-3">

                                    <div class="customer-timelineinner">
                                        <p>Company</p>
                                        <h4>{{$trip -> order->customer_company_name()}}</h4>
                                    </div>
                                    <div class="customer-timelineinner">
                                        <p>Project</p>
                                        <h4>{{$trip -> order->project_detail ?-> name}}</h4>
                                    </div>
                                    <div class="customer-timelineinner">
                                        <p>Site Location</p>
                                        <h4>{{$trip ->order->customer_company_name()}}</h4>
                                        <h4>{{$trip ->order-> customer_site ?-> name}}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="mix-details mt-sm-5 mt-3">
                <h2>Trip Status</h2>

                <div class="time-linescroll mt-3">
                    <div class="line_box">
                        <div class="text_circle {{$trip->actual_loading_time ? 'done': ''}}">
                            <a class="tvar active"><span></span></a>
                            <div class="circle">
                                <p>Loading</p>
                                <h4 class="text-muted">{{ \Carbon\Carbon::parse($trip->planned_loading_start)->format('h:iA') }}</h4>

                                <div class=" {{$trip->actual_loading_time ? 'subline_bold': 'subline'}}" >
                                    <h6> {{$trip->actual_loading_time?$trip->actual_loading_time: $trip->planned_loading_time}} min</h6>
                                </div>
                            </div>
                        </div>
                        <div class="text_circle {{$trip->actual_qc_time ? 'done': ''}}">
                            <a class="tvar active"><span></span></a>
                            <div class="circle">
                                <p>QC</p>
                                <h4 class="text-muted">{{ \Carbon\Carbon::parse($trip->planned_qc_start)->format('h:iA') }}</h4>
                                <div class=" {{$trip->actual_qc_time ? 'subline_bold': 'subline'}}" >
                                     <h6>{{ $trip->actual_qc_time? $trip->actual_qc_time :$trip->planned_qc_time}} min</h6>
                                </div>
                            </div>
                        </div>
                        <div class="text_circle {{ $trip->actual_travel_time ? 'done': ''}}">
                            <a class="tvar active"></a>
                            <div class="circle">

                                <p>To Site</p>

                                <h4 class="text-muted">{{ \Carbon\Carbon::parse($trip->planned_travel_start)->format('h:iA') }}</h4>
                                <div class=" {{$trip->actual_travel_time ? 'subline_bold': 'subline'}}" >
                                    <h6>{{ $trip->actual_travel_time ?$trip->actual_travel_time:$trip->planned_travel_time}} min</h6>
                                </div>
                            </div>
                        </div>
                        <div class="text_circle {{$trip->actual_insp_time ? 'done': ''}}">

                            <a class="tvar active"></a>
                            <div class="circle">
                                <p>Inspaction</p>

                                <h4 class="text-muted">{{ \Carbon\Carbon::parse($trip->planned_insp_start)->format('h:iA') }}</h4>
                                <div class=" {{$trip->actual_insp_time ? 'subline_bold': 'subline'}}" >
                                    <h6>{{ $trip->actual_insp_time ?$trip->actual_insp_time : $trip->planned_insp_time}} min</h6>
                                </div>
                            </div>
                        </div>
                        <div class="text_circle {{$trip->actual_pouring_time? 'done': ''}}">
                            <a class="tvar"></a>
                            <div class="circle">
                                <p>Pouring</p>
                                <h4 class="text-muted">{{ \Carbon\Carbon::parse($trip->planned_pouring_start)->format('h:iA') }}</h4>
                                <div class=" {{$trip->actual_pouring_time ? 'subline_bold': 'subline'}}" >
                                    <h6>{{$trip->actual_pouring_time ?$trip->actual_pouring_time : $trip->planned_pouring_time}} min</h6>
                                   </div>
                            </div>
                        </div>
                        <div class="text_circle {{$trip->actual_cleaning_time ? 'done': ''}}">

                            <a class="tvar"></a>
                            <div class="circle">
                                <p>Cleaning</p>
                                <h4 class="text-muted">{{ \Carbon\Carbon::parse($trip->planned_cleaning_start)->format('h:iA') }}</h4>
                                <div class=" {{$trip->actual_cleaning_time ? 'subline_bold': 'subline'}}" >
                                    <h6>{{$trip->actual_cleaning_time ? $trip->actual_cleaning_time  :$trip->planned_cleaning_time}}  min</h6>
                                    </div>
                            </div>
                        </div>
                        <div class="text_circle {{$trip->actual_return_time ? 'done': ''}}">
                            <a class="tvar"></a>
                            <div class="circle">

                                <p>To Plant</p>
                                <h4 class="text-muted">{{ \Carbon\Carbon::parse($trip->planned_return_start)->format('h:iA') }}</h4>

                                {{-- <div class=" {{$trip->actual_return_time ? 'subline_bold': 'subline'}}" >
                                    <h6> {{$trip->actual_return_time ? $trip->actual_return_time:$trip->planned_return_time}} </h6>
                                    </div> --}}
                            </div>
                        </div>
                    </div>
                    {{-- new --}}
                    <div class="line_box">
                        <div class="text_circle_new">
                            <a class=""><span></span></a>
                            <div>
                                <p style="font-weight :bold;">{{ isset($trip->actual_loading_start)?\Carbon\Carbon::parse($trip->actual_loading_start)->format('h:iA'): '' }}</p>
                            </div>
                        </div>
                        <div class="text_circle_new">
                            <a class=""><span></span></a>
                            <div>
                                <p style="font-weight :bold;" >{{ isset($trip->actual_qc_start)? \Carbon\Carbon::parse($trip->actual_qc_start)->format('h:iA'):'' }}</p>
                            </div>
                        </div>
                        <div class="text_circle_new">
                            <a class=""><span></span></a>
                            <div>
                                <p style="font-weight :bold;"> {{ isset($trip->actual_travel_start) ?\Carbon\Carbon::parse($trip->actual_travel_start)->format('h:iA') :''}}</p>
                            </div>
                        </div>
                        <div class="text_circle_new">
                            <a class=""><span></span></a>
                            <div>
                                <p style="font-weight :bold;">{{ isset($trip->actual_insp_start)? \Carbon\Carbon::parse($trip->actual_insp_start)->format('h:iA'):'' }}</p>
                            </div>
                        </div>
                        <div class="text_circle_new">
                            <a class=""><span></span></a>
                            <div>
                                <p style="font-weight :bold;">{{ isset($trip->actual_pouring_start)?\Carbon\Carbon::parse($trip->actual_pouring_start)->format('h:iA'):'' }}</p>
                            </div>
                        </div>
                        <div class="text_circle_new">
                            <a class=""><span></span></a>
                            <div>
                                <p style="font-weight :bold;"> {{ isset($trip->actual_cleaning_start)? \Carbon\Carbon::parse($trip->actual_cleaning_start)->format('h:iA') :''}}</p>
                            </div>
                        </div>

                        <div class="text_circle_new">
                            <a class=""><span></span></a>
                            <div>
                                <p style="font-weight :bold;">{{isset($trip->actual_return_time)? \Carbon\Carbon::parse($trip->actual_return_time)->format('h:iA'):'' }}</p>
                            </div>
                        </div>
                    </div>
                </div>  
            </div>
                 @else
                <div class="loader"></div>
                @endif
        </div>
    </div>
</div>
</div>


<script>

    var currentSelectedTruck = null;
    var map;
    var mapZoom = 8;
    var mapInitialized = false; // Flag to check if map is initialized
    var startMarker;
    var endMarker;
    var truckMarkers = [];
    var directionsService;
    var directionsRenderer;

    var truckImages = [];

    var startPosition = null;
    var endPosition = null;

    var truckDirectionService = null;
    var truckDirectionRenderer = null;

    var unsubscribe; // Variable to hold the Firestore unsubscribe function

    function scriptInitialization() {

        currentSelectedTruck = null;
        map = null;
        mapInitialized = false; // Flag to check if map is initialized
        startMarker = null;
        endMarker = null;
        truckMarkers = [];
        directionsService = null;
        directionsRenderer = null;

        startPosition = null;
        endPosition =  null;

        truckDirectionService = new google.maps.DirectionsService();
        truckDirectionRenderer = new google.maps.DirectionsRenderer({
            map: map,
            polylineOptions: {
                strokeColor: '#37009b',
                strokeWeight: 4,
            },
            suppressMarkers: true,
            preserveViewport : true
        });

        unsubscribe = null; // Variable to hold the Firestore unsubscribe function

        initMap();
    }

    // Step 4: Initialize the Map
    function initMap() {
        if (!mapInitialized) {
            mapInitialized = true;
            //Create google map
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: mapZoom,
                mapId : 'LIVE_TRACKER_ORDER_MAP'
            });
            //Route setup
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                polylineOptions: {
                    strokeColor: 'black',
                    strokeWeight: 4,
                },
                suppressMarkers: true,
                preserveViewport : true
            });
            // Start Point
            let startLat = "{{isset($start_point['latitude']) ? $start_point['latitude'] : null}}";
            let startLng = "{{isset($start_point['longitude']) ? $start_point['longitude'] : null}}";
            if (startLat && startLng) {
                startPosition = new google.maps.LatLng(startLat, startLng);
                if (startMarker) {
                    startMarker.position = (startPosition);
                } else {
                    // A marker with a with a URL pointing to a PNG.
                    var startPointImage = document.createElement("img");
                    startPointImage.src = "{{asset('assets/img/start_point_map.svg')}}";

                    startMarker = new google.maps.marker.AdvancedMarkerElement({
                        position: startPosition,
                        map: map,
                        title: "{{$start_point['name']}}",
                        content: startPointImage
                    });
                }
            }

            // End Point
            let endLat = "{{isset($end_point['latitude']) ? $end_point['latitude'] : null}}";
            let endLng = "{{isset($end_point['longitude']) ? $end_point['longitude'] : null}}";
            if (endLat && endLng) {
                endPosition = new google.maps.LatLng(endLat, endLng);
                if (endMarker) {
                    endMarker.position = (endPosition);
                } else {
                    // A marker with a with a URL pointing to a PNG.
                    var endPointImage = document.createElement("img");
                    endPointImage.src = "{{asset('assets/img/end_point_map.svg')}}";

                    endMarker = new google.maps.marker.AdvancedMarkerElement({
                        position: endPosition,
                        map: map,
                        title: "{{$end_point['name']}}",
                        content: endPointImage
                    });
                }
            }


            // Draw Route
            let request = {
                origin: startPosition,
                destination: endPosition,
                travelMode: google.maps.TravelMode.DRIVING
            };
            directionsService.route(request, (result, status) => {
                if (status == 'OK') {
                    directionsRenderer.setDirections(result);
                    // Fit the map to the bounds of the route
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend(startPosition);
                    bounds.extend(endPosition);
                    map.fitBounds(bounds);

                }
            });


        }
    }

    function startRealtimeTracking() {
        // Step 5: Fetch Data in Real-Time
        unsubscribe = myFirebaseDb.collection("driver_locations").doc("orderId_" + "{{$trip->order-> id}}").collection('drivers').onSnapshot((doc) => {
        // console.log('sd');


            if (true) {
                let data = doc;
                let index = -1;
                let driverIds = [];

                // Trucks
                data.forEach((truckDocument) => {
                        index = index + 1;
                        let truck = truckDocument.data();
                        let currentTripId = "{{$trip->id}}"
                        if (currentTripId == truck.id) {
                            if (!(truckImages.length > 0 && truckImages.length === data.size))
                            {
                                if (!truckImages || truckImages.length === 0) {
                                    truckImages = [];
                                }

                                let truckImage = document.createElement('img');
                                truckImage.id = "Truck-" + index;
                                truckImage.src = "{{asset('assets/img/truck_icon.svg')}}";

                                let selectedTruckImage = document.createElement('img');
                                selectedTruckImage.id = "STruck-" + index;
                                selectedTruckImage.src = "{{asset('assets/img/selected_truck_icon.svg')}}";

                                truckImages.push({
                                    image : truckImage,
                                    selectedImage : selectedTruckImage
                                });

                            }
                            //Truck position
                                let truckLat = truck.transit_mixer_location.latitude;
                                let truckLng = truck.transit_mixer_location.longitude;
                                let truckPosition = new google.maps.LatLng(truckLat, truckLng);

                                const truckIndex = truckMarkers.findIndex(truckMarkerSingle => truckMarkerSingle.driver_id == truck.driver_id);
                                if (truckIndex != -1) {
                                    truckMarkers[truckIndex].marker.position = (truckPosition);
                                } else {
                                    let truckMarker = new google.maps.marker.AdvancedMarkerElement({
                                        position: truckPosition,
                                        map: map,
                                        title: truck?.transit_mixer_detail?.truck_name + ", Plate No - " + truck?.transit_mixer_detail?.registration_no + ", Driver - " + truck?.driver_details?.name + " (" + truck?.current_activity + ")",
                                        content : currentSelectedTruck === index ? truckImages[index].selectedImage : truckImages[index].image
                                    });

                                    // Add click event listener to marker
                                    truckMarker.addListener('click', () => {


                                        // Action to perform when marker is clicked
                                        currentSelectedTruck = truckMarkers.findIndex(truckMarkerSingle => truckMarkerSingle.driver_id == truck.driver_id);
                                        truckMarkers.forEach((currentTruck, idx) => {
                                            const currentTruckImages = truckImages && truckImages[idx];

                                            const newContent = currentSelectedTruck === idx
                                            ? currentTruckImages?.selectedImage
                                            : currentTruckImages?.image;
                                            if (newContent) {
                                            currentTruck.marker.content = newContent;
                                            }
                                        });

                                        updateMainPolyline(startPosition, endPosition, truckPosition, directionsService, directionsRenderer);

                                    });

                                    truckMarkers.push({
                                        driver_id : truck.driver_id,
                                        marker : truckMarker
                                    });
                                }
                            driverIds.push(truck.driver_id);
                        }



                });

                if (currentSelectedTruck !== null && truckMarkers[currentSelectedTruck]) {
                    updateMainPolyline(startPosition, endPosition, truckMarkers[currentSelectedTruck].marker.position, directionsService, directionsRenderer);
                }

                //Remove deleted collection document of truck
                let deletedDocument = truckMarkers.findIndex(truckDocumentSingle => !driverIds.includes(truckDocumentSingle.driver_id));
                if (deletedDocument != -1)
                {
                    truckMarkers[deletedDocument].marker.setMap(null);
                    truckMarkers.splice(deletedDocument,1);
                }

            } else {
                console.log("No such document!");
            }
        });
    }

    function updateMainPolyline(startPosition, endPosition, truckPosition, directionsService, directionsRenderer) {

        // Draw Route
        let requestNew = {
            origin: startPosition,
            destination: endPosition,
            waypoints: [{ location: truckPosition }],
            travelMode: google.maps.TravelMode.DRIVING
        };
        directionsService.route(requestNew, (result, status) => {
            if (status == 'OK') {
                directionsRenderer.setDirections(result);
                updateTrucksPolyline(truckPosition, endPosition);
            }
        });

    }

    function updateTrucksPolyline(truckPosition, endPosition) {
        if (truckDirectionService && truckDirectionRenderer) {
            truckDirectionRenderer.setMap(null);
        }
        truckDirectionService = new google.maps.DirectionsService();
                truckDirectionRenderer = new google.maps.DirectionsRenderer({
                    map: map,
                    polylineOptions: {
                        strokeColor: '#37009b',
                        strokeWeight: 4,
                    },
                    suppressMarkers: true,
                    preserveViewport : true
                });
                // Draw Route
                let request = {
                    origin: truckPosition,
                    destination: endPosition,
                    travelMode: google.maps.TravelMode.DRIVING
                };
                truckDirectionService.route(request, (result, status) => {
                    if (status == 'OK') {
                        truckDirectionRenderer.setDirections(result);
                    }
                });

    }

    scriptInitialization();
    // Start Firestore listener when the modal is shown
    $('#liveTripTrackDetails').on('shown.bs.modal', function () {
        startRealtimeTracking();
        console.log('Firestore listener started');
    });

    // Stop Firestore listener when the modal is hidden
    $('#liveTripTrackDetails').on('hidden.bs.modal', function () {
        if (unsubscribe) {
            unsubscribe();
            console.log('Firestore listener stopped');
        }
    });
</script>
