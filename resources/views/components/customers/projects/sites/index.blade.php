@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-4 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Project Sites</h1>
                            <h6><span class="active"> Customer Projects </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Sites </h6>
                        </div>
                    </div>

                    <div class="col-md-4"></div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="home" role="tabpanel"
                                aria-labelledby="home-tab">
                                <div class="row mt-sm-4 mt-3">
                                    <div class="col-md-4">
                                        <form class="search-form" role="search">
                                            <input type = "hidden" name = "project_id"
                                                value = "{{ request()->project_id }}" />
                                            <input type = "hidden" name = "customer_id"
                                                value = "{{ request()->customer_id }}" />
                                            <div class="form-group position-relative">
                                                <input type="text" name="search" value="{{ @$search }}"
                                                    class="form-control search-byinpt padding-right"
                                                    placeholder="Search By..." onchange="this.form.submit()">

                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <div class="d-flex justify-content-end">
                                            <button data-toggle="modal" data-target="#site-model"
                                                onclick = "setProjectId();" class="btn btn-success mr-2">Create New</button>
                                            {{-- <button type="button" class="btn export-btn mr-2">Export</button> --}}

                                            <a type="button" class="btn export-btn mr-2"
                                            href="{{ route('settings.customerProjectSites.export', ['search' => request('search'), 'customer_id' => request('customer_id'),'project_id'=> request('project_id'),'name' => request('name'), 'type' => request('type')]) }}"
                                            class="btn btn-success mr-2">Export</a>


                                            <div class="dropdown drop-mainbox">
                                                <button class="btn filter-boxbtn dropdown-toggle" type="button"
                                                    id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    Filters
                                                </button>

                                                <form method="GET">
                                                    <input type="hidden" id = "project_id_filter" name="project_id"
                                                        value="{{ request()->project_id }}">

                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <label class="fliter-label">Filters</label>


                                                        <div class="select-box form-group">
                                                            <label class="selext-label">Projects</label>
                                                            <select class="form-control select-contentbox"
                                                                name ='company_location_id'>
                                                                <option value="">Select </option>
                                                                @foreach ($sites as $site)
                                                                    <option value="{{ $site->project?->name }}">
                                                                        {{ $site->project?->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="row align-items-center mt-3">
                                                            <div class="col-md-6 col-4">

                                                                <a class="reset-text"
                                                                    href="{{ url('project-sites') . '?project_id=' . request()->project_id }}">Reset
                                                                </a>
                                                            </div>
                                                            <div class="col-md-6 col-8 text-right">
                                                                <button type="button" class="btn apply-btnnew "
                                                                    onclick="this.form.submit()">Apply now</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3 mt-sm-2 align-items-center">
                                    <div class="col-md-6 mb-sm-0 mb-2">

                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table general-table">
                                                <thead>
                                                    <tr>
                                                        <th>Customer</th>
                                                        <th>Project</th>
                                                        <th>Site Name</th>
                                                        <th>Site Address</th>
                                                        <th>Service Location</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {{-- @dd($sites); --}}
                                                    @foreach ($sites as $site)

                                                        <tr>
                                                            <td>{{ $site->project?->customer?->name }}</td>
                                                            <td>{{ $site->project?->name }}</td>
                                                            <td>{{ $site->name }}</td>
                                                            <td>{{ $site->address }}</td>
                                                            <td>{{ $site->service_company_location?->site_name }}</td>
                                                            <td
                                                                class="{{ $site->status == 'Active' ? 'table-activetext' : 'table-inactivetext' }}">
                                                                {{ $site->status }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center justify-content-between"
                                                                    style = "margin:0.4rem;">
                                                                    <div class="dropdown more-drop">
                                                                        <button class="table-drop" type="button"
                                                                            id="dropdownMenuButton" data-toggle="dropdown"
                                                                            aria-haspopup="true" aria-expanded="false">
                                                                            <i class="fa fa-ellipsis-v fa-lg more-icon"
                                                                                aria-hidden="true"></i>
                                                                        </button>
                                                                        <div class="dropdown-menu"
                                                                            aria-labelledby="dropdownMenuButton">
                                                                            <a class="dropdown-item" href="#"
                                                                                onclick="editSite({{ @$site->id }}, {{ @$site->cust_project_id }})">Details</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                {!! $sites->links('partials.pagination') !!}
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>

        <div class="modal fade filter-modal" id="site-model" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <img src="{{ asset('assets/img/filter-close.svg') }}" alt=""> close
                        </button>
                    </div>
                    <form action="{{ route('customers.store_site_address') }}" role="post-data" method="POST">
                        @csrf
                        <input type="hidden" id = "project_id_input" name="project_id" value="">
                        <input type="hidden" id = "site_id_input" name="siteId" value="">
                        <div class="modal-body">
                            <div class="filter-contentbox">
                                <h6>Site Address</h6>
                            </div>

                            <div class="active-switch d-flex mt-3 mb-4 justify-content-end align-items-center"
                                id="site-switch">
                                <label class="switch">
                                    <input type="checkbox" id="site_staus" name="site_status"
                                        value="{{ @$site->status ?? 'Active' }}" class="activeclass"
                                        onclick="{{ @$site->status == 'Inactive' || !isset($site) ? 'active' : 'inactive' }}Staus('site_staus', 'site_status')"
                                        checked />
                                    <div class="slider round">
                                        <span class="{{ @$site->status == 'Inactive' ? 'swinactive' : 'swactive' }}">
                                        </span>
                                    </div>
                                </label>
                                <p id="site_status">Active</p>
                            </div>

                            <div class="profileinput-box mt-4 form-group position-relative">
                                <label class="selext-label">Site Name</label>
                                <input type="text" name="site_name"
                                    class="form-control user-profileinput padding-right" placeholder="Enter">
                            </div>

                            <!-- map -->
                            <div class="map-boxcustomer mt-sm-4 mt-4">
                                <div class="position-relative">
                                    <div class="map-box">
                                        <input type="text" id="search_in_map"
                                            class="form-control map-searchinputbox padding-right"
                                            placeholder="Search By...">
                                        <img src="{{ asset('assets/img/fill-search.svg') }}" class="map-searchicon"
                                            alt="">
                                    </div>
                                </div>
                                <div id="map" class="edit-map" style="height:300px; border-radius: 10px;"></div>
                            </div>

                            <div class="profileinput-box position-relative mt-4">
                                <label class="selext-label">Address</label>
                                <textarea class="form-control user-profileinput" id="address" name="site_address" placeholder="Enter"
                                    rows="2"></textarea>
                            </div>
                            <label style = "margin-top : 1rem; margin-bottom : 1rem;">Preferred Service Location</label>
                            <div class="profileinput-box form-group position-relative">
                                <label class="selext-label">Company</label>
                                <select class="form-control select-contentbox" name="group_company_id"
                                    onchange = "changeDropdownOptions(this, ['company_location_dropdown'], ['company_locations'] , '/get/order-creation-data/')">
                                    @foreach (@$groupCompanies as $company)
                                        <option value="{{ $company->value }}"
                                            @if (@$site?->service_company_location?->group_company_id == $company->value) selected @endif>{{ $company->label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="profileinput-box form-group position-relative">
                                <label class="selext-label">Location</label>
                                <select class="form-control select-contentbox" name="company_location_id"
                                    id = "company_location_dropdown">
                                    @foreach (@$locations as $location)
                                        <option value="{{ $location->value }}"
                                            @if (@$site?->company_location_id == $location->value) selected @endif>{{ $location->label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="latitude" id="lat" value="">
                            <input type="hidden" name="longitude" id="lng" value="">
                            <div class="mt-sm-5 mt-3">
                                <button type="button" onclick="storeProjectSiteAddress(this)"
                                    class="btn apply-btn btn-block">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <style>
            #drop-area.highlight {
                border-color: purple;
            }
        </style>

    </section>

    <script>
        function setProjectId() {
            var url = new URL(window.location.href);
            var projectId = url.searchParams.get("project_id");
            document.getElementById('project_id_input').value = projectId;
            initMap();

        }

        function editSite(siteId, projectId) {
            document.getElementById('project_id_input').value = projectId;
            document.getElementById('site_id_input').value = siteId;
            $.ajax({
                url: "{{ url('/customers/edit-project-site') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    siteId: siteId
                },

                success: function(response) {
                    console.log("success", response);
                    $('#site-switch').html('');
                    let html = '';
                    if (response) {
                        $('input[name="siteId"]').val(response?.id);
                        $('input[name="site_name"]').val(response?.name);
                        $('select[name="company_location_id"]').val(response?.company_location_id);
                        $('select[name="group_company_id"]').val(response?.service_group_company_id);
                        $('textarea[name="site_address"]').val(response?.address);
                        $('input[name="latitude"]').val(response?.latitude);
                        $('input[name="longitude"]').val(response?.longitude);
                        html = `<label class="switch">
                                <input type="checkbox" id="site_staus" name="site_status"
                                    value="${response.status}" class="activeclass"
                                    onclick="${response.status == 'Inactive' || !response ? 'inactive' : 'active' }Staus('site_staus', 'site_status')"
                                    ${response?.status == 'Inactive' ? '' : 'checked' } />
                                <div class="slider round">
                                    <span
                                        class="${response.status == 'Inactive' ? 'swinactive' : 'swactive'}">
                                    </span>
                                </div>
                            </label>
                            <p id="site_status">${response.status == 'Inactive' ? 'Inactive' : 'Active' }</p>`;

                        $('#site-switch').html(html);
                        initMap(response?.latitude, response?.longitude);
                        $('#site-model').modal('show');
                    }
                },
                error: function($response) {

                },
            });
        }

        function storeProjectSiteAddress(elem) {
            var form = $(elem).closest('form');
            var formData = new FormData(form[0]);
            var url = $(form[0]).attr('action');

            $.ajax({
                url: url,
                data: formData,
                cache: false,
                type: 'POST',
                dataType: 'JSON',
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loaderDiv').show();
                    $('.help-block').remove();
                },
                success: function($response) {
                    if ($response.status === 200) {
                        toast("success", $response.message);
                        setTimeout(function() {
                            $('#loaderDiv').hide();
                            window.location.reload();
                        }, 2200);
                    }
                },
                error: function($response) {
                    $('#loaderDiv').hide();
                    if ($response.status === 422) {
                        if (Object.size($response.responseJSON) > 0 && Object.size($response
                                .responseJSON.errors) > 0) {
                            show_validation_error($response.responseJSON.errors);
                        }
                    } else {
                        Swal.fire(
                            'Error', $response.responseJSON.message, 'warning'
                        )
                        setTimeout(function() {}, 1200)
                    }
                }
            });
        }
        let map;
        let autocomplete;
        let geocoder;

        async function initMap(lat = null, lng = null) {
            geocoder = new google.maps.Geocoder();
            // Marking Pointer
            var lat = lat ? parseFloat($('#lat').val()) : -34.397;
            var lng = lng ? parseFloat($('#lng').val()) : 150.644;
            const {
                Map
            } = await google.maps.importLibrary("maps");
            $('.map-box').show();
            console.log(lat, lng);

            map = new Map(document.getElementById("map"), {
                center: {
                    lat: lat,
                    lng: lng
                },
                zoom: 8,
                streetViewControl: false,
                mapTypeControl: false
            });

            var marker = new google.maps.Marker({
                position: {
                    lat: lat,
                    lng: lng
                },
                map: map,
                draggable: true,
                animation: google.maps.Animation.BOUNCE
            });

            // Add event listener for marker dragend
            google.maps.event.addListener(marker, 'dragend', function() {
                const position = marker.getPosition();
                updateAddress(position);
            });

            // AutoComplete Address
            autoComplete = new google.maps.places.Autocomplete(document.getElementById("search_in_map"), {
                types: ['geocode'],
            });
            autoComplete.bindTo('bounds', map);
            console.log("searching:", autoComplete);

            autoComplete.addListener('place_changed', function() {
                var place = autoComplete.getPlace();
                console.log("place:", place);
                if (!place.geometry) {
                    Swal.fire({
                        type: 'error',
                        title: 'Oops...',
                        text: 'Location Not Found!',
                    });
                    return;
                }
                map.setCenter(place.geometry.location);
                marker.setPosition(place.geometry.location);

                $('#address').val(place?.formatted_address);
                $('#lat').val(place?.geometry['location'].lat());
                $('#lng').val(place?.geometry['location'].lng());
            });
        }

        $('#site-model').on('shown.bs.modal', function() {
            initMap();
            google.maps.event.trigger(map, 'resize');
            map.setCenter({
                lat: $('#lat').val(),
                lng: $('#lng').val()
            }); // Center the map after resize
        });

        function updateAddress(position) {
            geocoder.geocode({
                'location': position
            }, function(results, status) {
                if (status === 'OK') {
                    if (results[0]) {
                        $('#address').val(results[0].formatted_address);
                        $('#lat').val(position.lat());
                        $('#lng').val(position.lng());
                    } else {
                        Swal.fire({
                            type: 'error',
                            title: 'Oops...',
                            text: 'No results found!',
                        });
                    }
                } else {
                    Swal.fire({
                        type: 'error',
                        title: 'Oops...',
                        text: 'Geocoder failed due to: ' + status,
                    });
                }
            });

            function activeStaus(id, field) {
                console.log(typeof(id), field);
                if (typeof(id) == 'object' && typeof(field) == 'object') {
                    id = id.id;
                    field = field.name;
                }
                var status = 'active';
                // $('#status').val(status);
                document.getElementById(id).setAttribute('onclick', `inactiveStaus(${id}, ${field})`);
                $(".slider span").addClass("swactive");
                $(`#${field}`).html('Inactive');
                $(`input[name='${field}']`).val('Inactive');

                //projectStatusToggle(status);

            }

            function inactiveStaus(id, field) {
                var status = 'inactive';
                if (typeof(id) == 'object' && typeof(field) == 'object') {
                    id = id.id;
                    field = field.name;
                }
                console.log(id, field);
                document.getElementById(id).setAttribute('onclick', `activeStaus(${id}, ${field})`);
                $(".slider span").addClass("swinactive");
                $(`#${field}`).html('Active');
                $(`input[name='${field}']`).val('Active');
                //projectStatusToggle(status);
            }
        }
    </script>
@endsection
