@extends('layouts.auth.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <form action="/store-order" role="post-data" method="post">
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
                            <button onclick="goBackWithReload();" type="button" class="btn back-btn">Back</button>
                        </div>
                    </div>

                    <div class="row mt-sm-4 mt-2  align-items-center">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="project-details">Project Details</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group ">

                                        <div class="profileinput-box  position-relative">
                                            <label class="selext-label">Customer</label>
                                            <select id = "customers_dropdown" name = "customer_id"
                                                class="form-control select-contentbox"
                                                onchange = "changeDropdownOptions(this, ['projects_dropdown'], ['customer_projects'] , '/customer-projects/get/', null, ['projects_dropdown', 'projects_sites_dropdown', 'mix_codes_dropdown'])">
                                                <option>Select</option>
                                                @forelse ($customers as $customer)
                                                    {{-- <option value = "{{$customer -> value}}"> {{$customer -> label}}</option> --}}
                                                    <option value="{{ $customer->value }}"
                                                        {{ old('customer_id') == $customer->value ? 'selected' : '' }}>
                                                        {{ $customer->label }}
                                                    </option>
                                                @empty
                                                @endforelse
                                            </select>
                                        </div>
                                        @if ($errors->has('customer_id'))
                                            <span class="text-danger">{{ $errors->first('customer_id') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="profileinput-box position-relative">
                                            <label class="selext-label">Project Name</label>
                                            <select id = "projects_dropdown" class="form-control select-contentbox"
                                                name = "project_id"
                                                onchange = "changeDropdownOptions(this, ['projects_sites_dropdown', 'mix_codes_dropdown'], ['project_sites', 'mix_codes'] , '/project-sites/get/', null, ['projects_sites_dropdown'])">
                                                <option value = "">Select</option>
                                                @forelse ($customerProjects as $customerProject)
                                                    {{-- <option value = "{{$customerProject -> value}}"> {{$customerProject -> label}}</option> --}}
                                                    <option value="{{ $customerProject->value }}"
                                                        {{ old('project_id') == $customerProject->value ? 'selected' : '' }}>
                                                        {{ $customerProject->label }}

                                                    @empty
                                                @endforelse
                                            </select>
                                        </div>
                                        @if ($errors->has('project_id'))
                                            <span class="text-danger">{{ $errors->first('project_id') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">

                                        <div class="profileinput-box  position-relative">
                                            <label class="selext-label">Project Site</label>
                                            <select class="form-control select-contentbox" id = "projects_sites_dropdown"
                                                name = "site_id" onchange = "siteOnChange(this)">
                                                <option value = " ">Select</option>
                                                @forelse ($customerProjectSites as $customerProjectSite)
                                                    {{-- <option value = "{{$customerProjectSite -> value}}"> {{$customerProjectSite -> label}}</option> --}}
                                                    <option value="{{ $customerProjectSite->value }}"
                                                        {{ old('site_id') == $customerProjectSite->value ? 'selected' : '' }}>
                                                        {{ $customerProjectSite->label }}
                                                    @empty
                                                @endforelse
                                            </select>
                                        </div>
                                        @if ($errors->has('site_id'))
                                            <span class="text-danger">{{ $errors->first('site_id') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Company Name</label>
                                        <select id = 'group_company_dropdown'
                                            class="form-control select-contentbox readonlyFields">
                                            <option value = "0"></option>
                                            @forelse ($groupCompanies as $groupCompany)
                                                <option value = "{{ $groupCompany->value }}">
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
                                            <option value = "0"></option>
                                            @forelse ($companyLocations as $groupCompanyLocation)
                                                <option value = "{{ $groupCompanyLocation->value }}">
                                                    {{ $groupCompanyLocation->label }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">

                                        <div class="profileinput-box  position-relative">
                                            <label class="selext-label">Delivery Date</label>
                                            <input type="date" min = "{{ Carbon\Carbon::now()->format('Y-m-d') }}"
                                                name = "delivery_date" class="form-control user-profileinput">
                                        </div>
                                        @if ($errors->has('delivery_date'))
                                            <span class="text-danger">{{ $errors->first('delivery_date') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group ">
                                        <div class="profileinput-box position-relative">
                                            <label class="selext-label">Delivery Time</label>
                                            <input type="time" name = "delivery_time"
                                                class="form-control user-profileinput">
                                        </div>
                                        @if ($errors->has('delivery_time'))
                                            <span class="text-danger">{{ $errors->first('delivery_time') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Interval</label>
                                        <input type="number" name = "interval" class="form-control user-profileinput"
                                            placeholder="Enter">
                                    </div>
                                    @if ($errors->has('interval'))
                                        <span class="text-danger">{{ $errors->first('interval') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="row">
                                <div class="col">
                                    <label class="project-details">Site Readyness</label>
                                </div>
                            </div>
                            <div class="order-sitereadynessbox" style = "min-height: 18rem !important;">
                                <div class="order-sitereadynesscontentbox">
                                    <div class="card-body">

                                        <div class="row align-items-center mt-4">
                                            <div class="col text-center">
                                                @include('partials.details_not_found_left')
                                                <div class="mt-2">Details not available</div>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- <div class="row">
                        <div class="col-md-6">
                            <label class="project-details">Structure Details</label>
                        </div>
                    </div> --}}
                    {{-- <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="profileinput-box position-relative">
                                <label class="selext-label">Structure Ref.</label>
                                <select class="form-control select-contentbox reset_from_company"
                                    id = "struct_ref_dropdown" name = "structural_reference_id">
                                    <option value = "">Select</option>
                                    @forelse ($structuralReferences as $structuralReference)
                                        <option value="{{ $structuralReference->value }}"
                                            {{ old('structural_reference_id') == $structuralReference->value ? 'selected' : '' }}>
                                            {{ $structuralReference->label }}

                                        @empty
                                    @endforelse
                                </select>
                            </div>
                            @if ($errors->has('structural_reference_id'))
                                <span class="text-danger">{{ $errors->first('structural_reference_id') }}</span>
                            @endif
                        </div>

                    </div> --}}
                    <div class="row mt-sm-4 mt-2  align-items-center">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="project-details">Mix Details</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="profileinput-box  position-relative">
                                            <label class="selext-label">Mix Code</label>
                                            <select id = 'mix_codes_dropdown' name = "id"
                                                class="form-control select-contentbox" onchange = "mixOnChange(this)">
                                                <option value = "">Select</option>
                                                @forelse ($customerProjectProducts as $customerProjectProduct)
                                                    <option value="{{ $customerProjectProduct->value }}"
                                                        {{ old('id') == $customerProjectProduct->value ? 'selected' : '' }}>
                                                        {{ $customerProjectProduct->label }}
                                                        {{-- <option value = "{{$customerProjectProduct -> value}}"> {{$customerProjectProduct -> label}}</option> --}}
                                                    @empty
                                                @endforelse
                                            </select>
                                        </div>

                                        @if ($errors->has('id'))
                                            <span class="text-danger">{{ $errors->first('id') }}</span>
                                        @endif
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="profileinput-box form-group position-relative">
                                            <label class="selext-label">Quantity (in CUM)</label>
                                            <input type="number" name = "quantity" id = "ordered_quantity_field"
                                                class="form-control user-profileinput" placeholder="Enter quantity">
                                        </div>
                                        @if ($errors->has('quantity'))
                                            <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Mix Name</label>
                                        <input type="text" id = "mix_name_field"
                                            class="form-control user-profileinput readonlyFields">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Mix Type</label>
                                        <input type="text" id = "mix_type_field"
                                            class="form-control user-profileinput readonlyFields">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Total Qty (in CUM)</label>
                                        <input type="number" id = "total_quantity_field"
                                            class="form-control user-profileinput readonlyFields">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative readonlyFieldsContainer">
                                        <label class="selext-label">Remaining Qty (in CUM)</label>
                                        <input type="number" id = "remaining_quantity_field"
                                            class="form-control user-profileinput readonlyFields">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="row">
                                <div class="col">
                                    <label class="project-details">Approval History</label>
                                </div>
                            </div>
                            <div class="order-sitereadynessbox" style = "max-height: 13rem !important;">
                                <div class="order-sitereadynesscontentbox">
                                    <div class="card-body">

                                        <div class="row align-items-center mt-4">
                                            <div class="col text-center">
                                                @include('partials.details_not_found_right')
                                                <div class="mt-2">Details not available</div>
                                            </div>
                                        </div>

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
                                            id = "struct_ref_dropdown" name = "structural_reference_id">
                                            <option value = "">Select</option>
                                            @forelse ($structuralReferences as $structuralReference)
                                                <option value="{{ $structuralReference->value }}"
                                                    {{ old('structural_reference_id') == $structuralReference->value ? 'selected' : '' }}>
                                                    {{ $structuralReference->label }}

                                                @empty
                                            @endforelse
                                        </select>
                                    </div>
                                    @if ($errors->has('structural_reference_id'))
                                        <span class="text-danger">{{ $errors->first('structural_reference_id') }}</span>
                                    @endif
                                </div>

                            </div>

                            <div class="filter-check mt-sm-4">
                                <input type="checkbox" class="filled-in" name = "is_tech_required"
                                    id="techRequiredCheck">
                                <label class="temperature-label" for="techRequiredCheck">
                                    Technician Required ?
                                </label>
                            </div>


                            <div class="filter-check mt-sm-4">
                                <input type="checkbox" onchange="toggleSection(this, 'temp_req_section')"
                                    class="filled-in" id="tempCheck" name = "is_temp_required">
                                <label class="temperature-label" for="tempCheck">
                                    Temperature Control Required ?
                                </label>
                            </div>

                            <div id = "temp_req_section" class = "hidden_content">
                                <div class="row mt-sm-3 mt-2 tempCtrlUi0">
                                    <div class="col-md-5">
                                        <div class="profileinput-box position-relative">
                                            <label class="selext-label">Temperature (°C)</label>
                                            <select class="form-control select-contentbox reset_from_company temp_dropdown"
                                                id = "temp_dropdown_0" name = "temp_values[]">
                                                <option value = "0">Select</option>
                                                @forelse ($temps as $temp)
                                                    <option value = "{{ $temp->value }}"> {{ $temp->label }}</option>
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
                            </div>

                            <div class="filter-check mt-sm-3">
                                <input type="checkbox" class="filled-in" id="pumpCheck"
                                    onchange="toggleSection(this, 'pump_req_section')" name = "is_pump_required">
                                <label class="temperature-label" for="pumpCheck">
                                    Pump Required ?
                                </label>
                            </div>

                            <div id = "pump_req_section" class = "hidden_content">
                                <div class="row mt-sm-3 mt-2 pumpReqUi0">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <div class="profileinput-box position-relative">
                                                <label class="selext-label">Pump Type</label>
                                                <select
                                                    class="form-control select-contentbox reset_from_company pump_type_dropdown"
                                                    id = "pump_type_dropdown_0" name = "pump_types[]" onchange="selectedPumpType(this,0)">
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
                                                    <option value = "{{ $pumpSize->value }}"> {{ $pumpSize->label }}
                                                    </option>
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
                            </div>

                            <div class="filter-check mt-sm-3">
                                <input type="checkbox" class="filled-in" id="cubeMouldCheck"
                                    name = "is_cube_mould_required"
                                    onchange="toggleSection(this, 'cube_mould_req_section')">
                                <label class="temperature-label" for="cubeMouldCheck">
                                    Cube Mould Required ?
                                </label>
                            </div>
                            <div id = "cube_mould_req_section" class="row mt-sm-3 mt-2 hidden_content">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="profileinput-box form-group position-relative">
                                            <label class="selext-label">No. of Moulds</label>
                                            <input type="number" name = "cube_mould_req_quantity"
                                                oninput="cubeMouldOnChange(this)" class="form-control user-profileinput"
                                                placeholder="Enter">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div><input type="hidden" name="checkdistance"></div>
                    <button type="submit" data-request="ajax-submit" data-target="[role=post-data]"
                        class="btn save-btn mt-4">Submit</button>

            </form>

        </div>

        <div class="modal fade filter-modal" id="filter" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <img src="{{ asset('assets/img/filter-close.svg') }}" alt=""> close
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="filter-contentbox">
                            <h6>Status</h6>
                        </div>
                        <div class="row mt-sm-4 mt-3">
                            <div class="col-md-12">
                                <div class="profileinput-box form-group position-relative">
                                    <label class="selext-label">Approval Status</label>
                                    <select class="form-control select-contentbox">
                                        <option>Approve</option>
                                        <option>RMB Dubai</option>
                                        <option>RMB Dubai</option>
                                        <option>RMB Dubai</option>
                                        <option>RMB Dubai</option>
                                    </select>
                                </div>
                                <div class="profileinput-box form-group position-relative">
                                    <label class="selext-label">Add Remarks</label>
                                    <textarea class="form-control user-profileinput" placeholder="Write..." rows="6"></textarea>
                                </div>

                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="validatedCustomFile" required>
                                    <label class="custom-file-label" for="validatedCustomFile">Upload Documents</label>
                                </div>

                                <div class="mt-sm-4 mt-2 text-right">
                                    <span class="max-filesizetext">Max size 2.0 MB</span>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-2">
                                        <div class="upload-image">
                                            <img src="{{ asset('assets/img/course2.png') }}" alt="">
                                            <span class="upload-imagecross"><i class="fa fa-times"
                                                    aria-hidden="true"></i></span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="mt-sm-5 mt-4">
                            <button type="button" class="btn apply-btn btn-block">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <script>
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

            // Automatically open the dropdown when it gains focus
            $(document).on('focus', '.select2-selection--single', function() {
                $(this).closest('.select2-container').siblings('select:enabled').select2('open');
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
//Duplicate function definition , removed by ANKIT - 24/12/24
        // function siteOnChange(element) {
        //     console.log('ab');
        //     document.getElementById('group_company_dropdown').value = "";
        //     document.getElementById('company_location_dropdown').value = "";
        //     const selectedOption = element.options[element.selectedIndex];
        //     fetch("project-sites/get/details/" + selectedOption.value, {
        //         method: "GET",
        //         headers: {
        //             'Content-Type': 'application/json',
        //             'X-CSRF-TOKEN': '{{ csrf_token() }}'
        //         },
        //     }).then(response => response.json()).then(data => {
        //         const response = data.data;
        //         console.log(response);return false;
        //         if (response.site) {
        //             document.getElementById('group_company_dropdown').value = response.site
        //             .service_group_company_id;
        //             document.getElementById('company_location_dropdown').value = response.site.company_location_id;
        //             changeDropdownOptions(document.getElementById('group_company_dropdown'), ['struct_ref_dropdown',
        //                 {
        //                     type: 'class',
        //                     value: 'temp_dropdown'
        //                 }, {
        //                     type: 'class',
        //                     value: 'pump_type_dropdown'
        //                 }, {
        //                     type: 'class',
        //                     value: 'pump_size_dropdown'
        //                 }], ['structural_references', 'temps', 'pump_types', 'pump_sizes'],
        //                 '/get/order-creation-data/', 'reset_from_company');
        //         }
        //     }).catch(error => {
        //         console.log("Error : ", error);
        //     })
        // }

        function structureOnChange(element) {
            const structureId = element.value;
            changeDropdownOptions(document.getElementById('projects_dropdown'), ['mix_codes_dropdown'], [ 'mix_codes'] , '/project-sites/get/', null, ['mix_codes_dropdown'],structureId,"structure_id")
        }


        function siteOnChange(element) {
            // console.log('a');
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
                // console.log(response);return false;
                if (response.site) {
                    document.getElementById('group_company_dropdown').value = response.site
                    .service_group_company_id;
                    document.getElementById('company_location_dropdown').value = response.site.company_location_id;
                    changeDropdownOptions(document.getElementById('group_company_dropdown'), ['struct_ref_dropdown',
                        {
                            type: 'class',
                            value: 'temp_dropdown'
                        }, {
                            type: 'class',
                            value: 'pump_type_dropdown'
                        }, {
                            type: 'class',
                            value: 'pump_size_dropdown'
                        }], ['structural_references', 'temps', 'pump_types', 'pump_sizes'],
                        '/get/order-creation-data/', 'reset_from_company');
                }
            }).catch(error => {
                console.log("Error : ", error);
            })
        }

        function mixOnChange(element) {
            fetch("customer-products/show/" + element.value, {
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
            var index = (existingElements ? existingElements.length : 0) + 1;
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
            var index = (existingElements ? existingElements.length : 0) + 1;
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
                                        <select class="form-control select-contentbox reset_from_company pump_type_dropdown" name = "pump_types[]" id = "pump_type_dropdown_${index}" onchange="selectedPumpType(this, ${index})" >
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

        function selectedPumpType(element, index){

            console.log(element.value);
             const companyId = document.getElementById('group_company_dropdown').value;
            changeDropdownOptions(element, ["pump_size_dropdown_"+index], [ 'pump_sizes'] , '/settings/pumps/get-size/', null, ['pump_size_dropdown_'+index],companyId,"group_company_id")

        }
    </script>
@endsection
