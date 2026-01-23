@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-3 col-8 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Settings</h1>
                        <h6><span class="active"> Users </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                            Create New </h6>
                    </div>
                </div>
                
                <div class="col-md-3 col-4 text-right">
                    <a href="{{route('setting.index')}}" class="btn back-btn">Back</a>
                </div>
            </div>
            <div class="batching-plantaddbox mt-sm-4 mt-3">
                <form action="/setting/users/store" role="post-data" method="POST" redirect="/setting/index?activeTab=profile">
                    @csrf
                    <input type="hidden" name="userId" value="{{@$user->id}}">
                    <div class="row">
                        <div class="col-md-8 order-2 order-sm-1">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Name</label>
                                        <input type="text" name="name" value="{{@$user->name}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Mobile</label>
                                        <input type="text" name="phone" value="{{@$user->mobile_no}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Email Address</label>
                                        <input type="email" name="email" value="{{@$user->email}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Role</label>
                                        <select class="form-control select-contentbox" name="role_id">
                                            <option value="">Select</option>
                                            @foreach(@$roles as $role)
                                                <option value="{{$role->id}}" @if(@$user->role_id == $role->id) selected @endif</option>{{$role->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-sm-3 mt-2">
                                <div class="col-md-4 col-7">
                                    <button type="button" class="btn apply-btn btn-block" data-request="ajax-submit" data-target="[role=post-data]">Submit</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 order-sm-2 order-1 mb-2 mb-sm-0">
                            <div class="active-switch d-flex justify-content-end align-items-center">
                               
                                <label class="switch mr-2">
                                    <input type="checkbox" id="staus" name="status" 
                                        value="{{ @$user->status ?? 'Active' }}" class="activeclass"
                                        onclick="toggleStatus(this, 'status')" 
                                        @if(@$user->status == 'Active' || !isset($user)) checked @endif/>
                                    <div class="slider round">
                                        <span class="{{ @$user->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                    </div>
                                </label>
                                <p id="status">{{'Active'}}</p>
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