@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <!-- Form start-->
            <form action="/customers/store" role="post-data" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-3 col-8 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Customers</h1>
                            @if(isset($customer))
                            <h6><span class="active"> Registered Customer </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Edit Customer </h6>
                            @else
                            <h6><span class="active"> Registered Customer </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Create New </h6>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3 col-4 text-right">
                        <!-- <button type="button" class="btn save-btn mr-3">Save</button>     -->
                        <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn new-btn mr-3">Submit</button>
                        <a href="{{route('customers.index')}}" class="btn back-btn">Back</a>
                    </div>
                </div>
                <input type="hidden" name="customerId" value="{{@$customer->id}}">
                <div class="batching-plantaddbox mt-sm-4 mt-3">
                    <div class="row">

                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Customer Code</label>
                                        <input type="text" name="code" value="{{@$customer->code}}" class="form-control user-profileinput" placeholder="Enter Code">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Customer Name</label>
                                        <input type="text" name="name" value="{{@$customer->name}}" class="form-control user-profileinput" placeholder="Enter Name">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Contact Person</label>
                                        <input type="text" name="contact_person" value="{{@$customer->contact_person}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Mobile</label>
                                        <input type="text" name="mobile_no" value="{{@$customer->mobile_no}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Email Address</label>
                                        <input type="email" name="email_id" value="{{@$customer->email_id}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Username</label>
                                        <input type="email" name="username" value="{{@$customer->contact_person_details?->username}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Address</label>
                                        <input type="text" id="search_in_map" name="address" id="address" value="{{@$customer->address ? $customer->address->address : ''}}" class="form-control user-profileinput" placeholder="Enter">
                                        <input type="hidden" id="country" name="country" value="">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Company</label>
                                        <select class="" id = "group_companies_dropdown" name="group_companies[]" multiple = "multiple">
                                            @foreach(@$groupCompanies as $company)
                                            <option value="{{$company->value}}" {{isset($customerGroupCompanies) && in_array($company -> value, $customerGroupCompanies -> toArray()) ? 'selected' : ''}}>{{$company->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col-md-4">
                            <!-- <div class="col-md-4"> -->
                                <div class="active-switch mb-4 d-flex justify-content-end align-items-center">
                                    <label class="switch">
                                        <input type="checkbox" id="staus" name="status"
                                            value="{{ @$customer->status ?? 'Active' }}" class="activeclass"
                                            onclick="toggleStatus(this, 'status')"
                                            @if(@$customer->status == 'Active' || !isset($customer)) checked @endif/>
                                        <div class="slider round">
                                            <span class="{{ @$customer->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                        </div>
                                    </label>
                                    <p id="status">{{@$customer->status ? $customer->status : 'Active'}}</p>
                                </div>

                                <label class="company-logolabel">Company Logo</label>
                                <div class="darg-dropbox text-center" id="drop-area">
                                    <h6>Drag &amp; Drop OR Upload</h6>
                                    <p>Max size: 05 MB</p>
                                    <div class="upload-btn-wrapper new-browsebtn mt-3">
                                        <button class="uploadBtn">
                                            Browse
                                        </button>
                                        <input type="file" name="image" id="customerImage" onchange="renderCustomerImage(this,'preview_image','preview_image_link')">
                                    </div>
                                </div>

                                <div class="drag-dropimgbox mt-3" style="display: {{ isset($customer->image[0]->original_url) ? 'block' : 'none'}};">
                                    <span class="red-crossimgbox" style="cursor:pointer;">
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </span>
                                    <a id="preview_image_link" href="{{ @$customer->image[0] ?  @$customer->image[0]->original_url : 'javascript:void(0)' }}" target="_blank">
                                    <img src="{{@$customer->image[0]->original_url ? @$customer->image[0]->original_url : asset('assets/img/course2.png')}}" alt="preview image" id="preview_image">
                                    </a>
                                </div>


                        </div>
                    </div>
                    <!-- <div class="row mt-sm-3 mt-2">
                        <div class="col-md-3 col-7">
                            <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn apply-btn btn-block">Submit</button>
                        </div>
                    </div> -->
                </div>
            </form>
            <!-- Form Close -->

        </div>
    </div>
</section>
<style>
    #drop-area.highlight {
    border-color: purple;
}
</style>
@endsection

@section('scripts')
<script>

    $(document).ready(function(e) {
        $('#group_companies_dropdown').select2({
            allowClear : true
        });
        initMap();
    });

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

    // drag-drop feature
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('customerImage');

    // Prevent default behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

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

    // Remove Image
    $('.red-crossimgbox').click(function(){
        $("#preview_image").attr('src',"");
        $("#customerImage").val("");
        $(".drag-dropimgbox").hide();
    })


    function appendFilesToFileInput(files) {
        const dataTransfer = new DataTransfer();

        for (let i = 0; i < files.length; i++) {
            dataTransfer.items.add(files[i]);
        }

        fileInput.files = dataTransfer.files;
    }

    function renderCustomerImage(input, render_place_id, render_link_id = null) {
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
            $('.drag-dropimgbox').show();
            reader.readAsDataURL(input.files[0]);
        }
    }


    // Address Auto Search
    let map;
    async function initMap() {
        // AutoComplete Address
        autoComplete = new google.maps.places.Autocomplete(document.getElementById("search_in_map"), {
                            types: ['geocode'],
                        });
        console.log(autoComplete);

        autoComplete.addListener('place_changed', function(){
            var place = autoComplete.getPlace();
            console.log("place:", place);
            if (!place.geometry) {
                Swal.fire({
                        type: 'error',
                        title: 'Oops...',
                        text: 'Location Not Found!',
                    });
                return;
            }

            $('#address').val(place?.formatted_address);

            var address_components = place?.address_components;

            address_components.forEach((address, index, array) => {
                if(address.types[0] == 'country'){
                    $('#country').val(address.long_name);
                }
            });
        });
    }


</script>
@endsection
