@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-3 col-8 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Team Members</h1>
                        @if(isset($member))
                        <h6><span class="active"> Team Members </span> <i class="fa fa-angle-right"
                                aria-hidden="true"></i> Edit </h6>
                        @else
                        <h6><span class="active"> Team Members </span> <i class="fa fa-angle-right"
                                aria-hidden="true"></i> Create </h6>
                        @endif
                    </div>
                </div>

               
            </div>

            <div class="batching-plantaddbox mt-sm-4 mt-3">
                <form action="/settings/customer-team/store" role="post-data" method="POST">
                    @csrf
                    <input type="hidden" name="member_id" value="{{@$member->id}}">
                    <input type="hidden" name="customer_id" value="{{request() -> customer_id}}">
                    <div class="row">
                        <div class="col-md-8 order-2 order-sm-1">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Name</label>
                                        <input type="text" name="name" value="{{@$member->name}}" class="form-control user-profileinput" placeholder="Enter Name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Email</label>
                                        <input type="text" name="email" value="{{@$member->email}}" class="form-control user-profileinput" placeholder="Enter Email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Phone No</label>
                                        <input type="text" name="phone_no" value="{{@$member->phone_no}}" class="form-control user-profileinput" placeholder="Enter Phone">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profileinput-box form-group position-relative">
                                        <label class="selext-label">Username</label>
                                        <input type="text" name="username" value="{{@$member->username}}" class="form-control user-profileinput" placeholder="Enter Username">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                <div class="filter-check" style = "margin:1rem;">
                                                        <input type="checkbox" class="filled-in" id = "is_admin_input" name = "is_admin" oninput = "changeAdminCheck(this);" {{isset($member) && $member -> is_admin ? 'checked' : ''}}>
                                                        <label class="temperature-label" for="is_admin_input">
                            Is Admin ?
                                                        </label>
                                                    </div>
                                </div>

                                <div class="col-md-12" id = "access_right_heading">
                                        <label style = "margin-top : 1rem; margin-bottom:1rem;">Access Rights</label>
                                </div>

                                <div class="table-responsive" id = "access_right_heading_detail">
                                        <table class="table general-table">
                                            <thead>
                                                <tr>
                                                    <th>Project</th>
                                                    <th>Order View</th>
                                                    <th>Order Create</th>
                                                    <th>Order Edit</th>
                                                    <th>Order Cancel</th>
                                                    <th>Chat</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($projects as $project)
                                                <tr>
                                                    @php
                                                        $currentAccessRight = isset($accessRights) ? $accessRights -> firstWhere('customer_project_id', $project -> id) : null;
                                                    @endphp
                                                    <td>{{$project->name}}</td>
                                                    <td>
                                                    <div class="filter-check" style = "margin:1rem;">
															<input data-project = "{{$project -> id}}" data-column = "order_view" name="projectAccess_{{$project -> id}}_view" id = "project_view_{{$project -> id}}"
																type="checkbox" class="filled-in" {{isset($currentAccessRight) && $currentAccessRight -> order_view ? 'checked' : ''}}
																>
															<label class="temperature-label"
																for="project_view_{{$project -> id}}"></label>
														</div>
                                                    </td>
                                                    <td>
                                                        <div class="filter-check" style = "margin:1rem;">
															<input data-project = "{{$project -> id}}" data-column = "order_create" name="projectAccess_{{$project -> id}}_create" id = "project_create_{{$project -> id}}"
																type="checkbox" class="filled-in" {{isset($currentAccessRight) && $currentAccessRight -> order_create ? 'checked' : ''}}
																>
															<label class="temperature-label"
																for="project_create_{{$project -> id}}"></label>
														</div>
                                                    </td>
                                                    <td>
                                                        <div class="filter-check" style = "margin:1rem;">
															<input data-project = "{{$project -> id}}" data-column = "order_edit" name="projectAccess_{{$project -> id}}_edit" id = "project_edit_{{$project -> id}}"
																type="checkbox" class="filled-in" {{isset($currentAccessRight) && $currentAccessRight -> order_edit ? 'checked' : ''}}
																>
															<label class="temperature-label"
																for="project_edit_{{$project -> id}}"></label>
														</div>
                                                    </td>
                                                    <td>
                                                        <div class="filter-check" style = "margin:1rem;">
															<input data-project = "{{$project -> id}}" data-column = "order_cancel" name="projectAccess_{{$project -> id}}_cancel" id = "project_cancel_{{$project -> id}}"
																type="checkbox" class="filled-in" {{isset($currentAccessRight) && $currentAccessRight -> order_cancel ? 'checked' : ''}}
																>
															<label class="temperature-label"
																for="project_cancel_{{$project -> id}}"></label>
														</div>
                                                    </td>
                                                    <td>
                                                    <div class="filter-check" style = "margin:1rem;">
                                                        <input data-project = "{{$project -> id}}" data-column = "chat" type="checkbox" class="filled-in" id = "project_chat_{{$project -> id}}"  name = "projectAccess_{{$project -> id}}_chat"
                                                        {{isset($currentAccessRight) && $currentAccessRight -> chat ? 'checked' : ''}}
                                                        >
                                                        <label class="temperature-label" for="project_chat_{{$project -> id}}">
                            
                                                        </label>
                                                    </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                </div>
                                
                            </div>

                            <div class="row mt-sm-3 mt-2">
                                <div class="col-md-4 col-7">
                                <button type="button" onclick = "submitForm(this);" class="btn apply-btn btn-block">Submit</button>
                                </div>
                            </div>
    
                            </div>
    
                            <div class="col-md-4 order-sm-2 order-1 mb-2 mb-sm-0">
                            <div class="active-switch d-flex justify-content-end align-items-center">
                                <label class="switch mr-2">
                                    <input type="checkbox" id="member_status_toggle" name="member_status" 
                                        value="{{ @$member->status ?? 'Active' }}" class="activeclass"
                                        onclick="toggleStatus(this, 'mmember_status')" 
                                        @if(@$member->status == 'Active' || !isset($member)) checked @endif/>
                                    <div class="slider round">
                                        <span class="{{ @$member->status == 'Inactive' ? 'swinactive' : 'swactive' }}"></span>
                                    </div>
                                </label>
                                <p id="member_status">{{@$member->status ? $member->status : 'Active'}}</p>
                            </div>
                        </div>
    
                        </div>
                        
                    </div>
                </form>
            </div>
        </div>

    </div>
</section>
@endsection

@section('scripts')
<script>

changeAdminCheck(document.getElementById('is_admin_input'));

    function changeAdminCheck(element)
    {
        if (element.checked)
        {
            document.getElementById('access_right_heading_detail').classList.add('hidden_content');
            document.getElementById('access_right_heading').classList.add('hidden_content');
        }
        else 
        {
            document.getElementById('access_right_heading_detail').classList.remove('hidden_content');
            document.getElementById('access_right_heading').classList.remove('hidden_content');
        }
    }

function submitForm(elem) {
		var form = $(elem).closest('form');
		var formData = new FormData(form[0]);
        var access_rights = [];
        const checkboxes = document.querySelectorAll('.filled-in');
        checkboxes.forEach(element => {
            const hasProjectIndex = access_rights.findIndex(right => right.project_id == element.dataset.project);
            if (hasProjectIndex != -1)
            {
                access_rights[hasProjectIndex][element.dataset.column] = element.checked ? true : false;
            }
            else 
            {
                if (element.checked)
                {
                    const currentKey = element.dataset.column;
                    access_rights.push({
                        project_id : element.dataset.project,
                        [currentKey] : element.checked ? true : false
                    });
                }
                
            }

        });
        formData.append('access_rights', JSON.stringify(access_rights));
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
						window.location.href = $response.redirect_url;
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