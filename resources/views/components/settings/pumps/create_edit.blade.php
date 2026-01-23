@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-3 col-8 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Settings</h1>
                        @if(isset($pumpDetails))
                        <h6><span class="active"> Pumps </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                            Edit </h6>
                        @else
                        <h6><span class="active"> Pumps </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                            Create </h6>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 col-4 text-right">
                    <a href="{{route('settings.pumps.index')}}" class="btn back-btn">Back</a>
                </div>
            </div>

            <div class="batching-plantaddbox mt-sm-4 mt-3">
                <form action="/settings/pumps/store" role="post-data" method="POST">
                    @csrf
                    <input type="hidden" name="pumpId" value="{{@$pumpDetails->id}}">
                    <div class="row">
                        <div class="col-md-8 order-2 order-sm-1">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Company</label>
                                        <select class="form-control select-contentbox" id = "companies_dropdown" name="group_company_id" onchange = "changeDropdownOptions(this, ['pump_type_dropdown'], ['pump_types'] , '/get/order-creation-data/')">
                                            @foreach(@$groupCompanies as $company)
                                            <option value="{{$company->value}}" @if(@$pumpDetails->group_company_id == $company->value) selected @endif>{{$company->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Operator Name</label>
                                        <select class="form-control select-contentbox" name="operator_id">
                                            <option value="">Select</option>

                                            @foreach($operators as $operator)
                                                <option value="{{ $operator->id }}" {{ @$operator->id == $pumpDetails?->operator_id ? 'selected' : '' }}>{{ $operator->name }}</option>
                                            @endforeach
                                            {{-- {{ @$operator->id == $pumpDetails?->operator_id ? 'selected' : '' }} --}}
                                        </select>
                                    </div>
                                </div>



                                {{-- <div class="col-md-6"></div> --}}

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Type</label>
                                        <select class="form-control select-contentbox" name="type" id = "pump_type_dropdown">
                                            @foreach(@$pumpTypes as $type)
                                            <option value="{{$type->value}}" @if(@$pumpDetails->type == $type->value) selected @endif> {{$type->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Pump Name</label>
                                        <input type="text" name="pump_name" value="{{@$pumpDetails->pump_name}}" class="form-control user-profileinput" placeholder="Enter Code">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Capacity (In Meters)</label>

                                        <select class="form-control select-contentbox" name="pump_capacity" id = "companies_dropdown">
                                            @foreach(@$capacities as $capacity)
                                            <option value="{{$capacity->label}}" @if(@$mixerDetails->pump_capacity == $capacity->value) selected @endif>{{$capacity->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Description</label>
                                        <input type="text" name="description" value="{{@$pumpDetails->description}}" class="form-control user-profileinput" placeholder="Write...">
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
                                    <input type="checkbox" id="pump_status_toggle" name="pump_status"
                                        value="{{ @$pumpDetails->status ?? 'Active' }}" class="activeclass"
                                        onclick="toggleStatus(this, 'pump_status')"
                                        @if(@$pumpDetails->status == 'Active' || !isset($pumpDetails)) checked @endif/>
                                    <div class="slider round">
                                        <span class="{{ @$pumpDetails->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                    </div>
                                </label>
                                <p id="pump_status">{{@$pumpDetails->status ? $pumpDetails->status : 'Active'}}</p>
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
