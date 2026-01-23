@extends('layouts.auth.app')
@section('content')
    @php
        $productTypes = [];
    @endphp
    <section class="content">
        <div class="container-fluid">
            <div class="px-sm-4">
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-4 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Mix Designs</h1>
                            <h6><span class="active"> Customer Projects </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Mix Designs </h6>
                        </div>
                    </div>

                    <div class="col-md-4"></div>
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
                                                <input type = "hidden" name = "project_id"
                                                    value = "{{ request()->project_id }}" />
                                                <input type = "hidden" name = "customer_id"
                                                    value = "{{ request()->customer_id }}" />
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


                                            <button data-toggle="modal" data-target="#user-profile2"
                                                onclick = "setProjectId();" class="btn btn-success mr-2">Create New</button>
                                            {{-- <button type="button" class="btn export-btn mr-2">Export</button> --}}

                                            <a type="button" class="btn export-btn mr-2"
                                            href="{{ route('settings.customerProjectMix.export', ['search' => request('search'), 'customer_id' => request('customer_id'),'project_id'=> request('project_id'),'name' => request('name'), 'type' => request('type')]) }}"
                                            class="btn btn-success mr-2">Export</a>
                                            <div class="dropdown drop-mainbox">
                                                <button class="btn filter-boxbtn dropdown-toggle" type="button"
                                                    id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    Filters
                                                </button>

                                                <form method="GET">
                                                    <input type="hidden"
                                                        value="{{ request()->customer_id }}"name="customer_id">
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <label class="fliter-label">Filters</label>
                                                        <div class="select-box form-group">
                                                            <label class="selext-label">projects</label>
                                                            <select class="form-control select-contentbox" name ='name'>
                                                                <option value="">Select </option>
                                                                @foreach (@$projects as $project)
                                                                    <option value="{{ @$project->value }}">
                                                                        {{ @$project->label }}</option>
                                                                @endforeach
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
                                        <div class="table-responsive">
                                            <table class="table general-table">
                                                <thead>
                                                    <tr>
                                                        <th>Customer</th>
                                                        <th>Project</th>
                                                        <th>Mix Code</th>
                                                        <th>Mix Name</th>
                                                        <th>Mix Type</th>
                                                        <th>Total Qty</th>
                                                        <th>Utilized Qty</th>
                                                        <th>Remaining Qty</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $productTypes = $products->pluck('product_id');
                                                    @endphp
                                                    @foreach ($products as $product)
                                                        <tr>
                                                            <td>{{ @$product?->customer_project?->customer?->name }}</td>
                                                            <td>{{ @$product?->customer_project?->name }}</td>
                                                            <td>{{ @$product->product->code }}</td>
                                                            <td>{{ @$product->product->name }}</td>
                                                            <td>{{ @$product->product->product_type->type }}</td>
                                                            <td>{{ @$product->total_quantity }}</td>
                                                            <td>{{ @$product->ordered_quantity }}</td>
                                                            <td>{{ @$product->remaining_quantity }}</td>
                                                            <td
                                                                class="{{ $product->status == 'Active' ? 'table-activetext' : 'table-inactivetext' }}">
                                                                {{ $product->status }}</td>
                                                            <td width="100px" class="text-center">
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
                                                                                onclick="editProduct({{ @$product->id }}, {{ request()->project_id }}, {{ request()->customer_id }})"
                                                                                href = "#">Details</a>
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
                                {!! $products->links('partials.pagination') !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade filter-modal full-heightmodal" id="user-profile2" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <img src="{{ asset('assets/img/filter-close.svg') }}" alt=""> close
                        </button>
                    </div>
                    <form action="{{ route('customers.store_product') }}" method="post">
                        @csrf
                        <input type="hidden" name="customerProductId" value="" id="customer_product_id">
                        <div class="modal-body">
                            <div class="filter-contentbox">
                                <h6>Mix Design</h6>
                            </div>

                            <div class="active-switch d-flex mt-3 mb-4 justify-content-end align-items-center"
                                id="product-switch">
                                <label class="switch">
                                    <input type="checkbox" id="product_staus" name="product_status"
                                        value="{{ @$product->status ?? 'Active' }}" class="activeclass"
                                        onclick="{{ @$product->status == 'Inactive' || !isset($product) ? 'active' : 'inactive' }}Staus('product_staus', 'product_status')"
                                        checked />
                                    <div class="slider round">
                                        <span class="{{ @$product->status == 'Inactive' ? 'swinactive' : 'swactive' }}">
                                        </span>
                                    </div>
                                </label>
                                <p id="product_status">Active</p>
                            </div>

                            <div class="form-group">
                                <div class="profileinput-box position-relative">
                                    <label class="selext-label">Customer</label>
                                    <select class="form-control select-contentbox responsive-input"
                                        id = "product_customer_id" name="customer_id"
                                        onchange = "changeDropdownOptions(this, ['projects_dropdown'], ['customer_projects'] , '/customer-projects/get/', null, ['projects_dropdown'])">
                                        @foreach (@$customers as $customer)
                                            <option value="{{ @$customer->value }}"
                                                {{ request()->customer_id == @$customer->value ? 'selected' : '' }}>
                                                {{ @$customer->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="profileinput-box position-relative">
                                    <label class="selext-label">Project</label>
                                    <select class="form-control select-contentbox responsive-input" name="project_id"
                                        id = "projects_dropdown">
                                        @foreach (@$projects as $project)
                                            <option value="{{ @$project->value }}">{{ @$project->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="profileinput-box position-relative">
                                    <label class="selext-label">Mix Code</label>
                                    <select class="form-control select-contentbox responsive-input" name="product_id"
                                        onchange="getMixDetails(this)" id="productCode">
                                        <option value="">Select</option>
                                        @foreach (@$allProducts as $product)
                                            <option value="{{ @$product->id }}">{{ @$product->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="profileinput-box position-relative readonlyFieldsContainer">
                                    <label class="selext-label">Mix Code Name</label>
                                    <input type="text" name="product_code_name" id="productCodeName"
                                        class="form-control user-profileinput responsive-input readonlyFields">
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="profileinput-box position-relative readonlyFieldsContainer">
                                    <label class="selext-label">Mix Type</label>
                                    <input type="text" name="product_type" id="productType"
                                        class="form-control user-profileinput responsive-input readonlyFields">
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="profileinput-box position-relative">
                                    <label class="selext-label">Total Quantity</label>
                                    <input type="text" name="total_qty" id="totalQty"
                                        class="form-control user-profileinput responsive-input" placeholder="Enter Qty">
                                </div>
                            </div>

                            <div class="mt-sm-5 mt-3">
                                <button type="button" onclick="storeProduct(this)"
                                    class="btn apply-btn btn-block">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        function setProjectId() {
            var url = new URL(window.location.href);
            var projectId = url.searchParams.get("project_id");
            document.getElementById('project_id_input').value = projectId;
        }

        function activeStaus(id, field) {
            console.log(typeof(id), field);
            if (typeof(id) == 'object' && typeof(field) == 'object') {
                id = id.id;
                field = field.name;
            }
            var status = 'active';
            // $('#status').val(status);
            document.getElementById(id).setAttribute('onclick', `inactiveStaus(${id}, ${field})`);
            $(".slider span").addClass("swactive");
            $(`#${field}`).html('Inactive');
            $(`input[name='${field}']`).val('Inactive');

            //projectStatusToggle(status);

        }

        function inactiveStaus(id, field) {
            var status = 'inactive';
            if (typeof(id) == 'object' && typeof(field) == 'object') {
                id = id.id;
                field = field.name;
            }
            console.log(id, field);
            document.getElementById(id).setAttribute('onclick', `activeStaus(${id}, ${field})`);
            $(".slider span").addClass("swinactive");
            $(`#${field}`).html('Active');
            $(`input[name='${field}']`).val('Active');
            //projectStatusToggle(status);
        }

        // get details of product_type
        function getMixDetails(__this) {
            var productId = __this.value;

            $.ajax({
                url: "{{ url('/customer-products/product/details') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: productId
                },
                success: function(response) {
                    console.log(response);
                    if (response) {
                        $('input[name="product_code_name"]').val(response?.name);
                        $('input[name="product_type"]').val(response?.product_type?.type);
                        $('input[name="product_id"]').val(response?.id);
                    }
                },
                error: function(response) {
                    console.log(response);
                },
            });
        }

        function storeProduct(__this) {
            var form = $(__this).closest('form');
            var formData = new FormData(form[0]);
            var url = $(form[0]).attr('action');

            $.ajax({
                url: url,
                data: formData,
                cache: false,
                type: 'POST',
                dataType: 'JSON',
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loaderDiv').show();
                    $('.help-block').remove();
                },
                success: function($response) {
                    if ($response.status === 200) {
                        toast("success", $response.message);
                        setTimeout(function() {
                            $('#loaderDiv').hide();
                            window.location.reload();
                        }, 2200);
                    }
                },
                error: function($response) {
                    $('#loaderDiv').hide();
                    if ($response.status === 422) {
                        if (Object.size($response.responseJSON) > 0 && Object.size($response
                                .responseJSON.errors) > 0) {
                            show_validation_error($response.responseJSON.errors);
                        }
                    } else {
                        Swal.fire(
                            'Error', $response.responseJSON.message, 'warning'
                        )
                        setTimeout(function() {}, 1200)
                    }
                }
            });
        }

        function editProduct(productId, projectId, customerId) {
            $.ajax({
                url: "{{ url('/customers/edit-project-product') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    productId: productId,
                    projectId: projectId,
                    customerId: customerId,
                },

                success: function(response) {
                    console.log("success", response);
                    let html = '';
                    $('#product-switch').html('');
                    //    $('#productCode').empty();

                    if (response) {
                        $('#productCode').val(response?.productDetail?.product?.id);
                        $('#productCodeName').val(response?.productDetail?.product?.name);
                        // $('#productCodeName').attr('readonly', true);
                        $('#productType').val(response?.productDetail?.product?.product_type?.type);
                        // $('#productType').attr('readonly', true);
                        $('#totalQty').val(response?.productDetail?.total_quantity);

                        $('#product_customer_id').val(response?.productDetail?.customer_id);
                        $('#projects_dropdown').val(response?.productDetail?.project_id);
                        $('#productCode').val(response?.productDetail?.product_id);
                        $('#customer_product_id').val(response?.productDetail?.id);

                        html =
                            `<label class="switch">
                                <input type="checkbox" id="product_staus" name="product_status"
                                    value="${response?.productDetail?.status}" class="activeclass"
                                    onclick="${response?.productDetail?.status == 'Inactive' || !response?.productDetail ? 'inactive' : 'active' }Staus('product_staus', 'product_status')"
                                    ${response?.productDetail?.status == 'Inactive' ? '' : 'checked' } />
                                <div class="slider round">
                                    <span
                                        class="${response?.productDetail?.status == 'Inactive' ? 'swinactive' : 'swactive'}">
                                    </span>
                                </div>
                            </label>
                            <p id="product_status">${response?.productDetail?.status == 'Inactive' ? 'Inactive' : 'Active' }</p>`;

                        $('#product-switch').html(html);
                        $('#user-profile2').modal('show');
                    }
                },
                error: function($response) {
                    console.log($response);
                },
            });
        }
    </script>
@endsection
