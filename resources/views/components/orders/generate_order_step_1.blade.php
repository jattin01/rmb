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
											<button type="submit" class="btn apply-btn btn-block">Continue</button>
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

<script>

	var selectedDateInput = document.getElementById("schedule_date");
	var selectedDateLabel = document.getElementById("schedule_date_label");

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
			select: function (start) {
				if (start.str != selectedDateInput.value) {
					var dropdown_ele = document.getElementById("calendar-drop-down");
					dropdown_ele.classList.remove("show");
				}
				// Update the hidden input with the selected date
				selectedDateInput.value = start.startStr;
				selectedDateLabel.innerHTML = moment(start.startStr).format("dddd, D MMMM YYYY");
				document.getElementById('schedule_date_input').value = start.startStr;
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
        });
    });
</script>
@endsection