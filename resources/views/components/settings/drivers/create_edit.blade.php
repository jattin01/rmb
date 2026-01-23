@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="px-sm-4">
            <form action="/settings/drivers/store" role="post-data" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-3 col-8 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Driver</h1>
                            @if(isset($driver))
                            <h6><span class="active"> Driver Details </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Edit </h6>
                            @else
                            <h6><span class="active"> Driver Details </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Create </h6>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3 col-4 text-right">
                        <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn apply-btn new-btn mr-3">Submit</button>
                        <a href="{{route('settings.drivers.index')}}" class="btn back-btn">Back</a>
                    </div>
                </div>
                <input type="hidden" name="driver_id" value="{{@$driver->id}}">
                <div class="batching-plantaddbox mt-sm-4 mt-3">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Company</label>
                                        <select class="form-control select-contentbox"  name="group_company_id">
                                            @foreach($groupCompanies as $company)
                                            <option value="{{$company->value}}" @if(isset($driver) && ($driver->group_company_id == $company->value)) selected @endif>{{$company->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Role</label>
                                        <select class="form-control select-contentbox" id="user_role" name="user_role">
                                            <option value="driver" {{ @$driver->user_role == 'driver' ? 'selected' : '' }}>Driver</option>
                                            <option value="operator" {{ @$driver->user_role == 'operator' ? 'selected' : '' }}>Operator</option>
                                        </select>
                                    </div>
                                </div>


                                {{-- <div class="col-md-6"></div> --}}
                                <!-- <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Driver Code</label>
                                        <input type="text" name="code" value="{{@$driver->code}}" class="form-control user-profileinput" placeholder="Enter Code">
                                    </div>
                                </div> -->
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Driver Name</label>
                                        <input type="text" name="name" value="{{@$driver->name}}" class="form-control user-profileinput" placeholder="Enter Name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Employee Code</label>
                                        <input type="text" name="employee_code" value="{{@$driver->employee_code}}" class="form-control user-profileinput" placeholder="Enter Code">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">License No</label>
                                        <input type="text" name="license_no" value="{{@$driver->license_no}}" class="form-control user-profileinput" placeholder="Enter Number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">License Expiry</label>
                                        <input type="date" min = "{{Carbon\Carbon::now() -> format('Y-m-d')}}"  name="license_expiry" value="{{@$driver->license_expiry}}" class="form-control user-profileinput">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Email</label>
                                        <input type="text" name="email_id" value="{{@$driver->email_id}}" class="form-control user-profileinput" placeholder="Enter Email">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Phone No.</label>
                                        <input type="text" name="phone" value="{{@$driver->phone}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Username</label>
                                        <input type="text" name="username" value="{{@$driver->username}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>

                            </div>

                        </div>
                        <div class="col-md-4 mb-2 mb-sm-0">
                           

                            <div class="active-switch d-flex justify-content-end align-items-center">
                                <label class="switch mr-2">
                                    <input type="checkbox" id="mixer_status_toggle" name="driver_status"
                                        value="{{ @$driver
                                            ->status ?? 'Active' }}" class="activeclass"
                                        onclick="toggleStatus(this, 'driver_status')"
                                        @if(@$driver->status == 'Active' || !isset($driver)) checked @endif/>
                                    <div class="slider round">
                                        <span class="{{ @$driver->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                    </div>
                                </label>
                                <p id="driver_status">{{@$driver->status ? $driver->status : 'Active'}}</p>
                            </div>
                            <label class="company-logolabel">Driver Photo</label>
                            <div class="darg-dropbox text-center" id="drop-area">
                                <h6>Drag &amp; Drop OR Upload</h6>
                                <p>Max size: 05 MB</p>
                                <div class="upload-btn-wrapper new-browsebtn mt-3">
                                    <button class="uploadBtn">
                                        Browse
                                    </button>
                                    <input type="file" name="image" id="projectImage" onchange="renderProjectImage(this,'preview_image','preview_image_link')">
                                </div>
                            </div>

                            <div class="drag-dropimgbox mt-3" style="display: {{ isset($driver->user -> profile_icon) ? 'block' : 'none'}};">
                                <span class="red-crossimgbox" style="cursor:pointer;">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </span>
                                <a id="preview_image_link" href="{{ @$driver->user -> profile_icon ?  @$driver->user -> profile_icon : 'javascript:void(0)' }}" target="_blank">
                                <img src="{{@$driver->user -> profile_icon ? @$driver->user -> profile_icon : asset('assets/img/course2.png')}}" alt="preview image" id="preview_image">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
@section('scripts')
<script>
    $(document).ready(function() {
        $('#companies_dropdown').select2({
            placeholder: 'Select Company'
        });
    });

    function activeStaus(id,field) {
        console.log(typeof(id), field);
        if(typeof(id) == 'object' && typeof(field) == 'object'){
            id = id.id;
            field = field.name;
        }
        var status = 'active';
        document.getElementById(id).setAttribute('onclick', `inactiveStaus(${id}, ${field})`);
        $(".slider span").addClass("swactive");
        $(`#${field}`).html('Inactive');
        $(`input[name='${field}']`).val('Inactive');

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
        $(`#${field}`).html('Active');
        $(`input[name='${field}']`).val('Active');

    }

    // drag-drop feature
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('projectImage');
     // Highlight drop area when item is dragged over it
     ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropArea.classList.add('highlight');
    }

    function unhighlight(e) {
        dropArea.classList.remove('highlight');
    }

    // Handle dropped files
    dropArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        console.log("e:",e);
        const dt = e.dataTransfer;
        const files = dt.files;

        handleFiles(files);
    }

    function handleFiles(files) {
        // console.log("files:",files);
        ([...files]).forEach(uploadFile);
        appendFilesToFileInput(files);
    }

    function uploadFile(file) {
        console.log(file);
        const reader = new FileReader();

        reader.onload = function(event) {
            const imgElement = document.getElementById('preview_image');
            imgElement.src = event.target.result;
            imgElement.style.display = 'block';
            $('.drag-dropimgbox').show();
        };

        reader.readAsDataURL(file);
    }

    function appendFilesToFileInput(files) {
        const dataTransfer = new DataTransfer();

        for (let i = 0; i < files.length; i++) {
            dataTransfer.items.add(files[i]);
        }

        fileInput.files = dataTransfer.files;
    }

    function renderProjectImage(input, render_place_id, render_link_id = null) {
        if (input.files && input.files[0]) {
            var fileType = input.files[0].type;
            var size = input.files[0].size;

            if(((size / 1024)/1024) > 5){
                input.value = '';
                $('#' + render_place_id + '').attr('src', '');
                Swal.fire(
                    'Warning!', 'You can upload maximum of 5 MB file.<br>Kindly select again.', 'warning',{
                        html: true,
                });
            }

            if (fileType !== 'image/jpeg' && fileType !== 'image/png' && fileType !== 'image/jpg') {
                // Show SweetAlert alert for invalid file type
                $('#' + render_place_id + '').attr('src', '');
                Swal.fire({
                    type: 'error',
                    title: 'Invalid File Type',
                    text: 'Please upload files with extensions jpeg, png or jpg.',
                });

                // Clear the file input
                input.value = '';
                return;
            }

            var reader = new FileReader();

            reader.onload = function (e) {
                $('#' + render_place_id + '').attr('src', e.target.result);
                $('#' + render_place_id + '').show();
                if(render_link_id){
                    $('#' + render_link_id + '').attr('href', e.target.result);
                    $('#' + render_link_id + '').attr('target', '_blank');
                    $('#' + render_link_id + '').show();
                }

            }

            reader.readAsDataURL(input.files[0]);
            $('.drag-dropimgbox').show();
        }
    }

    $('.red-crossimgbox').click(function(){
        $("#preview_image").attr('src',"");
        $("#projectImage").val("");
        $(".drag-dropimgbox").hide();

    })


    function toggleStatus(checkbox, field) {
        var statusText = document.getElementById(field);
        var currentStatus = checkbox.checked ? 'Active' : 'Inactive';

        if (currentStatus === 'Active') {
            statusText.innerHTML = 'Active';
            $(checkbox).next(".slider").find("span").removeClass("swinactive").addClass("swactive");
        } else {
            statusText.innerHTML = 'Inactive';
            $(checkbox).next(".slider").find("span").removeClass("swactive").addClass("swinactive");
        }

        $(`input[name='${field}']`).val(currentStatus);
    }
</script>
@endsection
