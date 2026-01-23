@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-5 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Settings</h1>
                        <p>Mix Types</p>
                    </div>
                </div>
                <div class="col-md-3 col-4 text-right">
                    <a href="{{route('settings.home')}}" class="btn back-btn">Back</a>
                </div>

            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
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
                                    <div class="d-flex justify-content-end">
                                    <a href="{{route('settings.productTypes.create')}}" class="btn btn-success mr-2">Create New</a>
                                    {{-- <button type="button" class="btn export-btn mr-2">Export</button> --}}
                                    <a type="button" class="btn export-btn mr-2"
                                    href="{{ route('settings.productMixType.export', ['search' => request('search'), 'group_company_id' => request('group_company_id')]) }}"
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
                                                            <option
                                                                value="{{ $company->value }}">
                                                                {{ $company->label}}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="select-box form-group">
                                                    <label class="selext-label">Mix Type</label>
                                                    <select class="form-control select-contentbox" name="type">
                                                        <option value="">Select project</option>
                                                        @forelse ($productTypes as $type)
                                                            <option value = "{{ $type->type }}">
                                                                {{ $type->type }}
                                                            </option>
                                                        @empty
                                                        @endforelse
                                                    </select>
                                                </div>
                                                <div class="row align-items-center mt-3">
                                                    <div class="col-md-6 col-4">
                                                        <a class="reset-text"
                                                            href="{{ url('settings/product_types') }}">Reset
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

                           <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table general-table">
                                            <thead>
                                                <tr>
                                                    <th>Company</th>
                                                    <th>Mix Type</th>
                                                    <th>Batching Time (Min)</th>
                                                    <th>Tempeature Time (Min)</th>
                                                    <th>Description</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(@$productTypes as $type)
                                                <tr>
                                                    <td>{{$type->group_company ?-> comp_name}}</td>
                                                    <td>{{$type->type}}</td>
                                                    <td>{{$type->batching_creation_time }}</td>
                                                    <td>{{$type->temperature_creation_time}}</td>
                                                    <td>{{$type->description}}</td>
                                                    <td class="{{$type->status == 'Active' ? 'table-activetext' : 'table-inactivetext'}}">{{$type->status}}</td>
                                                    <td>
                                                    <div class="d-flex align-items-center justify-content-between" style = "margin:0.4rem;">
																<div class="dropdown more-drop">
																	<button class="table-drop"  type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
																		<i class="fa fa-ellipsis-v fa-lg more-icon" aria-hidden="true"></i>
																	</button>
																	<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
																	  <a class="dropdown-item" href="{{route('settings.productTypes.edit', ['typeId' => $type->id])}}">Details</a>
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
                            {{ $productTypes -> links('partials.pagination') }}
                            {{--Pagination End--}}
                            <!-- Product Type List Closes -->
                        </div>
                        <!-- Product Type Tab -->
                    </div>
                </div>
            </div>

            <!-- pagination -->
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>

    function activeStaus(id,field) {
        if(typeof(id) == 'object' && typeof(field) == 'object'){
            id = id.id;
            field = field.name;
        }
        var status = 'active';
        document.getElementById(id).setAttribute('onclick', `inactiveStaus(${id}, ${field})`);
        $(".slider span").addClass("swactive");
        $(`#${field}`).html('Active');
        $(`input[name='${field}']`).val('Active');
    }

    function inactiveStaus(id,field) {
        var status = 'inactive';
        if(typeof(id) == 'object' && typeof(field) == 'object'){
            id = id.id;
            field = field.name;
        }
        console.log(id, field);
        document.getElementById(id).setAttribute('onclick', `activeStaus(${id}, ${field})`);
        $(".slider span").addClass("swinactive");
        $(`#${field}`).html('Inactive');
        $(`input[name='${field}']`).val('Inactive');
    }
</script>
@endsection
