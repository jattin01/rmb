@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-4 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Customers</h1>
                            <p>Registered Customer</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="home" role="tabpanel"
                                aria-labelledby="home-tab">
                                <div class="row mt-sm-4 mt-3">
                                    <div class="col-md-4">
                                        <form class="search-form" role="search">
                                            <div class="form-group position-relative">
                                                <input type="text" name="search" value="{{ @$search }}"
                                                    class="form-control search-byinpt padding-right"
                                                    placeholder="Search By..." onchange="this.form.submit()">
                                                <img src="{{ asset('assets/img/fill-search.svg') }}" class="fill-serchimg"
                                                    alt="">
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <div class="d-flex justify-content-end">

                                            <a href="{{ route('customers.create') }}" class="btn btn-success mr-2">Create
                                                New</a>
                                            <!-- <button type="button" class="btn export-btn mr-2">Export</button> -->
                                            <a type="button" class="btn export-btn mr-2"
                                                href="{{ route('customers.export', ['search' => request('search'), 'group_company_id' => request('group_company_id')]) }}"
                                                class="btn btn-success mr-2">Export</a>
                                            <div class="dropdown drop-mainbox">
                                                <button class="btn filter-boxbtn dropdown-toggle" type="button"
                                                    id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    Filters
                                                </button>

                                                <form method="GET">
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <label class="fliter-label">Filters</label>
                                                        <div class="select-box form-group">
                                                            <label class="selext-label">company name</label>

                                                            <select class="" id = "group_companies_dropdown"
                                                                name="group_company_id[]" multiple = "multiple">
                                                                @foreach (@$groupCompanies as $company)
                                                                    <option value="{{ $company->value }}">
                                                                        {{ $company->label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="select-box form-group">
                                                            <label class="selext-label">By customer</label>
                                                            <select class="form-control select-contentbox" name="customer_id">
                                                                <option value="">Select customer</option>
                                                                @forelse ($searchCustomer as $customer)
                                                                    <option value = "{{ @$customer->value }}">
                                                                        {{ @$customer->label }}
                                                                    </option>
                                                                @empty
                                                                @endforelse
                                                            </select>
                                                        </div>
                                                        <div class="row align-items-center mt-3">
                                                            <div class="col-md-6 col-4">

                                                                <a class="reset-text"
                                                                    href="{{ url('customers/index') }}">Reset
                                                                </a>
                                                            </div>
                                                            <div class="col-md-6 col-8 text-right">
                                                                <button type="button" class="btn apply-btnnew "
                                                                    onclick="this.form.submit()">Apply now</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3 mt-sm-2 align-items-center">
                                    <div class="col-md-6 mb-sm-0 mb-2">

                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="table-responsive full-table">
                                            <table class="table general-table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Customer</th>
                                                        <th>Contact Person</th>
                                                        <th>Mobile</th>
                                                        <th>Email Address</th>
                                                        <th>Company</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach (@$customers as $customer)
                                                        <tr>
                                                            <td>{{ @$customer->code }}</td>
                                                            <td>{{ @$customer->name }}</td>
                                                            <td>{{ $customer->contact_person }}</td>
                                                            <td>{{ $customer->mobile_no }}</td>
                                                            <td>{{ $customer->email_id }}</td>
                                                            <td>
                                                                @forelse ($customer -> group_companies as $companyKey => $group_company)
                                                                    {{ $group_company?->company?->comp_name }}
                                                                    {{ $companyKey === count($customer->group_companies) - 1 ? '' : ',' }}
                                                                @empty
                                                                    No Company Assigned
                                                                @endforelse
                                                            </td>
                                                            <td
                                                                class="{{ $customer->status == 'Active' ? 'table-activetext' : 'table-inactivetext' }}">
                                                                {{ ucfirst($customer->status) }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center justify-content-between"
                                                                    style = "margin:0.4rem;">
                                                                    <div class="dropdown more-drop">
                                                                        <button class="table-drop" type="button"
                                                                            id="dropdownMenuButton" data-toggle="dropdown"
                                                                            aria-haspopup="true" aria-expanded="false">
                                                                            <i class="fa fa-ellipsis-v fa-lg more-icon"
                                                                                aria-hidden="true"></i>
                                                                        </button>
                                                                        <div class="dropdown-menu"
                                                                            aria-labelledby="dropdownMenuButton">
                                                                            <a class="dropdown-item"
                                                                                href="{{ route('customers.edit', ['customerId' => $customer->id]) }}">Details</a>
                                                                            <a class="dropdown-item"
                                                                                href="{{ route('settings.customerProjects.index', ['customerId' => $customer->id]) }}">Projects</a>
                                                                            <a class="dropdown-item"
                                                                                href="{{ route('settings.customerProjectSites.index', ['customer_id' => $customer->id]) }}">Project
                                                                                Sites</a>
                                                                            <a class="dropdown-item"
                                                                                href="{{ route('settings.customerProjectProducts.index', ['customer_id' => $customer->id]) }}">Mix
                                                                                Designs</a>
                                                                            <a class="dropdown-item"
                                                                                href="{{ route('settings.customerTeam.index', ['customerId' => $customer->id]) }}">Team
                                                                                Members</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                {!! $customers->links('partials.pagination') !!}
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </section>

    <script>
        $('.dropdown-menu').on('click', function(event) {
            event.stopPropagation();
        });



        $(document).ready(function(e) {
            $('#group_companies_dropdown').select2({
                allowClear: true
            });
            initMap();
        });
    </script>
@endsection
