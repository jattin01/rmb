@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="px-sm-4">
            <form action="/settings/products/store" role="post-data" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                    <div class="col-md-3 col-8 mb-sm-0 mb-2">
                        <div class="top-head">
                            <h1>Mix</h1>
                            @if(isset($product))
                            <h6><span class="active"> Mix Overview </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Edit </h6>
                            @else
                            <h6><span class="active"> Mix Overview </span> <i class="fa fa-angle-right"
                                    aria-hidden="true"></i> Create </h6>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3 col-4 text-right">
                        <button type="button" data-request="ajax-submit" data-target="[role=post-data]" class="btn apply-btn new-btn mr-3">Submit</button>
                        <a href="{{route('settings.products.index')}}" class="btn back-btn">Back</a>
                    </div>
                </div>
                <input type="hidden" name="productId" value="{{@$product->id}}">
                <div class="batching-plantaddbox mt-sm-4 mt-3">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Company</label>
                                        <select class="form-control select-contentbox" id = "companies_dropdown" name="group_company_id" onchange = "changeDropdownOptions(this, ['product_type_dropdown'], ['product_types'] , '/settings/product_types/get/')">
                                            @foreach($groupCompanies as $company)
                                            <option value="{{$company->value}}" @if(isset($product) && ($product->group_company_id == $company->value)) selected @endif>{{$company->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                {{-- <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Structure</label>
                                        <select name="structure_reference_id[]" multiple="multiple" class="form-control js-example-basic-multiple select-contentbox">
                                            @foreach ($structures as $structure)
                                                <option value="{{ $structure->id }}"{{in_array($structure->id,$selectedStructure ) ? 'selected' : ''}} >{{ $structure->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div> --}}

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Mix Type</label>
                                        <select class="form-control select-contentbox" name="product_type_id" id = "product_type_dropdown">
                                            @foreach($productTypes as $type)
                                            <option value="{{$type->value}}" @if(isset($product) && ($product->product_type_id == $type->value)) selected @endif>{{$type->label}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Mix Code</label>
                                        <input type="text" name="code" value="{{@$product->code}}" class="form-control user-profileinput" placeholder="Enter Code">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Mix Name</label>
                                        <input type="text" name="name" value="{{@$product->name}}" class="form-control user-profileinput" placeholder="Enter Name">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Density</label>
                                        <input type="number" name="density" value="{{@$product->density}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Usage</label>
                                        <input type="text" name="usage" value="{{@$product->usage}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                {{-- <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Batching Creation Time (Min)</label>
                                        <input type="text" name="batching_creation_time" value="{{@$product->batching_creation_time}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Tempeature Creation Time (Min)</label>
                                        <input type="text" name="temperature_creation_time" value="{{@$product->temperature_creation_time}}" class="form-control user-profileinput" placeholder="Enter">
                                    </div>
                                </div> --}}

                            </div>

                        </div>
                        <div class="col-md-4 mb-2 mb-sm-0">
                            <div class="active-switch d-flex justify-content-end align-items-center">
                                <label class="switch mr-2">
                                    <input type="checkbox" id="product_staus" name="product_status"
                                        value="{{ @$product->status ?? 'Active' }}" class="activeclass"
                                        onclick="{{ @$product->status == 'Inactive' || !isset($product) ? 'active' : 'inactive' }}Staus('product_staus', 'product_status')"
                                        {{ @$product->status == 'Inactive'? '' : 'checked' }} />
                                    <div class="slider round">
                                        <span
                                            class="{{ @$product->status == 'Inactive' ? 'swinactive' : 'swactive' }}">
                                        </span>
                                    </div>
                                </label>
                                <p id="product_status">{{ @$product->status == 'Inactive' ? 'Inactive' : 'Active' }}</p>
                            </div>
                            <label class="company-logolabel">Product Photo</label>
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

                            <div class="drag-dropimgbox mt-3" style="display: {{ isset($product->image[0]) ? 'block' : 'none'}};">
                                <span class="red-crossimgbox" style="cursor:pointer;">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </span>
                                <a id="preview_image_link" href="{{ @$product->image[0] ?  @$product->image[0]->original_url : 'javascript:void(0)' }}" target="_blank">
                                <img src="{{@$product->image[0] ? @$product->image[0]->original_url : asset('assets/img/course2.png')}}" alt="preview image" id="preview_image">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Material Content Table -->
            <div class="product-detailscard mt-sm-5 mt-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6 col-5">
                            <label class="project-details pl-sm-3">Material contents in 1 CUM:</label>
                        </div>
                        <div class="col-md-6 col-7 text-right">
                            <!-- <button type="button" class="btn btn-success" data-toggle="modal"data-target="#user-profile">Add More</button> -->
                            @if(isset($product) && count($product->productContents) > 0)
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#user-profile">Add More</button>
                            @elseif(isset($product) && count($product->productContents) == 0)
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#user-profile">Create New</button>
                            @else
                                <div  class="btn btn-success tool">Create New
                                    <span class="tooltiptext">Please create a product first!</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table general-table">
                                    <thead>
                                        <tr>
                                            <th>Content Name</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($product) && $product->productContents)
                                            @foreach($product->productContents as $content)
                                            <tr>
                                                <td>{{$content->content}}</td>
                                                <td>{{$content->quantity}}</td>
                                                <td class="{{ $content->status == 'Active' ? 'table-activetext' : 'table-inactivetext'}}">{{$content->status}}</td>
                                                <td  class="gray-text">
                                                <div class="d-flex align-items-center justify-content-between" style = "margin:0.4rem;">
																<div class="dropdown more-drop">
																	<button class="table-drop"  type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
																		<i class="fa fa-ellipsis-v fa-lg more-icon" aria-hidden="true"></i>
																	</button>
																	<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
																	  <a href="#" onclick="editProductContent({{$content->id}})" class="dropdown-item" >Details</a>
																	</div>
																  </div>
																</div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Material Content Table -->
        </div>

    </div>
</section>

<div class="modal fade filter-modal full-heightmodal" id="user-profile" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <img src="{{asset('assets/img/filter-close.svg')}}" alt=""> close
                    </button>
                </div>
                <form action="content/store" role="post-data" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{@$product->id}}">
                    <input type="hidden" name="productContentId" value="">
                    <div class="modal-body">
                        <div class="filter-contentbox">
                            <h6>Mix Content</h6>
                        </div>

                        <div class="active-switch d-flex mt-3 mb-4 justify-content-end align-items-center" id="content-switch">
                            <label class="switch">
                                <input type="checkbox" id="content_staus" name="content_status"
                                    value="{{ @$content->status ?? 'Active' }}" class="activeclass"
                                    onclick="{{ @$content->status == 'Inactive' || !isset($content) ? 'active' : 'inactive' }}Staus('content_staus', 'content_status')" checked/>
                                <div class="slider round">
                                    <span
                                        class="{{ @$content->status == 'Inactive' ? 'swinactive' : 'swactive' }}">
                                    </span>
                                </div>
                            </label>
                            <p id="content_status">Active</p>
                        </div>

                        <div class="profileinput-box mt-4 form-group position-relative">
                            <label class="selext-label">Name</label>
                            <input type="text" name="content" class="form-control user-profileinput padding-right">
                        </div>

                        <div class="profileinput-box mt-4 form-group position-relative">
                            <label class="selext-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control user-profileinput padding-right">
                        </div>

                        <div class="mt-sm-5 mt-3">
                            {{-- <button type="button" onclick="saveProductContent(this)" class="btn apply-btn btn-block">Submit</button> --}}
                            <button type="submit" data-request="ajax-submit" data-target="[role=post-data]"
                            class="btn save-btn mt-4">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <style>
        #drop-area.highlight {
            border-color: purple;
        }
    </style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#companies_dropdown').select2({
            placeholder: 'Select Location(s)'
        });
        $('#product_type_dropdown').select2({
            placeholder: 'Select Location(s)'
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

    // store content
    function saveProductContent(elem) {
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

    function editProductContent(productContentId){
        $.ajax({
           url  : "{{url('settings/products/content/edit')}}",
           type : "GET",
           data : {
                _token: "{{ csrf_token() }}",
                productContentId : productContentId,
               },

            success	: function(response){
               console.log("success",response);
               let html = '';
               $('#content-switch').html('');

               if(response){
                    $('input[name="content"]').val(response?.content);
                    $('input[name="quantity"]').val(response?.quantity);
                    $('input[name="productContentId"]').val(response?.id);

                    html = `<label class="switch">
                                <input type="checkbox" id="content_staus" name="content_status"
                                    value="${response?.status}" class="activeclass"
                                    onclick="${response?.status == 'Inactive' || !response.status ? 'inactive' : 'active' }Staus('content_staus', 'content_status')"
                                    ${response?.status == 'Inactive' ? '' : 'checked' } />
                                <div class="slider round">
                                    <span
                                        class="${response?.status == 'Inactive' ? 'swinactive' : 'swactive'}">
                                    </span>
                                </div>
                            </label>
                            <p id="content_status">${response?.status == 'Inactive' ? 'Inactive' : 'Active' }</p>`;

                    $('#content-switch').html(html);
                    $('#user-profile').modal('show');

                }
            },
           error : function($response){
               console.log($response);
           },
       });
    }

    $(document).ready(function() {
    $('.js-example-basic-multiple').select2();
});

</script>
@endsection
