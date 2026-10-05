@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-3 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Schedule Orders</h1>
                            <p>Overview</p>
                        </div>
                    </div>
                </div>
                <div class="row mt-sm-4 mt-2">
                    <div class="col-md-4">
                        <form class="search-form" role="search" method="GET">
                            <div class="form-group position-relative">
                                <input type="text" name="search" value="{{ @$search }}"
                                    class="form-control search-byinpt padding-right" placeholder="Search By..."
                                    onchange="this.form.submit()">
                                <img src="{{ asset('assets/img/fill-search.svg') }}" class="fill-serchimg" alt="">
                            </div>
                            {{-- keep active filters when searching --}}
                            <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                            <input type="hidden" name="project_id" value="{{ request('project_id') }}">
                            <input type="hidden" name="site_id" value="{{ request('site_id') }}">
                            <input type="hidden" name="delivery_date" value="{{ request('delivery_date') }}">
                            <input type="hidden" name="interval_from" value="{{ request('interval_from') }}">
                            <input type="hidden" name="interval_to" value="{{ request('interval_to') }}">

                        </form>

                    </div>
                    <div class="col-md-8 mb-sm-0 mb-2 text-sm-right">

                        <div class="d-flex justify-content-end">

                    <form   method="GET">
                            {{-- keep the current search term when applying filters --}}
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <div class="dropdown drop-mainbox">
                                <button class="btn filter-boxbtn dropdown-toggle" type="button" id="dropdownMenuButton"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Filters
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <label class="fliter-label">Filters</label>

                                    <div class="select-box form-group" >
                                        <label class="selext-label">By customer</label>
                                        <select class="form-control select-contentbox" id="customers_dropdown" name="customer_id"  onchange = "changeDropdownOptions(this, ['projects_dropdown'], ['customer_projects'] , '/customer-projects/get/', null, ['projects_dropdown', 'projects_sites_dropdown', 'mix_codes_dropdown'])">
                                            <option value="">Select customer</option>
                                            @forelse ($customers as $customer)
                                                <option value = "{{ $customer->value }}"> {{ $customer->label }}
                                                </option>


                                            @empty
                                            @endforelse
                                        </select>
                                    </div>

                                    <div class="select-box form-group">
                                        <label class="selext-label">By project</label>
                                        <select class="form-control select-contentbox" id = "projects_dropdown" name = "project_id" onchange = "changeDropdownOptions(this, ['projects_sites_dropdown', 'mix_codes_dropdown'], ['project_sites', 'mix_codes'] , '/project-sites/get/', null, ['projects_sites_dropdown'])">
                                            <option value="">Select project</option>
                                            </select>
                                        </select>
                                    </div>

                                    <div class="select-box form-group">
                                        <label class="selext-label">Site location</label>
                                        <select class="form-control select-contentbox"  id = "projects_sites_dropdown" name = "site_id" onchange = "siteOnChange(this)">
                                            <option value="">Select site location</option>

                                        </select>
                                    </div>

                                    <div class="select-box form-group">
                                        <label class="selext-label">Company name</label>
                                        <select class="form-control select-contentbox" id = 'group_company_dropdown'>
                                            <option value="">Select company</option>
                                            @forelse ($groupCompanies as $groupCompany)
                                            <option value = "{{$groupCompany -> value}}"> {{$groupCompany -> label}}</option>
                                        @empty
                                        @endforelse
                                        </select>
                                    </div>

                                    <div class="select-box form-group">
                                        <label class="selext-label">Delivery date</label>
                                        {{-- <select class="form-control select-contentbox"> --}}
                                            {{-- <option>Select date</option> --}}
                                            <input type="date" name = "delivery_date" class="form-control user-profileinput">

                                        {{-- </select> --}}
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="profileinput-box drop form-group ">
                                                <label class="selext-label">Interval</label>
                                                <input type="number" name = "interval_from" class="form-control user-profileinput" placeholder="from">

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="profileinput-box drop form-group ">
                                                <label class="selext-label">Interval</label>
                                                <input type="number" name = "interval_to" class="form-control user-profileinput" placeholder="to">

                                            </div>
                                        </div>
                                    </div>

                                    <div class="row align-items-center mt-3">
                                        <div class="col-md-6 col-4">
                                            <a class="reset-text"
                                                                href="{{ url()->current() }}">Reset
                                                                </a>
                                        </div>
                                            <div class="col-md-6 col-8 text-right">
                                                <button type="button" class="btn apply-btnnew "  onclick="this.form.submit()">Apply now</button>
                                            </div>


                                    </div>
                                </div>
                            </div>

                        </form>
                        </div>


                    </div>
                </div>
                <form hidden class="form" method="POST" id = "import_orders" action="{{ route('orders.import') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="d-flex flex-row-reverse">
                        <button type="submit" onclick="importOrders();" class="btn export-btn">Import</button>
                        <input type="file" class="form-control @error('excel_file') is-invalid @enderror"
                            style="width:30%" placeholder="Enter Package Name" name="excel_file" id="excel_file"
                            accept=".xlsx, .xls">
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <input type="hidden" class="form-control" name="group_company_id" value = "1">
                    </div>
                </form>

                <div class="row">

                    <div class="col-md-12" hidden>
                        <ul class="nav nav-tabs plants-tab" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link" id="all-tab" onclick="reloadOrder()" data-toggle="tab" href="/orders-overview" role="tab" aria-controls="home" aria-selected="true">All</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="published-tab" onclick="publishedOrder()" data-toggle="tab" href="/orders-overview?type=published" role="tab" aria-controls="profile" aria-selected="false">Published</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="pending-tab" onclick="pendingOrder()" data-toggle="tab" href="/orders-overview?type=pending" role="tab" aria-controls="contact" aria-selected="false">Pending</a>
                            </li>
                        </ul>
                    </div>

                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table order-table">
                                <thead>
                                    <tr>
                                        <th style = "min-width:10rem;">Delivery Date</th>
                                        <th style = "min-width:8rem;">Total Qty.</th>
                                         <th style = "min-width:8rem;">Total Orders.</th>
                                        <th style = "min-width:14rem;">Order Nos</th>
                                        <th style = "min-width:10rem;">Used Plants</th>
                                        <th style = "min-width:10rem;">Used Pumps</th>
                                        <th style = "background-color: #f1f1f1; min-width:5rem">Schedule Status</th>
                                        <th style = "background-color: #f1f1f1;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($orders as $order)
@php
    $rowDate = \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d');
@endphp
<tr data-date="{{ $rowDate }}" data-company="{{ $order->group_company_id }}"
    data-status="{{ $order->schedule_status }}">
  <td>{{ \Carbon\Carbon::parse($order->delivery_date)->format('d-m-Y') }}</td>

    <td class="js-qty-cell">{{ $order->total_quantity }}</td>

    <td class="js-orders-cell">{{ $order->total_orders }}</td>

    <td class="js-ordernos-cell">{{ $order->order_nos }}</td>

    <td class="js-plants-cell">{{ $order->used_plants }}</td>

    <td class="js-pumps-cell">{{ $order->used_pumps }}</td>

    <td class="js-status-cell">
        @switch($order->schedule_status)
            @case('completed')
                <span class="badge badge-success">Completed</span>
                @break

            @case('processing')
                <span class="badge badge-warning">Processing</span>
                @break

            @case('queued')
                <span class="badge badge-secondary">Queued</span>
                @break

            @case('failed')
                <span class="badge badge-danger">Failed</span>
                @break

            @default
                <span class="badge badge-secondary">Pending</span>
        @endswitch
    </td>

    <td class="js-action-cell">
   @if($order->schedule_status === 'completed')
    <a
        href="{{ route('orders.schedule.view') }}?schedule_date={{ $rowDate }}&company_id={{ $order->group_company_id }}"
        class="btn btn-sm btn-primary">
        View Schedule
    </a>
    @elseif($order->schedule_status === 'processing')
        <span class="badge badge-warning">Processing</span>
    @elseif($order->schedule_status === 'queued')
        <span class="badge badge-secondary">Queued</span>
    @elseif($order->schedule_status === 'failed')
        <span class="badge badge-danger">Failed</span>
    @else
        <span class="badge badge-secondary">Pending</span>
    @endif
</td>
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
                {{-- Pagination --}}
                {{ $orders->links('partials.pagination') }}
                {{-- Pagination End --}}
            </div>
        </div>
    </section>

    <div class="overlay" id="overlay">
        <!-- Loader -->
        <div class="loader"></div>
    </div>

    <script>
        // Function to show the loader and overlay
        function showLoader() {
            document.getElementById('overlay').style.display = 'block';
        }

        // Function to hide the loader and overlay
        function hideLoader() {
            document.getElementById('overlay').style.display = 'none';
        }

        function importOrders() {
            showLoader();
        }

        function redirectToCreateOrderPage() {
            window.location.href = "{{ route('order.create.new') }}"
        }

        function redirectToOrder(orderId) {
            window.location.href = "{{ url('edit/order/') }}" + "/" + orderId;
        }


        $('.dropdown-menu').on('click', function(event) {
            event.stopPropagation();
        });


        function siteOnChange(element)
    {
        document.getElementById('group_company_dropdown').value = "";
        document.getElementById('company_location_dropdown').value = "";
        const selectedOption = element.options[element.selectedIndex];
        fetch("project-sites/get/details/" + selectedOption.value, {
            method : "GET",
            headers : {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
        }).then(response => response.json()).then(data => {
            const response = data.data;
            if (response.site) {
                document.getElementById('group_company_dropdown').value = response.site.service_group_company_id;
                document.getElementById('company_location_dropdown').value = response.site.company_location_id;
                changeDropdownOptions(document.getElementById('group_company_dropdown'), ['struct_ref_dropdown', {type : 'class', value :'temp_dropdown'}, {type : 'class', value :'pump_type_dropdown'}, {type : 'class', value :'pump_size_dropdown'}], ['structural_references', 'temps', 'pump_types', 'pump_sizes'] , '/get/order-creation-data/', 'reset_from_company');
            }
        }).catch(error => {
            console.log("Error : ", error);
        })
    }


    //  start for active new class{

    function setActiveTab(tabId) {
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.classList.remove('activenew');
        });

        document.getElementById(tabId).classList.add('activenew');
    }

    function reloadOrder() {
        setActiveTab('all-tab');
        window.location.href = "{{ route('orders.overview') }}";
    }

    function publishedOrder() {
        setActiveTab('published-tab');
        window.location.href = "{{ route('orders.overview', ['type' => 'published']) }}";
    }

    function pendingOrder() {
        setActiveTab('pending-tab');
        window.location.href = "{{ route('orders.overview', ['type' => 'pending']) }}";
    }

    document.addEventListener('DOMContentLoaded', function () {
        const currentUrl = window.location.href;

        if (currentUrl.includes('type=published')) {
            setActiveTab('published-tab');
        } else if (currentUrl.includes('type=pending')) {
            setActiveTab('pending-tab');
        } else {
            setActiveTab('all-tab');
        }
    });
    function viewSchedule(date, company_id) {
    window.location.href =
        "{{ route('orders.schedule.view') }}" +
        "?schedule_date=" + encodeURIComponent(date) +
        "&company_id=" + encodeURIComponent(company_id);
}

    // end active class}


    /* ============================================================
       Live schedule status: poll -> toastr -> update table row
       ============================================================ */

    // Endpoints (make sure these route names exist in web.php)
    var STATUS_URL       = "{{ route('orders.schedule.status') }}";
    var OVERVIEW_ROW_URL = "{{ route('orders.schedule.overview.row') }}";
    var SCHEDULE_VIEW_URL = "{{ route('orders.schedule.view') }}";
    var LIVE_LOGS_URL    = "{{ route('logs.live') }}";

    // Keeps track of dates we're already polling so we never double-poll one row
    var activePolls = {};

    // Processing badge with the engine's progress (schedule_runs.progress).
    function processingHtml(progress) {
        if (progress === undefined || progress === null) {
            return '<span class="badge badge-warning">Processing</span>';
        }
        var pct = Math.max(0, Math.min(100, parseInt(progress, 10) || 0));
        return '<span class="badge badge-warning">Processing ' + pct + '%</span>'
            + '<div class="progress mt-1" style="height:6px;min-width:90px;">'
            + '<div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" '
            + 'role="progressbar" style="width:' + pct + '%;" aria-valuenow="' + pct + '" '
            + 'aria-valuemin="0" aria-valuemax="100"></div></div>';
    }

    function statusBadgeHtml(status, progress) {
        switch (status) {
            case 'completed':  return '<span class="badge badge-success">Completed</span>';
            case 'processing': return processingHtml(progress);
            case 'queued':     return '<span class="badge badge-secondary">Queued</span>';
            case 'failed':     return '<span class="badge badge-danger">Failed</span>';
            default:           return '<span class="badge badge-secondary">Pending</span>';
        }
    }

    function actionCellHtml(status, scheduleDate, companyId) {
        if (status === 'completed') {
            var url = SCHEDULE_VIEW_URL
                + '?schedule_date=' + encodeURIComponent(scheduleDate)
                + '&company_id=' + encodeURIComponent(companyId);
            return '<a href="' + url + '" class="btn btn-sm btn-primary">View Schedule</a>';
        }
        if (status === 'processing') {
            return '<a href="' + LIVE_LOGS_URL + '" target="_blank" class="btn btn-sm btn-outline-secondary">Live logs</a>';
        }
        return statusBadgeHtml(status);
    }

    function applyRowStatus($tr, status, progress) {
        var scheduleDate = $tr.data('date');
        var companyId    = $tr.data('company');
        $tr.attr('data-status', status);
        $tr.find('.js-status-cell').html(statusBadgeHtml(status, progress));
        $tr.find('.js-action-cell').html(actionCellHtml(status, scheduleDate, companyId));
    }

    function notify(type, message) {
        if (window.toastr) {
            toastr[type](message);
        } else {
            // Fallback so you still see something if toastr isn't loaded
          //  alert(message);
        }
    }

    // Pull fresh aggregated numbers for a row (qty / total orders / status)
    function refreshOverviewRow(companyId, scheduleDate) {
        $.get(OVERVIEW_ROW_URL, { company_id: companyId, schedule_date: scheduleDate })
            .done(function (row) {
                var $tr = $('tr[data-date="' + scheduleDate + '"][data-company="' + companyId + '"]');
                if (!$tr.length) return;
                $tr.find('.js-qty-cell').text(row.total_quantity);
                $tr.find('.js-orders-cell').text(row.total_orders);
                $tr.find('.js-ordernos-cell').text(row.order_nos || '');
                $tr.find('.js-plants-cell').text(row.used_plants || '');
                $tr.find('.js-pumps-cell').text(row.used_pumps || '');
                applyRowStatus($tr, row.schedule_status);
            });
    }

    function pollScheduleStatus(companyId, scheduleDate, runId) {
        var key = companyId + '|' + scheduleDate;
        if (activePolls[key]) return;   // already polling this row

        var $tr = $('tr[data-date="' + scheduleDate + '"][data-company="' + companyId + '"]');
        if ($tr.length) applyRowStatus($tr, 'processing');

        function check() {
            var params = { company_id: companyId, schedule_date: scheduleDate };
            if (runId) params.run_id = runId;

            $.get(STATUS_URL, params).done(function (res) {
                if (res.status === 'processing' || res.status === 'queued') {
                    if ($tr.length) {
                        applyRowStatus($tr, res.status, res.progress);
                        showRunStatus($tr, res.status === 'processing' ? res.message : null);
                    }
                    return;
                }
                if ($tr.length) showRunStatus($tr, null);
                if (res.status === 'completed') {
                    clearInterval(activePolls[key]);
                    delete activePolls[key];
                    refreshOverviewRow(companyId, scheduleDate);
                    notify('success', 'Schedule generated successfully for ' + scheduleDate);
                } else if (res.status === 'failed') {
                    clearInterval(activePolls[key]);
                    delete activePolls[key];
                    refreshOverviewRow(companyId, scheduleDate);
                    notify('error', res.message || ('Schedule generation failed for ' + scheduleDate));
                }
            });
            // transient network errors: keep polling
        }

        activePolls[key] = setInterval(check, 4000);
        check();
    }

    // Live run details under the row: which order is being scheduled now,
    // which are confirmed and which could not be fitted. The job stores them
    // as JSON in schedule_runs.message while it runs.
    function showRunStatus($tr, message) {
        var $info = $tr.next('.js-run-status');
        var data = null;
        try { data = message ? JSON.parse(message) : null; } catch (e) { data = null; }

        if (!data || !data.stage) {
            $info.remove();
            return;
        }
        if (!$info.length) {
            $info = $('<tr class="js-run-status"><td colspan="8" style="background:#fffbea;font-size:13px;"></td></tr>');
            $tr.after($info);
        }

        var esc = function (s) { return $('<div>').text(s == null ? '' : String(s)).html(); };
        var html = '';
        if (data.stage === 'final') {
            html += '<b>Saving the final schedule…</b>';
        } else if (data.current) {
            html += '<b>Now scheduling:</b> order ' + esc(data.current.order_no)
                + ' (' + esc(data.current.qty) + ' m³, LPI #' + esc(data.current.seq) + ')'
                + ' — ' + esc(data.done_count) + ' of ' + esc(data.total) + ' orders checked';
            if (data.current.trying_delay) {
                html += ' · <span class="text-muted">does not fit at its time, trying '
                    + esc(data.current.trying_delay) + ' min later</span>';
            }
        }
        if (data.committed && data.committed.length) {
            html += '<br><span class="text-success">&#10003; Confirmed (' + data.committed.length + '):</span> '
                + data.committed.map(esc).join(', ');
        }
        if (data.rejected && data.rejected.length) {
            html += '<br><span class="text-danger">&#10007; Could not be scheduled (' + data.rejected.length + '):</span>';
            data.rejected.forEach(function (r) {
                // Older runs sent only the order number.
                var no = (typeof r === 'object') ? r.order_no : r;
                var reason = (typeof r === 'object') ? r.reason : '';
                html += '<br>&nbsp;&nbsp;&bull; <b>' + esc(no) + '</b>'
                    + (reason ? ' — <span class="text-danger">' + esc(reason) + '</span>' : '');
            });
        }
        $info.find('td').html(html);
    }

    // On load: resume polling for any row that's still running so the user
    // gets the live update + toastr even if they navigated here mid-run.
    document.addEventListener('DOMContentLoaded', function () {
        $('tr[data-date]').each(function () {
            var $tr = $(this);
            var status = ($tr.data('status') || '').toString().toLowerCase();
            if (status === 'processing' || status === 'queued') {
                pollScheduleStatus($tr.data('company'), $tr.data('date'));
            }
        });
    });

    // Expose so the Generate page (or an AJAX dispatch handler) can start polling
    // right after dispatch, e.g. pollScheduleStatus(companyId, date, res.run_id)
    window.pollScheduleStatus = pollScheduleStatus;


    </script>

@endsection