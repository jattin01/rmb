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
				@include('partials.order_tabs', ['active' => 'home'])
				<div class="col-md-3 order-sm-3 order-2 col-3 mb-sm-0 mb-3 text-right">
					<button onclick="window.history.back();" type="button" class="btn back-btn">Back</button>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="tab-pane fade show active" id="home">
						<div class="row justify-content-center mt-sm-5 mt-3">
							<div class="col-md-7">
								<form action="{{ route('orders.reset') }}" method="POST">
									@csrf
								<div class="row">
									<div class="col-md-6 mb-sm-0 mb-3">
										<div class="dropdown show calender-box">
											<input class="form-control" id="schedule_date" type="hidden" />
											<button class="btn calender-btn new-calenderbtn dropdown-toggle" href="#"
												role="button" id="dropdownMenuLink" data-toggle="dropdown"
												aria-haspopup="true" aria-expanded="false">
												<label class="selext-label mb-0">Select Date</label>
												<br>
												<div id="schedule_date_label">{{date('l, F j, Y')}}</div>
												<small id="schedule_date_orders" class="schedule-date-orders"></small>
												<span class="calender-img new-calenderimg">
													<img src="{{asset('assets/img/calender-img.svg')}}" alt="">
												</span>
											</button>
											<div class="dropdown-menu" id="calendar-drop-down"
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
											<select id = "company_dropdown" class="form-control select-contentbox" name = "company_id">
												@foreach ($groupCompanies as $groupCompany)
													<option value = "{{$groupCompany -> value}}">{{$groupCompany -> label}}</option>
												@endforeach
											</select>
										</div>
									</div>
								</div>
								<div class="middle-grilimg text-center mt-sm-5 mt-3">
									<img src="{{asset('assets/img/girl-calender.svg')}}" alt="">
								</div>
								<!--<div class="schedule-generatedtext text-center mt-sm-5 mt-3">
										<h4>Schedule Already Generated!</h4>
										<h6>Back</h6>
									</div>-->
									<input type="hidden" id="schedule_date_input" name="schedule_date" />
									<div class="row mt-sm-5 mt-4 justify-content-center">
										<div class="col-md-5 col-8">
											<button type="submit" id="continue_btn" class="btn apply-btn btn-block">Continue</button>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<style>
	/* Days without orders can't be picked */
	#calendar .fc-daygrid-day.no-orders { opacity: 0.3; cursor: not-allowed; }
	#calendar .fc-daygrid-day.has-orders { cursor: pointer; }
	/* Badge floats over the cell so rows keep their height (no scroll bar) */
	#calendar .fc-daygrid-day-frame { position: relative; }
	#calendar .order-count {
		position: absolute; left: 50%; bottom: 1px; transform: translateX(-50%);
		padding: 0 4px; min-width: 14px; pointer-events: none;
		border-radius: 7px; background: #10a37f; color: #fff;
		font-size: 8px; line-height: 12px; text-align: center;
	}
	.schedule-date-orders {
		display: block; max-width: 100%; overflow: hidden; text-overflow: ellipsis;
		white-space: nowrap; color: #10a37f; font-size: 11px;
	}
	.schedule-date-orders.empty { color: #dc3545; }
</style>

<script>

	var selectedDateInput = document.getElementById("schedule_date");
	var selectedDateLabel = document.getElementById("schedule_date_label");
	var selectedDateOrders = document.getElementById("schedule_date_orders");
	var continueBtn = document.getElementById("continue_btn");

	// date (YYYY-MM-DD) => [order_no, ...] for the dates the calendar shows.
	var orderDates = {};
	var orderDatesLoaded = false;
	var orderDatesRange = null;

	function ordersOn(date) {
		return orderDates[date] || [];
	}

	// Count badge + hover list on days with orders, greyed out days without.
	function markCalendarDays() {
		document.querySelectorAll('#calendar .fc-daygrid-day[data-date]').forEach(function (cell) {
			var orders = ordersOn(cell.getAttribute('data-date'));
			var badge = cell.querySelector('.order-count');
			if (badge) badge.remove();

			cell.classList.toggle('has-orders', orders.length > 0);
			cell.classList.toggle('no-orders', orderDatesLoaded && orders.length === 0);
			cell.title = orders.length ? orders.length + ' order(s): ' + orders.join(', ') : (orderDatesLoaded ? 'No orders' : '');

			if (orders.length) {
				badge = document.createElement('span');
				badge.className = 'order-count';
				badge.textContent = orders.length;
				(cell.querySelector('.fc-daygrid-day-frame') || cell).appendChild(badge);
			}
		});
	}

	// Order numbers under the selected date; Continue only when there are orders.
	function showSelectedDateOrders() {
		if (!orderDatesLoaded) return;
		var orders = ordersOn(selectedDateInput.value);
		selectedDateOrders.classList.toggle('empty', orders.length === 0);
		selectedDateOrders.textContent = orders.length
			? orders.length + ' order(s): ' + orders.join(', ')
			: 'No orders on this date';
		selectedDateOrders.title = selectedDateOrders.textContent;
		continueBtn.disabled = orders.length === 0;
	}

	function loadOrderDates() {
		if (!orderDatesRange) return;
		orderDatesLoaded = false;
		$.get("{{ route('orders.schedule.step.one.dates') }}", {
			company_id: $('#company_dropdown').val(),
			start: orderDatesRange.start,
			end: orderDatesRange.end
		}).done(function (res) {
			orderDates = res.dates || {};
			orderDatesLoaded = true;
		}).fail(function () {
			// Can't tell which dates have orders: leave every date selectable.
			orderDates = {};
			orderDatesLoaded = false;
			selectedDateOrders.textContent = '';
			continueBtn.disabled = false;
		}).always(function () {
			markCalendarDays();
			showSelectedDateOrders();
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		// setInputsFromQueryParams();
		selectedDateInput.value = moment().format("YYYY-MM-DD");
		document.getElementById('schedule_date_input').value = moment().format("YYYY-MM-DD");
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
			// Load the order dates for every month shown
			datesSet: function (info) {
				orderDatesRange = {
					start: moment(info.start).format("YYYY-MM-DD"),
					end: moment(info.end).format("YYYY-MM-DD")
				};
				markCalendarDays();
				loadOrderDates();
			},
			// Only dates that have orders can be picked
			selectAllow: function (info) {
				return !orderDatesLoaded || ordersOn(info.startStr).length > 0;
			},
			select: function (start) {
				if (start.str != selectedDateInput.value) {
					var dropdown_ele = document.getElementById("calendar-drop-down");
					dropdown_ele.classList.remove("show");
				}
				// Update the hidden input with the selected date
				selectedDateInput.value = start.startStr;
				selectedDateLabel.innerHTML = moment(start.startStr).format("dddd, D MMMM YYYY");
				document.getElementById('schedule_date_input').value = start.startStr;
				showSelectedDateOrders();
			}
		});
		calendar.render();
		var dropdown_ele = document.getElementById("calendar-drop-down");
		// Prevent the dropdown menu from closing when clicking inside it
		dropdown_ele.addEventListener("click", function (event) {
			// Stop the event propagation to prevent it from bubbling up to the document level
			event.stopPropagation();
		});
	});

	function getQueryParam(name) {
		var urlParams = new URLSearchParams(window.location.search);
		return urlParams.get(name);
	}

	$(document).ready(function() {
        $('#company_dropdown').select2({
            placeholder: 'Select Company'
        }).on('change', loadOrderDates);
    });
</script>
@endsection