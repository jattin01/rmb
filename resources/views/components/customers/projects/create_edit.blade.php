@extends('layouts.auth.app')
@section('content')

@php
    $productTypes = [];
@endphp
<section class="content">
    <div class="container-fluid">

        <div class="px-sm-4">
            <form action="/customer-projects/store" role="post-data" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-6 col-8 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Project</h1>
                            <h6><span class="active"> Registered Customer </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i>
                                @if(isset($project))
                                <span class="active"> Project </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Edit Project
                                @else
                                <span class="active"> Create New </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Add Projects
                                @endif
                            </h6>
                        </div>
                    </div>

                    <div class="col-md-3 col-4 text-right">
                        <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn new-btn mr-3">Submit</button>
                        <a href="{{route('settings.customerProjects.index', ['customerId' => request() -> customer_id])}}" class="btn back-btn">Back</a>
                    </div>
                </div>
                <input type="hidden" name="customerId" value="{{@$customerId ?? @$project->customer_id}}">
                <input type="hidden" name="projectId" value="{{@$project->id}}">
                <div class="batching-plantaddbox mt-sm-4 mt-3">
                    <div class="row">
                        <div class="col-md-8 mb-3 mb-sm-0">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Customer</label>
                                        <select class="form-control select-contentbox" name="customer_id" id = "customer_dropdown">
                                            <option value = "" >Select</option>
                                            @foreach(@$customers as $customer)
                                            <option value="{{@$customer->value}}" @if(@$project->customer_id == @$customer->value || request() -> customer_id == @$customer -> value) selected @endif>{{@$customer->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Project Code</label>
                                        <input type="text" name="project_code" value="{{@$project->code}}" class="form-control user-profileinput" placeholder="Enter Code">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Project Name</label>
                                        <input type="text" name="project_name" value="{{@$project->name}}" class="form-control user-profileinput" placeholder="Enter Name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Project Type</label>
                                        <select class="form-control select-contentbox" name="project_type">
                                            <option value="">Select</option>
                                            @foreach(@$projectTypes as $type)
                                            <option value="{{@$type->name}}" @if(@$project->type == @$type->name) selected @endif>{{@$type->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Contractor</label>
                                        <input type="text" name="contractor_name" value="{{@$project->contractor_name}}" class="form-control user-profileinput" placeholder="Enter Name">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Started on</label>
                                        <input type="date" name="start_date" id="start_date" value="{{isset($project) ? $project->start_date->format('Y-m-d') : ''}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">ETC</label>
                                        <input type="date" name="end_date" id="end_date" value="{{isset($project) ? @$project->end_date->format('Y-m-d') : ''}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Address</label>
                                        <input type="text" id="search_in_map" name="address" id="address" value="{{@$project->address ? $project->address->address : ''}}" class="form-control user-profileinput" placeholder="Enter">
                                        <input type="hidden" id="country" name="country" value="">
                                    </div>
                                </div>

                            </div>

                        </div>
                        <div class="col-md-4 mb-2 mb-sm-0">
                            <div class="active-switch mb-4 d-flex justify-content-end align-items-center">
                                <label class="switch">
                                    <input type="checkbox" name="project_status" id = "project_staus"
                                        value="{{ @$project->status ?? 'Active' }}" class="activeclass"
                                        onclick="{{ @$project->status == 'Inactive' || !isset($project) ? 'inactive' : 'active' }}Staus('project_staus', 'project_status')"
                                        {{ @$project->status == 'Inactive'? '' : 'checked' }} />
                                    <div class="slider round">
                                        <span
                                            class="{{ @$project->status == 'Inactive' ? 'swinactive' : 'swactive' }}">
                                        </span>
                                    </div>
                                </label>
                                <p id="project_status">{{ @$project->status == 'Inactive' ? 'Inactive' : 'Active' }}</p>
                            </div>

                            <label class="company-logolabel">Project Photo</label>

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

                            <div class="drag-dropimgbox mt-3" style="display: {{ isset($project->image[0]) ? 'block' : 'none'}};">
                                <span class="red-crossimgbox">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </span>
                                <a id="preview_image_link" href="{{ @$project->image[0] ?  @$project->image[0]->original_url : 'javascript:void(0)' }}" target="_blank">
                                <img src="{{@$project->image[0] ? @$project->image[0]->original_url : asset('assets/img/course2.png')}}" alt="preview image" id="preview_image">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            </div>

           <!-- Site Address Table -->

           <!-- ReadyMix Product Table Close -->
        </div>

    </div>


</section>
    <!-- Customer Project Product Modal -->

    <!-- Customer Project Site Address Modal -->


    <style>
        #drop-area.highlight {
            border-color: purple;
        }
    </style>
@endsection

@section('scripts')
<script>

    let map;
    let autocomplete;
    let geocoder;

    async function initMap(lat = null, lng = null) {
        // AutoComplete Address
        autoComplete = new google.maps.places.Autocomplete(document.getElementById("search_in_map"), {
            types: ['geocode'],
        });

        autoComplete.addListener('place_changed', function(){
            var place = autoComplete.getPlace();
            if (!place.geometry) {
                toast('warning', 'Location not found')
                return;
            }
        });
    }

    initMap();

    function activeStaus(id,field) {
        if(typeof(id) == 'object' && typeof(field) == 'object'){
            id = id.id;
            field = field.name;
        }
        var status = 'active';
        // $('#status').val(status);
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
            $('.drag-dropimgbox').show();
            reader.readAsDataURL(input.files[0]);
        }
    }

    // remove Image
    $('.red-crossimgbox').click(function(){
        $("#preview_image").attr('src',"");
        $("#projectImage").val("");
        $(".drag-dropimgbox").hide();
    })

    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        // const dateError = document.getElementById('date_error');

        function validateDates() {
            const startDateValue = startDateInput.value;
            const endDateValue = endDateInput.value;

            // If both dates are provided
            if (startDateValue && endDateValue) {
                const startDate = new Date(startDateValue);
                const endDate = new Date(endDateValue);

                if (endDate < startDate) {
                    // dateError.style.display = 'inline';
                    // endDateInput.setCustomValidity('End date cannot be earlier than start date.');
                    // Swal.fire({
                    //     type: 'error',
                    //     title: 'Oops...',
                    //     text: 'End date cannot be earlier than start date!',
                    // });
                    // endDateInput.value = '';
                    // return;
                }
            }
            else {
                // dateError.style.display = 'none';
                // endDateInput.setCustomValidity('');
            }
        }

        startDateInput.addEventListener('change', validateDates);
        endDateInput.addEventListener('change', validateDates);
    });

    function storeProjectSiteAddress(elem) {
		var form = $(elem).closest('form');
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

    // get details of product_type
    function getMixDetails(__this){
        var typeId = __this.value;

        $.ajax({
            url  : "{{url('/customers/product/details')}}",
            type : "POST",
            data : {
                        _token: "{{ csrf_token() }}",
                        typeId : typeId
                    },
            success	: function(response){
                console.log(response);
                if(response){
                    $('input[name="product_code_name"]').val(response?.product.name);
                    $('input[name="product_type"]').val(response?.type);
                    $('input[name="product_id"]').val(response?.product?.id);
                }
            },
            error : function(response){
                console.log(response);
            },
        });
    }

    function storeProduct(__this){
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

    function deleteSite(addressId){

		$.ajax({
            url  : "{{url('/customers/delete-project-site')}}",
            type : "POST",
            data : {
                    _token: "{{ csrf_token() }}",
                    addressId : addressId
                },
			beforeSend: function() {
				$('#loaderDiv').show();
				$('.help-block').remove();
			},
            success	: function($response){
                console.log("success",$response);
                if ($response.status === 200) {
					toast("success", $response.message);
					setTimeout(function() {
						$('#loaderDiv').hide();
						window.location.reload();
					}, 2200);
				}
            },
            error : function($response){
                $('#loaderDiv').hide();
				if ($response.status === 422) {
					if (Object.size($response.responseJSON) > 0 && Object.size($response
							.responseJSON.errors) > 0) {
						show_validation_error($response.responseJSON.errors);
					}
				} else {
                    console.log("error",$response);
					Swal.fire(
						'Error', $response.message, 'warning'
					)
					setTimeout(function() {}, 1200)
				}
            },
		});
    }

    function deleteProduct(productId){

       $.ajax({
           url  : "{{url('/customers/delete-project-product')}}",
           type : "POST",
           data : {
                   _token: "{{ csrf_token() }}",
                   productId : productId
               },
           beforeSend: function() {
               $('#loaderDiv').show();
               $('.help-block').remove();
           },
           success	: function($response){
               console.log("success",$response);
               if ($response.status === 200) {
                   toast("success", $response.message);
                   setTimeout(function() {
                       $('#loaderDiv').hide();
                       window.location.reload();
                   }, 2200);
               }
           },
           error : function($response){
               $('#loaderDiv').hide();
               if ($response.status === 422) {
                   if (Object.size($response.responseJSON) > 0 && Object.size($response
                           .responseJSON.errors) > 0) {
                       show_validation_error($response.responseJSON.errors);
                   }
               } else {
                   console.log("error",$response);
                   Swal.fire(
                       'Error', $response.message, 'warning'
                   )
                   setTimeout(function() {}, 1200)
               }
           },
       });
    }

    function editSite(siteId){
        $.ajax({
           url  : "{{url('/customers/edit-project-site')}}",
           type : "POST",
           data : {
                   _token: "{{ csrf_token() }}",
                   siteId : siteId
               },

            success	: function(response){
               console.log("success",response);
               $('#site-switch').html('');
               let html = '';
               if(response){
                   $('input[name="siteId"]').val(response?.id);
                   $('input[name="site_name"]').val(response?.name);
                   $('textarea[name="site_address"]').val(response?.address);
                   $('input[name="latitude"]').val(response?.latitude);
                   $('input[name="longitude"]').val(response?.longitude);
                   html = `<label class="switch">
                                <input type="checkbox" id="site_staus" name="site_status"
                                    value="${response.status}" class="activeclass"
                                    onclick="${response.status == 'Inactive' || !response ? 'inactive' : 'active' }Staus('site_staus', 'site_status')"
                                    ${response?.status == 'Inactive' ? '' : 'checked' } />
                                <div class="slider round">
                                    <span
                                        class="${response.status == 'Inactive' ? 'swinactive' : 'swactive'}">
                                    </span>
                                </div>
                            </label>
                            <p id="site_status">${response.status == 'Inactive' ? 'Inactive' : 'Active' }</p>`;

                   $('#site-switch').html(html);
                   initMap(response?.latitude, response?.longitude);
                   $('#user-profile').modal('show');
                }
            },
           error : function($response){

           },
       });
    }

    function editProduct(productId, projectId, customerId){
        $.ajax({
           url  : "{{url('/customers/edit-project-product')}}",
           type : "POST",
           data : {
                   _token: "{{ csrf_token() }}",
                   productId : productId,
                   projectId : projectId,
                   customerId : customerId,
               },

            success	: function(response){
               console.log("success",response);
               let html = '';
               $('#product-switch').html('');
               $('#productCode').empty();

               if(response){
                    $('#productCode').append($('<option>', {
                        value: response?.productDetail?.product?.product_type_id,
                        text: response?.productDetail?.product?.code
                    }));

                    $('#productCodeName').val(response?.productDetail?.product?.name);
                    $('#productCodeName').attr('readonly', true);
                    $('#productType').val(response?.productDetail?.product?.product_type?.type);
                    $('#productType').attr('readonly', true);
                    $('#totalQty').val(response?.productDetail?.total_quantity);

                    $('#product_customer_id').val(response?.productDetail?.customer_id);
                    $('#product_project_id').val(response?.productDetail?.project_id);
                    $('#product_product_id').val(response?.productDetail?.product_id);
                    $('#customer_product_id').val(response?.productDetail?.id);

                   html = `<label class="switch">
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
           error : function($response){
               console.log($response);
           },
       });
    }
</script>
@endsection
