@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-4 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Projects</h1>
                            <h6><span class="active"> Customer </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                                Projects </h6>
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
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-8 text-sm-right">
                                        <div class="d-flex justify-content-end">
                                            <a href="{{ route('settings.customerProjects.create', ['customer_id' => request()->customerId ?? null]) }}"
                                                class="btn btn-success mr-2">Create New</a>

                                            <a type="button" class="btn export-btn mr-2"
                                                href="{{ route('settings.customerProjects.export', ['search' => request('search'), 'customer_id' => request('customer_id'), 'name' => request('name'), 'type' => request('type')]) }}"
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
                                                            <label class="selext-label">By customer</label>
                                                            <select class="form-control select-contentbox"
                                                                name="customer_id">
                                                                <option value="">Select customer</option>


                                                                @foreach (@$customers as $customer)
                                                                    <option value="{{ @$customer->value }}"
                                                                        @if (@$project->customer_id == @$customer->value)  @endif>
                                                                        {{ @$customer->label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="select-box form-group">
                                                            <label class="selext-label">By project</label>

                                                            <select class="form-control select-contentbox" name="name">
                                                                <option value="">Select project</option>
                                                                @forelse ($projects as $customer)
                                                                    <option value = "{{ @$customer->name }}">
                                                                        {{ @$customer->name }}
                                                                    </option>
                                                                @empty
                                                                @endforelse
                                                            </select>
                                                        </div>

                                                        <div class="select-box form-group">
                                                            <label class="selext-label">Project type</label>

                                                            <select class="form-control select-contentbox" name="type">
                                                                <option value="">Select project</option>
                                                                @forelse ($projects as $customer)
                                                                    <option value = "{{ $customer->type }}">
                                                                        {{ $customer->type }}
                                                                    </option>
                                                                @empty
                                                                @endforelse
                                                            </select>
                                                        </div>
                                                        <div class="row align-items-center mt-3">
                                                            <div class="col-md-6 col-4">

                                                                <a class="reset-text"
                                                                    href="{{ url('customer-projects') }}">Reset
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
                                        <div class="table-responsive">
                                            <table class="table general-table">
                                                <thead>
                                                    <tr>
                                                        <th>Customer</th>
                                                        <th>Project Code</th>
                                                        <th>Project Name</th>
                                                        <th>Type</th>
                                                        <th>Contractor</th>
                                                        <th>Started on</th>
                                                        <th>ETC</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($projects as $project)
                                                        <tr>
                                                            <td>{{ @$project->customer?->name }}</td>
                                                            <td>{{ @$project->code }}</td>
                                                            <td>{{ $project->name }}</td>
                                                            <td>{{ $project->type }}</td>
                                                            <td>{{ $project->contractor_name }}</td>
                                                            <td>{{ $project->start_date->format('Y-m-d') }}</td>
                                                            <td>{{ @$project->end_date->format('Y-m-d') }}</td>
                                                            <td
                                                                class="{{ $project->status == 'Active' ? 'table-activetext' : 'table-inactivetext' }}">
                                                                {{ $project->status }}</td>
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
                                                                                href="{{ route('settings.customerProjects.edit', ['projectId' => $project->id, 'customerId' => $project->customer_id]) }}">Details</a>
                                                                            <a class="dropdown-item"
                                                                                href="{{ route('settings.customerProjectSites.index', ['project_id' => $project->id]) }}">Project
                                                                                Sites</a>
                                                                            <a class="dropdown-item"
                                                                                href="{{ route('settings.customerProjectProducts.index', ['project_id' => $project->id, 'customer_id' => $project->customer_id]) }}">Mix
                                                                                Designs</a>
                                                                            <a class="dropdown-item"
                                                                                href="{{ route('settings.customerTeam.index', ['customerId' => $project->customer_id]) }}">Team
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
                                {!! $projects->links('partials.pagination') !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
