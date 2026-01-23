@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-4 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Settings</h1>
                            <p>Users</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-4 text-right">
                        <a href="{{ route('settings.home') }}" class="btn back-btn">Back</a>
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
                                            <a href="{{ route('settings.structure.create') }}"
                                                class="btn btn-success mr-2">Create New</a>
                                            {{-- <button type="button" class="btn export-btn mr-2">Export</button> --}}
                                            <a type="button" class="btn export-btn mr-2"
                                            href="{{ route('settings.structure.export', ['search' => request('search'), 'group_company_id' => request('group_company_id')]) }}"
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
                                                            <select class="form-control select-contentbox"
                                                                name ='group_company_id'>
                                                                <option value="">Select </option>
                                                                @foreach ($groupCompanies as $company)
                                                                    <option value="{{ $company->value }}">
                                                                        {{ $company->label }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="row align-items-center mt-3">
                                                            <div class="col-md-6 col-4">
                                                                <a class="reset-text"
                                                                    href="{{ url('settings/structures') }}">Reset
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
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table general-table">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Company</th>
                                                        <th>Pouring Time W/O Pump</th>
                                                        <th>Pouring Time with Pump</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>

                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach (@$structures as $structure)
                                                        <tr>
                                                            <td>{{ $structure->name }}</td>
                                                            <td>{{ $structure->group_company?->comp_name }}</td>
                                                            <td>{{ $structure->pouring_wo_pump_time }} Mins</td>
                                                            <td>{{ $structure->pouring_w_pump_time }} Mins</td>

                                                            <td width="150px"
                                                                class="{{ $structure->status == 'Active' ? 'table-activetext' : 'table-inactivetext' }}">
                                                                {{ $structure->status }}</td>
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
                                                                                href="{{ route('settings.structure.create', ['structure_id' => $structure->id]) }}">Details</a>
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
                                {{-- Pagination --}}
                                {{ $structures->links('partials.pagination') }}
                                {{-- Pagination End --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
