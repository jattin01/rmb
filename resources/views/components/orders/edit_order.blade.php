@extends('layouts.auth.app')
@section('content')

    <section class="content">
        <div class="container-fluid">
            <form action="{{ route('order.store.new') }}" method="post">
                @csrf
                <div class="px-sm-4">
                    <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                        <div class="col-md-3 col-4 mb-sm-4 mb-4">
                            <div class="top-head">
                                <h1>Order Details</h1>
                                <h6><span class="active">Order</span> <i class="fa fa-angle-right" aria-hidden="true"></i>
                                    Create New </h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-8 text-right">
                            <button type="button" class="btn back-btn">Back</button>
                        </div>
                    </div>

                    <div class="row mt-sm-4 mt-2 align-items-center">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="project-details">Project Details</label>
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Customer</label>
                                        <select id = "customers_dropdown" name = "customer_id"
                                            class="form-control select-contentbox reset_from_company"
                                            onchange = "changeDropdownOptions(this, ['projects_dropdown'], ['customer_projects'] , '/customer-projects/get/', null, ['projects_dropdown', 'projects_sites_dropdown', 'mix_codes_dropdown'])">
                                            <option value = "0">Select</option>
                                            @forelse ($customers as $customer)
                                                <option value = "{{ $customer->value }}"
                                                    {{ $order->customer_id == $customer->value ? 'selected' : '' }}>
                                                    {{ $customer->label }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <div class = "col-md-6"></div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Project Name</label>
                                        <select id = "projects_dropdown"
                                            class="form-control select-contentbox reset_from_company" name = "project_id"
                                            onchange = "changeDropdownOptions(this, ['projects_sites_dropdown', 'mix_codes_dropdown'], ['project_sites', 'mix_codes'] , '/project-sites/get/', null, ['projects_sites_dropdown'])">
                                            <option value = "0">Select</option>
                                            @forelse ($customerProjects as $customerProject)
                                                <option value = "{{ $customerProject->value }}"
                                                    {{ $order->project_id == $customerProject->value ? 'selected' : '' }}>
                                                    {{ $customerProject->label }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Project Site</label>
                                        <select class="form-control select-contentbox reset_from_company"
                                            id = "projects_sites_dropdown" name = "site_id" onchange = "siteOnChange(this)">
                                            <option value = "0">Select</option>
                                            @forelse ($customerProjectSites as $customerProjectSite)
                                                <option value = "{{ $customerProjectSite->value }}"
                                                    {{ $order->site_id == $customerProjectSite->value ? 'selected' : '' }}>
                                                    {{ $customerProjectSite->label }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Company Name</label>
                                        <select id = 'group_company_dropdown'
                                            class="form-control select-contentbox readonlyFields">
                                            <option value = ""></option>
                                            @forelse ($groupCompanies as $groupCompany)
                                                <option value = "{{ $groupCompany->value }}"
                                                    {{ $order->group_company_id == $groupCompany->value ? 'selected' : '' }}>
                                                    {{ $groupCompany->label }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Company Location</label>
                                        <select id = 'company_location_dropdown'
                                            class="form-control select-contentbox readonlyFields">
                                            <option value = ""></option>
                                            @forelse ($companyLocations as $groupCompanyLocation)
                                                <option value = "{{ $groupCompanyLocation->value }}"
                                                    {{ $order->company_location_id == $groupCompanyLocation->value ? 'selected' : '' }}>
                                                    {{ $groupCompanyLocation->label }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Delivery Date</label>
                                        <input type="date" name = "delivery_date"
                                            value = "{{ Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') }}"
                                            class="form-control user-profileinput">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Delivery Time</label>
                                        <input type="time" name = "delivery_time"
                                            value = "{{ Carbon\Carbon::parse($order->delivery_date)->format('H:i') }}"
                                            class="form-control user-profileinput">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Interval</label>
                                        <input type="number" name = "interval" value = "{{ $order->interval }}"
                                            class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="project-details">Site Readyness</label>
                                </div>
                                <div class="col-md-7 text-right">
                                    @if (!$order->has_customer_confirmed)
                                        <button type="button" class="btn btn-primary" data-toggle="modal"
                                            data-target="#filter">Update Site Status</button>
                                    @else
                                        <i class="fa fa-check" style = "color:#775DA6;" aria-hidden="true"></i>
                                        <span class="casting-approve-badge">Ready for casting</span>
                                    @endif

                                </div>
                            </div>
                            <div class="order-sitereadynessbox" style = "margin-top:0.2rem; min-height:18rem !important;">
                                <div class="order-sitereadynesscontentbox">
                                    <div class="card-body">

                                        @if ($order->has_customer_confirmed)
                                            <div class="approval-historycontentbox">
                                                <div class="approved-borderbottom">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6 col-6">
                                                            <h6>{{ $order->customer_approval_user?->name }}</h6>
                                                            <p>{{ Carbon\Carbon::parse($order->customer_confirmed_at)->format('d F, Y h:i A') }}
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6 col-6 text-right">
                                                            <i class="fa fa-check" style = "color:#775DA6;"
                                                                aria-hidden="true"></i>
                                                            <span class="casting-approve-badge">Ready for casting</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <h5>Remarks</h5>
                                                <h4>{{ $order->customer_confirm_remarks }}</h4>
                                                @if (count($order->get_order_confirmation_documents()) > 0)
                                                    <h5>Attachments</h5>
                                                    <div class="row mt-3">
                                                        @forelse ($order -> get_order_confirmation_documents() as $doc)
                                                            <div class="col-md-3 col-4">
                                                                <div class="upload-image">
                                                                    @if (str_starts_with($doc['file_mime_type'], 'image/'))
                                                                        <a href = "{{ $doc['file_url'] }}"
                                                                            target = "_blank">
                                                                            <img src="{{ $doc['file_url'] }}"
                                                                                alt="">
                                                                        </a>
                                                                    @else
                                                                        <a href = "{{ $doc['file_url'] }}"
                                                                            target = "_blank">
                                                                            <img src="{{ asset('assets/img/attachment.svg') }}"
                                                                                alt="Attachment" />
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @empty
                                                        @endforelse

                                                    </div>
                                                @endif




                                            </div>
                                        @else
                                            <div class="row align-items-center mt-4">
                                                <div class="col text-center">
                                                    @include('partials.details_not_found_left')
                                                    <div class="mt-2">Details not available</div>
                                                </div>
                                            </div>
                                        @endif


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-sm-4 mt-2  align-items-center">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="project-details">Mix Details</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Mix Code</label>
                                        <select id = 'mix_codes_dropdown' name = "id"
                                            class="form-control select-contentbox reset_from_company"
                                            onchange = "mixOnChange(this)">
                                            <option value = "0">Select</option>
                                            @forelse ($customerProjectProducts as $customerProjectProduct)
                                                <option value = "{{ $customerProjectProduct->id }}"
                                                    {{ $order->cust_product_id == $customerProjectProduct->id ? 'selected' : '' }}>
                                                    {{ $customerProjectProduct->name }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Quantity (in CUM)</label>
                                        <input type="number" name = "quantity" value = "{{ $order->quantity }}"
                                            id = "ordered_quantity_field" class="form-control user-profileinput"
                                            placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Mix Name</label>
                                        <input type="text" id = "mix_name_field"
                                            value = "{{ $order->customer_product?->product_code }}"
                                            class="form-control user-profileinput readonlyFields">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Mix Type</label>
                                        <input type="text" id = "mix_type_field"
                                            value = "{{ $order->customer_product?->mix_code }}"
                                            class="form-control user-profileinput readonlyFields">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Total Qty (in CUM)</label>
                                        <input type="number" id = "total_quantity_field"
                                            value = "{{ $order->customer_product?->total_quantity }}"
                                            class="form-control user-profileinput readonlyFields">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Remaining Qty (in CUM)</label>
                                        <input type="number" id = "remaining_quantity_field"
                                            value = "{{ $order->customer_product?->remaining_quantity }}"
                                            class="form-control user-profileinput readonlyFields">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="project-details">Approval History</label>
                                </div>
                                <div class="col-md-7 text-right">
                                    @if ($canApprove)
                                        <button type="button" class="btn btn-success mr-2" data-toggle="modal"
                                            data-target="#approvalModal"
                                            onclick = "setApproveDropdown('Approved')">Approve</button>
                                        <button type="button" class="btn reject-btn" data-toggle="modal"
                                            data-target="#approvalModal"
                                            onclick = "setApproveDropdown('Rejected')">Reject</button>
                                    @else
                                        <span class="approve-badge">{{ $order->approval_status }}</span>
                                    @endif

                                </div>
                            </div>
                            <div class="order-sitereadynessbox"
                                style = "margin-top:0.2rem;min-height:13rem;max-height:13rem;">
                                <div class="order-sitereadynesscontentbox"
                                    style = "max-height:30rem !important;overflow-y: auto !important;">
                                    <div class="card-body">

                                        @forelse ($order -> approvals as $approvalKey => $approval)
                                            <div class="approval-historycontentbox"
                                                style = "{{ $approvalKey === 0
                                                    ? 'border-top-right-radius:2px; border-top-left-radius:20px;'
                                                    : ($approvalKey === count($order->approvals) - 1
                                                        ? 'border-bottom-right-radius:2px; border-bottom-left-radius:20px;'
                                                        : '') }}">
                                                <div class="approved-borderbottom">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6 col-6">
                                                            <h6>{{ $approval->user?->name }}</h6>
                                                            <p>{{ $approval->created_at }}</p>
                                                        </div>
                                                        <div class="col-md-6 col-6 text-right">
                                                            @if ($approval->approval_status === 'Approved')
                                                                <i class="fa fa-check" style = "color: #0AAB7C;"
                                                                    aria-hidden="true"></i>
                                                                <span
                                                                    class="text-sucess small">{{ $approval->approval_status }}</span>
                                                            @endif
                                                            @if ($approval->approval_status === 'Sent Back' || $approval->approval_status === 'Rejected')
                                                                <i class="fa fa-times" style = "color: #E84D88;"
                                                                    aria-hidden="true"></i>
                                                                <span
                                                                    class="text-danger small">{{ $approval->approval_status }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <h5>Remarks</h5>
                                                <h4>{{ $approval->remarks }}</h4>
                                                @if (count($approval->documents()) > 0)
                                                    <h5>Attachments</h5>
                                                    <div class="row mt-3">
                                                        @forelse ($approval -> documents() as $doc)
                                                            <div class="col-md-3 col-4">
                                                                <div class="upload-image">
                                                                    @if (str_starts_with($doc['file_mime_type'], 'image/'))
                                                                        <a href = "{{ $doc['file_url'] }}"
                                                                            target = "_blank">
                                                                            <img src="{{ $doc['file_url'] }}"
                                                                                alt="">
                                                                        </a>
                                                                    @else
                                                                        <a href = "{{ $doc['file_url'] }}"
                                                                            target = "_blank">
                                                                            <img src="{{ asset('assets/img/attachment.svg') }}"
                                                                                alt="Attachment" />
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @empty
                                                        @endforelse

                                                    </div>
                                                @endif




                                            </div>
                                        @empty
                                            <div class="row align-items-center mt-4">
                                                <div class="col text-center">
                                                    @include('partials.details_not_found_right')
                                                    <div class="mt-2">Details not available</div>
                                                </div>
                                            </div>
                                        @endforelse

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-sm-4 mt-2  align-items-center">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="project-details">Other Details</label>
                                </div>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="profileinput-box position-relative">
                                        <label class="selext-label">Structure Ref.</label>
                                        <select class="form-control select-contentbox reset_from_company"
                                            id = "struct_ref_dropdown" name = "structural_reference_id"
                                            onchange = "structureOnChange(this)">
                                            <option value = "0">Select</option>
                                            @forelse ($structuralReferences as $structuralReference)
                                                <option value = "{{ $structuralReference->value }}"
                                                    {{ $order->structural_reference_id == $structuralReference->value ? 'selected' : '' }}>
                                                    {{ $structuralReference->label }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                        </select>
                                    </div>
                                </div>

                            </div>

                            <div class="filter-check mt-sm-4">
                                <input type="checkbox" class="filled-in" name = "is_tech_required"
                                    id="techRequiredCheck" {{ $order->is_technician_required ? 'checked' : '' }}>
                                <label class="temperature-label" for="techRequiredCheck">
                                    Technician Required ?
                                </label>
                            </div>


                            <div class="filter-check mt-sm-4">
                                <input type="checkbox" onchange="toggleSection(this, 'temp_req_section')"
                                    class="filled-in" id="tempCheck" name = "is_temp_required"
                                    {{ count($order->order_temp_control) > 0 ? 'checked' : '' }}>
                                <label class="temperature-label" for="tempCheck">
                                    Temperature Control Required ?
                                </label>
                            </div>

                            <div id = "temp_req_section"
                                class = "{{ count($order->order_temp_control) > 0 ? '' : 'hidden_content' }}">

                                @forelse ($order -> order_temp_control as $orderKey => $orderTemp)
                                    <div class="row mt-sm-3 mt-2 tempCtrlUi{{ $orderKey }}"
                                        id = "tempCtrlUi{{ $orderKey }}">
                                        <div class="col-md-5">
                                            <div class="profileinput-box position-relative">
                                                <label class="selext-label">Temperature (°C)</label>
                                                <select
                                                    class="form-control select-contentbox reset_from_company temp_dropdown"
                                                    id = "temp_dropdown_{{ $orderKey }}" name = "temp_values[]">
                                                    <option value = "0">Select</option>
                                                    @forelse ($temps as $temp)
                                                        <option value = "{{ $temp->value }}"
                                                            {{ $orderTemp->temp == $temp->value ? 'selected' : '' }}>
                                                            {{ $temp->label }}</option>
                                                    @empty
                                                    @endforelse
                                                </select>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-5" style="display: none;">
                                            <div class="form-group">
                                                <div class="profileinput-box form-group position-relative">
                                                    <label class="selext-label">Quantity</label>
                                                    <input type="number" class="form-control user-profileinput"
                                                        value = "{{ $orderTemp->qty }}" placeholder="Enter"
                                                        name = "temp_qty[]">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">

                                            <span class="delete-btn mr-2 {{ $orderKey > 0 ? '' : 'hidden_content' }}"
                                                onclick = "removeTempUI({{ $orderKey }});">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </span>
                                            <span class="add-btn" onclick="createTempControlUI();">
                                                <i class="fa fa-plus" aria-hidden="true"></i>
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="row mt-sm-3 mt-2 tempCtrlUi0">
                                        <div class="col-md-5">
                                            <div class="profileinput-box position-relative">
                                                <label class="selext-label">Temperature (°C)</label>
                                                <select
                                                    class="form-control select-contentbox reset_from_company temp_dropdown"
                                                    id = "temp_dropdown_0" name = "temp_values[]">
                                                    <option value = "0">Select</option>
                                                    @forelse ($temps as $temp)
                                                        <option value = "{{ $temp->value }}"> {{ $temp->label }}
                                                        </option>
                                                    @empty
                                                    @endforelse
                                                </select>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-5" style="display:none">
                                            <div class="form-group">
                                                <div class="profileinput-box form-group position-relative">
                                                    <label class="selext-label">Quantity</label>
                                                    <input type="number" class="form-control user-profileinput"
                                                        placeholder="Enter" name = "temp_qty[]">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="delete-btn mr-2 hidden_content">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </span>
                                            <span class="add-btn" onclick="createTempControlUI();">
                                                <i class="fa fa-plus" aria-hidden="true"></i>
                                            </span>
                                        </div>
                                    </div>
                                @endforelse
                            </div>

                            <div class="filter-check mt-sm-3">
                                <input type="checkbox" class="filled-in" id="pumpCheck"
                                    onchange="toggleSection(this, 'pump_req_section')" name = "is_pump_required"
                                    {{ count($order->order_pumps) > 0 ? 'checked' : '' }}>
                                <label class="temperature-label" for="pumpCheck">
                                    Pump Required ?
                                </label>
                            </div>

                            <div id = "pump_req_section"
                                class = "{{ count($order->order_pumps) > 0 ? '' : 'hidden_content' }}">

                                @forelse ($order -> order_pumps as $pumpKey => $orderPump)
                                    <div class="row mt-sm-3 mt-2 pumpReqUi{{ $pumpKey }}"
                                        id = "pumpReqUi{{ $pumpKey }}">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <div class="profileinput-box position-relative">
                                                    <label class="selext-label">Pump Type</label>
                                                    <select
                                                        class="form-control select-contentbox reset_from_company pump_type_dropdown"
                                                        id = "pump_type_dropdown_{{ $pumpKey }}"
                                                        name = "pump_types[]">
                                                        <option value = "0">Select</option>
                                                        @forelse ($pumpTypes as $pumpType)
                                                            <option value = "{{ $pumpType->value }}"
                                                                {{ $orderPump->type == $pumpType->value ? 'selected' : '' }}>
                                                                {{ $pumpType->label }}</option>
                                                        @empty
                                                        @endforelse
                                                    </select>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="profileinput-box position-relative">
                                                <label class="selext-label">Pump Sizes</label>
                                                <select
                                                    class="form-control select-contentbox reset_from_company pump_size_dropdown"
                                                    id = "pump_size_dropdown_{{ $pumpKey }}" name = "pump_sizes[]">
                                                    <option value = "0">Select</option>
                                                    @forelse ($pumpSizes as $pumpSize)
                                                        <option value = "{{ $pumpSize->value }}"
                                                            {{ $orderPump->pump_size == $pumpSize->value ? 'selected' : '' }}>
                                                            {{ $pumpSize->label }}</option>
                                                    @empty
                                                    @endforelse
                                                </select>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <div class="profileinput-box form-group position-relative">
                                                    <label class="selext-label">No. of Pumps</label>
                                                    <input type="number" class="form-control user-profileinput"
                                                        value = "{{ $orderPump->qty }}" placeholder="Enter"
                                                        name = "no_of_pumps[]">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <div class="profileinput-box form-group position-relative">
                                                    <label class="selext-label">No. of Pipes</label>
                                                    <input type="number" class="form-control user-profileinput"
                                                        value = "{{ $orderPump->pipe_size }}" placeholder="Enter"
                                                        name = "no_of_pipes[]">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="delete-btn mr-2 {{ $pumpKey > 0 ? '' : 'hidden_content' }}"
                                                onclick = "removePumpUI({{ $pumpKey }});">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </span>
                                            <span class="add-btn" onclick="createPumpReqUI();">
                                                <i class="fa fa-plus" aria-hidden="true"></i>
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="row mt-sm-3 mt-2 pumpReqUi0">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <div class="profileinput-box position-relative">
                                                    <label class="selext-label">Pump Type</label>
                                                    <select
                                                        class="form-control select-contentbox reset_from_company pump_type_dropdown"
                                                        id = "pump_type_dropdown_0" name = "pump_types[]"
                                                        onchange="selectedPumpType(this,0)">
                                                        <option value = "0">Select</option>
                                                        @forelse ($pumpTypes as $pumpType)
                                                            <option value = "{{ $pumpType->value }}">
                                                                {{ $pumpType->label }}</option>
                                                        @empty
                                                        @endforelse
                                                    </select>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="profileinput-box position-relative">
                                                <label class="selext-label">Pump Sizes</label>
                                                <select
                                                    class="form-control select-contentbox reset_from_company pump_size_dropdown"
                                                    id = "pump_size_dropdown_0" name = "pump_sizes[]">
                                                    <option value = "0">Select</option>
                                                    @forelse ($pumpSizes as $pumpSize)
                                                        <option value = "{{ $pumpSize->value }}">
                                                            {{ $pumpSize->label }}</option>
                                                    @empty
                                                    @endforelse
                                                </select>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <div class="profileinput-box form-group position-relative">
                                                    <label class="selext-label">No. of Pumps</label>
                                                    <input type="number" class="form-control user-profileinput"
                                                        placeholder="Enter" name = "no_of_pumps[]">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <div class="profileinput-box form-group position-relative">
                                                    <label class="selext-label">No. of Pipes</label>
                                                    <input type="number" class="form-control user-profileinput"
                                                        placeholder="Enter" name = "no_of_pipes[]">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="delete-btn mr-2 hidden_content">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </span>
                                            <span class="add-btn" onclick="createPumpReqUI();">
                                                <i class="fa fa-plus" aria-hidden="true"></i>
                                            </span>
                                        </div>
                                    </div>
                                @endforelse

                            </div>

                            <div class="filter-check mt-sm-3">
                                <input type="checkbox" class="filled-in" id="cubeMouldCheck"
                                    name = "is_cube_mould_required"
                                    onchange="toggleSection(this, 'cube_mould_req_section')"
                                    {{ count($order->order_cube_moulds) > 0 ? 'checked' : '' }}>
                                <label class="temperature-label" for="cubeMouldCheck">
                                    Cube Mould Required ?
                                </label>
                            </div>
                            <div id = "cube_mould_req_section"
                                class="row mt-sm-3 mt-2 {{ count($order->order_cube_moulds) > 0 ? '' : 'hidden_content' }}">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="profileinput-box form-group position-relative">
                                            <label class="selext-label">No. of Moulds</label>
                                            <input type="number" name = "cube_mould_req_quantity"
                                                oninput="cubeMouldOnChange(this)"
                                                value = "{{ count($order->order_cube_moulds) > 0 ? $order->order_cube_moulds->first()->qty : '' }}"
                                                class="form-control user-profileinput" placeholder="Enter">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <input type = "hidden" value = "{{ $order->id }}" name = "order_id" />
                    <button type="submit" class="btn save-btn mt-4">Save</button>
                </div>
            </form>

        </div>

        <div class="modal fade filter-modal" id="filter" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <img src="img/filter-close.svg" alt=""> close
                        </button>
                    </div>
                    <form id = "siteApprovalForm" action = "{{ route('web.order.update.site.status') }}"
                        enctype = "multipart/form-data" method = "POST">
                        @csrf
                        <div class="modal-body">
                            <div class="filter-contentbox">
                                <h6>Status</h6>
                            </div>
                            <div class="row mt-sm-4 mt-3">
                                <div class="col-md-12">
                                    <div class="profileinput-box form-group position-relative readonlyFields">
                                        <label class="selext-label">Casting Status</label>
                                        <select class="form-control select-contentbox">
                                            <option value = "Approved" selected>Ready for Casting</option>
                                        </select>
                                    </div>

                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Add Remarks</label>
                                        <textarea class="form-control user-profileinput" placeholder="Write..." rows="6" name = "remarks"></textarea>
                                    </div>

                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="validatedCustomFile" multiple
                                            name = 'documents[]' onchange="addFiles(this, 'imgPreview', 'siteApproval')">
                                        <label class="custom-file-label" for="validatedCustomFile">Upload
                                            Documents</label>
                                    </div>

                                    <div class="row mt-3" id = 'imgPreview'>

                                    </div>

                                    <div class="mt-sm-4 mt-2 text-right">
                                        <span class="max-filesizetext">Max size 2.0 MB</span>
                                    </div>



                                </div>
                            </div>

                            <input type = "hidden" value = "{{ $order->id }}" name = "order_id">

                            <div class="mt-sm-5 mt-4">
                                <button type="submit" class="btn apply-btn btn-block">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade filter-modal" id="approvalModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <img src="img/filter-close.svg" alt=""> close
                        </button>
                    </div>
                    <form id = "orderApprovalForm" method = "POST" action = "{{ route('web.order.add.approval') }}"
                        enctype = "multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="filter-contentbox">
                                <h6>Status</h6>
                            </div>
                            <div class="row mt-sm-4 mt-3">
                                <div class="col-md-12">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Approval Status</label>
                                        <select class="form-control select-contentbox" id = "approval_status_dropdown"
                                            name = "approval_status">
                                            @foreach ($approvalStatuses as $approvalStatus)
                                                <option value = "{{ $approvalStatus['value'] }}">
                                                    {{ $approvalStatus['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Add Remarks</label>
                                        <textarea class="form-control user-profileinput" name = "remarks" placeholder="Write..." rows="6"></textarea>
                                    </div>

                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="validatedCustomFile2"
                                            multiple name = 'docs[]'
                                            onchange="addFiles(this, 'imgPreview2', 'confiramtionApproval')">
                                        <label class="custom-file-label" for="validatedCustomFile">Upload
                                            Documents</label>
                                    </div>

                                    <div class="row mt-3" id = 'imgPreview2'>

                                    </div>

                                    <div class="mt-sm-4 mt-2 text-right">
                                        <span class="max-filesizetext">Max size 2.0 MB</span>
                                    </div>

                                </div>
                            </div>

                            <input type = "hidden" value = "{{ $order->id }}" name = "order_id">

                            <div class="mt-sm-5 mt-4">
                                <button type="submit" class="btn apply-btn btn-block">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>


    <script>
        let siteApprovalFiles = [];
        let confirmationApprovalFiles = [];

        $(document).ready(function() {
            $('#customers_dropdown').select2({
                placeholder: 'Select Customer'
            });
            $('#projects_dropdown').select2({
                placeholder: 'Select Project'
            });
            $('#projects_sites_dropdown').select2({
                placeholder: 'Select Site'
            });
            $('#mix_codes_dropdown').select2({
                placeholder: 'Select Mix'
            });
            $('#struct_ref_dropdown').select2({
                placeholder: 'Select Structure'
            });
            $('.temp_dropdown').select2({
                placeholder: 'Select Temperature'
            });
            $('.pump_type_dropdown').select2({
                placeholder: 'Select Type'
            });
            $('.pump_size_dropdown').select2({
                placeholder: 'Select Size'
            });
        });


        document.addEventListener('DOMContentLoaded', (event) => {
            const inputField = document.getElementsByClassName('readonlyFields');
            // Function to prevent changes
            const preventChange = (event) => {
                event.preventDefault();
            };
            for (let index = 0; index < inputField.length; index++) {
                // Prevent key presses
                inputField[index].addEventListener('keypress', preventChange);

                // Prevent cut, copy, paste
                inputField[index].addEventListener('cut', preventChange);
                inputField[index].addEventListener('copy', preventChange);
                inputField[index].addEventListener('paste', preventChange);

                // Prevent context menu
                inputField[index].addEventListener('contextmenu', preventChange);

                // Prevent changes via input event
                inputField[index].addEventListener('input', preventChange);
            }
        });

        function siteOnChange(element) {
            document.getElementById('group_company_dropdown').value = "";
            document.getElementById('company_location_dropdown').value = "";
            const selectedOption = element.options[element.selectedIndex];
            fetch("project-sites/get/details/" + selectedOption.value, {
                method: "GET",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
            }).then(response => response.json()).then(data => {
                const response = data.data;
                if (response.site) {
                    document.getElementById('group_company_dropdown').value = response.site.service_company_location
                        ?.group_company_id;
                    document.getElementById('company_location_dropdown').value = response.site.company_location_id;
                }
            }).catch(error => {
                console.log("Error : ", error);
            })
        }

        function mixOnChange(element) {
            fetch("{{ url('customer-products/show') }}" + "/" + element.value, {
                method: "GET",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
            }).then(response => response.json()).then(data => {
                const response = data.data;
                if (response.customer_product && response.customer_product.id) {
                    document.getElementById('mix_name_field').value = response.customer_product.product_name;
                    document.getElementById('mix_type_field').value = response.customer_product.mix_code;
                    document.getElementById('total_quantity_field').value = response.customer_product
                    .total_quantity;
                    document.getElementById('remaining_quantity_field').value = response.customer_product
                        .remaining_quantity;
                }
            }).catch(error => {
                console.log("Error : ", error);
            })
        }

        function cubeMouldOnChange(element) {
            if (element.value) {
                document.getElementById('cubeMouldCheck').checked = true;
            } else {
                document.getElementById('cubeMouldCheck').checked = false;
            }
        }

        function toggleSection(element, sectionId) {
            if (element.checked) {
                document.getElementById(sectionId).classList.remove('hidden_content');
            } else {
                document.getElementById(sectionId).classList.add('hidden_content');
            }
        }

        function createTempControlUI() {
            var existingElements = document.getElementsByClassName("tempCtrlUi");
            var index = (existingElements ? existingElements.length : -1) + 1;
            var newOptions = ``;
            var previousTempOptions = document.getElementById('temp_dropdown_0');
            if (previousTempOptions) {
                newOptions = previousTempOptions.innerHTML;
            }
            var tempDiv = document.createElement('div');
            tempDiv.className = "row mt-sm-3 mt-2 tempCtrlUi";
            tempDiv.id = "tempCtrlUi" + index;
            tempDiv.innerHTML = `
                            <div class="col-md-5">
                                <div class="profileinput-box position-relative">
                                    <label class="selext-label">Temperature (°C)</label>
                                    <select class="form-control select-contentbox reset_from_company temp_dropdown" name = "temp_values[]" id = "temp_dropdown_${index}">
                                        ${newOptions}
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Quantity</label>
                                        <input type="number" id = "temp_qty_${index}" class="form-control user-profileinput" name = "temp_qty[]" placeholder="Enter">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <span class="delete-btn mr-2" onclick = "removeTempUI(${index});">
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                </span>
                                <span class="add-btn" onclick = "createTempControlUI();">
                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                </span>
                            </div>
        `;

            document.getElementById('temp_req_section').appendChild(tempDiv);

            $('.temp_dropdown').select2({
                placeholder: 'Select Temperature'
            });
        }

        function createPumpReqUI() {
            var existingElements = document.getElementsByClassName("pumpReqUi");
            var index = (existingElements ? existingElements.length : -1) + 1;
            var newPumpTypeOptions = ``;
            var previousPumpTypeOptions = document.getElementById('pump_type_dropdown_0');
            if (previousPumpTypeOptions) {
                newPumpTypeOptions = previousPumpTypeOptions.innerHTML;
            }
            var newPumpSizeOptions = ``;
            var previousPumpSizeOptions = document.getElementById('pump_size_dropdown_0');
            if (previousPumpSizeOptions) {
                newPumpSizeOptions = previousPumpSizeOptions.innerHTML;
            }
            var tempDiv = document.createElement('div');
            tempDiv.className = "row mt-sm-3 mt-2 pumpReqUi";
            tempDiv.id = "pumpReqUi" + index;
            tempDiv.innerHTML = `
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="profileinput-box position-relative">
                                        <label class="selext-label">Pump Type</label>
                                        <select class="form-control select-contentbox reset_from_company pump_type_dropdown" name = "pump_types[]" id = "pump_type_dropdown_${index}"   onchange="selectedPumpType(this, ${index})">
                                            ${newPumpTypeOptions}
                                            </select>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="profileinput-box position-relative">
                                    <label class="selext-label">Pump Sizes</label>
                                    <select class="form-control select-contentbox reset_from_company pump_size_dropdown" name = "pump_sizes[]" id = "pump_size_dropdown_${index}">
                                            ${newPumpSizeOptions}
                                        </select>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                    <div class="form-group">
                                        <div class="profileinput-box form-group position-relative">
                                            <label class="selext-label">No. of Pumps</label>
                                            <input type="number" id = "no_of_pumps_${index}" name = "no_of_pumps[]" class="form-control user-profileinput" placeholder="Enter">
                                        </div>
                                    </div>
                            </div>
                            <div class="col-md-2">
                                    <div class="form-group">
                                        <div class="profileinput-box form-group position-relative">
                                            <label class="selext-label">No. of Pipes</label>
                                            <input type="number" id = "no_of_pipes_${index}" name = "no_of_pipes[]" class="form-control user-profileinput" placeholder="Enter">
                                        </div>
                                    </div>
                            </div>
                            <div class="col-md-2">
                                <span class="delete-btn mr-2" onclick = "removePumpUI(${index});">
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                </span>
                                <span class="add-btn" onclick = "createPumpReqUI();">
                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                </span>
                            </div>
        `;
            document.getElementById('pump_req_section').appendChild(tempDiv);

            $('.pump_size_dropdown').select2({
                placeholder: 'Select Size'
            });
            $('.pump_type_dropdown').select2({
                placeholder: 'Select Type'
            });

        }

        function removeTempUI(index) {
            if (index == 0) {
                return;
            } else {
                const elementToBeRemoved = document.getElementById('tempCtrlUi' + index);
                if (elementToBeRemoved) {
                    elementToBeRemoved.remove();
                }
            }
        }

        function removePumpUI(index) {
            if (index == 0) {
                return;
            } else {
                const elementToBeRemoved = document.getElementById('pumpReqUi' + index);
                if (elementToBeRemoved) {
                    elementToBeRemoved.remove();
                }
            }
        }

        function addFiles(element, id, type) {

            const input = element;
            const files = input.files;

            const selectedFilesDiv = element;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];

                // Only process image files

                const tempDiv = document.createElement('div');
                tempDiv.className = "col-md-2";
                const reader = new FileReader();
                reader.onload = function(e) {
                    var htmlData = ``;

                    if (!file.type.startsWith('image/')) {
                        htmlData = `
                        <div class="col-md-2">
                            <div class="upload-image">
                                <img src="{{ asset('assets/img/attachment.svg') }}" alt="">
                                <span class="upload-imagecross"><i class="fa fa-times" aria-hidden="true"></i></span>
                            </div>
                        </div>
                    `;
                    } else {
                        htmlData = `
                        <div class="col-md-2">
                            <div class="upload-image">
                                <img src="${e.target.result}" alt="">
                                <span class="upload-imagecross"><i class="fa fa-times" aria-hidden="true"></i></span>
                            </div>
                        </div>
                    `;
                    }

                    tempDiv.innerHTML = htmlData;
                    const element = document.getElementById(id);
                    element.appendChild(tempDiv);
                }

                reader.readAsDataURL(file);

            }

        }

        function setApproveDropdown(type) {
            document.getElementById('approval_status_dropdown').value = type;
        }



        function selectedPumpType(element, index) {

            console.log(element.value);
            const companyId = document.getElementById('group_company_dropdown').value;
            changeDropdownOptions(element, ["pump_size_dropdown_" + index], ['pump_sizes'], '/settings/pumps/get-size/',
                null, ['pump_size_dropdown_' + index], companyId, "group_company_id")

        }
    </script>
@endsection
