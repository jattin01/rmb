@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-5 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Mix</h1>
                        <p>Overview</p>
                    </div>
                </div>
                <div class="col-md-3 mb-sm-0 mb-3">
                    <ul class="nav nav-tabs order-tab mr-5" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab"
                                aria-controls="profile" aria-selected="false">Mix Type</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="home-tab" data-toggle="tab" href="#home" role="tab"
                                aria-controls="home" aria-selected="true">Mix</a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">

                </div>

            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="tab-content" id="myTabContent">
                        <!-- Product Tab -->
                        <div class="tab-pane fade" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <div class="row mt-sm-4 mt-3">
                                <div class="col-md-4">
                                    <form class="search-form" role="search">
                                        <div class="form-group position-relative">
                                            <input type="text" name="search" value="{{@$search}}" class="form-control search-byinpt padding-right"
                                                placeholder="Search By..." onchange="this.form.submit()">
                                            <img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
                                        </div>
                                        <input type="hidden" name="activeTab" value="home">
                                    </form>
                                </div>
                                <div class="col-md-8 text-sm-right">
                                    <a href="{{route('products.create')}}" class="btn btn-success mr-2">Create New</a>
                                    <button type="button" class="btn export-btn mr-2">Export</button>
                                    <select class="filter-select mr-2 mt-sm-0 mt-3">
                                        <option>Filters</option>
                                        <option>2</option>
                                        <option>3</option>
                                        <option>4</option>
                                        <option>5</option>
                                    </select>

                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table order-table">
                                            <thead>
                                                <tr>
                                                    <th style = "background-color: #f1f1f1;">Mix Code</th>
                                                    <th style = "background-color: #f1f1f1;">Mix Name</th>
                                                    <th>Mix Type</th>
                                                    <th>Density</th>
                                                    <th style = "background-color: #f1f1f1;">Usage</th>
                                                    <th style = "background-color: #f1f1f1;">Status</th>
                                                    <th style = "background-color: #f1f1f1;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(@$products as $product)
                                                <tr>
                                                    <td>{{$product->code}}</td>
                                                    <td>{{$product->name}}</td>
                                                    <td>{{$product->product_type->type}}</td>
                                                    <td>{{$product->density}}</td>
                                                    <td>{{$product->usage}}</td>
                                                    <td class="{{$product->status == 'Active' ? 'table-activetext' : 'table-inactivetext'}}">{{$product->status}}</td>
                                                    <td width="120px" class="gray-text">
                                                        <a href="{{route('products.edit', ['productId' => $product->id])}}" class="table-edits"><img src="{{asset('assets/img/pencil.svg')}}"alt=""></a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Product Tab -->

                        <!-- Product Type Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <div class="row mt-sm-4 mt-3">
                                <div class="col-md-4">
                                    <form class="search-form" role="search">
                                        <div class="form-group position-relative">
                                            <input type="text" name="type_search" value="{{@$typeSearch}}" class="form-control search-byinpt padding-right"
                                                placeholder="Search By..." onchange="this.form.submit()">
                                            <img src="{{asset('assets/img/fill-search.svg')}}" class="fill-serchimg" alt="">
                                        </div>
                                        <input type="hidden" name="activeTab" value="profile">
                                    </form>
                                </div>
                                <div class="col-md-8 text-sm-right">
                                    <button type="button" class="btn export-btn mr-2">Export</button>
                                </div>
                            </div>
                            
                            <!-- Product Type Form -->
                            <form action="/products/type/store" role="post-data" method="POST">
                                @csrf
                                <input type="hidden" name="typeId" value="{{@$typeDetail->id}}">
                                <div class="row align-items-center mt-4">
                                    <div class="col-md-3 mb-sm-0 mb-3">
                                        <div class="profileinput-box position-relative">
                                            <label class="selext-label">Mix Type</label>
                                            <input type="text" name="type" value="{{@$typeDetail->type}}" class="form-control user-profileinput"
                                                placeholder="Enter Code">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-sm-0 mb-3">
                                        <div class="profileinput-box position-relative">
                                            <label class="selext-label">Description</label>
                                            <input type="text" name="description" value="{{@$typeDetail->description}}" class="form-control user-profileinput"
                                                placeholder="Write...">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex justify-content-between">
                                            <div class="active-switch d-flex  align-items-center">
                                                <label class="switch mr-2">
                                                    <input type="checkbox" id="type_staus" name="type_status"
                                                        value="{{ @$typeDetail->status ?? 'Active' }}" class="activeclass"
                                                        onclick="{{ @$typeDetail->status == 'Inactive' || !isset($typeDetail) ? 'active' : 'inactive' }}Staus('type_staus', 'type_status')"
                                                        {{ @$typeDetail->status == 'Inactive'? '' : 'checked' }} />
                                                    <div class="slider round">
                                                        <span
                                                            class="{{ @$typeDetail->status == 'Inactive' ? 'swinactive' : 'swactive' }}">
                                                        </span>
                                                    </div>
                                                </label>
                                                <p id="type_status">{{ @$typeDetail->status == 'Inactive' ? 'Inactive' : 'Active' }}</p>
                                            </div>
                                            @if(isset($typeDetail))
                                            <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn btn-success ">Update</button>
                                            @else
                                            <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn btn-success ">Add</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <!-- Form Close -->

                            <!-- Product Type List-->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table order-table">
                                            <thead>
                                                <tr>
                                                    <th style = "background-color: #f1f1f1;">Mix Type</th>
                                                    <th style = "background-color: #f1f1f1;">Description</th>
                                                    <th style = "background-color: #f1f1f1;">Status</th>
                                                    <th style = "background-color: #f1f1f1;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(@$productTypes as $type)
                                                <tr>
                                                    <td>{{$type->type}}</td>
                                                    <td>{{$type->description}}</td>
                                                    <td class="{{$type->status == 'Active' ? 'table-activetext' : 'table-inactivetext'}}">{{$type->status}}</td>
                                                    <td width="120px" class="gray-text">
                                                        <a href="{{route('products.index', ['typeId' => $type->id, 'activeTab' => 'profile'])}}" class="table-edits"><img src="{{asset('assets/img/pencil.svg')}}"alt=""></a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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
    document.addEventListener("DOMContentLoaded", function() {
        // Get the active tab from the URL if present
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('activeTab');

        // If there's an active tab in the URL, set it as the active tab
        if (activeTab) {
            const tabToShow = document.getElementById(activeTab + '-tab');
            const tabPaneToShow = document.getElementById(activeTab);

            // If both the tab and pane exist, make them active
            if (tabToShow && tabPaneToShow) {
                document.querySelectorAll('.nav-link').forEach(tab => tab.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));

                tabToShow.classList.add('active');
                tabPaneToShow.classList.add('show', 'active');
            }
        }

    });

    function activeStaus(id,field) {
        // alert("active");
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
        // alert('inactive');
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