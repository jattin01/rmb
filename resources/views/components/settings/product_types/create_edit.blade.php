@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-3 col-8 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Settings</h1>
                        @if(isset($typeDetail))
                        <h6><span class="active"> Mix Type </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                            Edit </h6>
                        @else
                        <h6><span class="active"> Mix Type </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                            Create </h6>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 col-4 text-right">
                    <a href="{{route('settings.productTypes.index')}}" class="btn back-btn">Back</a>
                </div>
            </div>

            <div class="batching-plantaddbox mt-sm-4 mt-3">
                <form action="/settings/product_types/store" role="post-data" method="POST">
                    @csrf
                    <input type="hidden" name="typeId" value="{{@$typeDetail->id}}">
                    <div class="row">
                        <div class="col-md-8 order-2 order-sm-1">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Company</label>
                                        <select class="form-control select-contentbox" name="group_company_id" id = "companies_dropdown">
                                            @foreach(@$groupCompanies as $company)
                                            <option value="{{$company->value}}" @if(@$typeDetail->group_company_id == $company->value) selected @endif>{{$company->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Mix Type</label>
                                        <input type="text" name="type" value="{{@$typeDetail->type}}" class="form-control user-profileinput" placeholder="Enter Type">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Batching Creation Time (Min)</label>
                                        <input type="text" name="batching_creation_time" value="{{@$typeDetail->batching_creation_time}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Tempeature Creation Time (Min)</label>
                                        <input type="text" name="temperature_creation_time" value="{{@$typeDetail->temperature_creation_time}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Description</label>
                                        <input type="text" name="description" value="{{@$typeDetail->description}}" class="form-control user-profileinput" placeholder="Write...">
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
                                    <input type="checkbox" id="type_status_toggle" name="type_status"
                                        value="{{ @$typeDetail->status ?? 'Active' }}" class="activeclass"
                                        onclick="toggleStatus(this, 'type_status')"
                                        @if(@$typeDetail->status == 'Active' || !isset($typeDetail)) checked @endif/>
                                    <div class="slider round">
                                        <span class="{{ @$typeDetail->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                    </div>
                                </label>
                                <p id="type_status">{{@$typeDetail->status ? $typeDetail->status : 'Active'}}</p>
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
