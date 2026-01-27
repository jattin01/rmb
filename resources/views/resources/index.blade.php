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
                <div class="col-md-4 mb-sm-0 mb-3">
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
                <div class="col-md-4  text-sm-right">
                    <div class="dropdown show calender-box">
                        <button class="btn calender-btn dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="calender-img"><img src="{{asset('assets/img/calender-img.svg')}}" alt=""></span>
                            Monday, 26 July, 2023
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
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <div class="row mt-sm-5 mt-3">
                                <div class="col-md-4">
                                    <form class="search-form" role="search">
                                        <div class="form-group position-relative">
                                            <input type="text" name="search" value="{{@$search}}" class="form-control search-byinpt padding-right"
                                                placeholder="Search By..." onchange="this.form.submit()">
                                            <img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
                                        </div>
                                        <input type="hidden" name="activeTab" value="home">
                                    </form>
                                </div>
                                <div class="col-md-8 text-sm-right">
                                    <a href="{{route('resources.create_batching_plant')}}" class="btn btn-success mr-2">Create New</a>
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
                            <!-- Listing Batching Plants -->
                                @if(isset($companyLocations))
                                    @foreach($companyLocations as $key => $location)
                                        
                                        @if($location->batchingPlants->count() > 0)
                                            <div class="{{$key % 2 == 0 ? 'resources-batchingplantcontentbox mt-3' : 'resources-batchingplantcontentbox resources-batchingplantcontentboxgray mt-3'}}">
                                                <div class="row align-items-center">
                                                    <div class="col-md-2 mb-3 mb-sm-0">
                                                        <div class="loaction-contentbox">
                                                            <p>Location:</p>
                                                            <h6>{{$location->location}}, {{$location->address}}</h6>
                                                            <h5 class="mt-sm-3 mt-2">Batching Plant: <span>{{$location->batchingPlants->count()}}</span></h5>
                                                            <h5>Utilization: <span>0%</span></h5>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-10">
                                                        <div class="row">
                                                            @foreach($location->batchingPlants as $plant)
                                                            <div class="col-md-3 col-6 mb-2 mb-sm-0">
                                                                <div class="progress-contentbox text-center">
                                                                    <div class="dropdown more-drop">
                                                                        <button class="more-iconprogressbox" type="button"
                                                                            id="dropdownMenuButton" data-toggle="dropdown"
                                                                            aria-haspopup="true" aria-expanded="false">
                                                                            <i class="fa fa-ellipsis-v more-icon"
                                                                                aria-hidden="true"></i>
                                                                        </button>
                                                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                                            <a class="dropdown-item" href="{{route('resources.edit_batching_plant', ['plantId' => $plant->id])}}">Details</a>
                                                                            <!-- <a class="dropdown-item" href="#">Edit</a> -->
                                                                        </div>
                                                                    </div>

                                                                    <div class="progress-circle progress-0  mt-3">
                                                                        <h5 class="progress-txt">0% <br> <span
                                                                            class="progress-txtgray">Utilization</span>
                                                                        </h5>
                                                                    </div>
                                                                    <h2>{{$plant->long_name}} {{$plant->plant_name}}</h2>
                                                                    <h3>{{$plant->description}}</h3>
                                                                </div>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            
                             <!-- Listing Batching Plants Close-->
                        </div>

                        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <div class="row mt-sm-5 mt-3">
                                <div class="col-md-4">
                                    <form class="search-form" role="search">
                                        <div class="form-group position-relative">
                                            <input type="text" name="mixer_search" value="{{@$mixerSearch}}" class="form-control search-byinpt padding-right"
                                                placeholder="Search By..." onchange="this.form.submit()">
                                            <img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
                                        </div>
                                        <input type="hidden" name="activeTab" value="profile">
                                    </form>
                                </div>
                                <div class="col-md-8 text-sm-right">
                                    <a href="{{route('resources.transit_mixer_create')}}" class="btn btn-success mr-2">Create New</a>
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

                            <label class="batching-plantlabel mt-sm-4 mt-3">At Plant ({{isset($transitMixers) ? $transitMixers->count():0}})</label>
                            <div class="row mt-2 noflexwrap">
                                @if(isset($transitMixers))
                                    @foreach($transitMixers as $mixer)
                                    <div class="col-md-2 col-6 mb-2 mb-sm-0">
                                        <div class="plant-detailscontentbox">
                                            <div class="dropdown more-drop">
                                                <button class="more-iconprogressbox" type="button"
                                                    id="dropdownMenuButton" data-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-v more-icon"
                                                        aria-hidden="true"></i>
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                    <a class="dropdown-item" href="{{route('resources.transit_mixer_edit', ['mixerId' => $mixer->id])}}">Details</a>
                                                    <!-- <a class="dropdown-item" href="#">Edit</a> -->
                                                </div>
                                            </div>
                                            <h2>{{$mixer->truck_name}} {{$mixer->plate_no}} </h2>
                                            <p>({{$mixer->truck_capacity}} CUM) </p>
                                            <h6>Jebel Ali, Dubai</h6>
                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>

                            <label class="batching-plantlabel mt-sm-4 mt-3">On Trip (45)</label>

                            <div class="batching-plantbordertop mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-sm-center">
                                        <span class="pink-textbox cursor-pointer" data-toggle="modal"
                                            data-target="#trip">Truck 001 / 12 CUM</span>
                                        <h2>DUB F 7689531</h2>
                                    </div>
                                    <div class="col-md-10">

                                        <div class="dropdown more-drop">
                                            <button class="more-iconprogressbox more-iconprogressboxnew" type="button"
                                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                aria-expanded="false">
                                                <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Details</a>
                                                <a class="dropdown-item" href="#">Edit</a>
                                            </div>
                                        </div>

                                        <div class="time-linescroll">
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

                            <div class="batching-plantbordertop mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-sm-center">
                                        <span class="pink-textbox green">Truck 001 / 12 CUM</span>
                                        <h2>DUB F 7689531</h2>
                                    </div>
                                    <div class="col-md-10">

                                        <div class="dropdown more-drop">
                                            <button class="more-iconprogressbox more-iconprogressboxnew" type="button"
                                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                aria-expanded="false">
                                                <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Details</a>
                                                <a class="dropdown-item" href="#">Edit</a>
                                            </div>
                                        </div>

                                        <div class="time-linescroll">
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

                            <div class="batching-plantbordertop mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-sm-center">
                                        <span class="pink-textbox">Truck 001 / 12 CUM</span>
                                        <h2>DUB F 7689531</h2>
                                    </div>
                                    <div class="col-md-10">

                                        <div class="dropdown more-drop">
                                            <button class="more-iconprogressbox more-iconprogressboxnew" type="button"
                                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                aria-expanded="false">
                                                <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Details</a>
                                                <a class="dropdown-item" href="#">Edit</a>
                                            </div>
                                        </div>

                                        <div class="time-linescroll">
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

                            <div class="batching-plantbordertop mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-sm-center">
                                        <span class="pink-textbox green">Truck 001 / 12 CUM</span>
                                        <h2>DUB F 7689531</h2>
                                    </div>
                                    <div class="col-md-10">

                                        <div class="dropdown more-drop">
                                            <button class="more-iconprogressbox more-iconprogressboxnew" type="button"
                                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                aria-expanded="false">
                                                <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Details</a>
                                                <a class="dropdown-item" href="#">Edit</a>
                                            </div>
                                        </div>

                                        <div class="time-linescroll">
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
                                                <div class="text_circle done">

                                                    <a class="tvar active"></a>
                                                    <div class="circle">
                                                        <p>Loading</p>
                                                        <h4>09:00AM</h4>
                                                    </div>
                                                </div>
                                                <div class="text_circle done">
                                                    <a class="tvar active"></a>
                                                    <div class="circle">
                                                        <p>Loading</p>
                                                        <h4>09:00AM</h4>
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

                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="batching-plantbordertop mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-sm-center">
                                        <span class="pink-textbox">Truck 001 / 12 CUM</span>
                                        <h2>DUB F 7689531</h2>
                                    </div>
                                    <div class="col-md-10">

                                        <div class="dropdown more-drop">
                                            <button class="more-iconprogressbox more-iconprogressboxnew" type="button"
                                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                aria-expanded="false">
                                                <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Details</a>
                                                <a class="dropdown-item" href="#">Edit</a>
                                            </div>
                                        </div>

                                        <div class="time-linescroll">
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

                            <div class="batching-plantbordertop mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-sm-center">
                                        <span class="pink-textbox green">Truck 001 / 12 CUM</span>
                                        <h2>DUB F 7689531</h2>
                                    </div>
                                    <div class="col-md-10">

                                        <div class="dropdown more-drop">
                                            <button class="more-iconprogressbox more-iconprogressboxnew" type="button"
                                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                aria-expanded="false">
                                                <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Details</a>
                                                <a class="dropdown-item" href="#">Edit</a>
                                            </div>
                                        </div>

                                        <div class="time-linescroll">
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

                            <div class="batching-plantbordertop mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-sm-center">
                                        <span class="pink-textbox">Truck 001 / 12 CUM</span>
                                        <h2>DUB F 7689531</h2>
                                    </div>
                                    <div class="col-md-10">

                                        <div class="dropdown more-drop">
                                            <button class="more-iconprogressbox more-iconprogressboxnew" type="button"
                                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                aria-expanded="false">
                                                <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Details</a>
                                                <a class="dropdown-item" href="#">Edit</a>
                                            </div>
                                        </div>

                                        <div class="time-linescroll">
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
                                                        <div class="subline">
                                                            <h6>05 min</h6>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text_circle">
                                                    <a class="tvar"></a>
                                                    <div class="circle">
                                                        <p>Loading</p>
                                                        <h4>09:00AM</h4>
                                                        <div class="subline">
                                                            <h6>45 min</h6>
                                                        </div>
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

                        <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                            <div class="row mt-sm-5 mt-3">
                                <div class="col-md-4">
                                    <form class="search-form" role="search">
                                        <div class="form-group position-relative">
                                            <input type="text" name="pump_search" value="{{@$pumpSearch}}" class="form-control search-byinpt padding-right"
                                                placeholder="Search By..." onchange="this.form.submit()">
                                            <img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
                                        </div>
                                        <input type="hidden" name="activeTab" value="contact">
                                    </form>
                                </div>
                                <div class="col-md-8 text-sm-right">
                                    <a href="{{route('resources.pump_create')}}" class="btn btn-success mr-2">Create New</a>
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


                            <label class="batching-plantlabel mt-sm-4 mt-3">At Plant ({{isset($pumps) ? $pumps->count():0}})</label>
                            <div class="row mt-2 noflexwrap">
                                @if(isset($pumps))
                                    @foreach($pumps as $pump)
                                    <div class="col-md-2 col-6 mb-2 mb-sm-0">
                                        <div class="plant-detailscontentbox">
                                            <div class="dropdown more-drop">
                                                <button class="more-iconprogressbox" type="button"
                                                    id="dropdownMenuButton" data-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-v more-icon"
                                                        aria-hidden="true"></i>
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                    <a class="dropdown-item" href="{{route('resources.pump_edit', ['pumpId' => $pump->id])}}">Details</a>
                                                    <!-- <a class="dropdown-item" href="#">Edit</a> -->
                                                </div>
                                            </div>
                                            <h2>{{$pump->pump_name}}</h2>
                                            <p>({{$pump->pump_capacity}} CUM) </p>
                                            <h6>Jebel Ali, Dubai</h6>
                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>

                            <label class="batching-plantlabel mt-sm-4 mt-3">On Trip (34)</label>

                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <div class="resources-pumpsbox">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-sm-center">
                                                <span class="pink-textbox cursor-pointer" data-toggle="modal"
                                                    data-target="#trip2">Pump 011</span>
                                                <h3>Type: 42 M</h3>
                                            </div>
                                            <div class="col-md-9 pl-0">
                                                <div class="dropdown more-drop">
                                                    <button class="more-iconprogressbox more-iconprogressboxnew"
                                                        type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Details</a>
                                                        <a class="dropdown-item" href="#">Edit</a>
                                                    </div>
                                                </div>
                                                <div class="time-linescroll">
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
                                <div class="col-md-6 mb-3">
                                    <div class="resources-pumpsbox">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-sm-center">
                                                <span class="pink-textbox green cursor-pointer" data-toggle="modal"
                                                    data-target="#trip2">Pump 011</span>
                                                <h3>Type: 42 M</h3>
                                            </div>
                                            <div class="col-md-9 pl-0">
                                                <div class="dropdown more-drop">
                                                    <button class="more-iconprogressbox more-iconprogressboxnew"
                                                        type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Details</a>
                                                        <a class="dropdown-item" href="#">Edit</a>
                                                    </div>
                                                </div>
                                                <div class="time-linescroll">
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
                                                            </div>
                                                        </div>
                                                        <div class="text_circle">
                                                            <a class="tvar active"></a>
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

                                <div class="col-md-6 mb-3">
                                    <div class="resources-pumpsbox">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-sm-center">
                                                <span class="pink-textbox green cursor-pointer" data-toggle="modal"
                                                    data-target="#trip2">Pump 011</span>
                                                <h3>Type: 42 M</h3>
                                            </div>
                                            <div class="col-md-9 pl-0">
                                                <div class="dropdown more-drop">
                                                    <button class="more-iconprogressbox more-iconprogressboxnew"
                                                        type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Details</a>
                                                        <a class="dropdown-item" href="#">Edit</a>
                                                    </div>
                                                </div>
                                                <div class="time-linescroll">
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
                                                            </div>
                                                        </div>
                                                        <div class="text_circle">
                                                            <a class="tvar active"></a>
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
                                <div class="col-md-6 mb-3">
                                    <div class="resources-pumpsbox">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-sm-center">
                                                <span class="pink-textbox cursor-pointer" data-toggle="modal"
                                                    data-target="#trip2">Pump 011</span>
                                                <h3>Type: 42 M</h3>
                                            </div>
                                            <div class="col-md-9 pl-0">
                                                <div class="dropdown more-drop">
                                                    <button class="more-iconprogressbox more-iconprogressboxnew"
                                                        type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Details</a>
                                                        <a class="dropdown-item" href="#">Edit</a>
                                                    </div>
                                                </div>
                                                <div class="time-linescroll">
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
                                <div class="col-md-6 mb-3">
                                    <div class="resources-pumpsbox">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-sm-center">
                                                <span class="pink-textbox cursor-pointer" data-toggle="modal"
                                                    data-target="#trip2">Pump 011</span>
                                                <h3>Type: 42 M</h3>
                                            </div>
                                            <div class="col-md-9 pl-0">
                                                <div class="dropdown more-drop">
                                                    <button class="more-iconprogressbox more-iconprogressboxnew"
                                                        type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Details</a>
                                                        <a class="dropdown-item" href="#">Edit</a>
                                                    </div>
                                                </div>
                                                <div class="time-linescroll">
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
                                <div class="col-md-6 mb-3">
                                    <div class="resources-pumpsbox">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-sm-center">
                                                <span class="pink-textbox green cursor-pointer" data-toggle="modal"
                                                    data-target="#trip2">Pump 011</span>
                                                <h3>Type: 42 M</h3>
                                            </div>
                                            <div class="col-md-9 pl-0">
                                                <div class="dropdown more-drop">
                                                    <button class="more-iconprogressbox more-iconprogressboxnew"
                                                        type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Details</a>
                                                        <a class="dropdown-item" href="#">Edit</a>
                                                    </div>
                                                </div>
                                                <div class="time-linescroll">
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
                                                            </div>
                                                        </div>
                                                        <div class="text_circle">
                                                            <a class="tvar active"></a>
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
                                <div class="col-md-6 mb-3">
                                    <div class="resources-pumpsbox">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-sm-center">
                                                <span class="pink-textbox green cursor-pointer" data-toggle="modal"
                                                    data-target="#trip2">Pump 011</span>
                                                <h3>Type: 42 M</h3>
                                            </div>
                                            <div class="col-md-9 pl-0">
                                                <div class="dropdown more-drop">
                                                    <button class="more-iconprogressbox more-iconprogressboxnew"
                                                        type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Details</a>
                                                        <a class="dropdown-item" href="#">Edit</a>
                                                    </div>
                                                </div>
                                                <div class="time-linescroll">
                                                    <div class="line_box">
                                                        <div class="text_circle sub">
                                                            <a class="tvar active"><span></span></a>
                                                            <div class="circle">
                                                                <p>Loading</p>
                                                                <h4>09:00AM</h4>
                                                            </div>
                                                        </div>
                                                        <div class="text_circle ">
                                                            <a class="tvar "><span></span></a>
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
                                <div class="col-md-6 mb-3">
                                    <div class="resources-pumpsbox">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-sm-center">
                                                <span class="pink-textbox cursor-pointer" data-toggle="modal"
                                                    data-target="#trip2">Pump 011</span>
                                                <h3>Type: 42 M</h3>
                                            </div>
                                            <div class="col-md-9 pl-0">
                                                <div class="dropdown more-drop">
                                                    <button class="more-iconprogressbox more-iconprogressboxnew"
                                                        type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v more-icon" aria-hidden="true"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Details</a>
                                                        <a class="dropdown-item" href="#">Edit</a>
                                                    </div>
                                                </div>
                                                <div class="time-linescroll">
                                                    <div class="line_box">
                                                        <div class="text_circle sub">
                                                            <a class="tvar active"><span></span></a>
                                                            <div class="circle">
                                                                <p>Loading</p>
                                                                <h4>09:00AM</h4>
                                                            </div>
                                                        </div>
                                                        <div class="text_circle">
                                                            <a class="tvar"><span></span></a>
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

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
		$(function () {
			$('[data-toggle="tooltip"]').tooltip()
		})

		document.addEventListener('DOMContentLoaded', function () {
			var calendarEl = document.getElementById('calendar');
			var calendar = new FullCalendar.Calendar(calendarEl, {
				headerToolbar: {
					left: 'title',
					center: '',
					right: 'prev,next'
				},
				editable: true,
				dayMaxEvents: true, // allow "more" link when too many events
				eventClick: function (event, jsEvent, view) {
					alert();
				},
				//dateClick: function(info) {
				//alert();
				//},
				// eventContent: function (info) {
				// 	return { html: info.event.title };
				// },
				events: [
					// {
					// 	title: '<div class="dotcirclatt"></div>',
					// start: '2023-07-01'
					// 			},
					// {
					// 	title: '<div class="dotcirclatt present"></div>',
					// start: '2023-07-02'
					// 			},
					// {
					// 	title: '<div class="dotcirclatt absent"></div>',
					// start: '2023-07-03'
					// 			},
					// {
					// 	title: '<div class="dotcirclatt absent"></div>',
					// start: '2023-07-04'
					// 			},
					// {
					// 	title: '<div class="dotcirclatt present"></div>',
					// start: '2023-07-05'
					// 			},
					// {
					// 	title: '<div class="dotcirclatt present"></div>',
					// start: '2023-07-06'
					// 			},
					// {
					// 	title: '<div class="dotcirclatt absent"></div>',
					// start: '2023-07-07'
					// 			},
					// {
					// 	title: '<div class="dotcirclatt"></div>',
					// start: '2023-07-08'
					// 			},
					{
						title: '<div class="dotcirclatt"></div>',
						start: '2023-07-15'
					},
					// {
					// 	title: '<div class="dotcirclatt"></div>',
					// start: '2023-07-22'
					// 			},
					// {
					// 	title: '<div class="dotcirclatt present"></div>',
					// start: '2023-07-29'
					// 			},
					// {
					// 	title: '<div class="dotcirclatt"></div>',
					// start: '2023-07-14'
					// 			},

					{
						title: '<div class="dotcirclatt"></div>',
						start: '2023-07-18'
					},
					{
						title: '<div class="dotcirclatt "></div>',
						start: '2023-07-30'
					}
				]
			});
			calendar.render();
		});

		$('.dropdown-menu').on('click', function (event) {
			event.stopPropagation();
		});

        document.addEventListener("DOMContentLoaded", function() {
            // Get the active tab from the URL if present
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('activeTab');
    
            // If there's an active tab in the URL, set it as the active tab
            if (activeTab) {
                const tabToShow = document.getElementById(activeTab + '-tab');
                const tabPaneToShow = document.getElementById(activeTab);
    
                // If both the tab and pane exist, make them active
                if (tabToShow && tabPaneToShow) {
                    document.querySelectorAll('.nav-link').forEach(tab => tab.classList.remove('active'));
                    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
    
                    tabToShow.classList.add('active');
                    tabPaneToShow.classList.add('show', 'active');
                }
            }
    
        });
</script>
@endsection