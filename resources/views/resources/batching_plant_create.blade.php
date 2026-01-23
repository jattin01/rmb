@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-3 col-8 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Resources</h1>
                        @if(isset($plantDetail))
                        <h6><span class="active"> Batching Plant </span> <i class="fa fa-angle-right"
                                aria-hidden="true"></i> Update </h6>
                        @else
                        <h6><span class="active"> Batching Plant </span> <i class="fa fa-angle-right"
                                aria-hidden="true"></i> Create New </h6>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 col-4 text-right">
                    <a href="{{route('resources.index')}}" class="btn back-btn">Back</a>
                </div>
            </div>

            <div class="batching-plantaddbox mt-sm-4 mt-3">
                <form action="/resources/batching-plant-store" role="post-data" method="POST">
                    @csrf
                    <input type="hidden" name="plantId" value="{{@$plantDetail->id}}">
                    <div class="row">
                        <div class="col-md-8 order-2 order-sm-1">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Batching plant Code</label>
                                        <input type="text" name="plant_name" value="{{@$plantDetail->plant_name}}" class="form-control user-profileinput" placeholder="Enter Code">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Batching plant Name</label>
                                        <input type="text" name="long_name" value="{{@$plantDetail->long_name}}" class="form-control user-profileinput" placeholder="Enter Name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Location</label>
                                        <select class="form-control select-contentbox" name="company_location_id">
                                            <option value="">Select</option>
                                            @foreach(@$locations as $location)
                                            <option value="{{$location->id}}" @if(@$plantDetail->company_location_id == $location->id) selected @endif>{{$location->location}} {{$location->site_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Type / Capacity (In CUM)</label>
                                        <input type="text" name="capacity" value="{{@$plantDetail->capacity}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Description</label>
                                        <input type="text" name="description" value="{{@$plantDetail->description}}" class="form-control user-profileinput" placeholder="Enter">
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
                                    <input type="checkbox" id="plant_staus" name="plant_status"
                                        value="{{ @$plantDetail->status ?? 'Active' }}" class="activeclass"
                                        onclick="{{ @$plantDetail->status == 'Active' || !isset($plantDetail) ? 'active' : 'inactive' }}Staus('plant_staus', 'plant_status')" @if(@$plantDetail->status == 'Active' || !isset($plantDetail)) checked @endif/>
                                    <div class="slider round">
                                        <span
                                            class="{{ @$plantDetail->status == 'Inactive' ? 'swinactive' : 'swactive' }}">
                                        </span>
                                    </div>
                                </label>
                                <p id="plant_status">{{@$plantDetail->status ? $plantDetail->status : 'Active'}}</p>
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
    function activeStaus(id,field) {
        console.log(typeof(id), field);
        if(typeof(id) == 'object' && typeof(field) == 'object'){
            id = id.id;
            field = field.name;
        }
        var status = 'active';
        document.getElementById(id).setAttribute('onclick', `inactiveStaus(${id}, ${field})`);
        $(".slider span").addClass("swactive");
        $(`#${field}`).html('Inactive');
        $(`input[name='${field}']`).val('Inactive');

    }

    function inactiveStaus(id,field) {
        var status = 'inactive';
        if(typeof(id) == 'object' && typeof(field) == 'object'){
            id = id.id;
            field = field.name;
        }
        console.log(id, field);
        document.getElementById(id).setAttribute('onclick', `activeStaus(${id}, ${field})`);
        $(".slider span").addClass("swinactive");
        $(`#${field}`).html('Active');
        $(`input[name='${field}']`).val('Active');
        
    }
</script>
@endsection