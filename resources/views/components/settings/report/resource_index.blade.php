@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">

            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-3 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Resources</h1>
                            <p>Batching Plant</p>
                        </div>
                    </div>
                    <div class="col-md-5 mb-sm-0 mb-3">
                        <ul class="nav nav-tabs order-tab" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab"
                                    aria-controls="home" aria-selected="true">Batching Plant</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab"
                                    aria-controls="profile" aria-selected="false">Transit Mixer</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab"
                                    aria-controls="contact" aria-selected="false">Pumps</a>
                            </li>

                        </ul>
                    </div>
                    <div class="col-md-3  text-sm-right">
                        <div class="dropdown show calender-box">
                            <button class="btn calender-btn dropdown-toggle" href="#" role="button"
                                id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span id="schedule_date_label" class="calender-img"><img
                                        src="{{ asset('assets/img/calender-img.svg') }}" alt=""></span>
                                {{ date('l, F j, Y') }}

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

                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="home" role="tabpanel"
                                aria-labelledby="home-tab">
                                <div class="row mt-sm-5 mt-3">
                                    <div class="col-md-4">
                                        {{-- <div class="form-group position-relative">
                                            <input type="email" class="form-control search-byinpt padding-right"
                                                placeholder="Search By...">
                                            <img src="{{ asset('assets/img/fill-search.svg') }}" class="fill-serchimg"
                                                alt="">
                                        </div> --}}
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <a href="{{ route('settings.batchingPlants.create') }}"
                                            class="btn btn-success mr-2">Create New</a>
                                        {{-- <button type="button" class="btn export-btn mr-2">Export</button>
                                    <select class="filter-select mr-2 mt-sm-0 mt-3">
                                        <option>Filters</option>
                                        <option>2</option>
                                        <option>3</option>
                                        <option>4</option>
                                        <option>5</option>
                                    </select> --}}

                                    </div>
                                </div>

                                @foreach ($batchingDetails as $batchingDetail)
                                    <div class="resources-batchingplantcontentbox mt-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-2 mb-3 mb-sm-0">
                                                <div class="loaction-contentbox">

                                                    <p>Location:</p>
                                                    <h6>{{ $batchingDetail->location }}</h6>
                                                    <h5>Utilization: <span>{{$batchingDetail->totalOcupancy}}%</span></h5>
                                                </div>
                                            </div>

                                            <div class="col-md-10">
                                                <div class="row">
                                                    @foreach ($batchingDetail->plants as $plant)
                                                        <div class="col-md-3 col-6 mb-2 mb-sm-0">

                                                            <div class="progress-contentbox text-center">
                                                                <div class="dropdown more-drop">
                                                                    <button class="more-iconprogressbox" type="button"
                                                                        id="dropdownMenuButton" data-toggle="dropdown"
                                                                        aria-haspopup="true" aria-expanded="false">
                                                                        {{-- <i class="fa fa-ellipsis-v more-icon"
                                                                            aria-hidden="true"></i> --}}
                                                                    </button>
                                                                    {{-- <div class="dropdown-menu"
                                                                        aria-labelledby="dropdownMenuButton">
                                                                        <a class="dropdown-item" href="#">Details</a>
                                                                        <a class="dropdown-item" href="#">Edit</a>
                                                                    </div> --}}
                                                                </div>
                                                                @php
                                                                    $start_time = Carbon\Carbon::parse(
                                                                        $plant->Batching_plant_occupancy?->start_time,
                                                                    );
                                                                    $end_time = Carbon\Carbon::parse(
                                                                        $plant->Batching_plant_occupancy?->end_time,
                                                                    );
                                                                    $diffInMinutes =
                                                                        $start_time && $end_time
                                                                            ? $start_time->diffInMinutes($end_time)
                                                                            : 0;

                                                                    $occupancy =
                                                                        $diffInMinutes > 0
                                                                            ? ($plant->Batching_plant_occupancy
                                                                                    ?->occupied /
                                                                                    $diffInMinutes) *
                                                                                100
                                                                            : 0;
                                                                @endphp


                                                                <div class="progress-circle progress-{{$occupancy}} mt-3">
                                                                    <h5 class="progress-txt">{{ number_format($occupancy) }}
                                                                        %
                                                                        <br> <span
                                                                            class="progress-txtgray">Utilization</span>
                                                                    </h5>
                                                                </div>
                                                                {{-- <a href="{{ route('batching.details') }}"> --}}
                                                                <a href="#">
                                                                    <h2>{{ $plant->plant_name }}</h2>
                                                                    <h3>{{ $plant->description }}</h3>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endforeach


                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">

                                <div class="row mt-sm-5 mt-3">
                                    <div class="col-md-4">
                                        {{-- <div class="form-group position-relative">
                                            <input type="email" class="form-control search-byinpt padding-right"
                                                placeholder="Search By...">
                                            <img src="{{ asset('/assets/img/fill-search.svg') }}" class="fill-serchimg"
                                                alt="">
                                        </div> --}}
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <a href="{{ route('settings.transitMixers.create') }}"
                                            class="btn btn-success mr-2">Create New</a>

                                        {{-- <button type="button" class="btn export-btn mr-2">Export</button>
                                    <select class="filter-select mr-2 mt-sm-0 mt-3">
                                        <option>Filters</option>
                                        <option>2</option>
                                        <option>3</option>
                                        <option>4</option>
                                        <option>5</option>
                                    </select> --}}
                                    </div>
                                </div>

                                <div class="row mt-sm-4 mt-3">
                                    <div class="col-md-10">
                                        <div class="row">
                                            <div class="col-md-3 mb-2 mb-sm-0">
                                                <div class="transit-mixerbox">
                                                    <div class="row justify-content-between">
                                                        <div class="col-md-6 col-6 border-right">
                                                            <h6>09</h6>
                                                            <p>At Plant</p>
                                                        </div>
                                                        <div class="col-md-5 col-5">
                                                            <h6>10</h6>
                                                            <p>On Trip</p>
                                                        </div>
                                                    </div>
                                                    <h2>Capacity: <span>12 CUM</span> </h2>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-2 mb-sm-0">
                                                <div class="transit-mixerbox">
                                                    <div class="row justify-content-between">
                                                        <div class="col-md-6 col-6 border-right">
                                                            <h6>09</h6>
                                                            <p>At Plant</p>
                                                        </div>
                                                        <div class="col-md-5 col-5">
                                                            <h6>10</h6>
                                                            <p>On Trip</p>
                                                        </div>
                                                    </div>
                                                    <h2>Capacity: <span>12 CUM</span> </h2>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-2 mb-sm-0">
                                                <div class="transit-mixerbox">
                                                    <div class="row justify-content-between">
                                                        <div class="col-md-6 col-6 border-right">
                                                            <h6>09</h6>
                                                            <p>At Plant</p>
                                                        </div>
                                                        <div class="col-md-5 col-5">
                                                            <h6>10</h6>
                                                            <p>On Trip</p>
                                                        </div>
                                                    </div>
                                                    <h2>Capacity: <span>12 CUM</span> </h2>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="transit-mixerbox">
                                                    <div class="row justify-content-between">
                                                        <div class="col-md-6 col-6 border-right">
                                                            <h6>09</h6>
                                                            <p>At Plant</p>
                                                        </div>
                                                        <div class="col-md-5 col-5">
                                                            <h6>10</h6>
                                                            <p>On Trip</p>
                                                        </div>
                                                    </div>
                                                    <h2>Capacity: <span>12 CUM</span> </h2>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                               <label class="batching-plantlabel mt-sm-4 mt-3">At Plant
                                {{ collect($transitMixerDetails)->flatMap(function($mix) {
                                    return $mix->transit_mixers->filter(function($transit) {
                                        return optional($transit->occupancy)->current_status == 'on_plant';
                                    });
                                })->count() }}
                                    </label>
                                    <div class="row mt-2 noflexwrap">
                                        @foreach ($transitMixerDetails as $mix)
                                                @foreach ($mix->transit_mixers as $transit)
                                                    @if (optional($transit->occupancy)->current_status == 'on_plant')
                                        <div class="col-md-2 col-6 mb-2 mb-sm-0">
                                                        <div class="plant-detailscontentbox">
                                                            {{-- <span class="more-iconbox">
                                                                <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i></span> --}}
                                                            <h2>Plat No: {{$transit->registration_no}}</h2>
                                                            <p>{{$transit->truck_capacity}}(CUM)</p>
                                                            <h6>{{$transit->group_company->comp_name}}</h6>
                                                        </div>
                                                    </div>
                                                    @endif
                                                @endforeach
                                                @endforeach
                                             </div>

                                <label class="batching-plantlabel mt-sm-4 mt-3">On Trip
                                    {{ collect($transitMixerDetails)->flatMap(function($mix) {
                                        return $mix->transit_mixers->filter(function($transit) {
                                            return optional($transit->occupancy)->current_status == 'on_trip'; // Use optional here
                                        });
                                    })->count() }}
                                </label>
                                @foreach ($transitMixerDetails as $mix)
                                @foreach ($mix->transit_mixers as $transit)
                                    @if (optional($transit->occupancy)->current_status == 'on_trip')  <!-- Use optional here -->
                                        <div class="batching-plantbordertop mt-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-2 text-sm-center">
                                                    <span class="pink-textbox cursor-pointer" data-toggle="modal" data-target="#trip">
                                                        Truck {{$transit->truck_name}} / {{$transit->truck_capacity}} CUM
                                                    </span>
                                                    <h2>Plat No {{$transit->registration_no}}</h2>
                                                </div>
                                                <div class="col-md-10">
                                                    <div class="dropdown more-drop">
                                                        <button class="more-iconprogressbox more-iconprogressboxnew" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            {{-- <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i> --}}
                                                        </button>
                                                        {{-- <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                            <a class="dropdown-item" href="#">Details</a>
                                                            <a class="dropdown-item" href="#">Edit</a>
                                                        </div> --}}
                                                    </div>

                                                    <div class="time-linescroll">
                                                        <div class="line_box">
                                                            <!-- Loading Time -->
                                                            <div class="text_circle done">
                                                                <a class="tvar active"><span></span></a>
                                                                <div class="circle">
                                                                    <p>Loading</p>
                                                                    <h4>{{ \Carbon\Carbon::parse($mix->loading_start)->toDateString() }}</h4>
                                                                    <div class="subline">
                                                                        <h6>{{$mix->loading_time}} min</h6>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Internal Qc Time -->
                                                            <div class="text_circle done">
                                                                <a class="tvar active"><span></span></a>
                                                                <div class="circle">
                                                                    <p>Internal Qc</p>
                                                                    <h4>{{\Carbon\Carbon::parse($mix->qc_start)->toDateString()}}</h4>
                                                                    <div class="subline">
                                                                        <h6>{{$mix->qc_time}} min</h6>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Inspection Time -->
                                                            <div class="text_circle done">
                                                                <a class="tvar active"></a>
                                                                <div class="circle">
                                                                    <p>Inspection Time</p>
                                                                    <h4>{{\Carbon\Carbon::parse($mix->insp_start)->toDateString()}}</h4>
                                                                    <div class="subline">
                                                                        <h6>{{$mix->insp_time}} min</h6>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Pouring Time -->
                                                            <div class="text_circle">
                                                                <a class="tvar active"></a>
                                                                <div class="circle">
                                                                    <p>Pouring Time</p>
                                                                    <h4>{{\Carbon\Carbon::parse($mix->pouring_start)->toDateString()}}</h4>
                                                                </div>
                                                            </div>

                                                            <!-- Cleaning Time -->
                                                            <div class="text_circle">
                                                                <a class="tvar"></a>
                                                                <div class="circle">
                                                                    <p>Cleaning Time</p>
                                                                    <h4>{{\Carbon\Carbon::parse($mix->cleaning_start)->toDateString()}}</h4>
                                                                </div>
                                                            </div>

                                                            <!-- Return Time -->
                                                            <div class="text_circle">
                                                                <a class="tvar"></a>
                                                                <div class="circle">
                                                                    <p>Return Time</p>
                                                                    <h4>{{\Carbon\Carbon::parse($mix->return_start)->toDateString()}}</h4>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endforeach
                            </div>

                            <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                <div class="row mt-sm-5 mt-3">
                                    <div class="col-md-4">
                                        {{-- <div class="form-group position-relative">
                                            <input type="email" class="form-control search-byinpt padding-right"
                                                placeholder="Search By...">
                                            <img src="{{ asset('/assets/img/fill-search.svg') }}" class="fill-serchimg"
                                                alt="">
                                        </div> --}}
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <a href="{{ route('settings.pumps.create') }}"
                                            class="btn btn-success mr-2">Create New</a>

                                        {{-- <button type="button" class="btn btn-success mr-2">Create New</button> --}}
                                        {{-- <button type="button" class="btn export-btn mr-2">Export</button>
                                    <select class="filter-select mr-2 mt-sm-0 mt-3">
                                        <option>Filters</option>
                                        <option>2</option>
                                        <option>3</option>
                                        <option>4</option>
                                        <option>5</option>
                                    </select> --}}
                                    </div>
                                </div>

                                <div class="row mt-sm-4 mt-3">
                                    <div class="col-md-10">
                                        <div class="row">
                                            <div class="col-md-3 mb-2 mb-sm-0">
                                                <div class="transit-mixerbox">
                                                    <div class="row justify-content-between">
                                                        <div class="col-md-6 col-6 border-right">
                                                            <h6>09</h6>
                                                            <p>At Plant</p>
                                                        </div>
                                                        <div class="col-md-5 col-5">
                                                            <h6>10</h6>
                                                            <p>On Trip</p>
                                                        </div>
                                                    </div>
                                                    <h2>Capacity: <span>12 CUM</span> </h2>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-2 mb-sm-0">
                                                <div class="transit-mixerbox">
                                                    <div class="row justify-content-between">
                                                        <div class="col-md-6 col-6 border-right">
                                                            <h6>09</h6>
                                                            <p>At Plant</p>
                                                        </div>
                                                        <div class="col-md-5 col-5">
                                                            <h6>10</h6>
                                                            <p>On Trip</p>
                                                        </div>
                                                    </div>
                                                    <h2>Capacity: <span>12 CUM</span> </h2>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-2 mb-sm-0">
                                                <div class="transit-mixerbox">
                                                    <div class="row justify-content-between">
                                                        <div class="col-md-6 col-6 border-right">
                                                            <h6>09</h6>
                                                            <p>At Plant</p>
                                                        </div>
                                                        <div class="col-md-5 col-5">
                                                            <h6>10</h6>
                                                            <p>On Trip</p>
                                                        </div>
                                                    </div>
                                                    <h2>Capacity: <span>12 CUM</span> </h2>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="transit-mixerbox">
                                                    <div class="row justify-content-between">
                                                        <div class="col-md-6 col-6 border-right">
                                                            <h6>09</h6>
                                                            <p>At Plant</p>
                                                        </div>
                                                        <div class="col-md-5 col-5">
                                                            <h6>10</h6>
                                                            <p>On Trip</p>
                                                        </div>
                                                    </div>
                                                    <h2>Capacity: <span>12 CUM</span> </h2>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>



                                <label class="batching-plantlabel mt-sm-4 mt-3">At Plant
                                    {{ collect($pumpDetails)->flatMap(function($pump) {
                                        return $pump->pump_mixers->filter(function($item) {
                                            return optional($item->pump_occupancy)->current_status == 'on_plant';
                                        });
                                    })->count() }}
                                </label>
                                <div class="row mt-2 noflexwrap">
                                    @foreach ($pumpDetails as $pump)
                                    @foreach ($pump->pump_mixers as $item)
                                        @if (optional($item->pump_occupancy)->current_status == 'on_plant')
                                        <div class="col-md-2 col-6 mb-2 mb-sm-0">
                                                    <div class="plant-detailscontentbox">
                                                        {{-- <span class="more-iconbox"><i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i></span> --}}

                                                        <h2>Pump : {{$item->pump_name}}</h2>
                                                        <p>{{$item->pump_capacity}}(M)</p>
                                                        <h6>{{$item->group_company->comp_name}}</h6>
                                                    </div>
                                                </div>
                                                @endif
                                            @endforeach
                                    @endforeach
                                </div>

                                {{-- <label class="batching-plantlabel mt-sm-4 mt-3">On Trip (34)</label> --}}

                                <label class="batching-plantlabel mt-sm-4 mt-3">On Trip
                                    {{ collect($pumpDetails)->flatMap(function($pump) {
                                        return $pump->pump_mixers->filter(function($item) {
                                            return optional($item->pump_occupancy)->current_status == 'on_trip';
                                        });
                                    })->count() }}
                                </label>


                                <div class="row mt-3">
                                @foreach ($pumpDetails as $pump)
                                @foreach ($pump->pump_mixers as $item)
                                @if (optional($item->pump_occupancy)->current_status == 'on_trip')

                                    <div class="col-md-6 mb-3">
                                        <div class="resources-pumpsbox">
                                            <div class="row align-items-center">
                                                <div class="col-md-3 text-sm-center">
                                                    <span class="pink-textbox cursor-pointer" data-toggle="modal"
                                                        data-target="#trip2">Pump : {{$item->pump_name}}</span>
                                                    <h3>Size: {{$item->pump_capacity}}</h3>
                                                </div>
                                                <div class="col-md-9 pl-0">
                                                    <div class="dropdown more-drop">
                                                        <button class="more-iconprogressbox more-iconprogressboxnew"
                                                            type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                            aria-haspopup="true" aria-expanded="false">
                                                            {{-- <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i> --}}
                                                        </button>
                                                        {{-- <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                            <a class="dropdown-item" href="#">Details</a>
                                                            <a class="dropdown-item" href="#">Edit</a>
                                                        </div> --}}
                                                    </div>
                                                    <div class="time-linescroll">
                                                        <div class="line_box">
                                                            <div class="text_circle done">
                                                                <a class="tvar active"><span></span></a>
                                                                <div class="circle">
                                                                    <p>Internal Qc</p>
                                                                    <h4>{{\Carbon\Carbon::parse($pump->travel_start)->toDateString()}}</h4>
                                                                    <div class="subline">
                                                                        <h6>{{$pump->travel_time}} min</h6>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="text_circle">
                                                                <a class="tvar active"><span></span></a>
                                                                <div class="circle">
                                                                    <p>Inspection Time</p>
                                                                    <h4>{{\Carbon\Carbon::parse($pump->insp_start)->toDateString()}}</h4>

                                                                </div>
                                                            </div>
                                                            <div class="text_circle">
                                                                <a class="tvar"></a>
                                                                <div class="circle">
                                                                    <p>At Site</p>
                                                                    <h4>{{\Carbon\Carbon::parse($pump->insp_end)->toDateString()}}</h4>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>



                                    @endif
                                    @endforeach
                                    @endforeach

                                </div>
                            </div>

                        </div>
                    </div>
                </div>


                {{-- <div class="row mt-5 justify-content-center">
                    <div class="col-md-3 col-9">
                        <nav aria-label="Page navigation example">
                            <ul class="pagination ">
                                <li class="page-item">
                                    <a class="page-link next" href="#" aria-label="Previous">
                                        <span aria-hidden="true"><i class="fa fa-angle-left"
                                                aria-hidden="true"></i></span>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link active" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link next" href="#" aria-label="Next">
                                        <span aria-hidden="true"><i class="fa fa-angle-right"
                                                aria-hidden="true"></i></span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div> --}}

            </div>

            {{-- modal start --}}
            <div class="modal fade filter-modal order-model" id="trip" tabindex="-1" role="dialog"
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
                            <div class="filter-contentbox">
                                <h6>Trip Details</h6>
                            </div>

                            <div class="order-map mt-3">
                                <iframe class="order-mapinner"
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14025.378893509162!2d77.39042522978897!3d28.49927446945861!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390ce8609722a14b%3A0x31318b8199ad8290!2sSector%20135%2C%20Noida%2C%20Uttar%20Pradesh!5e0!3m2!1sen!2sin!4v1697536963022!5m2!1sen!2sin"
                                    allowfullscreen="" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                                <div class="fastest-routebox">
                                    <span> 30 min (32 km) </span> Fastest route now due to traffic conditions
                                </div>
                            </div>

                            <div class="order-details mt-4">
                                <div class="row">
                                    <div class="col-md-6 mb-sm-0 mb-2">
                                        <div class="order-detailscontent">
                                            <h1>Order Details</h1>
                                            <div class="d-flex justify-content-between mt-sm-3 mt-2">
                                                <div>
                                                    <p>Delivery Date/Time</p>
                                                    <h6>01/10/2022 04:00 PM</h6>
                                                </div>
                                                <div>
                                                    <p>Internal</p>
                                                    <h6>20 Min</h6>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between mt-sm-3 mt-2">
                                                <div>
                                                    <p>Delivery Date/Time</p>
                                                    <h6>01/10/2022 04:00 PM</h6>
                                                </div>
                                                <div>
                                                    <p>Internal</p>
                                                    <h6>20 Min</h6>
                                                </div>
                                            </div>

                                            <div class="row mt-3 align-items-center">
                                                <div class="col-md-4 col-4">
                                                    <div class="trip-userbox">
                                                        <img src="{{ asset('assets/img/course2.png') }}" alt="">
                                                    </div>
                                                </div>
                                                <div class="col-md-8 col-8 pl-0">
                                                    <h3>Mohammed Saad</h3>
                                                    <h4>DUB F 7689531</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">

                                        <div class="customer-infotext ">
                                            <div class="customer-infotextinner pb-sm-5">
                                                <h6>Customer Info</h6>

                                                <div class="customer-timeline mb-sm-3">
                                                    <div class="customer-timelineinner">
                                                        <p>Company</p>
                                                        <h4>ABC Company, Dubai</h4>
                                                    </div>
                                                    <div class="customer-timelineinner">
                                                        <p>Project</p>
                                                        <h4>Project Name 003</h4>
                                                    </div>
                                                    <div class="customer-timelineinner">
                                                        <p>Site Location</p>
                                                        <h4>Emaar Constructions Site</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="mix-details mt-sm-5 mt-3">
                                <h2>Trip Status</h2>

                                <div class="time-linescroll mt-3">
                                    <div class="line_box">
                                        <div class="text_circle done">
                                            <a class="tvar active"><span></span></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                                <div class="subline">
                                                    <h6>10 min</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text_circle done">
                                            <a class="tvar active"><span></span></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                                <div class="subline">
                                                    <h6>05 min</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text_circle done">
                                            <a class="tvar active"></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                                <div class="subline">
                                                    <h6>45 min</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text_circle">

                                            <a class="tvar active"></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                            </div>
                                        </div>
                                        <div class="text_circle">
                                            <a class="tvar"></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                            </div>
                                        </div>
                                        <div class="text_circle">

                                            <a class="tvar"></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                            </div>
                                        </div>
                                        <div class="text_circle">
                                            <a class="tvar"></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                            </div>
                                        </div>
                                        <div class="text_circle">
                                            <a class="tvar"></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
            {{-- modal end --}}

            <!-- filter -->
            <div class="modal fade filter-modal order-model" id="trip2" tabindex="-1" role="dialog"
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
                            <div class="filter-contentbox">
                                <h6>Trip Details</h6>
                            </div>

                            <div class="order-map mt-3">
                                <iframe class="order-mapinner"
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14025.378893509162!2d77.39042522978897!3d28.49927446945861!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390ce8609722a14b%3A0x31318b8199ad8290!2sSector%20135%2C%20Noida%2C%20Uttar%20Pradesh!5e0!3m2!1sen!2sin!4v1697536963022!5m2!1sen!2sin"
                                    allowfullscreen="" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                                <div class="fastest-routebox">
                                    <span> 30 min (32 km) </span> Fastest route now due to traffic conditions
                                </div>
                            </div>

                            <div class="order-details mt-4">
                                <div class="row">
                                    <div class="col-md-6 mb-sm-0 mb-2">
                                        <div class="order-detailscontent">
                                            <h1>Order Details</h1>
                                            <div class="d-flex justify-content-between mt-sm-3 mt-2">
                                                <div>
                                                    <p>Delivery Date/Time</p>
                                                    <h6>01/10/2022 04:00 PM</h6>
                                                </div>
                                                <div>
                                                    <p>Internal</p>
                                                    <h6>20 Min</h6>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between mt-sm-3 mt-2">
                                                <div>
                                                    <p>Delivery Date/Time</p>
                                                    <h6>01/10/2022 04:00 PM</h6>
                                                </div>
                                                <div>
                                                    <p>Internal</p>
                                                    <h6>20 Min</h6>
                                                </div>
                                            </div>

                                            <div class="row mt-3 align-items-center">
                                                <div class="col-md-4 col-4">
                                                    <div class="trip-userbox">
                                                        <img src="{{ asset('assets/img/course2.png') }}"
                                                            alt="">
                                                    </div>
                                                </div>
                                                <div class="col-md-8 col-8 pl-0">
                                                    <h3>Mohammed Saad</h3>
                                                    <h4>DUB F 7689531</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">

                                        <div class="customer-infotext ">
                                            <div class="customer-infotextinner pb-sm-5">
                                                <h6>Customer Info</h6>

                                                <div class="customer-timeline mb-sm-3">
                                                    <div class="customer-timelineinner">
                                                        <p>Company</p>
                                                        <h4>ABC Company, Dubai</h4>
                                                    </div>
                                                    <div class="customer-timelineinner">
                                                        <p>Project</p>
                                                        <h4>Project Name 003</h4>
                                                    </div>
                                                    <div class="customer-timelineinner">
                                                        <p>Site Location</p>
                                                        <h4>Emaar Constructions Site</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="mix-details mt-sm-5 mt-3">
                                <h2>Trip Status</h2>

                                <div class="time-linescroll mt-3">
                                    <div class="line_box">
                                        <div class="text_circle done">
                                            <a class="tvar active"><span></span></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                                <div class="subline">
                                                    <h6>10 min</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text_circle">
                                            <a class="tvar active"><span></span></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>
                                            </div>
                                        </div>
                                        <div class="text_circle">
                                            <a class="tvar"></a>
                                            <div class="circle">
                                                <p>Loading</p>
                                                <h4>09:00AM</h4>

                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- end filter -->
        </div>
    </section>


    <script>
        function redirectToPage(element) {
            window.location.href = element.dataset.url;
        }
    </script>
@endsection
