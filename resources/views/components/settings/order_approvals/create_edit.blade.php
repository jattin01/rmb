@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="px-sm-4">
        <form action="/settings/approvals/orders/store" role="post-data" method="POST" redirect="/settings/approvals/orders">
                    @csrf
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-3 col-8 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Approval Setup</h1>
                            
                            <h6><span class="active"> Order </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Workflow </h6>
                        </div>
                    </div>
    
                    <div class="col-md-3 col-4 text-right">
                        <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn apply-btn new-btn mr-3">Submit</button>
                        <a href="{{route('settings.orderApproval.index')}}" class="btn back-btn">Back</a>
                    </div>
                </div>
                <div class="batching-plantaddbox mt-sm-4 mt-3">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    @if (isset($setup))
                                        <input type = "hidden" name = "approval_setup_id" value = "{{$setup -> id}}" />
                                    @endif
                                    <div class="profileinput-box form-group position-relative {{isset(request() -> approval_setup_id) ? 'readonlyFieldsContainer' : ''}}">
                                        <label class="selext-label">Company</label>
                                        <select class="form-control select-contentbox {{isset(request() -> approval_setup_id) ? 'readonlyFields' : 'main_dropdown'}}" name = "group_company_id" onchange = "companyOnChange(this);">
                                            @foreach($groupCompanies as $company)
                                            <option value="{{$company->value}}" {{isset($setup) && $setup -> group_company_id === $company -> value ? 'selected' : ''}}>{{$company->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative {{isset(request() -> approval_setup_id) ? 'readonlyFieldsContainer' : ''}}">
                                        <label class="selext-label">Location</label>
                                        <select class="form-control select-contentbox {{isset(request() -> approval_setup_id) ? 'readonlyFields' : 'main_dropdown'}}" id = "company_location_dropdown" name = "location_id" onchange = "locationOnChange(this)">
                                            <option value = "">Select</option>
                                            @foreach($locations as $location)
                                            <option value="{{$location->value}}" {{isset($setup) && $setup -> location_id === $location -> value ? 'selected' : ''}}>{{$location->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class = "col-md-6">
                                    <div class="filter-check mb-3">
                                        <label class="temperature-label">
                                            Approval Levels
                                        </label>
                                    </div>
                                </div>
                                <div class = "col-md-6"></div>
                                <div class = "col-md-12" id = "level_ui_section">
                                @if (isset($setup))
                                @forelse ($setup -> levels as $levelKey => $level)
                                <div id = "levels_ui_{{$levelKey}}" class = "row levelsUi">
                                <div class="col-md-1">
                                    <h6 class = "mt-3 ml-3">
                                        {{$level -> level_no}}
                                    </h6>
                                </div>
                                <div class="col-md-6">
                                    @php
                                        $userIds = $level -> users -> pluck('user_id');
                                        $userIds = $userIds -> toArray();
                                    @endphp
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Users</label>
                                        <select class="form-control select-contentbox main_dropdown" multiple = "multiple" id = "levels_users_{{$levelKey}}" name = "level_{{$level -> level_no}}_users[]">
                                            @foreach($users as $user)
                                            <option value="{{$user->value}}" {{in_array($user -> value, $userIds) ? 'selected' : ''}}>{{$user->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
    
                                <div class="col-md-3">
                                    <div class="profileinput-box form-group position-relative">
                                    <label class="selext-label">Type</label>
                                        <select class="form-control select-contentbox main_dropdown_spaced" name = "level_types[]">
                                            @foreach($types as $type)
                                            <option value="{{$type}}" {{$level -> type === $type ? 'selected' : ''}}>{{$type}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <span class="delete-btn mr-2 {{$levelKey === 0 ? 'hidden_content' : ''}}" onclick = "removeLevelUi('{{$levelKey}}')">
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </span>
                                    <span class="add-btn" onclick = "addLevelUi()">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </span>
                                </div>
                                </div>
                                @empty
                                    <h6 class = "mt-3 ml-3">
                                        No Levels Found
                                    </h6>
                                @endforelse
                                @else
                                
                                    <h6 class = "mt-3 ml-3">
                                        Select a Location first to create Levels
                                    </h6>
                                
                                @endif
                                </div>
                            </div>
    
                        </div>
                        <div class="col-md-4 mb-2 mb-sm-0">
                            <div class="active-switch d-flex justify-content-end align-items-center">
                                <label class="switch mr-2">
                                    <input type="checkbox" id="setup_staus" name="setup_status"
                                        value="{{ @$setup->status ?? 'Active' }}" class="activeclass"
                                        onclick="{{ @$setup->status == 'Inactive' || !isset($setup) ? 'active' : 'inactive' }}Staus('setup_staus', 'setup_status')"
                                        {{ @$setup->status == 'Inactive'? '' : 'checked' }} />
                                    <div class="slider round">
                                        <span
                                            class="{{ @$setup->status == 'Inactive' ? 'swinactive' : 'swactive' }}">
                                        </span>
                                    </div>
                                </label>
                                <p id="setup_status">{{ @$setup->status == 'Inactive' ? 'Inactive' : 'Active' }}</p>
                            </div>
                        </div>
                        
                        
                    </div>
                    </form>
                    
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>

    $(document).ready(function() {
        $('.main_dropdown').select2({
            allowClear : true,
            placeholder: 'Select'
        });
        $('.main_dropdown_spaced').select2({
            allowClear : true,
            placeholder: 'Select',
            dropdownCssClass : 'spaced_dropdown',
            selectionCssClass : 'spaced_dropdown'
        });
    });

    function removeLevelUi(index)
    {
        if (index == 0) {
            return;
        } else {
            const elementToBeRemoved = document.getElementById('levels_ui_' + index);
            if (elementToBeRemoved) {
                elementToBeRemoved.remove();
            }
        }
    }

    function addLevelUi()
    {
        var existingElements = document.getElementsByClassName("levelsUi");
        var index = (existingElements ? existingElements.length : 0);
        var newOptions = ``;
        var previousUserOptions = document.getElementById('levels_users_0');
        if (previousUserOptions) {
            newOptions = previousUserOptions.innerHTML;
        }
        var tempDiv = document.createElement('div');
        tempDiv.className = "row levelsUi";
        tempDiv.id = "levels_ui_" + index;
        tempDiv.innerHTML = `
                                <div class="col-md-1">
                                    <h6 class = "mt-3 ml-3">
                                        ${index + 1}
                                    </h6>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Users</label>
                                        <select class="form-control select-contentbox main_dropdown" multiple = "multiple" name = "level_${index + 1}_users[]" id = "levels_users_${index}">
                                            ${newOptions}
                                        </select>
                                    </div>
                                </div>
    
                                <div class="col-md-3">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Type</label>
                                        <select class="form-control select-contentbox main_dropdown_spaced" name = "level_types[]">
                                            @foreach($types as $type)
                                            <option value="{{$type}}">{{$type}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <span class="delete-btn mr-2" onclick = "removeLevelUi(${index})">
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </span>
                                    <span class="add-btn" onclick = "addLevelUi()">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </span>
                                </div>
                            `;

        document.getElementById('level_ui_section').appendChild(tempDiv);

        $('.main_dropdown').select2({
            placeholder: 'Select'
        });
        $('.main_dropdown_spaced').select2({
            allowClear : true,
            placeholder: 'Select',
            dropdownCssClass : 'spaced_dropdown',
            selectionCssClass : 'spaced_dropdown'
        });

        $(`#levels_users_${index}`).val(null).trigger('change');

    }

    function addDefaultLevel()
    {
        const MainUi = `
        <div id = "levels_ui_0" class = "row levelsUi">
                                <div class="col-md-1">
                                    <h6 class = "mt-3 ml-3">
                                        1
                                    </h6>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Users</label>
                                        <select class="form-control select-contentbox main_dropdown" id = "levels_users_0" multiple = "multiple" name = "level_1_users[]">
                                            @foreach($users as $user)
                                            <option value="{{$user->value}}">{{$user->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
    
                                <div class="col-md-3">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Type</label>
                                        <select class="form-control select-contentbox main_dropdown_spaced" name = "level_types[]">
                                            @foreach($types as $type)
                                            <option value="{{$type}}">{{$type}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <span class="delete-btn mr-2 hidden_content">
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </span>
                                    <span class="add-btn" onclick = "addLevelUi();">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </span>
                                </div>
                                </div>
        `;
        document.getElementById('level_ui_section').innerHTML = MainUi;
        $('.main_dropdown').select2({
            placeholder: 'Select'
        });
        $('.main_dropdown_spaced').select2({
            allowClear : true,
            placeholder: 'Select',
            dropdownCssClass : 'spaced_dropdown',
            selectionCssClass : 'spaced_dropdown'
        });
    }

    function removeAllLevels()
    {
        document.getElementById('level_ui_section').innerHTML = ``;
    }

    function companyOnChange(element)
    {
        removeAllLevels();
        changeDropdownOptions(element, ['company_location_dropdown'], ['company_locations'] , '/group-company/get/locations/');

    }

    function locationOnChange(element)
    {
        addDefaultLevel();
        changeDropdownOptions(element, ['levels_users_0'], ['users'] , '/settings/locations/get/users/');
        
    }

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