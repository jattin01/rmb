@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-3 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Capacity</h1>
                        <h6><span class="active"> Settings </span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                        Capacity </h6>
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
                            <div class="row mt-sm-5 mt-3">
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

                                    <div class="d-flex justify-content-end">
                                    <a href="{{route('settings.capacity.create')}}" class="btn btn-success mr-2">Create New</a>

                                    </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                        <table class="table general-table">
                            <thead>
                                <tr>
                                    <th>Value</th>
                                    <th>UOM</th>

                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- @dd($capacities); --}}

                                @forelse ($capacities as $capacity)
                                <tr>
                                    <td>{{$capacity -> value}}</td>
                                    <td>{{$capacity -> uom}}</td>
                                    <td class="{{$capacity->status == 'Active' ? 'table-activetext' : 'table-inactivetext'}}">{{$capacity -> status}}</td>
                                    <td>
                                    <div class="d-flex align-items-center justify-content-between" style = "margin:0.4rem;">
																<div class="dropdown more-drop">
																	<button class="table-drop"  type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
																		<i class="fa fa-ellipsis-v fa-lg more-icon" aria-hidden="true"></i>
																	</button>
																	<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
																	  <a class="dropdown-item" href="{{route('settings.capacity.edit', ['CapacityId' => $capacity->id])}}">Details</a>
																	</div>
																  </div>
																</div>
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
                    {{ $capacities->links('partials.pagination') }}

                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
		$(function () {
			$('[data-toggle="tooltip"]').tooltip()
		})
</script>
@endsection
