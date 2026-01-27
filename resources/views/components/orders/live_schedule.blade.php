@extends('layouts.auth.app')
@section('content')

    <section class="content">
        <div class="container-fluid">

            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center">
                    <div class="col-md-3 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Schedule</h1>
                            <h6>Schedule <i class="fa fa-angle-right" aria-hidden="true"></i> <span class="active">Order</span>
                            </h6>
                        </div>
                    </div>
                    <div class="col-md-5 mb-sm-0 mb-3">
                        <!-- <ul class="nav nav-tabs order-tab" id="myTab" role="tablist">
             <li class="nav-item">
              <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home"
               role="tab" aria-controls="home" aria-selected="true">Orders</a>
             </li>
             <li class="nav-item">
              <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile"
               role="tab" aria-controls="profile" aria-selected="false">Batching Plant</a>
             </li>
             <li class="nav-item">
              <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact"
               role="tab" aria-controls="contact" aria-selected="false">Transit Mixer</a>
             </li>
             <li class="nav-item">
              <a class="nav-link" id="pumps-tab" data-toggle="tab" href="#pumps" role="tab"
               aria-controls="pumps" aria-selected="false">Pumps</a>
             </li>

            </ul> -->
                    </div>
                    <div class="col-md-4  text-sm-right">
                        <div class = "row">
                            <div class = "col-md-10">
                                <div class="dropdown show calender-box">
                                    <button class="btn calender-btn dropdown-toggle" href="#" role="button"
                                    id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false" disabled>
                                <span class="calender-img">
                                    <img src="{{ asset('assets/img/calender-img.svg') }}" alt="">
                                </span>
                                {{ Carbon\Carbon::now()->format('l, d F, Y') }}
                            </button>


                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="calendar-drop">
                                                    <div id="calendar"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class = "col-md-2">
                                <button type="button" class="turn-btn"><img src="{{ asset('assets/img/refresh-img.svg') }}"
                                        onclick = "refreshPage();" class="refresh-img" alt=""></button>

                            </div>
                        </div>





                    </div>

                </div>


                <div class="row">
                    <div class="col-md-12">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="home" role="tabpanel"
                                aria-labelledby="home-tab">

                                <div class="row mt-sm-5 mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group position-relative">
                                            <input type="email" class="form-control search-byinpt padding-right"
                                                placeholder="Search By...">
                                            <img src="{{ asset('assets/img/fill-search.svg') }}" class="fill-serchimg"
                                                alt="">
                                        </div>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <!-- <button type="button" class="btn btn-primary mr-2">Orders</button>
                <button type="button" class="btn btn-success mr-2">Resources</button> -->
                                        <button type="button" class="btn export-btn mr-2">Export</button>
                                        <select class="filter-select mr-2 mt-sm-0 mt-3">
                                            <option>Filters</option>
                                            <option>2</option>
                                            <option>3</option>
                                            <option>4</option>
                                            <option>5</option>
                                        </select>
                                        <button type="button" class="btn icon-btn mr-2" onclick="toggleTable();">
                                            <img src = "{{ asset('assets/img/toggle_table.svg') }}" />
                                        </button>

                                    </div>
                                </div>

                                <div class="row mt-3 mt-sm-2 align-items-center">
                                    <div class="col-md-6 mb-sm-0 mb-2">
                                        <!-- <div class="d-flex align-items-center">
                <span class="order-valuebox mr-sm-2 mr-1">2345 <span><img src="img/button-cross.svg" class="ml-sm-2 ml-1" alt=""></span> </span>
                <span class="order-valuebox mr-sm-3 mr-2">Aaron John <span><img src="img/button-cross.svg" class="ml-sm-2 ml-1" alt=""></span></span>
                <h6 class="clear-text">CLEAR FILTER</h6>
                </div> -->
                                    </div>
                                    <div class="col-md-6 text-sm-right">
                                        <span class="schedule-chartvalue mr-2"> <span class="dots-box planned"></span>
                                            Planned</span>
                                        <span class="schedule-chartvalue mr-2"> <span class="dots-box early-start"></span>
                                            Revised</span>
                                        <!-- <span class="schedule-chartvalue mr-2" > <span class="dots-box delivered"></span> On Time</span>
                <span class="schedule-chartvalue mr-2" > <span class="dots-box inprogress"></span> Behind Schedule</span> -->
                                        <span class="schedule-chartvalue mr-2"> <span class="dots-box delay"></span>
                                            Delay</span>
                                        <span class="schedule-chartvalue mr-2"> <span class="dots-box early-start"></span>
                                            Early Start</span>
                                    </div>
                                </div>

                                <div class="row mt-sm-4 mt-3">
                                    <div class="col-md-12">
                                        <div class="table-responsive position-relative">
                                            <table class="table chart-table progress-linechart">
                                                <tbody>
                                                    <tr>
                                                        <th>
                                                            <div class="head-innerbox">
                                                                Orders
                                                            </div>
                                                        </th>

                                                        @foreach ($slots as $slot)
                                                            <th>
                                                                <div class="inner-middle">
                                                                    <p class="chart-tableheadtext"> {{ $slot['end_time'] }}
                                                                    </p>
                                                                    <span class="firt-line middle"></span>
                                                                    <span class="firt-line "></span>
                                                                    <span class="firt-line "></span>
                                                                    <span class="firt-line"></span>
                                                                    <span class="firt-line"></span>
                                                                    <span class="firt-line"></span>
                                                                </div>
                                                            </th>
                                                        @endforeach




                                                    </tr>

                                                    @foreach ($result['resData'] as $res)
                                                        <tr>
                                                            <td>
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center">
                                                                    <div class = "text small">
                                                                        Order {{ $res['order_no'] }} ({{ $res['quantity'] }}
                                                                        CUM)
                                                                    </div>
                                                                    <div>
                                                                        <img src="{{ asset('assets/img/metro-info.svg') }}"
                                                                            class="mr-2 cursor-pointer"
                                                                            onclick = "getLiveOrderDetails({{ $res['id'] }});"
                                                                            alt="">
                                                                        <img src="{{ asset('assets/img/gray-more.svg') }}"
                                                                            id = "more-icon-{{ $res['order_no'] }}"
                                                                            onclick="toggleDropdown({{ $res['order_no'] }})"
                                                                            alt="" style="cursor: pointer;">
                                                                    </div>
                                                                </div>
                                                                <span
                                                                    class="plant-texttable">{{ $res['location'] }}</span>
                                                                <span class="text small">&nbsp; &nbsp; Trips:
                                                                    {{ count($res['schedule']) }} </span>
                                                                <span class="text small">&nbsp; &nbsp; Pumps:
                                                                    {{ ($res['pump_qty']) }} </span>

                                                            </td>
                                                            @foreach ($res['resultData'] as $resData)
                                                                <td
                                                                    colspan="{{ isset($resData['colspan']) ? $resData['colspan'] : 1 }}">

                                                                    @if (isset($resData['id']))
                                                                        <div class="main-progressbox">
                                                                            <div class="progress progress-bargreen ml{{ $resData['start_minutes'] }}"
                                                                                id = "progress-bar-{{ $res['id'] }}"
                                                                                style = "width : {{ $resData['total_pixels'] ? $resData['total_pixels'] . 'px' : '0%' }}">

                                                                                @php
                                                                                    $deviation =
                                                                                        $resData['late_deviation'] > 0
                                                                                            ? $resData[
                                                                                                    'late_deviation'
                                                                                                ] . ' Mins Late'
                                                                                            : ($resData[
                                                                                                'early_deviation'
                                                                                            ]
                                                                                                ? $resData[
                                                                                                        'early_deviation'
                                                                                                    ] . ' Mins Early'
                                                                                                : null);
                                                                                    $duration = Carbon\Carbon::parse(
                                                                                        $res['planned_start_time'],
                                                                                    )->eq(
                                                                                        Carbon\Carbon::parse(
                                                                                            $res['planned_end_time'],
                                                                                        ),
                                                                                    )
                                                                                        ? 'Schedule - ' .
                                                                                            Carbon\Carbon::parse(
                                                                                                $res[
                                                                                                    'planned_end_time'
                                                                                                ],
                                                                                            )->format('h:i A') .
                                                                                            ' '
                                                                                        : ' Schedule - ' .
                                                                                            Carbon\Carbon::parse(
                                                                                                $res[
                                                                                                    'planned_start_time'
                                                                                                ],
                                                                                            )->format('h:i A') .
                                                                                            ' to ' .
                                                                                            Carbon\Carbon::parse(
                                                                                                $res[
                                                                                                    'planned_end_time'
                                                                                                ],
                                                                                            )->format('h:i A') .
                                                                                            ' ';
                                                                                    $bar_title =
                                                                                        'Delivery - ' .
                                                                                        Carbon\Carbon::parse(
                                                                                            $res['delivery_date'],
                                                                                        )->format('h:i A') .
                                                                                        ', ' .
                                                                                        $duration;
                                                                                    $main_title =
                                                                                        $bar_title .
                                                                                        ($deviation
                                                                                            ? '(' . $deviation . ')'
                                                                                            : null);
                                                                                @endphp

                                                                                <!-- <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div> -->

                                                                                <!-- @if ($resData['early_deviation_pixel'] > 0)
    <div class="progress-bar multi-firstdarkblue" data-toggle="tooltip" data-placement="bottom" title="{{ $main_title }}" role="progressbar" style="padding : 0%; width: {{ $resData['early_deviation_pixel'] ? $resData['early_deviation_pixel'] . 'px' : '0%' }}" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
    @endif

                     @if ($resData['late_deviation_pixel'] > 0)
    <div class="progress-bar multi-firstred" data-toggle="tooltip" data-placement="bottom"title="{{ $main_title }}" role="progressbar" style="padding : 0%; width: {{ $resData['late_deviation_pixel'] ? $resData['late_deviation_pixel'] . 'px' : '0%' }}" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
    @endif -->

                                                                                <!-- <div class="progress-bar multi-firstblue" data-toggle="tooltip" data-placement="bottom" title="{{ $main_title }}" role="progressbar" style="padding : 0%; width  : {{ $resData['total_pixels'] ? $resData['total_pixels'] . 'px' : '0%' }}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div> -->
                                                                            </div>
                                                                        </div>

                                                                        <div class="stip-bgmainebox">
                                                                            @foreach ($resData['stripe'] as $stripe)
                                                                                <span
                                                                                    class="{{ $stripe % 2 !== 0 ? 'white-stip' : 'frist-stip' }}"></span>
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <div class="stip-bgmainebox">
                                                                            <span class="white-stip"></span>
                                                                            <span class="frist-stip"></span>
                                                                            <span class="white-stip"></span>
                                                                            <span class="frist-stip"></span>
                                                                            <span class="white-stip"></span>
                                                                            <span class="frist-stip"></span>
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                        </tr>

                                                        @if (isset($res['schedule']) && count($res['schedule']) > 0)
                                                            <tr class = "secound-table schedule-graph-hidden schedule-graph-{{ $res['order_no'] }}"
                                                                id = "schedule-{{ $res['order_no'] }}">
                                                                <td colspan="4">
                                                                    {{ Carbon\Carbon::parse($res['planned_start_time'])->format('h:i A') }}
                                                                    -
                                                                    {{ Carbon\Carbon::parse($res['planned_end_time'])->format('h:i A') }},
                                                                    {{ $res['customer'] }} - {{ $res['site'] }}
                                                                </td>
                                                                <td colspan="8">
                                                                    <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                                            class="dots-box early-start"></span> Loading
                                                                    </span>
                                                                    <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                                            class="dots-box internal"></span> Internal QC
                                                                    </span>
                                                                    <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                                            class="dots-box travelling"></span> Travelling
                                                                        to Site </span>
                                                                    <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                                            class="dots-box onsite"></span> Onsite
                                                                        Inspection </span>
                                                                    <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                                            class="dots-box  pouring"></span> Pouring
                                                                    </span>
                                                                    <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                                            class="dots-box cleaning"></span> Cleaning
                                                                    </span>
                                                                    <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                                            class="dots-box return"></span> Return to Plant
                                                                    </span>
                                                                </td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>

                                                            </tr>

                                                            @foreach ($res['schedule'] as $sch)
                                                                <tr class = "schedule-graph-hidden schedule-graph-{{ $res['order_no'] }}"
                                                                    id = "schedule-{{ $res['order_no'] }}">
                                                                    <td>
                                                                        <div
                                                                            class="d-flex align-items-center justify-content-between">
                                                                            <div>
                                                                                <div class = "text small">Truck
                                                                                    {{ $sch['transit_mixer'] . ' ' }}</div>
                                                                                <br />
                                                                                <div class = "text small muted">
                                                                                    {{ $sch['batching_plant'] }} -
                                                                                    {{ $sch['batching_qty'] }} CUM</div>
                                                                                <span class="text small"></span>
                                                                            </div>

                                                                            <div onclick="getLiveTripDetails({{$sch['id']}})" data-id="{{$sch['id']}}">
                                                                                <img src="{{ asset('assets/img/light-info.svg') }}"
                                                                                    alt="">
                                                                            </div>
                                                                        </div>
                                                                    </td>

                                                                    @foreach ($sch['resultData'] as $schResData)
                                                                        <td
                                                                            colspan="{{ isset($schResData['colspan']) ? $schResData['colspan'] : 1 }}">

                                                                            @if (isset($schResData['id']))
                                                                                <div class="main-progressbox">
                                                                                    <style>
                                                                                        .chartmiddlelineDynamic{{ $schResData['id'] }}::after {
                                                                                            content: " ";
                                                                                            border-bottom: 3px solid #000000;
                                                                                            left: 0;
                                                                                            position: absolute;
                                                                                            top: 9px;
                                                                                            width: {{ $schResData['live_marker_width'] }}px;
                                                                                        }
                                                                                    </style>
                                                                                    <div class="progress constructions-chart chartmiddlelineDynamic{{ $schResData['id'] }} ml"
                                                                                        style = "margin-left:{{ $schResData['start_minutes'] . 'px' }};">
                                                                                        @if ($schResData['loading_delay_pixels'])
                                                                                            <div class="progress-bar red"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Delay"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['loading_delay_pixels'] ? $schResData['loading_delay_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        @if ($schResData['loading_early_pixels'])
                                                                                            <div class="progress-bar dark-blue"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Early"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['loading_early_pixels'] ? $schResData['loading_early_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        <div class="progress-bar skyblue"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($sch['planned_loading_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['planned_loading_end'])->format('h:i A') . ' (' . $sch['planned_loading_time'] . ' mins)' }}"
                                                                                            role="progressbar"
                                                                                            style="padding : 0%; width :  {{ $schResData['planned_loading_pixels'] ? $schResData['planned_loading_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>

                                                                                        @if ($schResData['qc_delay_pixels'])
                                                                                            <div class="progress-bar red"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Delay"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['qc_delay_pixels'] ? $schResData['qc_delay_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        @if ($schResData['qc_early_pixels'])
                                                                                            <div class="progress-bar dark-blue"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Early"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['qc_early_pixels'] ? $schResData['qc_early_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        <div class="progress-bar pink"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($sch['planned_qc_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['planned_qc_end'])->format('h:i A') . ' (' . $sch['planned_qc_time'] . ' mins)' }}"
                                                                                            role="progressbar"
                                                                                            style="padding : 0%; width :  {{ $schResData['planned_qc_pixels'] ? $schResData['planned_qc_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>

                                                                                        @if ($schResData['travel_delay_pixels'])
                                                                                            <div class="progress-bar red"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Delay"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['travel_delay_pixels'] ? $schResData['travel_delay_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        @if ($schResData['travel_early_pixels'])
                                                                                            <div class="progress-bar dark-blue"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Early"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['travel_early_pixels'] ? $schResData['travel_early_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        <div class="progress-bar purple"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($sch['planned_travel_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['planned_travel_end'])->format('h:i A') . ' (' . $sch['planned_travel_time'] . ' mins)' }}"
                                                                                            role="progressbar"
                                                                                            style="padding : 0%; width :  {{ $schResData['planned_travel_pixels'] ? $schResData['planned_travel_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>

                                                                                        @if ($schResData['insp_delay_pixels'])
                                                                                            <div class="progress-bar red"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Delay"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['insp_delay_pixels'] ? $schResData['insp_delay_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif

                                                                                        @if ($schResData['insp_early_pixels'])
                                                                                            <div class="progress-bar dark-blue"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Early"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['insp_early_pixels'] ? $schResData['insp_early_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        <div class="progress-bar dark-green"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($sch['planned_insp_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['planned_insp_end'])->format('h:i A') . ' (' . $sch['planned_insp_time'] . ' mins)' }}"
                                                                                            role="progressbar"
                                                                                            style="padding : 0%; width :  {{ $schResData['planned_insp_pixels'] ? $schResData['planned_insp_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>

                                                                                        @if ($schResData['pouring_delay_pixels'])
                                                                                            <div class="progress-bar red"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Delay"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['pouring_delay_pixels'] ? $schResData['pouring_delay_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif

                                                                                        @if ($schResData['pouring_early_pixels'])
                                                                                            <div class="progress-bar dark-blue"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Early"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['pouring_early_pixels'] ? $schResData['pouring_early_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        <div class="progress-bar dark-blue"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($sch['planned_pouring_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['planned_pouring_end'])->format('h:i A') . ' (' . $sch['planned_pouring_time'] . ' mins)' }}"
                                                                                            role="progressbar"
                                                                                            style="padding : 0%; width :  {{ $schResData['planned_pouring_pixels'] ? $schResData['planned_pouring_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>

                                                                                        @if ($schResData['cleaning_delay_pixels'])
                                                                                            <div class="progress-bar red"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Delay"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['cleaning_delay_pixels'] ? $schResData['cleaning_delay_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        @if ($schResData['cleaning_early_pixels'])
                                                                                            <div class="progress-bar dark-blue"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Early"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['cleaning_early_pixels'] ? $schResData['cleaning_early_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        <div class="progress-bar nevy-blue"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($sch['planned_cleaning_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['planned_cleaning_end'])->format('h:i A') . ' (' . $sch['planned_cleaning_time'] . ' mins)' }}"
                                                                                            role="progressbar"
                                                                                            style="padding : 0%; width :  {{ $schResData['planned_cleaning_pixels'] ? $schResData['planned_cleaning_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>

                                                                                        @if ($schResData['return_delay_pixels'])
                                                                                            <div class="progress-bar red"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Delay"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['return_delay_pixels'] ? $schResData['return_delay_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif

                                                                                        @if ($schResData['return_early_pixels'])
                                                                                            <div class="progress-bar dark-blue"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Early"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $schResData['return_early_pixels'] ? $schResData['return_early_pixels'] . 'px' : '0%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        <div class="progress-bar light-green"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($sch['planned_return_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['planned_return_end'])->format('h:i A') . ' (' . $sch['planned_return_time'] . ' mins)' }}"
                                                                                            role="progressbar"
                                                                                            style="padding : 0%; width :  {{ $schResData['planned_return_pixels'] ? $schResData['planned_return_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>

                                                                                    </div>
                                                                                </div>

                                                                                <div class="stip-bgmainebox">
                                                                                    @foreach ($schResData['stripe'] as $stripeSch)
                                                                                        <span
                                                                                            class="{{ $stripeSch % 2 !== 0 ? 'white-stip' : 'frist-stip' }}"></span>
                                                                                    @endforeach
                                                                                </div>
                                                                            @else
                                                                                <div class="stip-bgmainebox">
                                                                                    <span class="white-stip"></span>
                                                                                    <span class="frist-stip"></span>
                                                                                    <span class="white-stip"></span>
                                                                                    <span class="frist-stip"></span>
                                                                                    <span class="white-stip"></span>
                                                                                    <span class="frist-stip"></span>
                                                                                </div>
                                                                            @endif
                                                                        </td>
                                                                    @endforeach

                                                                </tr>
                                                            @endforeach

                                                            @foreach ($res['pump_schedule'] as $psch)
                                                                <tr
                                                                    class = "schedule-graph-hidden schedule-graph-{{ isset($res['order_no']) ? $res['order_no'] : '' }}">
                                                                    <td>
                                                                        <div
                                                                            class="d-flex align-items-center justify-content-between">
                                                                            <div>
                                                                                Pump {{ $psch['pump'] }}
                                                                            </div>

                                                                            <div>
                                                                                <img src="{{ asset('assets/img/light-info.svg') }}"
                                                                                    alt="">
                                                                            </div>
                                                                        </div>
                                                                    </td>

                                                                    @foreach ($psch['resultData'] as $pschResData)
                                                                        <td
                                                                            colspan="{{ isset($pschResData['colspan']) ? $pschResData['colspan'] : 0 }}">

                                                                            @if (isset($pschResData['id']))
                                                                                <div class="main-progressbox">
                                                                                    <style>
                                                                                        .chartmiddlelineDynamic{{ $pschResData['id'] }}::after {
                                                                                            content: " ";
                                                                                            border-bottom: 3px solid #000000;
                                                                                            left: 0;
                                                                                            position: absolute;
                                                                                            top: 9px;
                                                                                            width: {{ $pschResData['live_marker_width'] }}px;
                                                                                        }
                                                                                    </style>
                                                                                    <div class="progress constructions-chart chartmiddlelineDynamic{{ $pschResData['id'] }}"
                                                                                        style = "margin-left:{{ $pschResData['start_minutes'] . 'px' }};">
                                                                                        @if ($pschResData['qc_delay_pixels'])
                                                                                            <div class="progress-bar red"
                                                                                                data-toggle="tooltip"
                                                                                                data-placement="bottom"
                                                                                                title="Delay"
                                                                                                role="progressbar"
                                                                                                style="padding : 0%; width :  {{ $pschResData['qc_delay_pixels'] ? $pschResData['qc_delay_pixels'] . 'px' : '10%' }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100"></div>
                                                                                        @endif
                                                                                        <div class="progress-bar pink"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($psch['planned_qc_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['planned_qc_end'])->format('h:i A') . ' (' . $psch['planned_qc_time'] . ' mins)' }}"
                                                                                            role="progressbar"
                                                                                            style="padding : 0%; width :  {{ $pschResData['planned_qc_pixels'] ? $pschResData['planned_qc_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>
                                                                                        <div class="progress-bar purple"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($psch['planned_travel_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['planned_travel_end'])->format('h:i A') . ' (' . $psch['planned_travel_time'] . ' mins)' }}"
                                                                                            role="progressbar"
                                                                                            style="padding : 0%; width :  {{ $pschResData['planned_travel_pixels'] ? $pschResData['planned_travel_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>
                                                                                        <div class="progress-bar dark-green"
                                                                                            role="progressbar"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($psch['planned_insp_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['planned_insp_end'])->format('h:i A') . ' (' . $psch['planned_insp_time'] . ' mins)' }}"
                                                                                            style="padding : 0%; width :  {{ $pschResData['planned_insp_pixels'] ? $pschResData['planned_insp_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>
                                                                                        <div class="progress-bar dark-blue"
                                                                                            role="progressbar"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($psch['planned_pouring_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['planned_pouring_end'])->format('h:i A') . ' (' . $psch['planned_pouring_time'] . ' mins)' }}"
                                                                                            style="padding : 0%; width :  {{ $pschResData['planned_pouring_pixels'] ? $pschResData['planned_pouring_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>
                                                                                        <div class="progress-bar nevy-blue"
                                                                                            role="progressbar"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($psch['planned_cleaning_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['planned_cleaning_end'])->format('h:i A') . ' (' . $psch['planned_cleaning_time'] . ' mins)' }}"
                                                                                            style="padding : 0%; width :  {{ $pschResData['planned_cleaning_pixels'] ? $pschResData['planned_cleaning_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>
                                                                                        <div class="progress-bar light-green"
                                                                                            role="progressbar"
                                                                                            data-toggle="tooltip"
                                                                                            data-placement="bottom"
                                                                                            title="{{ Carbon\Carbon::parse($psch['planned_return_start'])->format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['planned_return_end'])->format('h:i A') . ' (' . $psch['planned_return_time'] . ' mins)' }}"
                                                                                            style="padding : 0%; width :  {{ $pschResData['planned_return_pixels'] ? $pschResData['planned_return_pixels'] . 'px' : '0%' }}"
                                                                                            aria-valuemin="0"
                                                                                            aria-valuemax="100"></div>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="stip-bgmainebox">
                                                                                    @foreach ($pschResData['stripe'] as $pstripeSch)
                                                                                        <span
                                                                                            class="{{ $pstripeSch % 2 !== 0 ? 'white-stip' : 'frist-stip' }}"></span>
                                                                                    @endforeach
                                                                                </div>
                                                                            @else
                                                                                <div class="stip-bgmainebox">
                                                                                    <span class="white-stip"></span>
                                                                                    <span class="frist-stip"></span>
                                                                                    <span class="white-stip"></span>
                                                                                    <span class="frist-stip"></span>
                                                                                    <span class="white-stip"></span>
                                                                                    <span class="frist-stip"></span>
                                                                                </div>
                                                                            @endif
                                                                        </td>
                                                                    @endforeach

                                                                </tr>
                                                            @endforeach

                                                            <tr
                                                                class="secound-table schedule-graph-hidden schedule-graph-{{ isset($res['order_no']) ? $res['order_no'] : '' }}">
                                                                <td></td>
                                                                <td colspan="2"></td>
                                                                <td colspan="2"></td>
                                                                <td colspan="8">
                                                                </td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                            </tr>
                                                        @endif
                                                    @endforeach

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">

                                <div class="row mt-sm-5 mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group position-relative">
                                            <input type="email" class="form-control search-byinpt padding-right"
                                                placeholder="Search By...">
                                            <img src="img/fill-search.svg" class="fill-serchimg" alt="">
                                        </div>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <button type="button" class="btn export-btn mr-2">Export</button>
                                        <select class="filter-select mr-2 mt-sm-0 mt-3">
                                            <option>Filters</option>
                                            <option>2</option>
                                            <option>3</option>
                                            <option>4</option>
                                            <option>5</option>
                                        </select>

                                    </div>
                                </div>

                                <div class="row mt-3 mt-sm-2 align-items-center">
                                    <div class="col-md-6 mb-sm-0 mb-2">
                                        <div class="d-flex align-items-center">
                                            <span class="order-valuebox mr-sm-2 mr-1">2345 <span><img
                                                        src="img/button-cross.svg" class="ml-sm-2 ml-1"
                                                        alt=""></span> </span>
                                            <span class="order-valuebox mr-sm-3 mr-2">Aaron John <span><img
                                                        src="img/button-cross.svg" class="ml-sm-2 ml-1"
                                                        alt=""></span></span>
                                            <h6 class="clear-text">CLEAR FILTER</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-sm-right">
                                        <span class="schedule-chartvalue mr-2"> <span class="dots-box delivered"></span>
                                            On Time</span>
                                        <span class="schedule-chartvalue mr-2"> <span class="dots-box inprogress"></span>
                                            Behind Schedule</span>
                                        <span class="schedule-chartvalue mr-2"> <span class="dots-box delay"></span>
                                            Delay</span>
                                    </div>
                                </div>

                                <div class="row mt-sm-4 mt-3">
                                    <div class="col-md-12">
                                        <div class="table-responsive position-relative">
                                            <table class="table chart-table progress-linechart">
                                                <tbody>
                                                    <tr>
                                                        <th>
                                                            <div class="head-innerbox">
                                                                Orders
                                                            </div>
                                                        </th>

                                                        @foreach ($slots as $slot)
                                                            <th>
                                                                <div class="inner-middle">
                                                                    <p class="chart-tableheadtext">
                                                                        {{ $slot['end_time'] }}</p>
                                                                    <span class="firt-line middle"></span>
                                                                    <span class="firt-line "></span>
                                                                    <span class="firt-line "></span>
                                                                    <span class="firt-line"></span>
                                                                    <span class="firt-line"></span>
                                                                    <span class="firt-line"></span>
                                                                </div>
                                                            </th>
                                                        @endforeach


                                                    </tr>

                                                    <tr class="secound-table">
                                                        <td>Jebel Ali, Dubai</td>
                                                        <td colspan="2"></td>
                                                        <td colspan="2"></td>
                                                        <td colspan="8"></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline10">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 30%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  red" role="progressbar"
                                                                        style="width: 5%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 30%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  red" role="progressbar"
                                                                        style="width: 5%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 30%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div
                                                                    class="progress constructions-chart chartmiddleline11  ml10">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="progress constructions-chart  ml40">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="progress constructions-chart  ml30">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="progress constructions-chart  ml20">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>
                                                        <!-- <td colspan="3">
                     <div class="stip-bgmainebox">



                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 002
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/light-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline10 ml10">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  yellow" role="progressbar"
                                                                        style="width: 2%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div
                                                                    class="progress constructions-chart chartmiddleline12  ml20">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="progress constructions-chart  ml40">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="progress constructions-chart  ml15">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="progress constructions-chart  ml50">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr class="secound-table">
                                                        <td>Al Qusais, Dubai</td>
                                                        <td colspan="2"></td>
                                                        <td colspan="2"></td>
                                                        <td colspan="8"></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 002
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/light-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline14">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 59%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  yellow" role="progressbar"
                                                                        style="width: 2%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 39%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div
                                                                    class="progress constructions-chart chartmiddleline13  ml5">
                                                                    <div class="progress-bar red" role="progressbar"
                                                                        style="width: 5%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 40%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar yellow" role="progressbar"
                                                                        style="width: 5%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 40%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>

                                                                </div>
                                                                <div class="progress constructions-chart  ml40">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="progress constructions-chart  ml15">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="progress constructions-chart  ml15">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 002
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/light-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">

                                                                <div
                                                                    class="progress constructions-chart chartmiddleline15  ml10">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 30%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar yellow" role="progressbar"
                                                                        style="width: 5%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 20%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar yellow" role="progressbar"
                                                                        style="width: 5%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 40%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline17 ml15">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="progress constructions-chart  ml40">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="progress constructions-chart  ml15">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="progress constructions-chart  ml40">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 002
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/light-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">

                                                                <div
                                                                    class="progress constructions-chart chartmiddleline15  ml20">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 40%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar yellow" role="progressbar"
                                                                        style="width: 4%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 56%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline18 ml15">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="progress constructions-chart  ml30">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="progress constructions-chart  ml35">
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                <div class="row mt-sm-5 mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group position-relative">
                                            <input type="email" class="form-control search-byinpt padding-right"
                                                placeholder="Search By...">
                                            <img src="img/fill-search.svg" class="fill-serchimg" alt="">
                                        </div>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <button type="button" class="btn export-btn mr-2">Export</button>
                                        <select class="filter-select mr-2 mt-sm-0 mt-3">
                                            <option>Filters</option>
                                            <option>2</option>
                                            <option>3</option>
                                            <option>4</option>
                                            <option>5</option>
                                        </select>

                                    </div>
                                </div>

                                <div class="row mt-3 mt-sm-2 align-items-center">
                                    <div class="col-md-4 mb-sm-0 mb-2">
                                        <div class="d-flex align-items-center">
                                            <span class="order-valuebox mr-sm-2 mr-1">2345 <span><img
                                                        src="img/button-cross.svg" class="ml-sm-2 ml-1"
                                                        alt=""></span> </span>
                                            <span class="order-valuebox mr-sm-3 mr-2">Aaron John <span><img
                                                        src="img/button-cross.svg" class="ml-sm-2 ml-1"
                                                        alt=""></span></span>
                                            <h6 class="clear-text">CLEAR FILTER</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box early-start"></span> Loading </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box internal"></span> Internal QC </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box onsite"></span> Travelling to Site </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box onsite"></span> Onsite Inspection </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box  pouring"></span> Pouring </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box cleaning"></span> Cleaning </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box return"></span> Return to Plant </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box inprogress"></span> Behind Schedule</span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box delay"></span> Delay</span>

                                    </div>
                                </div>

                                <div class="row mt-sm-4 mt-3">
                                    <div class="col-md-12">
                                        <div class="table-responsive position-relative">
                                            <table class="table chart-table progress-linechart">
                                                <tbody>
                                                    <tr>
                                                        <th>
                                                            <div class="head-innerbox">
                                                                Orders
                                                            </div>
                                                        </th>

                                                        @foreach ($slots as $slot)
                                                            <th>
                                                                <div class="inner-middle">
                                                                    <p class="chart-tableheadtext">
                                                                        {{ $slot['end_time'] }}</p>
                                                                    <span class="firt-line middle"></span>
                                                                    <span class="firt-line "></span>
                                                                    <span class="firt-line "></span>
                                                                    <span class="firt-line"></span>
                                                                    <span class="firt-line"></span>
                                                                    <span class="firt-line"></span>
                                                                </div>
                                                            </th>
                                                        @endforeach


                                                    </tr>

                                                    <tr class="secound-table">
                                                        <td>Jebel Ali, Dubai</td>
                                                        <td colspan="2"></td>
                                                        <td colspan="2"></td>
                                                        <td colspan="8"></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline20">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml40 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>
                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>
                                                        <!-- <td colspan="3">
                     <div class="stip-bgmainebox">



                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar yellow"
                                                                        data-toggle="tooltip" data-placement="bottom"
                                                                        title=" 2min delay due to unavailabilitiy of transit mixer"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        data-toggle="tooltip" role="progressbar"
                                                                        style="width: 48%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline21">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        data-toggle="tooltip" role="progressbar"
                                                                        style="width: 48%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml30 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline22">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar red"
                                                                            data-toggle="tooltip"
                                                                            data-placement="bottom"
                                                                            title=" 2min delay due to unavailabilitiy of transit mixer"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 10%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar red" role="progressbar"
                                                                        style="width: 25%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 60%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 5%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="ml35 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml10 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td colspan="11">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15 ml30">
                                                                    <div class="progress-bar red" role="progressbar"
                                                                        style="width: 35%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 10%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 50%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 5%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="ml35 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml30 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>


                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr class="secound-table">
                                                        <td>Al Qusais, Dubai</td>
                                                        <td colspan="2"></td>
                                                        <td colspan="2"></td>
                                                        <td colspan="8"></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline20">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml40 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>
                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>
                                                        <!-- <td colspan="3">
                     <div class="stip-bgmainebox">



                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar yellow"
                                                                        data-toggle="tooltip" data-placement="bottom"
                                                                        title=" 2min delay due to unavailabilitiy of transit mixer"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        data-toggle="tooltip" role="progressbar"
                                                                        style="width: 48%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline21">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        data-toggle="tooltip" role="progressbar"
                                                                        style="width: 48%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml30 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline22">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar red"
                                                                            data-toggle="tooltip"
                                                                            data-placement="bottom"
                                                                            title=" 2min delay due to unavailabilitiy of transit mixer"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 10%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar red" role="progressbar"
                                                                        style="width: 25%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 60%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 5%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="ml35 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml10 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td colspan="11">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15 ml30">
                                                                    <div class="progress-bar red" role="progressbar"
                                                                        style="width: 35%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 10%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 50%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 5%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="ml35 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml30 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>


                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pumps" role="tabpanel" aria-labelledby="pumps-tab">

                                <div class="row mt-sm-5 mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group position-relative">
                                            <input type="email" class="form-control search-byinpt padding-right"
                                                placeholder="Search By...">
                                            <img src="img/fill-search.svg" class="fill-serchimg" alt="">
                                        </div>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <button type="button" class="btn export-btn mr-2">Export</button>
                                        <select class="filter-select mr-2 mt-sm-0 mt-3">
                                            <option>Filters</option>
                                            <option>2</option>
                                            <option>3</option>
                                            <option>4</option>
                                            <option>5</option>
                                        </select>

                                    </div>
                                </div>

                                <div class="row mt-3 mt-sm-2 align-items-center">
                                    <div class="col-md-4 mb-sm-0 mb-2">
                                        <div class="d-flex align-items-center">
                                            <span class="order-valuebox mr-sm-2 mr-1">2345 <span><img
                                                        src="img/button-cross.svg" class="ml-sm-2 ml-1"
                                                        alt=""></span> </span>
                                            <span class="order-valuebox mr-sm-3 mr-2">Aaron John <span><img
                                                        src="img/button-cross.svg" class="ml-sm-2 ml-1"
                                                        alt=""></span></span>
                                            <h6 class="clear-text">CLEAR FILTER</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box onsite"></span> Onsite Inspection </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box  pouring"></span> Pouring </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box return"></span> Return to Plant </span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box inprogress"></span> Behind Schedule</span>
                                        <span class="schedule-chartvalue mr-sm-2 mr-1"> <span
                                                class="dots-box delay"></span> Delay</span>
                                    </div>
                                </div>

                                <div class="row mt-sm-4 mt-3">
                                    <div class="col-md-12">
                                        <div class="table-responsive position-relative">
                                            <table class="table chart-table progress-linechart">
                                                <tbody>
                                                    <tr>
                                                        <th>
                                                            <div class="head-innerbox">
                                                                Orders
                                                            </div>
                                                        </th>

                                                        @foreach ($slots as $slot)
                                                            <th>
                                                                <div class="inner-middle">
                                                                    <p class="chart-tableheadtext">
                                                                        {{ $slot['end_time'] }}</p>
                                                                    <span class="firt-line middle"></span>
                                                                    <span class="firt-line "></span>
                                                                    <span class="firt-line "></span>
                                                                    <span class="firt-line"></span>
                                                                    <span class="firt-line"></span>
                                                                    <span class="firt-line"></span>
                                                                </div>
                                                            </th>
                                                        @endforeach


                                                    </tr>

                                                    <tr class="secound-table">
                                                        <td>Jebel Ali, Dubai</td>
                                                        <td colspan="2"></td>
                                                        <td colspan="2"></td>
                                                        <td colspan="8"></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline20">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml40 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>
                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>
                                                        <!-- <td colspan="3">
                     <div class="stip-bgmainebox">



                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar yellow"
                                                                        data-toggle="tooltip" data-placement="bottom"
                                                                        title=" 2min delay due to unavailabilitiy of transit mixer"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        data-toggle="tooltip" role="progressbar"
                                                                        style="width: 48%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline21">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        data-toggle="tooltip" role="progressbar"
                                                                        style="width: 48%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml30 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline22">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar red"
                                                                            data-toggle="tooltip"
                                                                            data-placement="bottom"
                                                                            title=" 2min delay due to unavailabilitiy of transit mixer"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 10%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar red" role="progressbar"
                                                                        style="width: 25%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 60%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 5%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="ml35 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml10 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td colspan="11">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15 ml30">
                                                                    <div class="progress-bar red" role="progressbar"
                                                                        style="width: 35%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 10%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 50%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 5%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="ml35 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml30 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>


                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr class="secound-table">
                                                        <td>Al Qusais, Dubai</td>
                                                        <td colspan="2"></td>
                                                        <td colspan="2"></td>
                                                        <td colspan="8"></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline20">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml40 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>
                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>
                                                        <!-- <td colspan="3">
                     <div class="stip-bgmainebox">



                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <!-- <td>
                     <div class="stip-bgmainebox">
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                      <span class="white-stip"></span>
                      <span class="frist-stip"></span>
                     </div>
                    </td> -->
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar yellow"
                                                                        data-toggle="tooltip" data-placement="bottom"
                                                                        title=" 2min delay due to unavailabilitiy of transit mixer"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        data-toggle="tooltip" role="progressbar"
                                                                        style="width: 48%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline21">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>
                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex  justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 48%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar  light-green"
                                                                        data-toggle="tooltip" role="progressbar"
                                                                        style="width: 48%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 4%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="ml30 w-100">
                                                                    <div
                                                                        class="progress constructions-chart chartmiddleline22">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar red"
                                                                            data-toggle="tooltip"
                                                                            data-placement="bottom"
                                                                            title=" 2min delay due to unavailabilitiy of transit mixer"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml15 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td colspan="13">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15">
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 10%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar red" role="progressbar"
                                                                        style="width: 25%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 60%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 5%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="ml35 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml10 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>


                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>



                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    Pump 001
                                                                    <span class="plant-texttable">(20 m3/hr)</span>
                                                                    <h6 class="table-hourstext"> 10 hrs <i
                                                                            class="fa fa-circle" aria-hidden="true"></i>
                                                                        200 CUM <i class="fa fa-circle"
                                                                            aria-hidden="true"></i> <span>80%</span> </h6>
                                                                </div>

                                                                <div>
                                                                    <a href="##"> <img src="img/metro-info.svg"
                                                                            alt=""> </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td colspan="11">
                                                            <div class="main-progressbox new-mainprogressbox">
                                                                <div
                                                                    class="progress constructions-chart  chartmiddleline15 ml30">
                                                                    <div class="progress-bar red" role="progressbar"
                                                                        style="width: 35%" aria-valuenow="15"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                    <div class="progress-bar dark-blue"
                                                                        role="progressbar" style="width: 10%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar light-green"
                                                                        role="progressbar" style="width: 50%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                    <div class="progress-bar nevy-blue"
                                                                        role="progressbar" style="width: 5%"
                                                                        aria-valuenow="15" aria-valuemin="0"
                                                                        aria-valuemax="100"></div>
                                                                </div>

                                                                <div class="ml35 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml30 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                                <div class="ml120 w-100">
                                                                    <div class="progress constructions-chart">
                                                                        <div class="progress-bar skyblue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar pink"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar purple"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar dark-blue"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar light-green"
                                                                            role="progressbar" style="width: 25%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                        <div class="progress-bar nevy-blue"
                                                                            role="progressbar" style="width: 5%"
                                                                            aria-valuenow="15" aria-valuemin="0"
                                                                            aria-valuemax="100"></div>
                                                                    </div>
                                                                    <span class="order-texttablechart">Order
                                                                        details</span>
                                                                </div>

                                                            </div>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>

                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>


                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="stip-bgmainebox">
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                                <span class="white-stip"></span>
                                                                <span class="frist-stip"></span>
                                                            </div>
                                                        </td>

                                                    </tr>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class = "tab-pane fade" id = "datagrid" role = "tabpanel"
                                aria-labelledby="datagrid-tab">
                                <div class="row mt-sm-5 mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group position-relative">
                                            <input type="email" class="form-control search-byinpt padding-right"
                                                placeholder="Search By...">
                                            <img src="{{ asset('assets/img/fill-search.svg') }}" class="fill-serchimg"
                                                alt="">
                                        </div>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <button type="button" class="btn export-btn mr-2">Export</button>
                                        <select class="filter-select mr-2 mt-sm-0 mt-3">
                                            <option>Filters</option>
                                            <option>2</option>
                                            <option>3</option>
                                            <option>4</option>
                                            <option>5</option>
                                        </select>
                                        <button onclick = "toggleGraph();" type="button" class="btn icon-btn mr-2">
                                            <img src = "{{ asset('assets/img/toggle_graph.svg') }}" />
                                        </button>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-2">
                                                <thead>
                                                    <tr>
                                                        <th style = "background-color: #f1f1f1;">Time</th>
                                                        <th style = "background-color: #f1f1f1; min-width:10rem;">Order
                                                        </th>
                                                        <th style = "background-color: #f1f1f1; min-width:8rem;">Project
                                                        </th>
                                                        <th style = "background-color: #f1f1f1; min-width:8rem;">Plant
                                                        </th>
                                                        <th style = "background-color: #f1f1f1;">Resource</th>
                                                        <th style = "background-color: #f1f1f1;">Status</th>
                                                        <th style = "min-width:8rem;">Mix</th>
                                                        <th>Qty</th>
                                                        <th>Pumps</th>
                                                        <th>Technician</th>
                                                        <th>Temp. Control</th>
                                                        <th>Cube Moulds</th>
                                                        <th>Interval</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($orderSchedules as $order)
                                                        @foreach ($order->schedule as $schedule)
                                                            <tr>
                                                                <td>
                                                                    {{ Carbon\Carbon::parse($schedule->planned_loading_start)->format('h:i A') }}
                                                                </td>
                                                                <td>
                                                                    {{ $order->order_no }}
                                                                    <br />
                                                                    <span
                                                                        class = "text-muted small">{{ $order->customer_company?->name }}</span>
                                                                </td>
                                                                <td>
                                                                    {{ $order->project }}
                                                                    <br />
                                                                    <span
                                                                        class = "text-muted small">{{ $order->customer_site?->name }}</span>
                                                                </td>
                                                                <td>
                                                                    {{ $schedule->batching_plant_detail?->plant_name }}
                                                                    <br />
                                                                    <span
                                                                        class = "text-muted small">{{ $schedule->batching_plant_detail?->company_location?->site_name }}</span>
                                                                </td>
                                                                <td>
                                                                    {{ $schedule->transit_mixer_detail?->truck_name }}
                                                                    <br />
                                                                    <span
                                                                        class = "text-muted small">{{ $schedule->transit_mixer_detail?->registration_no }}</span>
                                                                </td>
                                                                <td>
                                                                    @if ($schedule->actual_loading_start)
                                                                        @if ($schedule->getCurrentActivity() !== 'Trip Completed')
                                                                            <div class = "live_trip_progress">
                                                                                <div class = "live_trip_progress_track"
                                                                                    style = "width : {{ $schedule->getCurrentActivityPercentage() }}%;">
                                                                                    <div
                                                                                        class = "live_trip_progress_text">
                                                                                        {{ $schedule->getCurrentActivityPercentage() }}%
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif

                                                                        {{ $schedule->getCurrentActivity() }}
                                                                    @else
                                                                        <button data-id="{{ $schedule->id }}"
                                                                            type = "btn" class = "btn btn-dark"
                                                                            data-toggle="modal"
                                                                            data-target="#assignModal"
                                                                            onclick = "openAssingModal(this)" data-order-id="{{$res['id']}}">Start</button>
                                                                    @endif
                                                                </td>

                                                                <td>
                                                                    {{ $order->customer_product?->product_name }}
                                                                    <br />
                                                                    <span
                                                                        class = "text-muted small">{{ $order->customer_product?->mix_code }}</span>
                                                                </td>
                                                                <td>
                                                                    {{ $schedule->batching_qty }} CUM
                                                                    <br />
                                                                    <span class = "text-success small">Total -
                                                                        {{ $order->quantity }} CUM</span>
                                                                </td>
                                                                <td>{{ $schedule->pump_detail?->pump_name }}</td>

                                                                <td>{{ $order->is_technician_required ? 'Yes' : 'No' }}
                                                                </td>
                                                                <td>
                                                                    @foreach ($order->order_temp_control as $control)
                                                                        {{ $control->temp }}
                                                                        @if (!$loop->last), @endif
                                                                    @endforeach
                                                                </td>

                                                                <td>Cube Moulds</td>
                                                                <td>{{ $order->interval }} Min</td>
                                                            </tr>
                                                        @endforeach
                                                    @empty
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade filter-modal" id="assignModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <img src="{{ asset('assets/img/filter-close.svg') }}" alt=""> close
                        </button>
                    </div>
                    <form action = "{{ route('web.liveOrder.trip.assign') }}" method = "POST">
                        @csrf
                        <div class="modal-body">
                            <div class="filter-contentbox">
                                <h6>Assign Resources</h6>
                            </div>
                            <div class="row mt-sm-4 mt-3">
                                <div class="col-md-12">

                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Batching Plant</label>
                                        <select id = 'batching_plant_dropdown' class="form-control select-contentbox"
                                            name = "batching_plant_id">
                                            @foreach ($batching_plants as $plant)
                                                <option value = "{{ $plant->value }}"> {{ $plant->label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Transit Mixer</label>
                                        <select id = 'transit_mixer_dropdown' class="form-control select-contentbox"
                                            name = "transit_mixer_id">
                                            @foreach ($trucks as $truck)
                                                <option value = "{{ $truck->value }}"> {{ $truck->label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Available Quantity in CUM (Rejected Order)</label>
                                        <input type = "number" class="form-control user-profileinput"
                                            placeholder="Enter" name = "rejected_quantity"></input>
                                    </div>

                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Pump</label>
                                        <select id = 'pump_dropdown' class="form-control select-contentbox"
                                            name = "pump_id">
                                            @foreach ($pumps as $pump)
                                                <option value = "{{ $pump->value }}"> {{ $pump->label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div  id = 'temControllId'class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Temperature</label>
                                        <select  name="temp" multiple="multiple"
                                            class="form-control js-example-basic-multiple select-contentbox">
                                            @foreach ($temperatures as $temp)
                                                <option value="{{ $temp->value }}">{{ $temp->label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                            </div>

                            <input type = "hidden" id = "trip_id_input" value = "" name = "trip_id">

                            <div class="mt-sm-5 mt-4">
                                <button type="submit" class="btn apply-btn btn-block">Start</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id = "liveOrderDetailsModal">
        </div>

        <div id = "liveTripDetails">
        </div>




    </section>

    <style>
        .progress-linechart::after {
            content: " ";
            position: absolute;
            background: #000000;
            width: 5px;
            height: 100%;
            top: 0px;
            z-index: 0;
            left: {{ $marker_margin }}px;
        }
    </style>


    <script>
        $(document).ready(function() {
            $('.js-example-basic-multiple').select2({
                placeholder: "Select options"
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                headerToolbar: {
                    left: 'title',
                    center: '',
                    right: 'prev,next'
                },
                editable: true,
                dayMaxEvents: true, // allow "more" link when too many events

                selectable: true,
                select: function(start) {
                    // Update the hidden input with the selected date
                    selectedDateInput.value = start.startStr;
                    selectedDateLabel.innerHTML = moment(start.startStr).format("dddd, D MMMM YYYY");
                }
            });
            calendar.render();
        });

        function toggleDropdown(order_no) {

            var elements = document.getElementsByClassName("schedule-graph-" + order_no);

            // Iterate over the collection and modify styles
            for (var i = 0; i < elements.length; i++) {
                elements[i].style.visibility = elements[i].style.visibility === 'visible' ? "collapse" : "visible";
                // Add more style modifications as needed
            }
            document.getElementById("more-icon-" + order_no).src = document.getElementById("more-icon-" + order_no).src ==
                "{{ asset('assets/img/purple-more.svg') }}" ? "{{ asset('assets/img/gray-more.svg') }}" :
                "{{ asset('assets/img/purple-more.svg') }}";
        }

        function refreshPage() {
            window.location.reload();
        }




    function openAssingModal(element) {
         document.getElementById('pump_dropdown').innerHTML = '';

    const $method = 'GET'; // or 'GET', depending on your request type

    const orderId = $(element).data('order-id');
    /* console.log(orderId); */
    $.ajax({
        url: '/order/pump/detail',
        data: {
            orderId: orderId
        },
        type: $method,
        dataType: "JSON",
        success: function ($response) {
            var response = $response;
            var pumphtml = '';
            console.log($response); // Fixed typo here

            response.data.pumps.forEach(pump => {
                pumphtml += `<option value='${pump.value}'>${pump.label}</option>`;
            });

            document.getElementById('pump_dropdown').innerHTML = pumphtml;
        },
        error: function ($response) {
            console.log("Error:", $response);
        }
    });

    document.getElementById('trip_id_input').value = element.dataset.id;

    const params = {
        id: element.dataset.id,
    };

    const apiURL = "{{ route('web.liveOrder.trip.resources') }}";
    const queryParam = new URLSearchParams(params).toString();
    const urlWithParams = `${apiURL}?${queryParam}`;

    fetch(urlWithParams, {
        method: "GET",
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
    }).then(response => response.json()).then(data => {
        const response = data.data;
        console.log(response);
        if (response.trip && response.trip.id) {
            if (response.trip.temp) {
                document.getElementById('temControllId').style.display = '';
            } else {
                document.getElementById('temControllId').style.display = 'none';
            }

            document.getElementById('batching_plant_dropdown').value = response.trip.batching_plant_id;
            document.getElementById('transit_mixer_dropdown').value = response.trip.transit_mixer_id;
            document.getElementById('pump_dropdown').value = response.trip.pump_id;
        }
    }).catch(error => {
        console.log("Error:", error);
    });
}

        function toggleTable() {
            const allTabs = document.getElementsByClassName('tab-pane');
            for (var i = 0; i < allTabs.length; i++) {
                allTabs[i].classList.remove('show', 'active');
            }
            document.getElementById('datagrid').classList.add('show', 'active');
        }

        function toggleGraph() {
            const allTabs = document.getElementsByClassName('tab-pane');
            for (var i = 0; i < allTabs.length; i++) {
                allTabs[i].classList.remove('show', 'active');
            }
            document.getElementById('home').classList.add('show', 'active');
        }

        function getLiveOrderDetails(orderId) {
            const apiUrl = 'live-order/details/' + orderId;
            fetch(apiUrl, {
                method: "GET",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
            }).then(response => response.json()).then(data => {
                const response = data;
                // Update modal content with the rendered HTML
                $('#liveOrderDetailsModal').html(response.html);

                // Open the modal
                $('#liveOrderTrackDetails').modal('show');
            }).catch(error => {
                console.log("Error : ", error);
            });
        }

        function getLiveTripDetails(tripId) {
            const apiUrl = 'live-trip/details/' + tripId;
            fetch(apiUrl, {
                method: "GET",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
            }).then(response => response.json()).then(data => {
                const response = data;
                // Update modal content with the rendered HTML
                $('#liveTripDetails').html(response.html);

                // Open the modal
                $('#liveTripTrackDetails').modal('show');
            }).catch(error => {
                console.log("Error : ", error);
            });
        }


        /* function getLiveTripDetails()
	{
            // Open the modal
            $('#tripModal').modal('show');

        }; */

        </script>
@endsection
