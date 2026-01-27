@extends('layouts.auth.app')
@section('content')
<section class="content">
				<div class="container-fluid">
					

					<div class="px-sm-4">
						<div class="row mt-0 mt-sm-3 align-items-center">
							<div class="col-md-7 mb-sm-0 mb-2">
								<div class="top-head">
									<h1>Schedule</h1>
									<p>Batching Plants</p>
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
														Batching Plant
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
											@foreach ($result['resData'] as $locationKey => $locationValue)
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
															<br/>
															<span class = "small">
															{{intval($res['loading_time']/60)}}:{{intval($res['loading_time']%60)}} hrs
															</span>
															&nbsp;
															<span class = "small">
															{{$res['total_batching_qty']}} CUM
															</span>
															&nbsp;
															<span class = "small">
															{{number_format((($res['total_batching_qty'])/($res['capacity'] * 24))*100, 2)}} %
															</span>
														</div>
													</div>
												</td>
                                                @foreach ($res['resultData'] as $resData)
                                                <td colspan="{{isset($resData['colspan']) ? $resData['colspan'] : 1}}">
													
                                                        @if (isset($resData['id']))
                                                        <div class="main-progressbox">
                                                        <div class="progress multi-progress ml{{$resData['start_minutes']}}">

															@foreach ($resData['multi_pixels'] as $multiPixel)
																<div class="progress-bar multi-firstblue ml{{$multiPixel['margin']}}" data-toggle="tooltip" data-placement="bottom" title="{{ Carbon\Carbon::parse($multiPixel['loading_start']) -> format('h:i A') . ' to ' . Carbon\Carbon::parse($multiPixel['loading_end']) -> format('h:i A') . ' | ' . $multiPixel['mix'] . ' | ' . $multiPixel['batching_qty'] . ' CUM' . ' | Order - '. $multiPixel['order_no']}}" role="progressbar" style="padding : 0%; width  : {{$multiPixel['loading_pixels'] ? $multiPixel['loading_pixels'] . 'px' : '0%'}}" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
															@endforeach
															
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
                                            @endforeach
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