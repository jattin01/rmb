@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-3 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Orders</h1>
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

                        </form>

                    </div>
                    <div class="col-md-8 mb-sm-0 mb-2 text-sm-right">

                        <div class="d-flex justify-content-end">

                            <a type="button" class="btn btn-primary mr-2 mb-sm-0 mb-2"
                                href = "{{ route('orders.schedule.step.one') }}">Generate Schedule</a>
                            <button type="button" class="btn btn-success mr-2 mb-sm-0 mb-2"
                                onclick = "redirectToCreateOrderPage()">Create New</button>
                            <a  type="button" class="btn export-btn mr-2" href="{{ route('orders.export', ['search' => request('search'), 'customer_id' => request('customer_id'), 'project_id' => request('project_id'), 'site_id' => request('site_id'), 'delivery_date' => request('delivery_date'), 'interval_from' => request('interval_from'), 'interval_to' => request('interval_to')]) }}"
                                class="btn btn-success mr-2">Export</a>

                    <form   method="GET">
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
                                                                href="{{ url('orders-overview') }}">Reset
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
                <form class="form" method="POST" id = "import_orders" action="{{ route('orders.import') }}"
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

                    <div class="col-md-12">
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
                                        <th style = "background-color: #f1f1f1;">Order No.</th>
                                        <th style = "background-color: #f1f1f1;min-width:10rem;">Customer</th>
                                        <th style = "background-color: #f1f1f1;min-width:10rem;">Company</th>
                                        <th style = "min-width:10rem;">Delivery Date</th>
                                        <th style = "min-width:8rem;">Time</th>
                                        <th>Interval</th>
                                        <th style = "min-width:8rem;">Project</th>
                                        <th style = "min-width:8rem;">Site Location</th>
                                        <th style = "min-width:8rem;">Mix</th>
                                        <th style = "min-width:8rem;">Mix Code</th>
                                        <th style = "min-width:8rem;">Qty.</th>
                                        <th>Structure</th>
                                        <th>Technician</th>
                                        <th style = "min-width:8rem">Temp Control</th>
                                        <th style = "min-width:16rem">Pumps</th>
                                        <th>Cube Mould</th>
                                        <th style = "background-color: #f1f1f1; min-width:8rem">Site Status</th>
                                        <th style = "background-color: #f1f1f1; min-width:5rem">Order Status</th>
                                        <th style = "background-color: #f1f1f1;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($orders as $order)
                                        <tr>
                                            <td>{{ $order->order_no }}</td>
                                            <td>{{ $order->customer_company?->contact_person . ' - ' . $order->customer_company?->name }}
                                            </td>
                                            <td>{{ $order->group_company?->comp_name }}</td>
                                            <td>{{ Carbon\Carbon::parse($order->delivery_date)->format('d F, Y') }}
                                            </td>
                                            <td>{{ Carbon\Carbon::parse($order->delivery_date)->format('h:i A') }}</td>
                                            <td>{{ $order->interval }} MINS</td>
                                            <td>{{ $order->project }}</td>
                                            <td>{{ $order->site }}</td>
                                            <td>{{ $order->customer_product?->product_name }}</td>
                                            <td>{{ $order->customer_product?->product_code }}</td>
                                            <td>{{ $order->quantity }} CUM</td>
                                            <td>{{ $order->structural_reference }}</td>
                                            <td>{{ $order->is_technician_required ? 'Yes' : 'No' }}</td>
                                            <td>{{ $order->order_temp_control_display() }}</td>
                                            <td>{{ $order->order_pumps_display() }}</td>
                                            <td>{{ $order->order_cube_mould_display() }}</td>
                                            <td>
                                                @if ($order->has_customer_confirmed)
                                                    <span class="approved2-text">Ready for Casting</span>
                                                @else
                                                    <span class="pending-text">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($order->approval_status === 'Approved' || $order->approval_status == 'Partially Approved')
                                                    <span class="approved-text">{{ $order->approval_status }}</span>
                                                @endif
                                                @if ($order->approval_status === 'Pending')
                                                    <span class="pending-text">Pending</span>
                                                @endif
                                                @if ($order->approval_status === 'Rejected')
                                                    <span class="rejected-text">Rejected</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($order->published_by)
                                                    <span class="approved-text">Published</span>
                                                @else
                                                    <div class="d-flex align-items-center justify-content-between"
                                                        style = "margin:0.4rem;">
                                                        <div class="dropdown more-drop">
                                                            <button onclick = "redirectToOrder({{ $order->id }})"
                                                                class="table-drop" type="button" id="dropdownMenuButton"
                                                                data-toggle="dropdown" aria-haspopup="true"
                                                                aria-expanded="false">
                                                                <i class="fa fa-ellipsis-v fa-lg more-icon"
                                                                    aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>
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

    // end active class}


    </script>

@endsection
