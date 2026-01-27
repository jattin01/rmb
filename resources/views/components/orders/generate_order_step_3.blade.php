@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-3 order-1 order-sm-1 mb-sm-0 mb-3">
                        <div class="top-head">
                            <h1>Generate Schedule</h1>
                            <h6><span class="active">Schedule</span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                                Select Date </h6>
                        </div>
                    </div>

                    @include('partials.order_tabs', ['active' => 'contact'])

                    <div class="col-md-3 order-sm-3 order-2 col-3 mb-sm-0 mb-3 text-right">
                        <button onclick="window.history.back();" type="button" class="btn back-btn">Back</button>
                    </div>
                </div>
               
                <div class="row">
                    <div class="col-md-12">
                        <div class="tab-pane fade show active" id="contact">
                            <div class="readymix-contentbox mt-sm-5 mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6 mb-2 mb-sm-0">
                                        <h6>RMB Readymix Dubai</h6>
                                    </div>
                                    <div class="col-md-6 text-sm-right">
                                        <p id="schedule_date_label"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div id="accordion" class="resources-accordion">
                                        <div class="card">
                                            <div class="card-header" id="headingOne">
                                                <h5 class="mb-0">
                                                    <button class="btn btn-link" data-toggle="collapse"
                                                        data-target="#collapseOne" aria-expanded="true"
                                                        aria-controls="collapseOne">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-6 col-7">Batching Plants
                                                                (@php
                                                                    $totalCount = 0;
                                                                    $batching_plants->each(function (
                                                                        $firstLevelGroup,
                                                                    ) use (&$totalCount) {
                                                                        $firstLevelGroup->each(function (
                                                                            $secondLevelGroup,
                                                                        ) use (&$totalCount) {
                                                                            $totalCount += $secondLevelGroup->count();
                                                                        });
                                                                    });
                                                                    echo $totalCount;
                                                                @endphp)
                                                            </div>
                                                            <div class="col-md-6 col-5 pl-0 text-right">
                                                                <span class="selected-btn mr-2"
                                                                    id="batching-plant-total-selected">{{ $totalCount }}
                                                                    Selected</span>
                                                                <i class="fa fa-plus float-right" aria-hidden="true"></i>
                                                                <i class="fa fa-minus float-right" aria-hidden="true"></i>
                                                            </div>
                                                        </div>
                                                    </button>
                                                </h5>
                                            </div>
                                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                                                data-parent="#accordion">
                                                <div class="card-body pt-0">
                                                    <div class="border-top"></div>
                                                    {{-- @foreach ($batching_plants as $bp_key => $bp_val)
												<div class="row mt-sm-4 mt-3 align-items-center">
													<div class="col-md-6 col-6">
														<div class="filter-check new-filtercheck">
															<input type="checkbox" checked class="filled-in" disabled
																id="exampleCheck25">
															<label class="selected-label"
																for="exampleCheck25">{{$bp_key}}
																({{$bp_val->map->count()->sum()}})
															</label>
														</div>
													</div>
													<div class="col-md-5 col-6 text-right">
														<span class="selected-btn"
															id="sub-batching-plant-selected-{{$bp_key}}">{{$bp_val->map->count()->sum()}}
															Selected</span>
													</div>
												</div> --}}


                                                    @foreach ($batching_plants as $bp_key => $bp_val)
                                                        <div class="row mt-sm-4 mt-3 align-items-center">
                                                            <div class="col-md-6 col-6">
                                                                <div class="filter-check new-filtercheck">
                                                                    <input type="checkbox" class="filled-in"
                                                                        id="checkbox_{{ $bp_key }}"
                                                                        @if ($loop->first) checked @endif>
                                                                    <label class="selected-label"
                                                                        for="checkbox_{{ $bp_key }}">
                                                                        {{ $bp_key }}
                                                                        ({{ $bp_val->map->count()->sum() }})
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-5 col-6 text-right">
                                                                <span class="selected-btn"
                                                                    id="sub-batching-plant-selected-{{ $bp_key }}">
                                                                    {{ $bp_val->map->count()->sum() }} Selected
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="readymix-contentbox mt-sm-3 mt-2">
                                                            <div class="row">
                                                                @foreach ($bp_val as $sub_bp_key => $sub_bp_val)
                                                                    <div class="col-md-4 mb-2 mb-sm-0">
                                                                        <div class="row">
                                                                            <div class="col-md-8 col-7">
                                                                                <div class="hours-box">
                                                                                    <h3>{{ $sub_bp_key }} m3/hr</h3>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-4 col-5">
                                                                                <div class="input-group select-quntitybox">
                                                                                    <div class="input-group-prepend">
                                                                                        <span class=""><i
                                                                                                class="fa fa-minus"
                                                                                                onclick="remove_value('bp-input-{{ $bp_key }}-{{ $sub_bp_key }}')"
                                                                                                aria-hidden="true"></i></span>
                                                                                    </div>
                                                                                    <input type="number"
                                                                                        data-capacity="{{ $sub_bp_key }}"
                                                                                        data-loc="{{ $bp_key }}"
                                                                                        data-location="{{ $sub_bp_val[0]->company_location_id }}"
                                                                                        data-bps="{{ $sub_bp_val }}"
                                                                                        class="form-control input-quntitybox batching_input bp-{{ $bp_key }}"
                                                                                        value="{{ count($sub_bp_val) }}"
                                                                                        id="bp-input-{{ $bp_key }}-{{ $sub_bp_key }}"
                                                                                        min="0"
                                                                                        max="{{ count($sub_bp_val) }}"
                                                                                        onchange="bp_total_selected_count();">
                                                                                    <div class="input-group-append">
                                                                                        <span class=""><i
                                                                                                onclick="add_value('bp-input-{{ $bp_key }}-{{ $sub_bp_key }}')"
                                                                                                class="fa fa-plus"
                                                                                                aria-hidden="true"></i></span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-header" id="headingTwo">
                                                <h5 class="mb-0">
                                                    <button class="btn btn-link collapsed" data-toggle="collapse"
                                                        data-target="#collapseTwo" aria-expanded="false"
                                                        aria-controls="collapseTwo">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-6 col-7">Transit Mixer
                                                                ({{ $transit_mixers->map->count()->sum() }})
                                                            </div>
                                                            <div class="col-md-6 col-5 pl-0 text-right">
                                                                <span class="selected-btn mr-2"
                                                                    id="transit-mixer-total-selected">({{ $transit_mixers->map->count()->sum() }})
                                                                    Selected</span>
                                                                <i class="fa fa-plus float-right" aria-hidden="true"></i>
                                                                <i class="fa fa-minus float-right" aria-hidden="true"></i>
                                                            </div>
                                                        </div>
                                                    </button>
                                                </h5>
                                            </div>
                                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                                                data-parent="#accordion">
                                                <div class="card-body pt-0">
                                                    <div class="border-top"></div>
                                                    <div class="readymix-contentbox mt-sm-4 mt-2">
                                                        <div class="row">
                                                            @foreach ($transit_mixers as $tm_key => $tm_val)
                                                                <div class="col-md-4">
                                                                    <div class="row">
                                                                        <div class="col-md-8 col-7">
                                                                            <div class="hours-box">
                                                                                <h3>{{ $tm_key }} CUM</h3>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4 col-5">
                                                                            <div class="input-group select-quntitybox">
                                                                                <div class="input-group-prepend">
                                                                                    <span class=""><i
                                                                                            onclick="remove_value('tm-input-{{ $tm_key }}-{{ $tm_val }}')"
                                                                                            class="fa fa-minus"
                                                                                            aria-hidden="true"></i></span>
                                                                                </div>
                                                                                <input type="number"
                                                                                    data-capacity="{{ $tm_key }}"
                                                                                    data-tms="{{ $tm_val }}"
                                                                                    class="form-control input-quntitybox transit_mixer_input"
                                                                                    value="{{ count($tm_val) }}"
                                                                                    id="tm-input-{{ $tm_key }}-{{ $tm_val }}"
                                                                                    min="0"
                                                                                    max="{{ count($tm_val) }}"
                                                                                    onchange="tm_total_selected_count();">
                                                                                <div class="input-group-append">
                                                                                    <span class=""><i
                                                                                            onclick="add_value('tm-input-{{ $tm_key }}-{{ $tm_val }}')"
                                                                                            class="fa fa-plus"
                                                                                            aria-hidden="true"></i></span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-header" id="headingThree">
                                                <h5 class="mb-0">
                                                    <button class="btn btn-link collapsed" data-toggle="collapse"
                                                        data-target="#collapseThree" aria-expanded="false"
                                                        aria-controls="collapseThree">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-6 col-7">Pumps
                                                                (@php
                                                                    $pumpTotalCount = 0;
                                                                    $pumps->each(function ($firstLevelGroup) use (
                                                                        &$pumpTotalCount,
                                                                    ) {
                                                                        $firstLevelGroup->each(function (
                                                                            $secondLevelGroup,
                                                                        ) use (&$pumpTotalCount) {
                                                                            $pumpTotalCount += $secondLevelGroup->count();
                                                                        });
                                                                    });
                                                                    echo $pumpTotalCount;
                                                                @endphp)
                                                            </div>
                                                            <div class="col-md-6 col-5 pl-0 text-right">
                                                                <span class="selected-btn mr-2"
                                                                    id="pump-total-selected">{{ $pumpTotalCount }}
                                                                    Selected</span>
                                                                <i class="fa fa-plus float-right" aria-hidden="true"></i>
                                                                <i class="fa fa-minus float-right" aria-hidden="true"></i>
                                                            </div>
                                                        </div>
                                                    </button>
                                                </h5>
                                            </div>
                                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                                                data-parent="#accordion">
                                                <div class="card-body pt-0">
                                                    <div class="border-top"></div>
                                                    @foreach ($pumps as $pump_key => $pump_val)
                                                        <div class="row mt-sm-4 mt-3 align-items-center">
                                                            <div class="col-md-6 col-6">
                                                                <div class="pumps-check">
                                                                    <input type="checkbox" checked class="filled-in"
                                                                        disabled id="exampleCheck30">
                                                                    <label class="selected-label"
                                                                        for="exampleCheck30">{{ $pump_key }}</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-5 col-6 text-right">
                                                                <span class="selected-btn"
                                                                    id="sub-pump-selected-{{ $pump_key }}">
                                                                    {{ $pump_val->map->count()->sum() }} Selected</span>
                                                            </div>
                                                        </div>
                                                        <div class="readymix-contentbox mt-sm-3 mt-2">
                                                            <div class="row">
                                                                @foreach ($pump_val as $sub_pump_key => $sub_pump_val)
                                                                    <div class="col-md-4 mb-2 mb-sm-0">
                                                                        <div class="row">
                                                                            <div class="col-md-8 col-7">
                                                                                <div class="hours-box">
                                                                                    <h3>{{ $sub_pump_key }} m</h3>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-4 col-5">
                                                                                <div class="input-group select-quntitybox">
                                                                                    <div class="input-group-prepend">
                                                                                        <span class=""><i
                                                                                                onclick="remove_value('p-input-{{ $pump_key }}-{{ $sub_pump_key }}')"
                                                                                                class="fa fa-minus"
                                                                                                aria-hidden="true"></i></span>
                                                                                    </div>
                                                                                    <input type="number"
                                                                                        data-capacity="{{ $sub_pump_key }}"
                                                                                        data-pumps="{{ $sub_pump_val }}"
                                                                                        data-type="{{ $pump_key }}"
                                                                                        class="form-control input-quntitybox pump_input p-{{ $pump_key }}"
                                                                                        value="{{ count($sub_pump_val) }}"
                                                                                        id="p-input-{{ $pump_key }}-{{ $sub_pump_key }}"
                                                                                        min="0"
                                                                                        max="{{ count($sub_pump_val) }}"
                                                                                        onchange="p_total_selected_count();">
                                                                                    <div class="input-group-append">
                                                                                        <span class=""><i
                                                                                                onclick="add_value('p-input-{{ $pump_key }}-{{ $sub_pump_key }}')"
                                                                                                class="fa fa-plus"
                                                                                                aria-hidden="true"></i></span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-sm-5 mt-4 justify-content-center">
                                <div class="col-md-3 col-8">
                                    <button class="btn apply-btn btn-block" type="button"
                                        onclick="generate_schedule();">Continue</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade schedule-modalcontent" id="schedule-modal" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <img src="img/filter-close.svg" alt=""> close
                    </button>
                </div>
                <div class="modal-body">
                    <div class="generating-schedulebox text-center pb-sm-5 mb-2">
                        <div class="mb-sm-3 mb-2">
                            <!-- <img id="generate_loading_gif" src="{{ asset('assets/img/dots.gif') }}" alt=""> -->
                            <lottie-player src="{{ asset('assets/animation_llui8h16.json') }}" background="transparent"
                                speed="1" style="height: 92px;" loop autoplay></lottie-player>
                        </div>
                        <h6>Generating Schedule!</h6>
                        <p>This might take some time!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            setInputsFromQueryParams();
            var elements = document.getElementsByClassName('input-quntitybox');
            for (let index = 0; index < elements.length; index++) {
                elements[index].oninput = function() {
                    var max = parseInt(this.max);
                    if (parseInt(this.value) > max) {
                        this.value = max;
                    }
                }
            }
        });

        function add_value(id) {
            var element = document.getElementById(id);
            var max = parseInt(element.max);
            if (parseInt(element.value) >= max) {
                element.value = max;
            } else {
                element.value = parseInt(element.value) + 1;
            }
            bp_total_selected_count();
            tm_total_selected_count();
            p_total_selected_count();
        }

        function remove_value(id) {
            var element = document.getElementById(id);
            var min = 0;
            if (parseInt(element.value) <= min) {
                element.value = min;
            } else {
                element.value = parseInt(element.value) - 1;
            }
            bp_total_selected_count();
            tm_total_selected_count();
            p_total_selected_count();
        }

        function generate_schedule() {
            
            // Show the modal before making the API request
            $('#schedule-modal').modal('show');
            var date = getQueryParam('schedule_date');
            var company_id = getQueryParam('company_id');
            var pumps = [];
            var batching_plants = [];
            var transit_mixers = [];

            var bps = document.getElementsByClassName("batching_input");
            for (let bp_loop = 0; bp_loop < bps.length; bp_loop++) {
                if (document.getElementById("checkbox_" + bps[bp_loop].dataset.loc)?.checked == true) { // for selecting only checked data
                    for (let index = 0; index < bps[bp_loop].value; index++) {
                        batching_plants.push(JSON.parse(bps[bp_loop].dataset.bps)[index].id)
                    }
                }

            }
            var pumps_input = document.getElementsByClassName("pump_input");
            for (let pump_loop = 0; pump_loop < pumps_input.length; pump_loop++) {
                for (let index = 0; index < pumps_input[pump_loop].value; index++) {
                    pumps.push(JSON.parse(pumps_input[pump_loop].dataset.pumps)[index].id)
                }
            }
            var tm_inputs = document.getElementsByClassName("transit_mixer_input");
            for (let truck_loop = 0; truck_loop < tm_inputs.length; truck_loop++) {
                for (let index = 0; index < tm_inputs[truck_loop].value; index++) {
                    transit_mixers.push(JSON.parse(tm_inputs[truck_loop].dataset.tms)[index].id)
                }
            }
            localStorage.setItem("transit_mixers", JSON.stringify(transit_mixers));
            localStorage.setItem("pumps", JSON.stringify(pumps));
            localStorage.setItem("batching_plants", JSON.stringify(batching_plants));

            // Make your API request
            $.ajax({
                url: "{{ route('orders.schedule.generate') }}",
                method: 'POST',
                data: {
                    company_id: company_id,
                    schedule_date: date,
                    pumps: pumps,
                    transit_mixers: transit_mixers,
                    batching_plants: batching_plants,
                    schedule_preference: localStorage.getItem("schedule_preference"),
                    interval_deviation: localStorage.getItem("interval_deviation"),
                    '_token': '{{ csrf_token() }}'
                },
                success: function(data) {
                    // API request is complete, hide the modal
                    
                    window.location.href = "{{ route('orders.schedule.view') }}?schedule_date=" + date +
                        "&company_id=" + company_id;
                    $('#schedule-modal').modal('hide')
                    // Process the API response as needed
                },
                error: function(error) {
                    // Handle errors if necessary
                }
            });
        }

        function getQueryParam(name) {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        function setInputsFromQueryParams() {
            var sch_date = getQueryParam('schedule_date');
            // Set values to input elements
            document.getElementById('schedule_date_label').innerHTML = moment(sch_date).format("dddd, D MMMM YYYY");
        }

        function bp_total_selected_count() {
            var main_ele = document.getElementById("batching-plant-total-selected");
            var child_eles = document.getElementsByClassName("batching_input");
            var total = 0;
            var loc_array = [];
            for (let index = 0; index < child_eles.length; index++) {
                bp_location_selected_count(child_eles[index].dataset.loc, child_eles[index].className)
                total += parseInt(child_eles[index].value);
            }
            main_ele.innerHTML = total + " Selected";
        }

        function bp_location_selected_count(loc, child_id) {
            var main_ele = document.getElementById("sub-batching-plant-selected-" + loc);
            var child_eles = document.getElementsByClassName(child_id);
            var total = 0;
            for (let index = 0; index < child_eles.length; index++) {
                if (loc == child_eles[index].dataset.loc) {
                    total += parseInt(child_eles[index].value);
                }
            }
            main_ele.innerHTML = total + " Selected";
        }

        function p_total_selected_count() {
            var main_ele = document.getElementById("pump-total-selected");
            var child_eles = document.getElementsByClassName("pump_input");
            var total = 0;
            var loc_array = [];
            for (let index = 0; index < child_eles.length; index++) {
                p_location_selected_count(child_eles[index].dataset.type, child_eles[index].className)
                total += parseInt(child_eles[index].value);
            }
            main_ele.innerHTML = total + " Selected";
        }

        function p_location_selected_count(type, child_id) {
            var main_ele = document.getElementById("sub-pump-selected-" + type);
            var child_eles = document.getElementsByClassName(child_id);
            var total = 0;
            for (let index = 0; index < child_eles.length; index++) {
                if (type == child_eles[index].dataset.type) {
                    total += parseInt(child_eles[index].value);
                }
            }
            main_ele.innerHTML = total + " Selected";
        }

        function tm_total_selected_count() {
            var main_ele = document.getElementById("transit-mixer-total-selected");
            var child_eles = document.getElementsByClassName("transit_mixer_input");
            var total = 0;
            for (let index = 0; index < child_eles.length; index++) {
                total += parseInt(child_eles[index].value);
            }
            main_ele.innerHTML = total + " Selected";
        }

        function selectCheckbox(bpKey) {
            var checkbox = document.getElementById('checkbox_' + bpKey);
            checkbox.checked = true;
        }

        function unselectCheckbox(bpKey) {
            var checkbox = document.getElementById('checkbox_' + bpKey);
            checkbox.checked = false;
        }
    </script>
@endsection
