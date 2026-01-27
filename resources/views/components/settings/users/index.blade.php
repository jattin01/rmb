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
                    <a href="{{route('settings.home')}}" class="btn back-btn">Back</a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <div class="row mt-sm-4 mt-3">
                                <div class="col-md-4">
                                    <form class="search-form" role="search">
                                        <div class="form-group position-relative">
                                            <input type="text" name="search" value="{{@$search}}" class="form-control search-byinpt padding-right"
                                                placeholder="Search By..." onchange="this.form.submit()">
                                            <img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-8 text-sm-right">
                                    <a href="{{route('settings.users.create')}}" class="btn btn-success mr-2">Create New</a>
                                    {{-- <button type="button" class="btn export-btn mr-2">Export</button> --}}
                                    <a type="button" class="btn export-btn mr-2"
                                    href="{{ route('settings.users.export', ['search' => request('search'), 'group_company_id' => request('group_company_id')]) }}"
                                    class="btn btn-success mr-2">Export</a>


                                    {{-- <select class="filter-select mr-2 mt-sm-0 mt-3">
                                        <option>Filters</option>
                                    </select> --}}
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
                                                    <th>User Name</th>
                                                    <th>Name</th>
                                                    <th>Mobile</th>
                                                    <th>Email Address</th>
                                                    <th>Role</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(@$users as $user)
                                                <tr>
                                                    <td>{{$user->username}}</td>
                                                    <td>{{$user->name}}</td>
                                                    <td>{{$user->mobile_no}}</td>
                                                    <td>{{$user->email}}</td>
                                                    <td>{{$user -> role ?-> name}}</td>
                                                    <td width="150px" class="{{$user->status == 'Active' ? 'table-activetext' : 'table-inactivetext'}}">{{$user -> status}}</td>
                                                    <td>
                                                    <div class="d-flex align-items-center justify-content-between" style = "margin:0.4rem;">
																<div class="dropdown more-drop">
																	<button class="table-drop"  type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
																		<i class="fa fa-ellipsis-v fa-lg more-icon" aria-hidden="true"></i>
																	</button>
																	<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
																	  <a class="dropdown-item" href="{{route('settings.users.edit', ['userId' => $user->id])}}">Details</a>
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
                            {{ $users -> links('partials.pagination') }}
                            {{--Pagination End--}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
