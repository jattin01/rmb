@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-5 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Team Members</h1>
                        <h6><span class="active"> Customer </span> <i class="fa fa-angle-right"
                                aria-hidden="true"></i> Team Members </h6>
                    </div>
                </div>
                <div class="col-md-3 col-4 text-right">
                    <a href="{{route('customers.index')}}" class="btn back-btn">Back</a>
                </div>

            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="tab-content" id="myTabContent">
                        <!-- Product Tab -->
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <div class="row mt-sm-4 mt-3">
                                <div class="col-md-4">
                                    <form class="search-form" role="search">
                                        <div class="form-group position-relative">
                                            <input type="text" name="search" value="{{ old('search', $search) }}" class="form-control search-byinpt padding-right"
                                                placeholder="Search By..." onchange="this.form.submit()">


                                            <img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-8 text-sm-right">
                                    <a class="btn btn-success mr-2" href = "{{route('settings.customerTeam.create', ['customer_id' => request() -> customerId])}}">Create New</a>
                                    {{-- <button type="button" class="btn export-btn mr-2">Export</button> --}}
                                    <a type="button" class="btn export-btn mr-2"
                                    href="{{ route('settings.customerTeam.export', [ request()->segment(3)]) }}"
                                    class="btn btn-success mr-2">Export</a>
                                    {{-- <select class="filter-select mr-2 mt-sm-0 mt-3">
                                        <option>Filters</option>
                                        <option>2</option>
                                        <option>3</option>
                                        <option>4</option>
                                        <option>5</option>
                                    </select> --}}

                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="table-responsive full-table">
                                        <table class="table general-table">
                                            <thead>
                                                <tr>
                                                    <th>Customer</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Username</th>
                                                    <th>Mobile No</th>
                                                    <th>Admin</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(@$teamMembers as $member)
                                                <tr>
                                                    <td>{{$member->customer ?-> name}}</td>
                                                    <td>{{$member->name}}</td>
                                                    <td>{{$member->email}}</td>
                                                    <td>{{$member->username}}</td>
                                                    <td>{{$member->phone_no}}</td>
                                                    <td>{{$member->is_admin ? 'Yes' : 'No'}}</td>
                                                    <td class="{{$member->status == 'Active' ? 'table-activetext' : 'table-inactivetext'}}">{{$member->status}}</td>
                                                    <td class="gray-text">
                                                    <div class="d-flex align-items-center justify-content-between" style = "margin:0.4rem;">
																<div class="dropdown more-drop">
																	<button class="table-drop"  type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
																		<i class="fa fa-ellipsis-v fa-lg more-icon" aria-hidden="true"></i>
																	</button>
																	<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
																	  <a href = "{{route('settings.customerTeam.edit', ['member_id' => $member -> id, 'customer_id' => request() -> customerId])}}" class="dropdown-item">Details</a>
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
                            {{--Pagination--}}
                            {{ $teamMembers -> links('partials.pagination') }}
                            {{--Pagination End--}}
                        </div>
                    </div>
                </div>
            </div>

            <!-- pagination -->
        </div>
    </div>
</section>
@endsection
