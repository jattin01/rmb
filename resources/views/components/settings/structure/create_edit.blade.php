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
                        <a href="{{ route('setting.index') }}" class="btn back-btn">Back</a>
                    </div>
                </div>
                <div class="batching-plantaddbox mt-sm-4 mt-3">
                    <form action="/settings/structures/store" method="POST" role="post-data">
                        @csrf
                        <input type="hidden" name="structureRefId" value="{{@$structure->id}}">

                        <div class="row">
                            <div class="col-md-8 order-2 order-sm-1">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="profileinput-box form-group position-relative">
                                            <label class="select-label">Name</label>

                                            <input type="text" id="id" name="name" value="{{ $structure ? $structure->name : '' }}" class="form-control user-profileinput" required>
                                        </div>
                                    </div>


                                    {{-- <div class="col-md-6">
                                        <div class="profileinput-box form-group position-relative">
                                            <label for="group_company_id">Structure</label>

                                            <select class="form-control select-contentbox" id = "name" name = "name">
                                                @foreach ($structures as $structure)
                                                <option value="{{ $structure->value }}">{{ $structure->label }}</option>
                                            @endforeach
                                            </select>
                                        </div>
                                    </div> --}}
                                    <div class="col-md-6">
                                        <div class="profileinput-box form-group position-relative">
                                            <label for="group_company_id">Group Company</label>

                                            <select class="form-control select-contentbox" id = "group_company_id" name = "group_company_id">
                                                @foreach ($groupCompanies as $groupCompanie)
                                                <option value="{{ $groupCompanie->value }}">{{ $groupCompanie->label }}</option>
                                            @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="profileinput-box form-group position-relative">
                                            <label class="select-label">Pouring Time (Without Pump)</label>
                                            <input type="number" id="pouring_wo_pump_time" name="pouring_wo_pump_time" value="{{ $structure ? $structure->pouring_wo_pump_time : '' }}" class="form-control user-profileinput" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="profileinput-box form-group position-relative">
                                            <label class="select-label">Pouring Time (With Pump)</label>
                                            <input type="number" id="pouring_w_pump_time" name="pouring_w_pump_time" value="{{ $structure ? $structure->pouring_w_pump_time : '' }}" class="form-control user-profileinput" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-sm-3 mt-2">
                                    <div class="col-md-4 col-7">
                                        <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn new-btn mr-3">Submit</button>

                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 order-sm-2 order-1 mb-2 mb-sm-0">
                                <div class="active-switch d-flex justify-content-end align-items-center">
                                    <label class="switch mr-2">
                                        <input type="checkbox" id="mixer_status_toggle" name="status"
                                            value="{{ @$structure
                                                ->status ?? 'Active' }}" class="activeclass"
                                            onclick="toggleStatus(this, 'status')"
                                            @if(@$structure->status == 'Active' || !isset($structure)) checked @endif/>
                                        <div class="slider round">
                                            <span class="{{ @$structure->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                        </div>
                                    </label>
                                    <p id="status">{{@$structure->status ? $structure->status : 'Active'}}</p>
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

