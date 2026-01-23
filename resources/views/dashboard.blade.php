@extends('layouts.auth.app')
@section('content')
<!-- <div class="content-wrapper"> -->
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center">
                    <div class="col-md-9 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Dashboard</h1>
                            <p>Overview</p>
                        </div>
                    </div>

                </div>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card-head">
                                            {{-- <h1>Order Status (Today)</h1> --}}

                                            <div class="col-md-7 col-7 pl-sm-0">
                                                <select class="form-control today-select" name = "order_filter" onchange="rebuildOrderGraph(this);">
                                                    <option value = "today">Today</option>
                                                    <option value = "one_month" selected>one month</option>

                                                </select>
                                            </div>
                                            {{-- <p>Total {{$totalOrdersCount}} Orderd</p> --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="row justify-content-center mt-4">
                                    <div class="col-md-10 col-10">
                                        <div class="chart-box">
                                            <div id="area-chartnew"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4 mb-sm-4">
                                    <div class="col-md-4 col-4">
                                        <div class="card-head">
                                            <h6><span class="dots-box" ></span> <span id="pendingOrdersCount"> {{$pendingOrdersCount}}</span> </h6>
                                            <p>Pending</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-4">
                                        <div class="card-head">
                                            <h6><span class="dots-box inprogress"  ></span> <span id="inprograces"> {{$inprograces}}</span> </h6>
                                            <p>In Progress</p>
                                        </div>


                                    </div>
                                    <div class="col-md-4 col-4">
                                        <div class="card-head">
                                            <h6><span class="dots-box delivered" ></span><span id="delivered"> {{$delivered}}</span> </h6>
                                            <p>Delivered</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 col-6">
                                        <div class="card-head">
                                            <h1>Order Trends</h1>
                                            <h4>12PM-02PM</h4>
                                            <p>Peak TIme</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-6">
                                        <div class="form-group">
                                            <select class="form-control today-select" onchange="OrderTrendGraph(this);">
                                                <option value = "today" selected>Today</option>
                                                {{-- <option value = "Yesterday">Yesterday</option>
                                                <option value = "seven_days">07 Days</option>
                                                <option value = "one_month">01 Month</option>
                                                <option value = "six_month">06 Months</option> --}}
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="chart-box">
                                            <canvas id="countChart" height="320px" ></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="order-completionbg">
                            <div class="order-completioncontentbox">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 col-6">
                                            <h6>Order <br /> Completion</h6>
                                        </div>
                                        <div class="col-md-4 col-6">
                                            <h3 class="newcard-head">(Today)</h3>
                                        </div>
                                    </div>
                                    <?php
                                        $liveOrderCount = (count($total_live_orders) > 0) ?(count($total_live_orders) ):1;
                                    ?>
                                    <div class="row mt-sm-4 align-items-center">
                                        <div class="col-md-6 mb-sm-0 mb-3">
                                            <div class="order-completionchartvalue">
                                                <h4>
                                                    <span class="dots-box delivered"></span>
                                                    <span class="order-value">{{ number_format(count($live_on_time) / $liveOrderCount * 100, 2) }}%</span> On Time
                                                </h4>
                                                <h4>
                                                    <span class="dots-box purple"></span>
                                                    <span class="order-value purple">{{ number_format(count($live_delayed) / $liveOrderCount * 100, 2) }}%</span> Delay
                                                </h4>
                                                <h4>
                                                    <span class="dots-box  inprogress"></span>
                                                    <span class="order-value yellow">{{ number_format(count($live_pending) / $liveOrderCount * 100, 2) }}%</span> Hold
                                                </h4>
                                            </div>

                                        </div>
                                        <div class="col-md-6">
                                                {{-- <canvas id="zoneChart"  height="50px" ></canvas> --}}
                                                <canvas id="myChart" width="400" height="400"></canvas>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- <div class="card mt-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card-head">
                                            <h1>Order Cancellation</h1>
                                        </div>

                                        <div class="d-flex cancellation-boxmain mt-3">
                                            <div class="cancellation-box">
                                                <div class="bottom-shadowimg">
                                                    <img src="{{asset('assets/img/order-shadowimg.svg')}}" alt="">
                                                    <h6>12<span>%</span></h6>
                                                </div>
                                                <p>06 m</p>
                                            </div>
                                            <div class="cancellation-box">
                                                <div class="bottom-shadowimg">
                                                    <img src="{{asset('assets/img/order-shadowimg.svg')}}" alt="">
                                                    <h6>12<span>%</span></h6>
                                                </div>
                                                <p>06 m</p>
                                            </div>
                                            <div class="cancellation-box">
                                                <div class="bottom-shadowimg">
                                                    <img src="{{asset('assets/img/order-shadowimg.svg')}}" alt="">
                                                    <h6>12<span>%</span></h6>
                                                </div>
                                                <p>06 m</p>
                                            </div>
                                            <div class="cancellation-box">
                                                <div class="bottom-shadowimg">
                                                    <img src="{{asset('assets/img/order-shadowimg.svg')}}" alt="">
                                                    <h6>12<span>%</span></h6>
                                                </div>
                                                <p>06 m</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-5 mb-sm-0 mb-3">
                                        <div class="card-head">
                                            <h1>Order Volume</h1>
                                            <h4 id="totalOrderId">{{$orderCount}} CUM

                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="order-volumebox">
                                            <div class="row align-items-center">
                                                <div class="col-md-5 col-5 pr-0">
                                                {{-- <div class="last-daystext">Last 30 Days</div> --}}
                                                </div>
                                                <div class="col-md-7 col-7 pl-sm-0">
                                                    <select class="form-control today-select" name = "order_volume_filter" onchange="rebuildOrderVolumeGraph(this);">
                                                        <option value = "today" selected>Today</option>
                                                        <option value = "Yesterday">Yesterday</option>
                                                        <option value = "seven_days">07 Days</option>
                                                        <option value = "one_month">01 Month</option>
                                                        <option value = "six_month">06 Months</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>


                                <div class="row mt-4 justify-content-center">
                                    <div class="col-md-12">
                                        <div class="chart-box">
                                            <canvas id="countChart2" height="90px" ></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3 mb-sm-0">
                                        <div class="card-head">
                                            <h1>Resource Utilizations</h1>
                                            <h4>98% Utilization <i class="fa fa-circle" aria-hidden="true"></i>
                                                24th July, 2023</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-5 col-12 mb-2 mb-sm-0">
                                        <ul class="nav nav-tabs plants-tab justify-content-sm-end" id="myTab"
                                            role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" id="home-tab" data-toggle="tab"
                                                    href="#home" role="tab" aria-controls="home"
                                                    aria-selected="true">Batching plants</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="profile-tab" data-toggle="tab"
                                                    href="#profile" role="tab" aria-controls="profile"
                                                    aria-selected="false">Transit MIxer</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="contact-tab" data-toggle="tab"
                                                    href="#contact" role="tab" aria-controls="contact"
                                                    aria-selected="false">Pumps</a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-3 col-10">
                                        <select class="form-control today-select">
                                            <option>Select Batching Plant</option>
                                            <option>Tomorrow</option>
                                            <option>07 Days</option>
                                            <option>01 Month</option>
                                            <option>06 Months</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="tab-content" id="myTabContent">
                                            <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="chart-box">
                                                            {{-- <canvas id="myChartbar" height="120px"></canvas> --}}
                                                                <canvas id="myChartbar" height="120"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="profile" role="tabpanel"
                                                aria-labelledby="profile-tab">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="chart-box">
                                                            {{-- <canvas id="myChartbar2" height="120px"></canvas> --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="contact" role="tabpanel"
                                                aria-labelledby="contact-tab">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="chart-box">
                                                            {{-- <canvas id="myChartbar3" height="125px"></canvas> --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row justify-content-center mt-3">
                                    <div class="col-md-5">
                                        <div class="row">
                                            <div class="col-md-7 mb-2 mb-sm-0">
                                                <ul class="nav nav-tabs plants-tab justify-content-sm-end" id="myTab"
                                            role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" id="daily-tab" data-toggle="tab"
                                                    href="#daily" role="tab" aria-controls="daily"
                                                    aria-selected="true">Daily</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="Monthly-tab" data-toggle="tab"
                                                    href="#Monthly" role="tab" aria-controls="Monthly"
                                                    aria-selected="false">Monthly</a>
                                            </li>

                                        </ul>
                                            </div>
                                            <div class="col-md-5 col-9">
                                                <div class="tab-content" id="myTabContent">
                                                    <div class="tab-pane fade show active" id="daily" role="tabpanel" aria-labelledby="daily-tab">
                                                        <select class="form-control today-select">
                                                            <option>16 Sept, 2023</option>
                                                            <option>Tomorrow</option>
                                                            <option>07 Days</option>
                                                            <option>01 Month</option>
                                                            <option>06 Months</option>
                                                        </select>
                                                    </div>
                                                    <div class="tab-pane fade" id="Monthly" role="tabpanel"
                                                        aria-labelledby="Monthly-tab">
                                                        <select class="form-control today-select">
                                                            <option>July, 2023</option>
                                                            <option>Tomorrow</option>
                                                            <option>07 Days</option>
                                                            <option>01 Month</option>
                                                            <option>06 Months</option>
                                                        </select>
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
<!-- </div> -->
@endsection

@section('scripts')
<script>

    //first-chart

    var options = {
            series: [{{$delivered}}, {{$inprograces}}, {{$pendingOrdersCount}}],
            chart: {
                // width: 380,
                height: 380,
                type: 'polarArea'
            },
            labels: ['Delivered', 'In Progress', 'Pending'],
            fill: {
                opacity: 1
            },
            stroke: {
                width: 1,
                colors: undefined
            },
            yaxis: {
                show: false
            },
            legend: {
                position: 'bottom',
                show: false
            },
            plotOptions: {
                polarArea: {
                    rings: {
                        strokeWidth: 0
                    },
                    spokes: {
                        strokeWidth: 0
                    },
                }
            },
            colors: ['#00CF9E', '#F9C128', '#775DA6'],

        };

    var areaChart = new ApexCharts(document.querySelector("#area-chartnew"), options);
    areaChart.render();


	// scound


	var m = document.getElementById('countChart').getContext('2d');
    var actualEndTimes = @json($actualEndTimes);
    var deliveredQuantity = @json($deliveredQuantity);
        var countChart = new Chart(m, {
            type: 'line',
            data: {
                labels: actualEndTimes,
                datasets: [{
                    label: '',

                    data: deliveredQuantity,
                    fill: false,
                    borderColor: '#000000',
                    backgroundColor: "#fff",
                    pointHoverBackgroundColor: '#E84D88',

                    pointHoverBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointHoverBorderWidth: 3,

                    pointRadius: 4,
                    pointHoverRadius: 8,

                    tension: 0.1
                }]

            },
            options: {

                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });



	// Third

	var m = document.getElementById('countChart2').getContext('2d');
    var orderVolume = @json($orderVolume);
    var orderVolumeDate = @json($orderVolumeDate);
        var countChart2 = new Chart(m, {
            type: 'line',
            data: {
                labels: orderVolumeDate,
                datasets: [{
                    label: '',
                    data: orderVolume,
                    fill: true,
                    backgroundColor: '#f3e6ff7d',

                    pointBackgroundColor: '#FFF',
                    borderColor: '#000000',

                    pointHoverBackgroundColor: '#E84D88',

                    pointHoverBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointHoverBorderWidth: 3,

                    pointRadius: 4,
                    pointHoverRadius: 8,
                    tension: 0.1
                }]

            },
            options: {

                plugins: {
                    legend: {
                        display: false
                    }
                }
            }

        });


//fourth




const ctx = document.getElementById('myChartbar').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['11 Jul', '12 Jul', '13 Jul', '14 Jul', '15 Jul', '16 Jul', '17 Jul', '18 Jul', '19 Jul', '20 Jul', '21 Jul', '22 Jul', '23 Jul', '24 Jul', '25 Jul', '26 Jul', '27 Jul', '28 Jul', '29 Jul', '30 Jul', '31 Jul', '01 Aug', '02 Aug', '03 Aug', '04 Aug', '04 Aug', '06 Aug', '07 Aug', '08 Aug', '09 Aug', '10 Aug'],
                datasets: [{
                    data: [12, 19, 3, 5, 2, 3, 80, 23, 34, 45, 67, 34, 56, 23, 34, 25, 26, 35, 34, 23, 26, 27, 48, 58, 24, 25, 26, 25, 24, 50, 20],
                    borderWidth: 1,
                    borderRadius: 4,
                    backgroundColor: "#00CF9E",
                    borderColor: "#00CF9E",
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        enabled: true,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                hover: {
                    onHover: (event, chartElement) => {
                        if (chartElement.length) {
                            const datasetIndex = chartElement[0].datasetIndex;
                            const index = chartElement[0].index;

                            myChart.data.datasets[datasetIndex].backgroundColor = myChart.data.datasets[datasetIndex].data.map((_, i) =>
                                i === index ? "#008F6E" : "#00CF9E"
                            );
                            myChart.update();
                        }
                    }
                },
            },
            plugins: [{
                id: 'hoverEffect',
                afterDatasetsDraw: (chart) => {
                    const { ctx } = chart;
                    const meta = chart.getDatasetMeta(0);
                    meta.data.forEach((bar, index) => {
                        if (bar.active) {
                            const barX = bar.x;
                            const barY = bar.y;

                            ctx.save();
                            const dotWidth = 13;
                            const dotHeight = 13;
                            const dotBorderWidth = 1;
                            const bottomSpace = 8;

                            ctx.lineWidth = dotBorderWidth;
                            ctx.strokeStyle = "#fff";

                            ctx.fillStyle = "#E84D88";
                            ctx.beginPath();
                            ctx.ellipse(barX, barY - bottomSpace - dotHeight / 2, dotWidth / 2, dotHeight / 2, 0, 0, Math.PI * 2); // Adjust for width and height
                            ctx.fill();
                            ctx.stroke();
                            ctx.restore();

                            ctx.shadowColor = "#0000001A";
                            ctx.shadowBlur = 40;
                            ctx.shadowOffsetX = 0;
                            ctx.shadowOffsetY = 5;

                            ctx.save();
                            ctx.setLineDash([5, 5]);
                            ctx.lineWidth = 1;
                            ctx.strokeStyle = "#000";

                            const borderPadding = 4;
                            const borderRadius = 5;

                            const x = barX - bar.width / 2 - borderPadding;
                            const y = barY - borderPadding;
                            const width = bar.width + borderPadding * 2;
                            const height = bar.base - barY + borderPadding * 2;

                            ctx.beginPath();
                            ctx.moveTo(x + borderRadius, y);
                            ctx.arcTo(x, y, x, y + borderRadius, borderRadius);
                            ctx.lineTo(x, y + height);
                            ctx.lineTo(x + width, y + height);
                            ctx.lineTo(x + width, y + borderRadius);
                            ctx.arcTo(x + width, y, x + width - borderRadius, y, borderRadius);
                            ctx.closePath();
                            ctx.stroke();

                            ctx.restore();

                        }
                    });
                }
            }]
        });



        const chartCtx = document.getElementById('myChart').getContext('2d'); // Renamed to 'chartCtx'

        const data = {
            // labels: ['On Time', 'Hold', 'Delay' ],
            labels: ['On Time', 'Delay', 'Hold',],

            datasets: [
                {
                    // data: [180, 65, 60],

                    data: ["{{count($live_on_time)}}", "{{count($live_delayed)}}", "{{count($live_pending)}}"],
                    backgroundColor: ['#00CF9E', '#775DA6', '#F9C128',],
                    borderWidth: 0,
                },
            ],
        };

        const config = {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
            plugins: [
                {
                    id: 'customBorders',
                    afterDatasetDraw(chart) {
                        const ctx = chart.ctx;
                        const meta = chart.getDatasetMeta(0);
                        const element = meta.data[1];

                        if (element) {
                            const { startAngle, endAngle, outerRadius, innerRadius } = element;
                            const centerX = chart.width / 2;
                            const centerY = chart.height / 2;


                            ctx.save();
                            ctx.strokeStyle = '#14171F';
                            ctx.lineWidth = 14;
                            ctx.beginPath();
                            ctx.arc(centerX, centerY, outerRadius, startAngle, endAngle);
                            ctx.stroke();

                            ctx.beginPath();
                            ctx.arc(centerX, centerY, innerRadius, startAngle, endAngle);
                            ctx.stroke();
                            ctx.restore();
                        }
                    },
                },
            ],
        };

        new Chart(chartCtx, config);


        function rebuildOrderVolumeGraph(element) {
            var type = element.value;
            $.ajax({
                url: "{{route('dashboard.orderVolume.graph')}}" + "?type=" + type,
                method: "GET",
                dataType: "json",
                success: function (response) {
                    console.log(response);
                    if (response.data.orderVolume && response.data.orderVolumeDate) {

                        document.getElementById('totalOrderId').innerHTML=response.data.orderCount + ' CUM'
                        if (countChart2) {
                            countChart2.destroy();
                        }
                        countChart2 = new Chart(m, {
                            type: 'line',
                            data: {
                                labels:response.data.orderVolumeDate,
                                datasets: [{
                                    label: '',
                                    data:response.data.orderVolume,
                                    fill: false,
                                    borderColor: '#000000',
                                    backgroundColor: "#fff",
                                    tension: 0.1
                                }]

                            },
                            options: {

                                plugins: {
                                    legend: {
                                    display: false
                                    }
                                }
                            }

                        });

                    } else {
                        //error
                    }
                },
            });
        }
//
        function rebuildOrderGraph(element)
        {
            var type = element.value;
            $.ajax({
                url: "{{route('dashboard.order.graph')}}" + "?type=" + type,
                method: "GET",
                dataType: "json",
                success: function (response) {

                    $('#pendingOrdersCount').html(response.data.pendingOrdersCount.toString());
                    $('#inprograces').html(response.data.inprograces.toString());
                    $('#delivered').html(response.data.delivered.toString());


                    areaChart.updateSeries([
                        response.data.delivered.toString(),
                        response.data.inprograces.toString(),
                        response.data.pendingOrdersCount.toString(),
                    ])
                },
            });
        }



        function OrderTrendGraph(element) {
            var type = element.value;
            $.ajax({
                url: "{{route('dashboard.orderTrends.graph')}}" + "?type=" + type,
                method: "GET",
                dataType: "json",
                success: function (response) {
                    console.log(response);
                    if (response.data.deliveredQuantity && response.data.actualEndTimes) {

                        document.getElementById('totalOrderId').innerHTML=response.data.orderCount + ' CUM'
                        if (countChart2) {
                            countChart2.destroy();
                        }
                        countChart2 = new Chart(m, {
                            type: 'line',
                            data: {
                                labels:response.data.actualEndTimes,
                                datasets: [{
                                    label: '',
                                    data:response.data.deliveredQuantity,
                                    fill: false,
                                    borderColor: '#000000',
                                    backgroundColor: "#fff",
                                    tension: 0.1
                                }]

                            },
                            options: {

                                plugins: {
                                    legend: {
                                    display: false
                                    }
                                }
                            }

                        });

                    } else {
                        //error
                    }
                },
            });
        }

</script>
@endsection
