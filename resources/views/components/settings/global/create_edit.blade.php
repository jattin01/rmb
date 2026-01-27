@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-3 col-8 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Global Master</h1>
                            @if (isset($global))
                                <h6><span class="active"> Global Master
                                </span> <i class="fa fa-angle-right"
                                        aria-hidden="true"></i> Edit </h6>
                            @else
                                <h6><span class="active"> Global Master
                                </span> <i class="fa fa-angle-right"
                                        aria-hidden="true"></i> Create </h6>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3 col-4 text-right">
                        <a href="{{ route('settings.global.index') }}" class="btn back-btn">Back</a>
                    </div>
                </div>
                <div class="batching-plantaddbox mt-sm-4 mt-3">

                    <form action="/settings/global/store" method="POST" role="post-data">
                        @csrf
                        <input type="hidden" name="plantId" value="{{ @$global->id }}">

                        <div class="row justify-content-center py-sm-4 py-3">
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label class="plant-label">Company</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control new-select" id="companies_dropdown"
                                                name="group_company_id"
                                                onchange="changeDropdownOptions(this, ['company_location_dropdown'], ['company_locations'], '/group-company/get/locations/')">
                                            @if (!empty($groupCompanies) && $groupCompanies->isNotEmpty())
                                                @foreach ($groupCompanies as $company)
                                                    <option value="{{ $company->value }}"
                                                        @if (isset($global) && is_object($global) && isset($global->group_company_id) && $global->group_company_id == $company->value) selected @endif>
                                                        {{ $company->label }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="" disabled>No companies available</option>
                                            @endif
                                        </select>
                                    </div>

                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-5">
                                        <label class="plant-label">Location</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control new-select" name="company_location_id"
                                            id = "company_location_dropdown">
                                            @foreach (@$locations as $location)
                                                <option value="{{ $location->value }}"
                                                    @if (@$global->company_location_id == $location->value) selected @endif>{{ $location->label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row align-items-center mt-3">
                                    <div class="col-md-5">
                                        <label class="plant-label">Batching Quality Inspection</label>
                                    </div>
                                    {{-- @dd($global); --}}
                                    <div class="col-md-7">
                                        <div class="position-relative">
                                            <div class="input-group">

                                                <input type="text" class="form-control new-input"
                                                    name="batching_quality_inspection"
                                                    value="{{ $global ? $global->batching_quality_inspection : '' }}"
                                                    placeholder="Enter duration in minutes">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text min-text">(min)</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="row align-items-center mt-3">
                                    <div class="col-md-5">
                                        <label class="plant-label">Mixture Chute At Cleaning</label>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="position-relative">
                                            <div class="input-group">
                                                <input type="text" class="form-control new-input"
                                                    name="mixture_chute_cleaning"
                                                    value="{{ $global ? $global->mixture_chute_cleaning : '' }}"
                                                    placeholder="Enter duration in minutes">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text min-text">(min)</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}

                                <div class="row align-items-center mt-3">
                                    <div class="col-md-5">
                                        <label class="plant-label">Site Quality Inspection</label>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="position-relative">
                                            <div class="input-group">
                                                <input type="text" class="form-control new-input"
                                                    name="site_quality_inspection"
                                                    value="{{ $global ? $global->site_quality_inspection : '' }}"
                                                    placeholder="Enter duration in minutes">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text min-text">(min)</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- <div class="row align-items-center mt-3">
                                    <div class="col-md-5">
                                        <label class="plant-label">Chute Cleaning Site</label>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="position-relative">
                                            <div class="input-group">
                                                <input type="text" class="form-control new-input"
                                                    name="chute_cleaning_site"
                                                    value="{{ $global ? $global->chute_cleaning_site : '' }}"
                                                    placeholder="Enter duration in minutes">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text min-text">(min)</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="row align-items-center mt-3">
                                    <div class="col-md-5">
                                        <label class="plant-label">Transite Mixture Cleaning</label>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="position-relative">
                                            <div class="input-group">
                                                <input type="text" class="form-control new-input"
                                                    name="transite_mixture_cleaning"
                                                    value="{{ $global ? $global->transite_mixture_cleaning : '' }}"
                                                    placeholder="Enter duration in minutes">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text min-text">(min)</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-sm-3 mt-2">
                                        <div class="col-md-4 col-7">
                                            <button type="button" data-request="ajax-submit"
                                                data-target="[role=post-data]" class="btn new-btn mr-3">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
