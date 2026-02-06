@extends('layouts.auth.app')
@section('content')
<section class="content">
				<div class="container-fluid">

				@php
				$tm_resource = collect($transit_mixer['transit_mixers']) -> groupBy(["location", "capacity"]);
				$bp_resource = collect($batching_plant['batching_plants']) -> groupBy(["location", "capacity"]);
				$p_resource = collect($pumps['pumps']) -> groupBy(["location", "type", "capacity"]);
				@endphp


					<div class="px-sm-4">
						<div class="row mt-0 mt-sm-3 align-items-center">
						<div class="col-md-3 mb-sm-0 mb-2">
								<div class="top-head">
									<h1>Schedule</h1>
									Overview
								</div>
							</div>

							<div class="col-md-5 mb-sm-0 mb-3">
								<ul class="nav nav-tabs order-tab" id="myTab" role="tablist">
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
								</ul>
							</div>

							<div class="col-md-4">
								<div class="d-sm-flex d-block align-items-center justify-content-end">
								<button type="button" onclick = "redirectToLiveOrder();" class="btn save-btn mr-3">Today's Schedule</button>
								<div class="dropdown show calender-box mt-3 mt-sm-0">
                                    <button class="btn calender-btn new-calenderbtn dropdown-toggle"
                                    href="#" role="button" id="dropdownMenuLink"
                                    data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false" disabled>

                                <div id="schedule_date_label"></div>
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

						<div class="tab-content" id="myTabContent">
							<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
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
									<form id="publishOrders" action="{{ route('orders.schedule.publish') }}" method="POST" style = "display:inline;">
											@csrf
										<input type = "hidden" id = "input_gp_cmp_id" name = "group_company_id">
										<input type = "hidden" id = "input_sch_date" name = "schedule_date">
										<button type="button" onclick="confirmPublish()" class="btn btn-publish mr-2">Publish</button>


									</form>

										<button type="button" class="btn export-btn mr-2">Export</button>
										{{-- <select class="filter-select mr-2 mt-sm-0 mt-3">
											<option>Filters</option>

										</select> --}}

										<label>Interval Deviation - </label>
											<div class="form-group position-relative" style="display: inline;">
												<input type="number" class="form-control search-byinpt padding-right" style="display: inline; max-width: 15%;"
													id = "interval_deviation_input" type ="number" min = "0" max = "500" value="100">
												<img src="{{asset('assets/img/percentage_icon.png')}}" class="fill-percentageimg-2" alt="">
											</div>


									</div>

								</div>

								<div class="row mt-3 mt-sm-2">
									<div class="col-md-6 text-sm">
									<span class="text">
									@php
												$tdq = 0;
															
												$totalDeliveries = count($result['resData']);
												$totalOnTimeDeliveries = 0;
												$avgWeightedCsScore = 0;
												$avgCS = 0;
												foreach($result['resData'] as $resD)
												{
													$avgWeightedCsScore += $resD['cs_weighted_score'];
													if (abs($resD['deviation']) <= 20) {
														$totalOnTimeDeliveries += 1;
													}

													$tdq += $resD['delivered_quantity'];
												}
												$punctualityScore = round(($totalOnTimeDeliveries / $totalDeliveries) * 10, 0 );
												$avgWeightedCsScore = round($avgWeightedCsScore, 0);

												echo 'CS : ' . $avgWeightedCsScore . ', PUN : ' . $punctualityScore . ', PRD : ' . $result['productivity'];
												echo ', LPI : ' . round(($avgWeightedCsScore * 0.4) + ($punctualityScore * 0.2) + ($result['productivity'] * 0.4), 2);
											@endphp
										</span>
									</div>
									<div class="col-md-6 text-sm-right">
										<span data-toggle="modal" data-target="#requirement" class="resource-requirementtext">Resource Requirement</span>
										<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box early-start"></span>  Planned</span>
										<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box early"></span> Early Start </span>
										<span class="schedule-chartvalue mr-sm-2 mr-1" > <span class="dots-box delay"></span>  Delayed Start</span>
									</div>
								</div>

								<div class="row mt-sm-4 mt-3">
									<div class="col-md-12">
										<div class="table-responsive position-relative" >
											<table class="table chart-table   resource-requirementtable" >
												<tbody>
													<tr>
														<th>
															<div class="head-innerbox">
																Orders ({{$tdq}} CUM)
															</div>
															
																
														</th>

														@foreach ($result['heading'] as $head_time)
														<th>
															<div class="inner-middle">

																<p class="chart-tableheadtext">	{{$head_time['end_time']}} </p>
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
														<td class = "{{ ($res['schedule']) ? ($res['quantity'] <= $res['delivered_quantity'] ? '' : 'yellow-bgtd') : 'orange-bgtd' }}">
															<div class="d-flex justify-content-between align-items-center">
																<div>
																	Order {{$res['order_no']}} ({{ $res['quantity'] == $res['delivered_quantity'] ? $res['delivered_quantity'] : min($res['delivered_quantity'], $res['quantity']) . '/' .$res['quantity']}} CUM)
																</div>
																	<div>
																		@if($res['is_temp_required'])
																		<img src="{{asset('assets/img/ice.svg')}}" alt="">
																		@endif
																		
																		@if($res['pump_qty'])
																		<img src="{{asset('assets/img/pump.svg')}}"  alt="">
																		@endif
																	</div>
																<div>
														
																	<img src="{{asset('assets/img/gray-more.svg')}}" id = "more-icon-{{$res['order_no']}}"  alt="" style="cursor: pointer;" onclick="toggleDropdown({{$res['order_no']}})">
																</div>
															</div>
															<span class="plant-texttable">{{$res['location']}}</span>
															<span class="text small">&nbsp; &nbsp; Trips : {{count($res['schedule'])}} &nbsp; &nbsp; CS : {{$res['cs_score']}} </span>
														</td>
														@php
														$slotTimeFlag = false;
														

														@endphp

														@foreach ($res['resultData'] as $resData)

														@php
															$slotTime = isset($resData['slot']) ? $resData['slot']['start_time'] : '12 AM';
															
															if($slotTime == '05 AM') {
																$slotTimeFlag = !($slotTimeFlag);
															}

														@endphp
														<td colspan="{{isset($resData['colspan']) ? $resData['colspan'] : 1}}" >

																@if (isset($resData['id']))
																<div class="main-progressbox">
																<div class="progress multi-progress ml{{$resData['start_minutes']}}" id = "progress-bar-{{$res['id']}}" onclick = "setSelectedOrder({{$res['id']}});" data-target="#reschedule-order" data-orderId="{{$res['id']}}" data-deliveryDate = "{{$res['delivery_date']}}" data-interval = "{{$res['interval']}}" data-deviation = "{{$res['interval_deviation']}}" data-toggle="modal">

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
																		<span class="{{$stripe % 2 !== 0 ? 'white-stip' : 'frist-stip' }}  @if($slotTimeFlag) green-stip @endif"></span>
																	@endforeach
																</div>

																@else
																<div class="stip-bgmainebox"  >
																	<span class="white-stip  @if($slotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip  @if($slotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip  @if($slotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip  @if($slotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip  @if($slotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip  @if($slotTimeFlag) green-stip @endif"></span>
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
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box bg-yellow"></span> Pump Installation </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box bg-secondary"></span> Waiting </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box  pouring"></span> Pouring </span>
															<br>
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
														<td></td>
														<td></td>
														<td></td>
														<td></td>
														<td></td>
														<td></td>
														<td></td>
														<td></td>
													</tr>
													
													@foreach ($res['pump_schedule'] as $psch)
													<tr class = "schedule-graph-hidden schedule-graph-{{isset($res['order_no']) ? $res['order_no'] : ''}}">
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
														@php
														$pOrderSchSlotTimeFlag = false;
														@endphp
														@foreach ($psch['resultData'] as $pschResData)

														@php
															$pOrderSchSlotTime = $pschResData['slot']['start_time'];
															
															if($pOrderSchSlotTime == '05 AM') {
																$pOrderSchSlotTimeFlag = ($pOrderSchSlotTimeFlag);
															}

														@endphp
														<td colspan="{{isset($pschResData['colspan']) ? $pschResData['colspan'] : 0}}">

																@if (isset($pschResData['id']))
																<div class="main-progressbox">
																<div class="progress constructions-chart" style = "margin-left:{{$pschResData['start_minutes'] . 'px'}};">
																	<div class="progress-bar pink" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['qc_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['qc_end']) -> format('h:i A') . ' (' . $psch['qc_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$pschResData['qc_pixels'] ? $pschResData['qc_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
																	<div class="progress-bar purple" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['travel_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['travel_end']) -> format('h:i A') . ' (' . $psch['travel_time'] . ' mins)'}}" role="progressbar" style="padding : 0%; width :  {{$pschResData['travel_pixels'] ? $pschResData['travel_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
																	<div class="progress-bar dark-green" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['insp_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['insp_end']) -> format('h:i A') . ' (' . $psch['insp_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['insp_pixels'] ? $pschResData['insp_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
																	<div class="progress-bar yellow" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['install_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['install_end']) -> format('h:i A') . ' (' . $psch['install_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['install_pixels'] ? $pschResData['install_pixels'] . 'px' : '0%'}}" aria-valuemin="0" aria-valuemax="100"></div>
																	<div class="progress-bar bg-secondary" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['waiting_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['waiting_end']) -> format('h:i A') . ' (' . $psch['waiting_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['waiting_pixels'] ? $pschResData['waiting_pixels'] . 'px' : '0%'}}" aria-valuemin="0" aria-valuemax="100"></div>
																	<div class="progress-bar dark-blue" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['pouring_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['pouring_end']) -> format('h:i A') . ' (' . $psch['pouring_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['pouring_pixels'] ? $pschResData['pouring_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
																	<div class="progress-bar nevy-blue" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['cleaning_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['cleaning_end']) -> format('h:i A') . ' (' . $psch['cleaning_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['cleaning_pixels'] ? $pschResData['cleaning_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
																	<div class="progress-bar light-green" role="progressbar" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($psch['return_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($psch['return_end']) -> format('h:i A') . ' (' . $psch['return_time'] . ' mins)'}}" style="padding : 0%; width :  {{$pschResData['return_pixels'] ? $pschResData['return_pixels'] . 'px' : '0%'}}"  aria-valuemin="0" aria-valuemax="100"></div>
															</div>
																</div>

																<div class="stip-bgmainebox">
																	@foreach ($pschResData['stripe'] as $pstripeSch)
																		<span class="{{$pstripeSch % 2 !== 0 ? 'white-stip' : 'frist-stip' }} @if($pOrderSchSlotTimeFlag) green-stip @endif"></span>
																	@endforeach
																</div>
																@else
																<div class="stip-bgmainebox">
																	<span class="white-stip @if($pOrderSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($pOrderSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($pOrderSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($pOrderSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($pOrderSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($pOrderSchSlotTimeFlag) green-stip @endif"></span>
																</div>

																@endif
														</td>
														@endforeach

													</tr>
													@endforeach

													@foreach ($res['schedule'] as $sch)
													<tr class = "schedule-graph-hidden schedule-graph-{{$res['order_no']}}" id = "schedule-{{$res['order_no']}}">
														<td>
															<div class="d-flex align-items-center justify-content-between">
																<div>
																	Truck {{$sch['transit_mixer'] . " "}} ({{$sch['truck_capacity'] . " CUM"}})
																	<br/>
																	{{$sch['batching_plant']}} - {{$sch['batching_qty']}} CUM
																	<span class="text small"></span>
																</div>

																<div>
																	<img src="{{asset('assets/img/light-info.svg')}}" alt="">
																</div>
															</div>
														</td>
														@php
														$schSlotTimeFlag = false;
														@endphp
														@foreach ($sch['resultData'] as $schResData)
														@php
															$schSlotTime = $schResData['slot']['start_time'];
															
															if($schSlotTime == '05 AM') {
																$schSlotTimeFlag = ($schSlotTimeFlag);
															}

														@endphp
														<td colspan="{{isset($schResData['colspan']) ? $schResData['colspan'] : 1}}">

																@if (isset($schResData['id']))
																<div class="main-progressbox">
																<div class="progress constructions-chart ml" style = "margin-left:{{$schResData['start_minutes'] . 'px'}};">
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

																		<span class="{{$stripeSch % 2 !== 0 ? 'white-stip' : 'frist-stip' }} @if($schSlotTimeFlag) green-stip @endif"></span>
																	@endforeach
																</div>
																@else
																<div class="stip-bgmainebox">
																	<span class="white-stip @if($schSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($schSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($schSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($schSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($schSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($schSlotTimeFlag) green-stip @endif"></span>
																</div>


																@endif
														</td>
														@endforeach

													</tr>
													@endforeach
													

													

													<tr class="secound-table schedule-graph-hidden schedule-graph-{{isset($res['order_no']) ? $res['order_no'] : ''}}">
														<td></td>
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
								<div class="row mt-sm-4 mt-3">
									<div class="col-md-4">
										<div class="form-group position-relative">
											<input type="email" class="form-control search-byinpt padding-right"
												placeholder="Search By...">
											<img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
										</div>
									</div>
									<div class="col-md-8 text-sm-right">
										<button type="button" class="btn export-btn mr-2">Export</button>
										<select class="filter-select mr-2 mt-sm-0 mt-3">
											<option>Filters</option>

										</select>
									</div>
								</div>

								<div class="row mt-3 mt-sm-2 align-items-center">
									<div class="col-md-12 text-sm-right">
									<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box early-start"></span> Engaged </span>
									<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box delay"></span> Resource(s) Unavailable</span>
									</div>
								</div>

								<div class="row mt-sm-4 mt-3">
									<div class="col-md-12">
										<div class="table-responsive position-relative">
											<table class="table chart-table   resource-requirementtable">
												<tbody>
													<tr>
														<th>
															<div class="head-innerbox">
																Batching Plant
															</div>
														</th>

														@foreach ($batching_plant['heading'] as $head_time)
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
													
													
													@foreach ($batching_plant['resData'] as $locationKey => $locationValue)
													<tr class = "secound-table">
														<td colspan="4">
															{{$locationKey}}
														</td>
														<td colspan="10">
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
														<td></td>
														<td></td>
														<td></td>
													</tr>
													@foreach ($locationValue as $res)

													<tr>
														<td class = "{{ ($res['batching_plant']) ? '' : 'orange-bgtd' }}">
															<div class="d-flex justify-content-between align-items-center">
																<div>
																	{{$res['batching_plant']}}
																	<span class = "text-muted small">
																		({{$res['capacity']}} m3/hr)
																	</span>
																	&nbsp;
																	<span class = "small">
																	{{round($res['total_batching_qty']/ ($res['total_time'] / 60),2)}} m3/h
																	</span>
																	<br/>
																	<span class = "small">
																	{{intval($res['loading_time']/60)}}:{{intval($res['loading_time']%60)}} hrs
																	</span>
																	&nbsp;
																	<span class = "small">
																	<!-- {{round($res['total_batching_qty']/ ($res['total_time'] / 60),2)}} m3/h -->
																	{{round($res['total_batching_qty'])}} CUM
																	</span>
																	&nbsp;
																	<span class = "small">
																	{{number_format(($res['total_batching_time'] / $res['total_time']) * 100, 0)}} %
																	</span>
																</div>
															</div>
														</td>
														

														@php
														$bSchSlotTimeFlag = false;
														@endphp
														@foreach ($res['resultData'] as $resData)

														@php
															$bSchSlotTime = $resData['slot']['start_time'];
															
															if($bSchSlotTime == '05 AM') {
																$bSchSlotTimeFlag = ($bSchSlotTimeFlag);
															}
															
															

														@endphp
														

														<td colspan="{{isset($resData['colspan']) ? $resData['colspan'] : 1}}">
															

																@if (isset($resData['id']))
																<div class="main-progressbox">
																<div class="progress constructions-chart ml{{$resData['start_minutes']}}">
																
																	@foreach ($resData['multi_pixels'] as $multiPixel)
																		<div class="progress-bar {{$multiPixel['type'] == 'A' ? 'skyblue' : 'gap'}}" data-toggle="tooltip" data-placement="bottom" title="{{ isset($multiPixel['reason']) ? $multiPixel['reason'] : Carbon\Carbon::parse($multiPixel['loading_start']) -> format('h:i:s A') . ' to ' . Carbon\Carbon::parse($multiPixel['loading_end']) -> format('h:i:s A') . ' | ' . $multiPixel['mix'] . ' | ' . $multiPixel['batching_qty'] . ' CUM' . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="margin-left: {{$multiPixel['margin']  . 'px'}}; padding : 0%; min-width : {{$multiPixel['loading_pixels'] ? $multiPixel['loading_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																	@endforeach

																</div>
																</div>

																<div class="stip-bgmainebox">
																	@foreach ($resData['stripe'] as $stripe)
																		<span class="{{$stripe % 2 !== 0 ? 'white-stip' : 'frist-stip' }} @if($bSchSlotTimeFlag) green-stip @endif"></span>
																	@endforeach
																</div>

																@else
																<div class="stip-bgmainebox">
																	<span class="white-stip @if($bSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($bSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($bSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($bSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($bSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($bSchSlotTimeFlag) green-stip @endif"></span>
																</div>
																@endif
														</td>
														@endforeach
													</tr>
													@endforeach
													@endforeach
													<tr class="secound-table schedule-graph-hidden schedule-graph-{{isset($res['order_no']) ? $res['order_no'] : ''}}">
														<td></td>
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
														<td></td>
														<td></td>
														<td></td>
														<td></td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
							<div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
								<div class="row mt-sm-4 mt-3">
									<div class="col-md-4">
										<div class="form-group position-relative">
											<input type="email" class="form-control search-byinpt padding-right"
												placeholder="Search By...">
											<img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
										</div>
									</div>
									<div class="col-md-8 text-sm-right">

										<button type="button" class="btn export-btn mr-2">Export</button>
										<select class="filter-select mr-2 mt-sm-0 mt-3">
											<option>Filters</option>

										</select>
									</div>
								</div>

								<div class="row mt-3 mt-sm-2 align-items-center">
									<div class="col-md-12 text-sm-right">
										<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box early-start"></span> Loading </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box internal"></span> Internal QC </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box travelling"></span> Travelling to Site </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box onsite"></span> Onsite Inspection </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box bg-yellow"></span> Pump Installation </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box bg-secondary"></span> Waiting </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box  pouring"></span> Pouring </span>
															<br>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box cleaning"></span> Cleaning </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box return"></span> Return to Plant </span>
									</div>
								</div>

								<div class="row mt-sm-4 mt-3">
									<div class="col-md-12">
										<div class="table-responsive position-relative">
											<table class="table chart-table   resource-requirementtable">
												<tbody>
													<tr>
														<th>
															<div class="head-innerbox">
																Transit Mixer
															</div>
														</th>

														@foreach ($transit_mixer['heading'] as $head_time)
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
													@foreach ($transit_mixer['resData'] as $locationKey => $locationValue)
													<tr class = "secound-table">
														<td colspan="4">
															{{$locationKey}}
														</td>
														<td colspan="10">
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
														<td></td>
														<td></td>
														<td></td>
													</tr>
													@foreach ($locationValue as $res)
													<tr>
														<td class = "{{ ($res['transit_mixer']) ? '' : 'orange-bgtd' }}">
															<div class="d-flex justify-content-between align-items-center">
																<div>
																	{{$res['transit_mixer']}}
																	<span class = "text-muted small">
																		({{$res['capacity']}} CUM)
																	</span>
																	<br/>
																	<span class = "small">
																	{{intval($res['total_time']/60)}}:{{intval($res['total_time']%60)}} hrs
																	</span>
																	&nbsp;
																	<span class = "small">
																	{{$res['total_batching_qty']}} CUM
																	</span>
																	&nbsp;
																	<span class = "small">
																	{{number_format((($res['total_time'])/$res['total_actual_time'])*100,0)}} %
																	</span>

																</div>
															</div>
														</td>

														@php
														$tSchSlotTimeFlag = false;
														@endphp
														@foreach ($res['resultData'] as $resData)

														@php
															$tSchSlotTime = $resData['slot']['start_time'];
															
															if($tSchSlotTime == '05 AM') {
																$tSchSlotTimeFlag = ($tSchSlotTimeFlag);
															}

														@endphp
														<td colspan="{{isset($resData['colspan']) ? $resData['colspan'] : 1}}">

																@if (isset($resData['id']))
																<div class="main-progressbox">
																<div class="progress constructions-chart ml{{$resData['start_minutes']}}">

																	@foreach ($resData['multi_pixels'] as $multiPixel)
																		<div class="progress-bar skyblue" data-toggle="tooltip" data-placement="bottom" title="{{'Loading | ' . Carbon\Carbon::parse($multiPixel['loading_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['loading_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="margin-left: {{$multiPixel['margin']  . 'px'}}; padding : 0%; min-width  : {{$multiPixel['loading_pixels'] ? $multiPixel['loading_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar pink" data-toggle="tooltip" data-placement="bottom" title="{{'Internal QC | ' . Carbon\Carbon::parse($multiPixel['qc_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['qc_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['qc_pixels'] ? $multiPixel['qc_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar purple" data-toggle="tooltip" data-placement="bottom" title="{{'Travel | ' . Carbon\Carbon::parse($multiPixel['travel_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['travel_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['travel_pixels'] ? $multiPixel['travel_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar dark-green" data-toggle="tooltip" data-placement="bottom" title="{{'Onsite Inspection | ' . Carbon\Carbon::parse($multiPixel['insp_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['insp_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['insp_pixels'] ? $multiPixel['insp_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar dark-blue" data-toggle="tooltip" data-placement="bottom" title="{{'Pouring | ' . Carbon\Carbon::parse($multiPixel['pouring_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['pouring_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['pouring_pixels'] ? $multiPixel['pouring_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar nevy-blue" data-toggle="tooltip" data-placement="bottom" title="{{'Cleaning | ' . Carbon\Carbon::parse($multiPixel['cleaning_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['cleaning_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['cleaning_pixels'] ? $multiPixel['cleaning_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar light-green" data-toggle="tooltip" data-placement="bottom" title="{{'Return | ' . Carbon\Carbon::parse($multiPixel['return_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['return_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['return_pixels'] ? $multiPixel['return_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																	@endforeach

																</div>
																</div>

																<div class="stip-bgmainebox">
																	@foreach ($resData['stripe'] as $stripe)
																		<span class="{{$stripe % 2 !== 0 ? 'white-stip' : 'frist-stip' }} @if($tSchSlotTimeFlag) green-stip @endif"></span>
																	@endforeach
																</div>

																@else
																<div class="stip-bgmainebox">
																	<span class="white-stip @if($tSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($tSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($tSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($tSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($tSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($tSchSlotTimeFlag) green-stip @endif"></span>
																</div>
																@endif
														</td>
														@endforeach
													</tr>
													@endforeach
													@endforeach
													<tr class="secound-table schedule-graph-hidden schedule-graph-{{isset($res['order_no']) ? $res['order_no'] : ''}}">
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
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
							<div class="tab-pane fade" id="pumps" role="tabpanel" aria-labelledby="pumps-tab">
								<div class="row mt-sm-4 mt-3">
									<div class="col-md-4">
										<div class="form-group position-relative">
											<input type="email" class="form-control search-byinpt padding-right"
												placeholder="Search By...">
											<img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
										</div>
									</div>
									<div class="col-md-8 text-sm-right">

										<button type="button" class="btn export-btn mr-2">Export</button>
										<select class="filter-select mr-2 mt-sm-0 mt-3">
											<option>Filters</option>

										</select>
									</div>
								</div>

								<div class="row mt-3 mt-sm-2 align-items-center">
									<div class="col-md-12 text-sm-right">
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box internal"></span> Internal QC </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box travelling"></span> Travelling to Site </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box onsite"></span> Onsite Inspection </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box bg-yellow"></span> Pump Installation </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box bg-secondary"></span> Waiting </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box  pouring"></span> Pouring </span>
															<br>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box cleaning"></span> Cleaning </span>
															<span class="schedule-chartvalue mr-sm-2 mr-1"> <span class="dots-box return"></span> Return to Plant </span>
									</div>
								</div>

								<div class="row mt-sm-4 mt-3">
									<div class="col-md-12">
										<div class="table-responsive position-relative">
											<table class="table chart-table   resource-requirementtable">
												<tbody>
													<tr>
														<th>
															<div class="head-innerbox">
																Pumps
															</div>
														</th>

														@foreach ($pumps['heading'] as $head_time)
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
													@foreach ($pumps['resData'] as $locationKey => $locationValue)
													<tr class = "secound-table">
														<td colspan="4">
															{{$locationKey}}
														</td>
														<td colspan="10">
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
														<td></td>
														<td></td>
														<td></td>
													</tr>
													
													

													
													
													@foreach ($locationValue as $res)

													<tr>
														<td class = "{{ ($res['pump']) ? '' : 'orange-bgtd' }}">
															<div class="d-flex justify-content-between align-items-center">
																<div>
																	{{$res['pump']}}
																	<span class = "text-muted small">
																		({{$res['capacity']}} m)
																	</span>
																	<br/>
																	<span class = "small">
																	{{intval($res['total_time']/60)}}:{{intval($res['total_time']%60)}} hrs
																	</span>
																	&nbsp;
																	<span class = "small">
																	{{$res['total_batching_qty']}} CUM
																	</span>
																	&nbsp;
																	<span class = "small">
																	{{number_format((($res['total_batching_qty'])/$pumps['total_batching_qty'])*100,0)}} %
																	</span>

																</div>
															</div>
														</td>
														@php
														$pSchSlotTimeFlag = false;
														@endphp
														@foreach ($res['resultData'] as $resData)

														@php
															$pSchSlotTime = $resData['slot']['start_time'];
															
															if($pSchSlotTime == '05 AM') {
																$pSchSlotTimeFlag = ($pSchSlotTimeFlag);
															}

														@endphp
														<td colspan="{{isset($resData['colspan']) ? $resData['colspan'] : 1}}">

																@if (isset($resData['id']))
																<div class="main-progressbox">
																<div class="progress constructions-chart ml{{$resData['start_minutes']}}">
																		
																	@foreach ($resData['multi_pixels'] as $multiPixel)
																	
																		<div class="progress-bar pink_pump" data-toggle="tooltip" data-placement="bottom" title="{{'Internal QC | ' . Carbon\Carbon::parse($multiPixel['qc_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['qc_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="margin-left: {{$multiPixel['margin']  . 'px'}} ;padding : 0%; min-width  : {{$multiPixel['qc_pixels'] ? $multiPixel['qc_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar purple" data-toggle="tooltip" data-placement="bottom" title="{{'Travel | ' . Carbon\Carbon::parse($multiPixel['travel_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['travel_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['travel_pixels'] ? $multiPixel['travel_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar dark-green" data-toggle="tooltip" data-placement="bottom" title="{{'Onsite Inspection | ' . Carbon\Carbon::parse($multiPixel['insp_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['insp_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['insp_pixels'] ? $multiPixel['insp_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		
																		<div class="progress-bar yellow" data-toggle="tooltip" data-placement="bottom" title="{{'Pump Install | ' . Carbon\Carbon::parse($multiPixel['install_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['install_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['install_pixels'] ? $multiPixel['install_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar bg-secondary" data-toggle="tooltip" data-placement="bottom" title="{{'Pump Waiting | ' . Carbon\Carbon::parse($multiPixel['waiting_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['waiting_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['waiting_pixels'] ? $multiPixel['waiting_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		
																		
																		
																		<div class="progress-bar dark-blue" data-toggle="tooltip" data-placement="bottom" title="{{'Pouring | ' . Carbon\Carbon::parse($multiPixel['pouring_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['pouring_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['pouring_pixels'] ? $multiPixel['pouring_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar nevy-blue" data-toggle="tooltip" data-placement="bottom" title="{{'Cleaning | ' . Carbon\Carbon::parse($multiPixel['cleaning_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['cleaning_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['cleaning_pixels'] ? $multiPixel['cleaning_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																		<div class="progress-bar light-green" data-toggle="tooltip" data-placement="bottom" title="{{'Return | ' . Carbon\Carbon::parse($multiPixel['return_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['return_end']) -> format('h:i A') . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; min-width  : {{$multiPixel['return_pixels'] ? $multiPixel['return_pixels'] . 'px !important' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
																	@endforeach

																</div>
																</div>

																<div class="stip-bgmainebox">
																	@foreach ($resData['stripe'] as $stripe)
																		<span class="{{$stripe % 2 !== 0 ? 'white-stip' : 'frist-stip' }} @if($pSchSlotTimeFlag) green-stip @endif"></span>
																	@endforeach
																</div>

																@else
																<div class="stip-bgmainebox">
																	<span class="white-stip @if($pSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($pSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($pSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($pSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="white-stip @if($pSchSlotTimeFlag) green-stip @endif"></span>
																	<span class="frist-stip @if($pSchSlotTimeFlag) green-stip @endif"></span>
																</div>
																@endif
														</td>
														@endforeach
													</tr>
													@endforeach
													@endforeach
													<tr class="secound-table schedule-graph-hidden schedule-graph-{{isset($res['order_no']) ? $res['order_no'] : ''}}">
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
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>

						</div>



					</div>

				</div>
			</section>

			<div class="modal fade schedule-modalcontent" id="reschedule-order" tabindex="-1" role="dialog"
			aria-labelledby="exampleModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content" style ="margin-top: 35%; width: 110%;">
					<div class="modal-header border-0">
						<h5 class="modal-title" id="exampleModalLabel"></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<img src="{{asset('assets/img/filter-close.svg')}}" alt=""> close
						</button>
					</div>
					<div class="modal-body">
						<div class="filter-contentbox mb-3">
							<h6>Reschedule Order</h6>
						</div>

						<div class = "row">

						</div>

						<div class = "row">
							<div class="col-md-6 order-sm-2 order-3">
									<div class="profileinput-box form-group position-relative">
										<label class="selext-label">Schedule Date</label>
										<input type="datetime-local" id = "reschedule_order_date" class="form-control user-profileinput" placeholder="Enter Schedule Date">
									</div>
							</div>

							<div class="col-md-3 order-sm-2 order-3">
								<div class="profileinput-box form-group position-relative">
									<label class="selext-label">Interval</label>
									<input type="number" id = "reschedule_order_interval"  class="form-control user-profileinput" placeholder="Enter Interval (mins)">
								</div>
							</div>
							<div class="col-md-3 order-sm-2 order-3">
								<div class="profileinput-box form-group position-relative">
									<label class="selext-label">Deviation %</label>
									<input type="number" id = "reschedule_order_deviation"  class="form-control user-profileinput" placeholder="Enter Interval Deviation (%)" >
								</div>
							</div>
						</div>


						<div class="mt-sm-4 mt-3">
							<button type="button" onclick = "updateSingleOrder();" class="btn apply-btn btn-block">Apply</button>
						</div>
					</div>
				</div>
			</div>
		</div>
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
							<!-- <img src="{{asset('assets/img/dots.gif')}}" alt=""> -->
							<lottie-player src="{{asset('assets/animation_llui8h16.json')}}" background="transparent" speed="1" style="height: 92px;" loop autoplay></lottie-player>
						</div>
						<h6>Generating Schedule!</h6>
						<p>This might take some time!</p>
					</div>
				</div>
			</div>
		</div>
	</div>



	<div class="modal fade filter-modal order-model" id="requirement" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
		aria-hidden="true">
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
						<h6>Resource Requirement</h6>
					</div>

					<div class="row mt-4">
						<div class="col-md-12">
							<div id="accordion" class="requirementaccordion">
								<div class="card">
								  <div class="card-header border-0" id="headingOne">
									<h5 class="mb-0">
									  <button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
										 Transit Mixer
										 (@php
																				$tm_resource_ctr = 0;
																				$tm_resource->each(function ($firstLevelGroup) use (&$tm_resource_ctr) {
																					$firstLevelGroup->each(function ($secondLevelGroup) use (&$tm_resource_ctr) {
																						$tm_resource_ctr += $secondLevelGroup->count();
																					});
																				});
																				echo($tm_resource_ctr);
																				@endphp)
										<i class="fa fa-plus float-right" aria-hidden="true"></i>
										<i class="fa fa-minus float-right" aria-hidden="true"></i>
									  </button>
									</h5>
								  </div>

								  <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
									<div class="card-body">
										<div class="border-bottom mb-3"></div>
										@foreach ($tm_resource as $tm_resource_loc => $tm_resource_val)
											<label class="requirementaccordion-label"> {{$tm_resource_loc}} ({{$tm_resource_val->map->count()->sum()}})</label>
											<div class="row mt-2 mb-3">
											@foreach ($tm_resource_val as $tm_resource_cap => $tm_resource_cap_val)
											<div class="col-md-3 col-6 mb-2 mb-sm-0">
												<div class="resource-requirementcontentbox">
													<div class="d-flex align-items-center justify-content-between">
														<p>{{$tm_resource_cap}} CUM</p>
														<h6>{{count($tm_resource_cap_val)}}</h6>
													</div>
												</div>
											</div>
											@endforeach
										</div>
										@endforeach

									</div>
								  </div>
								</div>

								<div class="card secound-accordcard">
								  <div class="card-header border-0" id="headingTwo">
									<h5 class="mb-0">
									  <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
										Batching Plants (@php
																				$bp_resource_ctr = 0;
																				$bp_resource->each(function ($firstLevelGroup) use (&$bp_resource_ctr) {
																					$firstLevelGroup->each(function ($secondLevelGroup) use (&$bp_resource_ctr) {
																						$bp_resource_ctr += $secondLevelGroup->count();
																					});
																				});
																				echo($bp_resource_ctr);
																				@endphp)
										<i class="fa fa-plus float-right" aria-hidden="true"></i>
										<i class="fa fa-minus float-right" aria-hidden="true"></i>
									  </button>
									</h5>
								  </div>
								  <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
									<div class="card-body">
										<div class="border-bottom mb-3"></div>
										
										@foreach ($bp_resource as $bp_resource_loc => $bp_resource_val)
										<label class="requirementaccordion-label">{{$bp_resource_loc}} ({{$bp_resource_val->map->count()->sum()}})</label>
										<div class="row mt-2 mb-3">
											@foreach ($bp_resource_val as $bp_resource_cap => $bp_resource_cap_val)
											<div class="col-md-3 col-6 mb-2 mb-sm-0">
												<div class="resource-requirementcontentbox">
													<div class="d-flex align-items-center justify-content-between">
														<p>{{$bp_resource_cap}} m3/hr</p>
														<h6>{{count($bp_resource_cap_val)}}</h6>
													</div>
												</div>
											</div>
											@endforeach
										</div>
										@endforeach
									</div>
								  </div>
								</div>

								<div class="card">
								  <div class="card-header border-0" id="headingThree">
									<h5 class="mb-0">
									  <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
										Pumps (@php
																				$p_resource_ctr = 0;
																				$p_resource->each(function ($firstLevelGroup) use (&$p_resource_ctr) {
																					$firstLevelGroup->each(function ($secondLevelGroup) use (&$p_resource_ctr) {
																						$secondLevelGroup->each(function ($thirdLevelGroup) use (&$p_resource_ctr) {
																							$p_resource_ctr += $thirdLevelGroup->count();
																						});
																					});
																				});
																				echo($p_resource_ctr);
																				@endphp)
										<i class="fa fa-plus float-right" aria-hidden="true"></i>
										<i class="fa fa-minus float-right" aria-hidden="true"></i>
									  </button>
									</h5>
								  </div>
								  <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
									<div class="card-body">

										<div class="border-bottom mb-3"></div>
										
										@foreach ($p_resource as $p_resource_loc => $p_resource_val)
											<label class="requirementaccordion-label">{{$p_resource_loc}} (@php
																				$p_resource_val_ctr = 0;
																				$p_resource_val->each(function ($firstLevelGroup) use (&$p_resource_val_ctr) {
																					$firstLevelGroup->each(function ($secondLevelGroup) use (&$p_resource_val_ctr) {
																						$p_resource_val_ctr += $secondLevelGroup->count();
																					});
																				});
																				echo($p_resource_val_ctr);
																				@endphp)</label>
											<div class="white-box">

										@foreach ($p_resource_val as $p_resource_type => $p_resource_type_val)
													<label class="pumps-label">{{$p_resource_type}} ({{$p_resource_type_val->map->count()->sum()}}) </label>
													<div class="row mt-2 mb-3">
														@foreach ($p_resource_type_val as $p_resource_cap => $p_resource_cap_val)
															<div class="col-md-3 col-6 mb-2 mb-sm-0">
																<div class="resource-requirementcontentbox gray">
																	<div class="d-flex align-items-center justify-content-between">
																		<p>{{$p_resource_cap}} m</p>
																		<h6>{{count($p_resource_cap_val)}}</h6>
																	</div>
																</div>
															</div>
														@endforeach
											</div>
												@endforeach
											</div>
										@endforeach
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
	<script>

window.onload = function() {
        var form = document.getElementById('publishOrders');
        form.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent form submission on Enter key press
            }
        });
    };


function confirmPublish() {
    console.log("confirmPublish function called"); // Check if the function is called
    Swal.fire({
        title: "Are you sure?",
        text: "Do you want to publish the orders?",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, publish it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        console.log("SweetAlert response:", result); // Check if this part is reached
        if (result.value) {
            console.log(result);


            document.getElementById("publishOrders").submit();
        }
    });
}






document.addEventListener('DOMContentLoaded', function () {
			setInputsFromQueryParams();
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
                select: function (start) {
                    // Update the hidden input with the selected date
                    selectedDateInput.value = start.startStr;
					selectedDateLabel.innerHTML = moment(start.startStr).format("dddd, D MMMM YYYY");
                }
			});
			calendar.render();
		});

		var selectedOrderId = null;

				function setSelectedOrder(id)
				{
					selectedOrderId = id;
					var element = document.getElementById("progress-bar-" + id);
					document.getElementById("reschedule_order_date").value = element.dataset.deliverydate;
					document.getElementById("reschedule_order_date").min = element.dataset.deliverydate;
					document.getElementById("reschedule_order_interval").value = element.dataset.interval;
					document.getElementById("reschedule_order_deviation").value = element.dataset.deviation;
				}

				function updateSingleOrder()
				{
					$.ajax({
						url: "{{route('orders.single.update')}}",
						method: 'POST',
						data : {
							order_id : selectedOrderId,
							delivery_date : document.getElementById("reschedule_order_date").value,
							interval : document.getElementById("reschedule_order_interval").value,
							interval_deviation : document.getElementById("reschedule_order_deviation").value,
							'_token': '{{ csrf_token() }}'
						},
						success: function (data) {
							$('#reschedule-order').modal('hide')
							$('#schedule-modal').modal('show')
							// API request is complete, hide the modal
							$.ajax({
								url: "{{route('orders.schedule.generate')}}",
								method: 'POST',
								data : {
									company_id : 1,
									schedule_date : getQueryParam('schedule_date'),
									pumps : JSON.parse(localStorage.getItem("pumps")),
									transit_mixers : JSON.parse(localStorage.getItem("transit_mixers")),
									batching_plants : JSON.parse(localStorage.getItem("batching_plants")),
									schedule_preference : localStorage.getItem("schedule_preference"),
									order_wise_interval : true,
									'_token': '{{ csrf_token() }}'
								},

								success: function (data) {

									$('#schedule-modal').modal('hide')

									// API request is complete, hide the modal
									window.location.reload();

									// Process the API response as needed
								},
								error: function (error) {
									// Handle errors if necessary
								}
			});

							// Process the API response as needed
						},
						error: function (error) {
							// Handle errors if necessary
						}
					});
				}

				function getQueryParam(name) {
					var urlParams = new URLSearchParams(window.location.search);
					return urlParams.get(name);
				}

				function redirectResources()
				{
					window.location.href = "{{route('orders.schedule.step.three')}}?schedule_date=" + getQueryParam('schedule_date') + "&company_id=1";
				}

				function redirectOrders()
				{
					window.location.href = "{{route('orders.schedule.step.two')}}?schedule_date=" + getQueryParam('schedule_date')  + "&company_id=1";
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

		function setInputsFromQueryParams() {
            var sch_date = getQueryParam('schedule_date');
			var grp_comp_id = getQueryParam("company_id");
			var int_dev_input = document.getElementById("interval_deviation_input");
			var int_dev_input_reschedule = document.getElementById("reschedule_order_deviation");
			int_dev_input.value = localStorage.getItem("interval_deviation") ? localStorage.getItem("interval_deviation") : 100;
			int_dev_input_reschedule.value = localStorage.getItem("interval_deviation") ? localStorage.getItem("interval_deviation") : 100;

            // Set values to input elements
            // document.getElementById('schedule_date').value = sch_date;
            document.getElementById('schedule_date_label').innerHTML = moment(sch_date).format("dddd, D MMMM YYYY");
            document.getElementById('input_sch_date').value = sch_date;
            document.getElementById('input_gp_cmp_id').value = grp_comp_id;
        }

		function onChangeSchedulePreference()
		{
			var e = document.getElementById("interval_deviation_input");
			$('#schedule-modal').modal('show')

			localStorage.setItem("interval_deviation", e.value);

			$.ajax({
								url: "{{route('orders.schedule.generate')}}",
								method: 'POST',
								data : {
									company_id : 1,
									schedule_date : getQueryParam('schedule_date'),
									pumps : JSON.parse(localStorage.getItem("pumps")),
									transit_mixers : JSON.parse(localStorage.getItem("transit_mixers")),
									batching_plants : JSON.parse(localStorage.getItem("batching_plants")),
									interval_deviation : e.value,
									'_token': '{{ csrf_token() }}'
								},

								success: function (data) {

									$('#schedule-modal').modal('hide')

									// API request is complete, hide the modal
									window.location.reload();

									// Process the API response as needed
								},
								error: function (error) {
									// Handle errors if necessary
									$('#schedule-modal').modal('hide')
								}
			});

		}

		// Add event listener to input field
		document.getElementById("interval_deviation_input").addEventListener("keyup", function(event) {
        // Check if the Enter key is pressed (key code 13)
        if (event.keyCode === 13) {
            // Call your function
            onChangeSchedulePreference();
        }
    });

	function redirectToLiveOrder()
	{
		window.location.href = "{{route('web.order.live.schedule')}}"
	}



</script>
@endsection
