@extends('layouts.auth.app')
@section('content')
<section class="content">
				<div class="container-fluid">
					

					<div class="px-sm-4">
						<div class="row mt-0 mt-sm-3 align-items-center">
						<div class="col-md-3 mb-sm-0 mb-2">
								<div class="top-head">
									<h1>Schedule</h1>
									Overview
								</div>
							</div>

							<div class="col-md-5 order-sm-2 order-3">

								<ul class="nav nav-tabs resources-texttab" id="myTab" role="tablist">
									<li class="nav-item">
										<a class="nav-link active" id="home-tab" data-toggle="tab" href="#home"
											role="tab" aria-controls="home" aria-selected="true"> <img
												src="img/date.svg" alt=""> Date</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile"
											role="tab" aria-controls="profile" aria-selected="false">
											<img src="img/dark-menu.svg" alt="">
											<!-- <i class="fa fa-sliders" aria-hidden="true"></i> -->
											Orders</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact"
											role="tab" aria-controls="contact" aria-selected="false"><img
												src="img/dark-dash.svg" alt=""> Resources</a>
									</li>
								</ul>

							</div>
							
						</div>

						<div class="tab-content" id="myTabContent">
							<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
								<div class="row justify-content-center mt-sm-5 mt-3">
									<div class="col-md-7">
										<div class="row">
											<div class="col-md-6 mb-sm-0 mb-3">
												<div class="dropdown show calender-box">
														
													<input class = "form-control" id= "schedule_date" type = "hidden"/>

													<button
														class="btn calender-btn new-calenderbtn dropdown-toggle"
														href="#" role="button" id="dropdownMenuLink"
														data-toggle="dropdown" aria-haspopup="true"
														aria-expanded="false">
														<label class="selext-label mb-0">Select Date</label>
														<br>
														<div id = "schedule_date_label">{{date('l, F j, Y')}}</div> <span
															class="calender-img new-calenderimg"><img
																src="{{asset('assets/img/calender-img.svg')}}" alt=""></span>
													</button>

													<div class="dropdown-menu"
														aria-labelledby="dropdownMenuLink">
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
											<div class="col-md-6">
												<div class="profileinput-box form-group position-relative">
													<label class="selext-label">Select Company</label>
													<select class="form-control select-contentbox">
														<option>RMB Readymix Dubai</option>
													</select>
												</div>
											</div>
										</div>

										<div class="middle-grilimg text-center mt-sm-5 mt-3">
											<img src="{{asset('assets/img/girl-calender.svg')}}" alt="">
										</div>

										<div class="schedule-generatedtext text-center mt-sm-5 mt-3">
											<h4>Schedule Already Generated!</h4>
											<h6>Back</h6>
										</div>

										<div class="row mt-sm-5 mt-4 justify-content-center">
											<div class="col-md-5 col-8">
											<button onclick ="generate_schedule_step_1();" class="btn apply-btn btn-block"
											>Continue</button>
											</div>
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
											<img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
										</div>
									</div>
									<div class="col-md-8 text-sm-right">
										
										<div class="d-sm-flex d-block justify-content-end">
											<div class="dropdown show calender-box mr-2">
												<input class = "form-control" id= "schedule_date" type = "hidden"/>
												<button
														class="btn calender-btn new-calenderbtn dropdown-toggle"
														href="#" role="button" id="dropdownMenuLink"
														data-toggle="dropdown" aria-haspopup="true"
														aria-expanded="false">

														<div id = "schedule_date_label">{{date('l, F j, Y')}}</div>
													</button>

													<div class="dropdown-menu"
														aria-labelledby="dropdownMenuLink">
														<div class="row">
															<div class="col-md-12">
																<div class="calendar-drop">
																	<div id="calendar"></div>
																</div>
															</div>
														</div>
													</div>

												
											</div>
											<select class="filter-select mr-2 mt-sm-0 mt-3">
												<option>RMB Readymix Dubai</option>
												
											</select>
											<select class="filter-select mr-2 mt-sm-0 mt-3">
												<option>Filters</option>
												<option>2</option>
												<option>3</option>
												<option>4</option>
												<option>5</option>
											</select>
										</div>
									</div>
								</div>
								<div class="row mt-3 mt-sm-2 align-items-center">
									

								</div>

								<div class="row mt-sm-4 mt-3">
									<div class="col-md-12">
										<div class="table-responsive">
											<table class="table order-table schedule-table">
												<thead>
													<tr>
														<th>
															<div class="filter-check new-filtercheck mt-sm-4">
																<input type="checkbox" class="filled-in" checked disabled
																	id="exampleCheck3">
																<label class="temperature-label"
																	for="exampleCheck3"></label>
															</div>
														</th>
														<th>Order</th>
														<th>Customer</th>
														<th>Delivery Date</th>
														<th>Time</th>
														<th>Interval</th>
														<th>Project</th>
														<th>Site Location</th>
														<th>Mix</th>
														<th>Mix Code</th>
														<th>Qty.</th>
														<th>Structure</th>
														<th>Technical</th>
														<th>Cube Mould</th>
														<th>Temp Control</th>
														<th>Pumps</th>
													</tr>
												</thead>
												<tbody>

													@forelse ($orders as $order)
													<tr>
														<td>
															<div class="filter-check new-filtercheck mt-sm-4">
																<input type="checkbox" class="filled-in" checked
																	id="check-order-{{$order -> id}}">
																<label class="temperature-label"
																	for="check-order-{{$order -> id}}"></label>
															</div>
														</td>
														<td>{{$order -> order_no}}</td>
														<td>{{$order -> customer}}</td>
														<td>{{Carbon\Carbon::parse($order -> delivery_date) -> format('Y-m-d')}}</td>
														<td>{{Carbon\Carbon::parse($order -> delivery_date) -> format('h:i A')}}</td>
														<td>{{$order -> interval}} Min</td>
														<td>{{$order -> project}}</td>
														<td>{{$order -> site}}</td>
														<td>{{$order -> mix_code}}</td>
														<td>{{$order -> mix_code}}</td>
														<td>{{$order -> quantity}} Cum</td>
														<td>Raft</td>
														<td class="gray-text">yes</td>
														<td>24</td>
														<td>30 Degree</td>
														<td>{{$order -> pump}} m</td>
													</tr>
													@empty
													<tr>
														<td colspan="16">
															<p>
																<center class="text-danger">No records found
																</center>
															</p>
														</td>
													</tr>
													@endforelse
													
												</tbody>
											</table>
										</div>
									</div>
									
								</div>
							</div>
							<div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
								<div class="readymix-contentbox mt-sm-5 mt-3">
									<div class="row align-items-center">
										<div class="col-md-6 mb-2 mb-sm-0">
											<h6>RMB Readymix Dubai</h6>
										</div>
										<div class="col-md-6 text-sm-right">
											<p id = "schedule_date_label"></p>
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
																		$batching_plants->each(function ($firstLevelGroup) use (&$totalCount) {
																			$firstLevelGroup->each(function ($secondLevelGroup) use (&$totalCount) {
																				$totalCount += $secondLevelGroup->count();
																			});
																		});
																		echo($totalCount);
																		@endphp)
																	
																</div>
																<!-- <div class="col-md-6 col-5 pl-0 text-right">
																	<span class="selected-btn mr-2">06
																		Selected</span>
																	<i class="fa fa-plus float-right"
																		aria-hidden="true"></i>
																	<i class="fa fa-minus float-right"
																		aria-hidden="true"></i>
																</div> -->
															</div>
														</button>
													</h5>
												</div>

												<div id="collapseOne" class="collapse show"
													aria-labelledby="headingOne" data-parent="#accordion">
													<div class="card-body pt-0">
														<div class="border-top"></div>
														@foreach ($batching_plants as $bp_key => $bp_val)
														<div class="row mt-sm-4 mt-3 align-items-center">
															<div class="col-md-6 col-6">
																<div class="filter-check new-filtercheck">
																	<input type="checkbox" checked class="filled-in"
																		id="exampleCheck25">
																	<label class="selected-label"
																		for="exampleCheck25">{{$bp_key}}
																		({{$bp_val->map->count()->sum()}})
																	</label>
																</div>
															</div>
															<!-- <div class="col-md-5 col-6 text-right">
																<span class="selected-btn">06 Selected</span>
															</div> -->
														</div>
														

														<div class="readymix-contentbox mt-sm-3 mt-2">
															<div class="row">
																@foreach ($bp_val as $sub_bp_key => $sub_bp_val)
																<div class="col-md-4 mb-2 mb-sm-0">
																	<div class="row">
																		<div class="col-md-8 col-7">
																			<div class="hours-box">
																				<h3>{{$sub_bp_key}} m3/hr</h3>
																			</div>
																		</div>
																		<div class="col-md-4 col-5">
																			<div
																				class="input-group select-quntitybox">
																				<div
																					class="input-group-prepend">
																					<span class=""><i
																							class="fa fa-minus" onclick="remove_value('bp-input-{{$bp_key}}-{{$sub_bp_key}}')"
																							aria-hidden="true"></i></span>
																				</div>
																				<input type="number"
																				data-capacity="{{$sub_bp_key}}"
																				data-location="{{$sub_bp_val[0] -> company_location_id}}"
																					class="form-control input-quntitybox batching_input"
																					value="{{count($sub_bp_val)}}" id ="bp-input-{{$bp_key}}-{{$sub_bp_key}}" min = "0" max = "{{count($sub_bp_val)}}">
																				<div class="input-group-append">
																					<span class=""><i
																							onclick="add_value('bp-input-{{$bp_key}}-{{$sub_bp_key}}')"
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
														<button class="btn btn-link collapsed"
															data-toggle="collapse" data-target="#collapseTwo"
															aria-expanded="false" aria-controls="collapseTwo">
															<div class="row align-items-center">
																<div class="col-md-6 col-7">Transit Mixer ({{$transit_mixers->map->count()->sum()}})
																</div>
																<!-- <div class="col-md-6 col-5 pl-0 text-right">
																	<span class="selected-btn mr-2">0
																		Selected</span>
																	<i class="fa fa-plus float-right"
																		aria-hidden="true"></i>
																	<i class="fa fa-minus float-right"
																		aria-hidden="true"></i>
																</div> -->
															</div>
														</button>
													</h5>
												</div>
												<div id="collapseTwo" class="collapse"
													aria-labelledby="headingTwo" data-parent="#accordion">
													<div class="card-body pt-0">
														<div class="border-top"></div>
														<div class="readymix-contentbox mt-sm-4 mt-2">
															
															<div class="row">
																@foreach ($transit_mixers as $tm_key => $tm_val)
																<div class="col-md-4">
																	<div class="row">
																		<div class=	"col-md-8 col-7">
																			<div class="hours-box">
																				<h3>{{$tm_key}} CUM</h3>
																			</div>
																		</div>
																		<div class="col-md-4 col-5">
																			<div
																				class="input-group select-quntitybox">
																				<div
																					class="input-group-prepend">
																					<span class=""><i
																							onclick="remove_value('bp-input-{{$tm_key}}-{{$tm_val}}')"
																							class="fa fa-minus"
																							aria-hidden="true"></i></span>
																				</div>
																				<input type="number"
																				data-capacity="{{$tm_key}}"
																					class="form-control input-quntitybox truck_input"
																					value="{{count($tm_val)}}" id ="bp-input-{{$tm_key}}-{{$tm_val}}" min = "0" max = "{{count($tm_val)}}">
																				<div class="input-group-append">
																					<span class=""><i
																						onclick="add_value('bp-input-{{$bp_key}}-{{$sub_bp_key}}')"
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
														<button class="btn btn-link collapsed"
															data-toggle="collapse" data-target="#collapseThree"
															aria-expanded="false" aria-controls="collapseThree">
															<div class="row align-items-center">
																<div class="col-md-6 col-7">Pumps 
																	(@php
																		$pumpTotalCount = 0;
																		$pumps->each(function ($firstLevelGroup) use (&$pumpTotalCount) {
																			$firstLevelGroup->each(function ($secondLevelGroup) use (&$pumpTotalCount) {
																				$pumpTotalCount += $secondLevelGroup->count();
																			});
																		});
																		echo($pumpTotalCount);
																		@endphp)
																</div>
																<!-- <div class="col-md-6 col-5 pl-0 text-right">
																	<span class="selected-btn mr-2">0
																		Selected</span>
																	<i class="fa fa-plus float-right"
																		aria-hidden="true"></i>
																	<i class="fa fa-minus float-right"
																		aria-hidden="true"></i>
																</div> -->
															</div>
														</button>
													</h5>
												</div>
												<div id="collapseThree" class="collapse"
													aria-labelledby="headingThree" data-parent="#accordion">
													<div class="card-body pt-0">
														<div class="border-top"></div>
														@foreach ($pumps as $pump_key => $pump_val)
														<div class="row mt-sm-4 mt-3 align-items-center">
															<div class="col-md-6 col-6">
																<div class="pumps-check">
																	<input type="checkbox" checked class="filled-in"
																		id="exampleCheck30">
																	<label class="selected-label"
																		for="exampleCheck30">{{$pump_key}}</label>
																</div>
															</div>
															<!-- <div class="col-md-5 col-6 text-right">
																<span class="selected-btn">06 Selected</span>
															</div> -->
														</div>

														<div class="readymix-contentbox mt-sm-3 mt-2">
															<div class="row">
														@foreach ($pump_val as $sub_pump_key => $sub_pump_val)
														<div class="col-md-4 mb-2 mb-sm-0">
																	<div class="row">
																		<div class="col-md-8 col-7">
																			<div class="hours-box">
																				<h3>{{$sub_pump_key}} m</h3>
																			</div>
																		</div>
																		<div class="col-md-4 col-5">
																			<div
																				class="input-group select-quntitybox">
																				<div
																					class="input-group-prepend">
																					<span class=""><i
																							onclick="remove_value('bp-input-{{$pump_key}}-{{$sub_pump_key}}')"
																							class="fa fa-minus"
																							aria-hidden="true"></i></span>
																				</div>
																				<input type="number"
																				data-capacity="{{$sub_pump_key}}"
																					class="form-control input-quntitybox pump_input"
																					value="{{count($sub_pump_val)}}" id ="bp-input-{{$pump_key}}-{{$sub_pump_key}}" min = "0" max = "{{count($sub_pump_val)}}">
																				<div class="input-group-append">
																					<span class=""><i
																							onclick="add_value('bp-input-{{$pump_key}}-{{$sub_pump_key}}')"
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
									<button class="btn apply-btn btn-block" onclick = "generate_schedule();">Continue</button>
									</div>
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

			<div class="modal fade filter-modal" id="reschedule-order" tabindex="-1" role="dialog"
			aria-labelledby="exampleModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header border-0">
						<h5 class="modal-title" id="exampleModalLabel"></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<img src="{asset('assets/img/filter-close.svg)}}" alt=""> close
						</button>
					</div>
					<div class="modal-body">
						<div class="filter-contentbox">
							<h6>Reschedule Order</h6>
						</div>

						<div class="row pt-sm-4 pt-3">
							<div class="col-md-12">
								<input type = "number" id = "reschedule_order_priority" placeholder = "Enter Priority"/>
							</div>
						</div>

						<div class="row pt-sm-4 pt-3">
							<div class="col-md-12">
								<input type = "datetime-local" id = "reschedule_order_date"/>
							</div>
						</div>
	
						<div class="mt-sm-4 mt-3">
							<button type="button" onclick = "updateSingleOrder();" class="btn apply-btn btn-block">Apply</button>
						</div>
					</div>
				</div>
			</div>
		</div>
			<script>

		var selectedDateInput = document.getElementById("schedule_date");
		var selectedDateLabel = document.getElementById("schedule_date_label");

document.addEventListener('DOMContentLoaded', function () {
			// setInputsFromQueryParams();
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
					selectedDateLabel.innerHTML = start.startStr;
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
					document.getElementById("reschedule_order_priority").value = (element.dataset.priority != 9999 ? element.dataset.priority : null);
				}

				function updateSingleOrder() 
				{
					$.ajax({
						url: "{{route('orders.single.update')}}",
						method: 'POST',
						data : {
							order_id : selectedOrderId,
							delivery_date : document.getElementById("reschedule_order_date").value,
							priority : document.getElementById("reschedule_order_priority").value ? document.getElementById("reschedule_order_priority").value : 9999 ,
						},
						success: function (data) {
							// API request is complete, hide the modal
							window.location.href = "{{route('schedule_match')}}?schedule_date=" + getQueryParam('schedule_date');

							$('#reschedule-order').modal('hide')
			
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

				function generate_schedule_step_1()
				{
					window.location.href = "{{route('generate_schedule_view')}}?schedule_date=" + selectedDateInput.value;
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

		function setInputsFromQueryParams() {
            var sch_date = getQueryParam('schedule_date');

            // Set values to input elements
            // document.getElementById('schedule_date').value = sch_date;
            document.getElementById('schedule_date_label').innerHTML = sch_date;
        }
</script>
@endsection