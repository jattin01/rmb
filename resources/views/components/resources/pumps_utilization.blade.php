@extends('layouts.auth.app')
@section('content')
<section class="content">
				<div class="container-fluid">
					

					<div class="px-sm-4">
						<div class="row mt-0 mt-sm-3 align-items-center">
							<div class="col-md-7 mb-sm-0 mb-2">
								<div class="top-head">
									<h1>Schedule</h1>
									<p>Pumps</p>
								</div>
							</div>
							<div class="col-md-5">
								<div class="d-sm-flex d-block align-items-center justify-content-end">
								<button type="button" class="btn save-btn mr-3">Today's Schedule</button>
								<div class="dropdown show calender-box mt-3 mt-sm-0">
									<button class="btn calender-btn dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
									 data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									  <span class="calender-img"><img src="{{asset('assets/img/calender-img.svg')}}" alt=""></span>  Monday, 26 July, 2023
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
						</div>

						<div class="row mt-sm-4 mt-3">
							<div class="col-md-4">
								<div class="form-group position-relative">
									<input type="email" class="form-control search-byinpt padding-right"
										placeholder="Search By...">
									<img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
								</div>
							</div>
							<div class="col-md-8 text-sm-right">
								<button onclick = "redirectOrders();" type="button" class="btn btn-primary mr-2">Orders</button>
								<button onclick = "redirectResources();" type="button" class="btn btn-success mr-2">Resources</button>
								<button type="button" class="btn btn-publish mr-2">Publish</button>
								<button type="button" class="btn export-btn mr-2">Export</button>
								<select class="filter-select mr-2 mt-sm-0 mt-3">
									<option>Filters</option>
									
								</select>
							</div>
						</div>

						<div class="row mt-3 mt-sm-2 align-items-center">
							<div class="col-md-6 text-sm-right">
								<span data-toggle="modal" data-target="#requirement" class="resource-requirementtext">Resource Requirement</span>
								<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box planned"></span>   Planned</span>
								<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box early-start"></span>  Revised</span>
								<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box cleaning"></span> Cleaning </span>
								<span class="schedule-chartvalue mr-sm-2 mr-1" > <span class="dots-box delay"></span>  Delay</span>
							</div>
						</div>

						<div class="row mt-sm-4 mt-3">
							<div class="col-md-12">
								<div class="table-responsive position-relative">
									<table class="table chart-table progress-linechart resource-requirementtable">
										<tbody>
											<tr>
												<th>
													<div class="head-innerbox">
														Orders
													</div>
												</th>

                                                @foreach ($result['heading'] as $head_time)
                                                <th>
													<div class="inner-middle">
														<p class="chart-tableheadtext">	{{$head_time['end_time']}}</p>
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
												<td class = "{{ ($res['schedule']) ? '' : 'orange-bgtd' }}">
													<div class="d-flex justify-content-between align-items-center"> 
														<div>
															Order {{$res['order_no']}} ({{$res['quantity']}} CUM)
														</div>
														<div>
															<img src="{{asset('assets/img/metro-info.svg')}}" class="mr-2 cursor-pointer" data-toggle="modal" data-target="#filter" alt="">
															<img src="{{asset('assets/img/gray-more.svg')}}" id = "more-icon-{{$res['order_no']}}"  alt="" style="cursor: pointer;" onclick="toggleDropdown({{$res['order_no']}})">
														</div>
													</div>
													<span class="plant-texttable">{{$res['location']}}</span>
												</td>
                                                @foreach ($res['resultData'] as $resData)
                                                <td colspan="{{isset($resData['colspan']) ? $resData['colspan'] : 1}}">
													
                                                        @if (isset($resData['id']))
                                                        <div class="main-progressbox">
                                                        <div class="progress multi-progress ml{{$resData['start_minutes']}}">

															@php
																$deviation = $resData['late_deviation'] > 0 ? $resData['late_deviation'] . " Mins Late" : ($resData['early_deviation'] ? $resData['early_deviation'] . " Mins Early" : null);
																$duration = Carbon\Carbon::parse($res['start_time']) -> eq(Carbon\Carbon::parse($res['end_time'])) ? 'Schedule - ' . Carbon\Carbon::parse($res['end_time']) -> format('h:i A') . ' ': ' Schedule - ' . Carbon\Carbon::parse($res['start_time']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($res['end_time']) -> format('h:i A') . ' ';
																$bar_title = 'Delivery - ' . Carbon\Carbon::parse($res['delivery_date']) -> format('h:i A') .  ', ' . $duration ;
																$main_title = $bar_title . ($deviation ? '(' .$deviation . ')' : null);
															@endphp

															@if ($resData['early_deviation_pixel'] > 0)
                                                                <div class="progress-bar multi-firstdarkblue" data-toggle="tooltip" data-placement="bottom" title="{{ $main_title }}" role="progressbar" style="padding : 0%; width: {{$resData['early_deviation_pixel'] ? $resData['early_deviation_pixel'] . 'px' : '0%'}}" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                                                            @endif

                                                            @if ($resData['late_deviation_pixel'] > 0)
                                                                <div class="progress-bar multi-firstred" data-toggle="tooltip" data-placement="bottom"title="{{ $main_title }}" role="progressbar" style="padding : 0%; width: {{$resData['late_deviation_pixel'] ? $resData['late_deviation_pixel'] . 'px' : '0%'}}" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                                                            @endif

															<div class="progress-bar multi-firstblue" data-toggle="tooltip" data-placement="bottom" title="{{ $main_title }}" role="progressbar" style="padding : 0%; width  : {{$resData['total_pixels'] ? $resData['total_pixels'] . 'px' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>

															
															
														</div>
                                                        </div>

                                                        <div class="stip-bgmainebox">
                                                            @foreach ($resData['stripe'] as $stripe)
                                                                <span class="{{$stripe % 2 !== 0 ? 'white-stip' : 'frist-stip' }}"></span>
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
                                            <tr class = "secound-table schedule-graph-hidden schedule-graph-{{$res['order_no']}}" id = "schedule-{{$res['order_no']}}" >
												<td colspan="4">
												{{Carbon\Carbon::parse($res['start_time']) -> format('h:i A')}} - {{Carbon\Carbon::parse($res['end_time']) -> format('h:i A')}}, {{$res['customer']}} - {{$res['site']}}
												</td>
												<td colspan="8">
													<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box early-start"></span> Loading </span>
													<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box internal"></span> Internal QC </span>
													<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box travelling"></span> Travelling to Site </span>
													<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box onsite"></span> Onsite Inspection </span>
													<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box  pouring"></span> Pouring </span>
													<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box cleaning"></span> Cleaning </span>
													<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box return"></span> Return to Plant </span>
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
												
											</tr>

                                            @foreach ($res['schedule'] as $sch)
                                            <tr class = "schedule-graph-hidden schedule-graph-{{$res['order_no']}}" id = "schedule-{{$res['order_no']}}">
												<td>
													<div class="d-flex align-items-center justify-content-between">
														<div>
															Truck {{$sch['transit_mixer'] . " "}} ({{$sch['truck_capacity'] . " CUM"}})
															<br/>
															{{$sch['batching_plant']}} - {{$sch['batching_qty']}} CUM
														</div>
														
														<div>
															<img src="{{asset('assets/img/light-info.svg')}}" alt="">
														</div>
													</div>
												</td>

                                                @foreach ($sch['resultData'] as $schResData)
                                                <td colspan="{{isset($schResData['colspan']) ? $schResData['colspan'] : 1}}">
													
                                                        @if (isset($schResData['id']))
                                                        <div class="main-progressbox">
                                                        <div class="progress constructions-chart ml{{$schResData['start_minutes']}}">
															<div class="progress-bar skyblue" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($sch['loading_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['loading_end']) -> format('h:i A') . ' (' . $sch['loading_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$schResData['loading_pixels'] ? $schResData['loading_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100" ></div>
															<div class="progress-bar pink" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($sch['qc_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['qc_end']) -> format('h:i A')  . ' (' . $sch['qc_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$schResData['qc_pixels'] ? $schResData['qc_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar purple" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($sch['travel_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['travel_end']) -> format('h:i A') . ' (' . $sch['travel_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$schResData['travel_pixels'] ? $schResData['travel_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar dark-green" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($sch['insp_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['insp_end']) -> format('h:i A') . ' (' . $sch['insp_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$schResData['insp_pixels'] ? $schResData['insp_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar dark-blue" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($sch['pouring_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['pouring_end']) -> format('h:i A') . ' (' . $sch['pouring_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$schResData['pouring_pixels'] ? $schResData['pouring_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar nevy-blue" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($sch['cleaning_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['cleaning_end']) -> format('h:i A'). ' (' . $sch['cleaning_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$schResData['cleaning_pixels'] ? $schResData['cleaning_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar light-green" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($sch['return_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($sch['return_end']) -> format('h:i A') . ' (' . $sch['return_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$schResData['return_pixels'] ? $schResData['return_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
														</div>
                                                        </div>

                                                        <div class="stip-bgmainebox">
                                                            @foreach ($schResData['stripe'] as $stripeSch)
                                                                <span class="{{$stripeSch % 2 !== 0 ? 'white-stip' : 'frist-stip' }}"></span>
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
                                            <tr class = "schedule-graph-hidden schedule-graph-{{$res['order_no']}}">
												<td>
													<div class="d-flex align-items-center justify-content-between">
														<div>
															Pump {{$psch['pump']}}
														</div>
														
														<div>
															<img src="{{asset('assets/img/light-info.svg')}}" alt="">
														</div>
													</div>
												</td>

                                                @foreach ($psch['resultData'] as $pschResData)
                                                <td colspan="{{isset($pschResData['colspan']) ? $pschResData['colspan'] : 0}}">
													
                                                        @if (isset($pschResData['id']))
                                                        <div class="main-progressbox">
                                                        <div class="progress constructions-chart ml{{$pschResData['start_minutes']}}">
															<div class="progress-bar pink" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['qc_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['qc_end']) -> format('h:i A') . ' (' . $sch['qc_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$pschResData['qc_pixels'] ? $pschResData['qc_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar purple" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['travel_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['travel_end']) -> format('h:i A') . ' (' . $sch['travel_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$pschResData['travel_pixels'] ? $pschResData['travel_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar dark-green" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['insp_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['insp_end']) -> format('h:i A') . ' (' . $sch['insp_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['insp_pixels'] ? $pschResData['insp_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar dark-blue" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['pouring_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['pouring_end']) -> format('h:i A') . ' (' . $sch['pouring_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['pouring_pixels'] ? $pschResData['pouring_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar nevy-blue" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['cleaning_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['cleaning_end']) -> format('h:i A') . ' (' . $sch['cleaning_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['cleaning_pixels'] ? $pschResData['cleaning_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															<div class="progress-bar light-green" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['return_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['return_end']) -> format('h:i A') . ' (' . $sch['return_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['return_pixels'] ? $pschResData['return_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
														</div>
                                                        </div>

                                                        <div class="stip-bgmainebox">
                                                            @foreach ($pschResData['stripe'] as $pstripeSch)
                                                                <span class="{{$pstripeSch % 2 !== 0 ? 'white-stip' : 'frist-stip' }}"></span>
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

											<tr class="secound-table schedule-graph-hidden schedule-graph-{{$res['order_no']}}">
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
											</tr>

											

                                            @endif
                                            @endforeach
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="row py-3 py-sm-5">
							<div class="col-md-12">
								<p class="copy-right">ABC Order Management System © . All rights reserved.</p>
							</div>
						</div>

					</div>

				</div>
			</section>
			<script>

				function getQueryParam(name) {
					var urlParams = new URLSearchParams(window.location.search);
					return urlParams.get(name);
				}

				function redirectResources()
				{
					window.location.href = "{{route('generate_schedule_3')}}?schedule_date=" + getQueryParam('schedule_date');
				}

				function redirectOrders()
				{
					window.location.href = "{{route('generate_schedule_2')}}?schedule_date=" + getQueryParam('schedule_date');
				}

				function toggleDropdown(order_no) {

					var elements = document.getElementsByClassName("schedule-graph-" + order_no);

					// Iterate over the collection and modify styles
					for (var i = 0; i < elements.length; i++) {
						elements[i].style.visibility = elements[i].style.visibility === 'visible' ? "collapse" : "visible";
						// Add more style modifications as needed
					}

					document.getElementById("more-icon-" + order_no).src = document.getElementById("more-icon-" + order_no).src == "{{asset('assets/img/purple-more.svg')}}" ? "{{asset('assets/img/gray-more.svg')}}" : "{{asset('assets/img/purple-more.svg')}}";

					

				}
</script>
@endsection