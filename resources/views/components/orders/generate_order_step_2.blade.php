@extends('layouts.auth.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<div class="px-sm-4">
			<div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
				<div class="col-md-3 order-1 order-sm-1 mb-sm-0 mb-3">
					<div class="top-head">
						<h1>Generate Schedule</h1>
						<h6>
							<span class="active">Schedule</span>
							<i class="fa fa-angle-right" aria-hidden="true">
							</i> Select Date
						</h6>
					</div>
				</div>

				@include('partials.order_tabs', ['active' => 'profile'])

				<div class="col-md-3 order-sm-3 order-2 col-3 mb-sm-0 mb-3 text-right">
					<button onclick="window.history.back();" type="button" class="btn back-btn">Back</button>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<form id="updateOrderForm" action="{{ route('orders.selected.update') }}" method="POST">
						@csrf
						<input type="hidden" id="schedule_date_input" name="schedule_date" />
						<input type="hidden" id="company_id_input" name="company_id" />
						<div class="tab-pane fade show active" id="profile">
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
											<input class="form-control" id="schedule_date" type="hidden" />
											<button class="btn calender-btn new-calenderbtn dropdown-toggle" href="#"
												role="button" id="dropdownMenuLink" data-toggle="dropdown"
												aria-haspopup="true" aria-expanded="false" disabled>
												<div id="schedule_date_label">{{date('l, F j, Y')}}</div>
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
										<select class="filter-select mr-2 mt-sm-0 mt-3">
											<option>RMB Readymix Dubai</option>
										</select>
										{{-- <select class="filter-select mr-2 mt-sm-0 mt-3">
											<option>Filters</option>
										</select> --}}
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
															<input type="checkbox" class="filled-in" checked
																id="all-order-check" onclick="onMultiCheckClick();">
															<label class="temperature-label"
																for="all-order-check"></label>
														</div>
													</th>
													<th>Order</th>
													<th>Customer</th>
													<th>Delivery Date</th>
													<th>Time</th>
													<th>Interval (Mins)</th>
													<th>Priority</th>
													<th>Flexibility</th>
													<th>Interval Deviation (%)</th>
													<th>Pouring Time</th>
													<th style="display: none;">Travel To Site (Mins)</th>
													<th style="display: none;">Return to Plant (Mins)</th>
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
												@forelse ($orders as $orderKey => $order)
												<input name="orders[{{$orderKey}}][order_id]" type="hidden"
													value="{{$order['id']}}" />
												<tr>
													<td>
														<div class="filter-check new-filtercheck mt">
															<input name="orders[{{$orderKey}}][selected]"
																type="checkbox" class="filled-in order"
																data-checked="{{$order -> selected}}"
																data-orderid="{{$order -> id}}"
																data-arraykey="{{$orderKey}}"
																data-qty="{{$order-> quantity}}"
																onclick="onCheck({{$order -> id}});" {{$order ->
															selected == 1 ? 'checked' : '' }}
															id="check-order-{{$order -> id}}">
															<label class="temperature-label"
																for="check-order-{{$order -> id}}"></label>
														</div>
													</td>
													<td>{{$order -> order_no}}</td>
													<td>{{$order -> customer_company ?-> contact_person . ' - ' .$order -> customer_company ?-> name }}</td>
													<td>{{Carbon\Carbon::parse($order -> delivery_date) -> format("d F,Y")}}</td>
													<td>
														<input name="orders[{{$orderKey}}][time]" type="time"
															value="{{Carbon\Carbon::parse($order -> delivery_date) -> format('H:i')}}"
															id="time-order-{{$order -> id}}"
															data-arraykey="{{$orderKey}}"
															onchange="onChangeEvent(this.value, 'time-order-', {{$order -> id}}, 'time');" />
													</td>
													<td>
														<input name="orders[{{$orderKey}}][interval]"
															style="width: 50px; text-align: right;" type="number"
															value="{{$order -> interval}}" data-arraykey="{{$orderKey}}"
															id="interval-order-{{$order -> id}}"
															onchange="onChangeEvent(this.value, 'interval-order-', {{$order -> id}}, 'interval');" />
													</td>
													<td>
														<input name="orders[{{$orderKey}}][priority]"
															style="width: 50px; text-align: right;" type="number"
															value="{{$order -> priority}}" data-arraykey="{{$orderKey}}"
															id="priority-order-{{$order -> id}}"
															onchange="onChangeEvent(this.value, 'priority-order-', {{$order -> id}}, 'priority');" />
													</td>
													<td>
														<input name="orders[{{$orderKey}}][flexibility]"
															style="width: 50px; text-align: right;" type="number"
															value="{{$order -> flexibility}}" data-arraykey="{{$orderKey}}"
															id="flexibility-order-{{$order -> id}}"
															onchange="onChangeEvent(this.value, 'flexibility-order-', {{$order -> id}}, 'flexibility');" />
													</td>
													<td>
														<input name="orders[{{$orderKey}}][interval_deviation]"
															style="width: 50px; text-align: right;" type="number"
															value="{{$order -> interval_deviation}}"
															min = "0"
															max = "500"
															id="priority-interval-deviation-{{$order -> id}}"
															data-arraykey="{{$orderKey}}"
															onchange="onChangeEvent(this.value, 'priority-interval-deviation-', {{$order -> id}}, 'priority');" />
													</td>
													<td>
														<input name="orders[{{$orderKey}}][pouring_time]"
															style="width: 50px; text-align: right;" type="number"
															value="{{$order -> pouring_time}}" data-arraykey="{{$orderKey}}"
															id="pouring_time-order-{{$order -> id}}"
															onchange="onChangeEvent(this.value, 'pouring_time-order-', {{$order -> id}}, 'pouring_time');" />
													</td>
													<td style="display: none;">
														<input name="orders[{{$orderKey}}][travel_to_site]"
															style="width: 50px; text-align: right;" type="number"
															value="{{$order -> travel_to_site}}"
															id="travel-order-{{$order -> id}}"
															data-arraykey="{{$orderKey}}"
															onchange="onChangeEvent(this.value, 'travel-order-', {{$order -> id}}, 'travel_to_site');" />
													</td>
													<td style="display: none;">
														<input name="orders[{{$orderKey}}][return_to_plant]"
															style="width: 50px; text-align: right;" type="number"
															value="{{$order -> return_to_plant}}"
															id="return-order-{{$order -> id}}"
															data-arraykey="{{$orderKey}}"
															onchange="onChangeEvent(this.value, 'return-order-', {{$order -> id}}, 'return_to_plant');" />
													</td>
													<td>{{$order -> project}}</td>
													<td>{{$order -> site}}</td>
													<td>{{$order -> mix_code}}</td>
													<td>{{$order -> mix_code}}</td>
													<td>{{$order -> quantity}} Cum</td>
													<td>{{$order -> structural_reference_details ?-> name}}</td>
													<td class="gray-text">{{$order -> is_techinician_required ? 'Yes' : 'No'}}</td>
													<td>{{$order -> order_cube_mould_display()}}</td>
													<td>{{$order -> order_temp_control_display()}}</td>
													<td>{{$order -> order_pumps_display()}}</td>
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

								<div style="margin-left:30%; margin-right:25%; margin-top:2%" class="col-md-5">
									<div class = "row">
										<div class="col mt-3">
											<label>Interval Deviation</label>
										</div>

										<div class="col">
											<div class="form-group position-relative">
												<input type="number" name = "interval_deviation" class="form-control search-byinpt padding-right"
													id = "interval_deviation_input" type ="number" min = "0" max = "500" value="100"
													style = "width: 100%;">
												<img src="{{asset('assets/img/percentage_icon.png')}}" class="fill-percentageimg" alt="">
											</div>
										</div>
										<div class="col md-3">
											<label>Total Selected Quantity:</label>
										</div>

										<div class="col">
											<span class="form-control search-byinpt padding-right" id="totalQty">0</span>
										</div>
									</div>
									
								</div>


							</div>
							<div class="row mt-sm-5 mt-4 justify-content-center">
								<div class="col-md-3 col-8">
									<button type="button" onclick="updateSelectedOrders();"
										class="btn apply-btn btn-block">Continue</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>

<script>

	var orders = [];
	var request_orders = [];
	orders.forEach(element => {
		request_orders.push({
			order_id: element.id,
			selected: element.selected
		});
	});
	var selectedDateInput = document.getElementById("schedule_date");
	var selectedDateLabel = document.getElementById("schedule_date_label");

	function initalize_orders() {
		orders = @json($orders);
		request_orders = [];
		orders.forEach(element => {
			request_orders.push({
				order_id: element.id,
				selected: element.selected,
				priority: element.priority == 9999 ? null : element.priority,
				interval: element.interval,
				travel_to_site: element.travel_to_site,
				return_to_plant: element.return_to_plant,
				time: moment(element.delivery_date).format("HH:mm")
			});
		});
	}

	function onChangeEvent(val, element, order_id, key)
	{
		var ele = document.getElementById(element + order_id);
		idx = ele.dataset.arraykey;
		request_orders[idx][key] = val;
	}

	const checkboxes = document.querySelectorAll('.order');
    const totalQtyEl = document.getElementById('totalQty');
    calculateTotal();

    function calculateTotal() {

        let total = 0;
        
        checkboxes.forEach(cb => {
            if (cb.checked) {

                total += parseInt(cb.dataset.qty, 10);
            }
        });
        totalQtyEl.textContent = total;
    }

	function onCheck(order_id)
	{
		var ele = document.getElementById("check-order-" + order_id);
		idx = ele.dataset.arraykey;
		request_orders[idx].selected = request_orders[idx].selected == 0 ? 1 : 0;
		calculateTotal();
	}

	function onMultiCheckClick()
	{
		var multi_check_ele = document.getElementById("all-order-check");
		if (multi_check_ele.checked) {
			request_orders.forEach((obj) => {
				return obj.selected = 1;
			});
			var all_elements = document.getElementsByClassName("filled-in");
			for (var i = 0; i < all_elements.length; i++) {
				all_elements[i].checked = true;
			}
		} else {
			request_orders.forEach((obj) => {
				return obj.selected = 0;
			});
			var all_elements = document.getElementsByClassName("filled-in");
			for (var i = 0; i < all_elements.length; i++) {
				all_elements[i].checked = false;
			}
		}
		calculateTotal();
	}

	document.addEventListener('DOMContentLoaded', function () {

		initalize_orders();
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

	function updateSelectedOrders()
	{
		localStorage.setItem("interval_deviation", document.getElementById("interval_deviation_input").value);
		// Get form action and add custom JSON data
		var form = document.getElementById('updateOrderForm');
		// Submit the form
		
		form.submit();
	}

	function getQueryParam(name) {
		var urlParams = new URLSearchParams(window.location.search);
		return urlParams.get(name);
	}

	function setInputsFromQueryParams() {
		var sch_date = getQueryParam('schedule_date');
		var comp_id = getQueryParam('company_id');
		var int_dev_input = document.getElementById("interval_deviation_input");
		int_dev_input.value = localStorage.getItem("interval_deviation") ? localStorage.getItem("interval_deviation") : 100;
		// Set values to input elements
		document.getElementById('schedule_date').value = sch_date;
		document.getElementById('schedule_date_input').value = sch_date;
		document.getElementById('company_id_input').value = comp_id;
		document.getElementById('schedule_date_label').innerHTML = moment(sch_date).format("dddd, D MMMM YYYY");
	}
</script>
@endsection
