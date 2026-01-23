@extends('layouts.auth.app')
@section('content')

<section class="content">
    <div class="container-fluid">
        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-6 col-8 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Settings</h1>
                        <h6><span class="active"> Locations </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                            Create New
                        </h6>
                    </div>
                </div>

                <div class="col-md-3 col-4 text-right">
                    <a href="{{route('setting.index')}}" class="btn back-btn">Back</a>
                </div>
            </div>
            <form action="/setting/location/store" role="post-data" method="POST" redirect="/setting/index">
                @csrf
                <input type="hidden" name="locationId" value="{{@$location->id}}">
                <div class="batching-plantaddbox mt-sm-4 mt-3">
                    <div class="row">
                        <div class="col-md-5 mb-3 mb-sm-0">
                            <div class="profileinput-box form-group position-relative">
                                <label class="selext-label">Location Code</label>
                                <input type="text" name="location_code" value="{{@$location->location}}" class="form-control user-profileinput"
                                    placeholder="Enter Code">
                            </div>

                            <div class="profileinput-box form-group position-relative">
                                <label class="selext-label">Name</label>
                                <input type="text" name="name" value="{{@$location->site_name}}" class="form-control user-profileinput"
                                    placeholder="Enter Name">
                            </div>

                            <div class="profileinput-box form-group position-relative">
                                <label class="selext-label">Contact Person</label>
                                <input type="text" name="contact_person" value="{{@$location->contact_person}}" class="form-control user-profileinput"
                                    placeholder="Enter">
                            </div>

                            <div class="profileinput-box form-group position-relative">
                                <label class="selext-label">Mobile</label>
                                <input type="text" name="mobile" value="{{@$location->phone}}" class="form-control user-profileinput"
                                    placeholder="Enter">
                            </div>

                            <div class="profileinput-box form-group position-relative">
                                <label class="selext-label">Email Address</label>
                                <input type="email" name="email" value="{{@$location->email}}" class="form-control user-profileinput"
                                    placeholder="Enter">
                            </div>

                            <div class="profileinput-box form-group position-relative">
                                <label class="selext-label">Country</label>
                                <select class="form-control select-contentbox" name="country" onchange="getProvince(this)">
                                    <option value="">Select</option>
                                    @foreach(@$countries as $country)
                                        <option value="{{$country->id}}" @if(@$location->country == $country->id) selected @endif>{{$country->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="profileinput-box form-group position-relative">
                                <label class="selext-label">Emirate</label>
                                <select class="form-control select-contentbox" name="province" id="province">
                                    <!-- Dynamic province -->
                                    @if(@$location)
                                    <option value="{{@$location->province}}">{{@$location->province}}</option>
                                    @else
                                    <option value="">select</option>
                                    @endif
                                </select>
                            </div>

                            <div class="active-switch d-flex align-items-center">
                                
                                <label class="switch mr-2">
                                    <input type="checkbox" id="staus" name="status" 
                                        value="{{ @$location->status ?? 'Active' }}" class="activeclass"
                                        onclick="toggleStatus(this, 'status')" 
                                        @if(@$location->status == 'Active' || !isset($location)) checked @endif/>
                                    <div class="slider round">
                                        <span class="{{ @$location->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                    </div>
                                </label>
                                <p id="status">{{@$location->status ? $location->status : 'Active'}}</p>
                            </div>

                        </div>
                        <div class="col-md-7">
                            <div class="map-boxcustomer">
                                <div class="position-relative">
                                    <div class="map-box">
                                        <input type="text" id="search_in_map" class="form-control map-searchinputbox padding-right" placeholder="Search By...">
                                        <img src="{{ asset('assets/img/fill-search.svg') }}" class="map-searchicon" alt="">
                                    </div>
                                </div>
                                <div id="map" class="edit-map" style="height:300px; border-radius: 10px;"></div>   
                            </div>

                            <div class="profileinput-box  position-relative mt-3">
                                <label class="selext-label">Address</label>
                                <textarea class="form-control user-profileinput" name="address" id="address" placeholder="Aldus PageMaker, Aldus PageMaker, Dubai" rows="5" >{{@$location->address}}</textarea>
                            </div>
                            <input type="hidden" name="latitude" value="{{@$location->latitude}}" id="lat">
                            <input type="hidden" name="longitude" value="{{@$location->longitude}}" id="lng">
                        </div>
                    </div>

                </div>

                <div class="row mt-sm-5 mt-3 justify-content-center">
                    <div class="col-md-3 col-7">
                        <button type="button" class="btn apply-btn btn-block" data-request="ajax-submit" data-target="[role=post-data]">Submit</button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</section>
@endsection

@section('scripts')
<script>
    $(document).ready(function(e) {
        initMap($('#lat').val(), $('#lng').val());
    });
    function getProvince(elem){
        var countryId = elem.value;
        $('#province').empty();
        $.ajax({
            url  : "{{url('setting/provinces')}}",
            type : "POST",
            data : {
                        _token: "{{ csrf_token() }}",
                        countryId : countryId
                    },
            success	: function(response){

                if(response){
                    $.each(response, (key, province) => {
                        console.log(key, province);
                        $('#province').append($('<option>', {
                            value: province?.name,
                            text: province?.name
                        }));
                    });
                }
            },
            error : function(response){
                console.log(response);
            },
        });
    }

    function toggleStatus(checkbox, field) {
        var statusText = document.getElementById(field);
        var currentStatus = checkbox.checked ? 'Active' : 'Inactive';

        if (currentStatus === 'Active') {
            statusText.innerHTML = 'Active';
            $(checkbox).next(".slider").find("span").removeClass("swinactive").addClass("swactive");
        } else {
            statusText.innerHTML = 'Inactive';
            $(checkbox).next(".slider").find("span").removeClass("swactive").addClass("swinactive");
        }

        $(`input[name='${field}']`).val(currentStatus);
    }

// Map
let map;
let autocomplete;
let geocoder;


async function initMap(lat,lng) {
    geocoder = new google.maps.Geocoder();
    // Marking Pointer
    var lat = lat ? parseFloat($('#lat').val()) : -34.397;
    var lng = lng ? parseFloat($('#lng').val()) : 150.644;
    const { Map} = await google.maps.importLibrary("maps");
    $('.map-box').show();
   
    map = new Map(document.getElementById("map"), {
        center: { lat: lat, lng: lng },
        zoom: 8,
        streetViewControl: false,
        mapTypeControl: false
    });
    
    var marker = new google.maps.Marker({
        position:{ lat: lat, lng: lng },
        map: map,
        draggable: true,
        animation:google.maps.Animation.BOUNCE
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
    console.log(autoComplete);

    autoComplete.addListener('place_changed', function(){
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

        $('#lat').val(place?.geometry['location'].lat());
        $('#lng').val(place?.geometry['location'].lng());
        $('#address').val(place?.formatted_address);
    });
}

function updateAddress(position) {
    geocoder.geocode({ 'location': position }, function(results, status) {
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
}

    
</script>
@endsection