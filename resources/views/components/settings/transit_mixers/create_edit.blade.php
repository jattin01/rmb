@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-3 col-8 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Settings</h1>
                        @if(isset($mixerDetails))
                        <h6><span class="active"> Transit Mixer </span> <i class="fa fa-angle-right"
                                aria-hidden="true"></i> Edit </h6>
                        @else
                        <h6><span class="active"> Transit Mixer </span> <i class="fa fa-angle-right"
                                aria-hidden="true"></i> Create </h6>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 col-4 text-right">
                    <a href="{{route('settings.transitMixers.index')}}" class="btn back-btn">Back</a>
                </div>
            </div>

            <div class="batching-plantaddbox mt-sm-4 mt-3">
                <form action="/settings/transit-mixers/store" role="post-data" method="POST">
                    @csrf
                    <input type="hidden" name="mixerId" value="{{@$mixerDetails->id}}">
                    <div class="row">
                        <div class="col-md-8 order-2 order-sm-1">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Company</label>
                                        <select class="form-control select-contentbox" name="group_company_id" id = "companies_dropdown">
                                            @foreach(@$groupCompanies as $company)
                                            <option value="{{$company->value}}" @if(@$mixerDetails->group_company_id == $company->value) selected @endif>{{$company->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Transit Mixer Code</label>
                                        <input type="text" name="truck_name" value="{{@$mixerDetails->truck_name}}" class="form-control user-profileinput" placeholder="Enter Code">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Capacity (In CUM)</label>

                                        <select class="form-control select-contentbox" name="capacity" id = "companies_dropdown">
                                            @foreach(@$capacities as $capacity)
                                            <option value="{{$capacity->label}}" @if(@$mixerDetails->capacity == $capacity->value) selected @endif>{{$capacity->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Plate Number</label>
                                        <input type="text" name="plate_no" value="{{@$mixerDetails->registration_no}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Driver Name</label>
                                        <select class="form-control select-contentbox" id = "drivers_dropdown" name="driver_code" onchange="setDriver(this)">
                                            <option value="">Select</option>
                                            @foreach(@$drivers as $driver)
                                            <option value="{{$driver->id}}" data-name="{{ $driver->name }}" @if(@$mixerDetails->driver_id == $driver->id) selected @endif>{{$driver->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="driver_name" value="{{@$mixerDetails->driver_name}}">

                                <div class="col-md-12">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Description</label>
                                        <input type="text" name="description" value="{{@$mixerDetails->description}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>

                            </div>

                            <div class="row mt-sm-3 mt-2">
                                <div class="col-md-4 col-7">
                                <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn apply-btn btn-block">Submit</button>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-4 order-sm-2 order-1 mb-2 mb-sm-0">
                            <div class="active-switch d-flex justify-content-end align-items-center">
                                <label class="switch mr-2">
                                    <input type="checkbox" id="mixer_status_toggle" name="mixer_status"
                                        value="{{ @$mixerDetails->status ?? 'Active' }}" class="activeclass"
                                        onclick="toggleStatus(this, 'mixer_status')"
                                        @if(@$mixerDetails->status == 'Active' || !isset($mixerDetails)) checked @endif/>
                                    <div class="slider round">
                                        <span class="{{ @$mixerDetails->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                    </div>
                                </label>
                                <p id="mixer_status">{{@$mixerDetails->status ? $mixerDetails->status : 'Active'}}</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</section>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#companies_dropdown').select2({
            placeholder: 'Select Company'
        });
        $('#drivers_dropdown').select2({
            placeholder: 'Select Driver'
        });
    });
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

    function setDriver(__this){
        var selectedOption = __this.options[__this.selectedIndex];
        var driverName = selectedOption.getAttribute('data-name');
        $('input[name="driver_name"]').val(driverName);
    }

</script>
@endsection
