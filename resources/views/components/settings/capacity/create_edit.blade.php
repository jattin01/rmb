@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-3 col-8 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Settings</h1>
                        {{-- @if(isset($pumpDetails))
                        <h6><span class="active"> Pumps </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                            Edit </h6>
                        @else
                        <h6><span class="active"> Pumps </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                            Create </h6>
                        @endif --}}
                    </div>
                </div>

                <div class="col-md-3 col-4 text-right">
                    <a href="{{route('settings.capacity.index')}}" class="btn back-btn">Back</a>
                </div>
            </div>

            <div class="batching-plantaddbox mt-sm-4 mt-3">
                <form action="/capacity/store" role="post-data" method="POST">
                    @csrf
                    <input type="hidden" name="CapacityId" value="{{ @$capacity->id }}">
                    <div class="row">
                        <div class="col-md-8 order-2 order-sm-1">
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Value</label>
                                        <input type="text" name="value" value="{{@$capacity->value}}" class="form-control user-profileinput" placeholder="Enter Value">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">UOM</label>
                                        <select class="form-control select-contentbox" name="uom">
                                            <option value="">Select</option>
                                            <option value="CUM" @if(@$capacity->uom == 'CUM') selected @endif>CUM</option>
                                            <option value="MTR" @if(@$capacity->uom == 'MTR') selected @endif>MTR</option>
                                        </select>

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
                                    <input type="checkbox" id="capacity_status_toggle" name="status"
                                        value="{{ @$capacity->status ?? 'Active' }}" class="activeclass"
                                        onclick="toggleStatus(this, 'status')"
                                        @if(@$capacity->status == 'Active' || !isset($capacity)) checked @endif/>
                                    <div class="slider round">
                                        <span class="{{ @$capacity->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                    </div>
                                </label>
                                <p id="status">{{@$capacity->status ? $capacity->status : 'Active'}}</p>
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
        $('#pump_type_dropdown').select2({
            placeholder: 'Select Type'
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


</script>
@endsection
