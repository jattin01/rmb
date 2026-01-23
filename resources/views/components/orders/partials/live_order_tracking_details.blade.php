<div class="modal fade filter-modal order-model" id="liveOrderTrackDetails" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="exampleModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <img src="{{asset('assets/img/filter-close.svg')}}" alt=""> close
                </button>
            </div>
            <div class="modal-body" style="min-height:100vh">
                @if ($orderDetail)
                <div class="filter-contentbox">
                    <h6>Order {{$orderDetail -> order_no}}</h6>
                </div>

                <div class="order-map mt-3" id="map">

                </div>

                <div class="order-details mt-4">
                    <div class="row">
                        <div class="col-md-6 mb-sm-0 mb-2">
                            <div class="order-detailscontent">
                                <h1>Order Details</h1>
                                <div class="d-flex justify-content-between mt-sm-3 mt-2">
                                    <div>
                                        <p>Delivery Date</p>
                                        <h6>{{Carbon\Carbon::parse($orderDetail -> planned_start_time) -> format('d F,
                                            Y')}}</h6>
                                    </div>

                                </div>
                                <div class="d-flex justify-content-between mt-sm-3 mt-2">
                                    <div>
                                        <p>Delivery Time</p>
                                        <h6>{{Carbon\Carbon::parse($orderDetail -> planned_start_time) -> format('h:i
                                            A')}}</h6>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-sm-3 mt-2">
                                    <div>
                                        <p>Interval</p>
                                        <h6>{{$orderDetail -> interval}} Mins</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">

                            <div class="customer-infotext">
                                <div class="customer-infotextinner">
                                    <h6>Customer Info</h6>

                                    <div class="customer-timeline">
                                        <div class="customer-timelineinner">
                                            <p>Company</p>
                                            <h4>{{$orderDetail -> customer_company_name()}}</h4>
                                        </div>
                                        <div class="customer-timelineinner">
                                            <p>Project</p>
                                            <h4>{{$orderDetail -> project_detail ?-> name}}</h4>
                                        </div>
                                        <div class="customer-timelineinner">
                                            <p>Site Location</p>
                                            <h4>{{$orderDetail -> customer_site ?-> name}}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="mix-details mt-sm-5 mt-3">
                    <h2>Mix Details</h2>
                    <div class="row mt-sm-3 mt-2">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-4 col-7">
                                    <p>Mix</p>
                                    <h6>{{$orderDetail -> customer_product ?-> product_name}}</h6>
                                </div>
                                <div class="col-md-3 col-5">
                                    <p>Qty.</p>
                                    <h6>{{$orderDetail -> quantity}}</h6>
                                </div>
                                <div class="col-md-5 col-7 mt-2 mt-sm-0">
                                    <p>Technician Required</p>
                                    <h6>{{$orderDetail -> is_technician_required ? 'Yes' : 'No'}}</h6>
                                </div>

                                <div class="col-md-4 col-5 mt-sm-3 mt-2">
                                    <p>Mix Code</p>
                                    <h6>{{$orderDetail -> customer_product ?-> mix_code}}</h6>
                                </div>
                                <div class="col-md-3 col-7 mt-sm-3 mt-2">
                                    <p>Structural Ref.</p>
                                    <h6>{{$orderDetail -> structural_reference}}</h6>
                                </div>
                                <div class="col-md-5 col-5 mt-sm-3 mt-2">
                                    <p>Cube Mould Req.</p>
                                    <h6>{{$orderDetail -> order_cube_mould_display()}}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="row mt-2 mt-sm-0">
                                <div class="col">
                                    <p>Temp Control</p>
                                    <h6>{{$orderDetail -> order_temp_control_display()}}</h6>
                                </div>
                            </div>
                            <div class="row mt-2 mt-sm-0">
                                <div class="col">
                                    <p>Pumps</p>
                                    <h6>{{$orderDetail -> order_pumps_display()}}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center mt-sm-5 mt-3">
                    <div class="col-md-9 col-12">
                        <div class="row">
                            <div class="col-md-4 col-6">
                                <button type="button" class="btn cancel-btn btn-block">Cancel</button>

                            </div>
                            <div class="col-md-4 col-6">
                                <a href="/edit/live-schedule/{{{$orderDetail -> id}}}">
                                    <button type="button" class="btn edit-btn btn-block">Edit</button>
                                </a>
                            </div>
                            <div class="col-md-4 col-6 mt-sm-0 mt-3">
                                <button type="button" class="btn apply-btn btn-block">Re-schedule</button>
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
        unsubscribe = myFirebaseDb.collection("driver_locations").doc("orderId_" + "{{$orderDetail -> id}}").collection('drivers').onSnapshot((doc) => {
            if (true) {
                let data = doc;
                let index = -1;
                let driverIds = [];

                // Trucks
                data.forEach((truckDocument) => {
                        index = index + 1;
                        let truck = truckDocument.data();
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
    $('#liveOrderTrackDetails').on('shown.bs.modal', function () {
        startRealtimeTracking();
        console.log('Firestore listener started');
    });

    // Stop Firestore listener when the modal is hidden
    $('#liveOrderTrackDetails').on('hidden.bs.modal', function () {
        if (unsubscribe) {
            unsubscribe();
            console.log('Firestore listener stopped');
        }
    });
</script>
