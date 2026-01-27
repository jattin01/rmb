<div class="content-header pb-0">
	<div class="container-fluid">
		<div class="row align-items-center">
			<div class="col-md-2 col-10">
				<a class="d-inline-block d-md-none mr-1 mr-sm-0" data-widget="pushmenu" href="#" role="button"><img
						src="{{asset('assets/img/menu-left-alt.svg')}}" /></a>
				<a class="" href="{{route('dashboard.index')}}" role="button"><img src="{{asset('assets/img/home-logo.svg')}}"
						class="home-logo" /></a>
			</div>
			<div class="col-md-10 col-2 user-profile ">

				<div class="float-right dropdown userData">
					<a data-toggle="dropdown" href="#" aria-expanded="true">
						<img src="{{auth() -> user() -> profile_icon ?? asset('assets/img/user-profileimg.svg')}}" class="img-circle mr-1" width="36" />
						<p class="d-sm-inline-block d-none cursor-pointer" >Hello, {{auth() -> user() -> name}} <span>{{auth() -> user() -> role ?-> name}}</span></p>
					</a>
					<div class="dropdown-menu dropdown-menu-md tableaction useraction-dropdown dropdown-menu-right"
						style="left: inherit; right: 0px;">
						<ul>
							<li data-toggle="modal"
							data-target="#user-profile-main"><a href="#"> <img src="{{asset('assets/img/user-picdrop.svg')}}" alt=""
										class="user-newimg">
									My Profile</a></li>
							<li><a href="{{route('auth.logout.submit')}}"> <img src="{{asset('assets/img/loguot.svg')}}"
										class="user-newimg" alt="">
									Logout</a></li>
						</ul>
					</div>
					<a href="#" aria-expanded="true">
						<img src="{{auth() -> user() -> user_group_companies -> first() ?-> groupCompany ?-> group ?-> image_url}}" class="img-circle mr-1" width="36" />
					</a>

				</div>

				<div class="float-right notificationbar d-sm-block d-none new-notification mx-4">
					<div data-toggle="dropdown" class="dropdown d-inline-block cursor-pointer"><img
							src="{{asset('assets/img/bell-header.svg')}}" class="float-none" />
						<span class="top-notificationpink hidden_content" id = "notify-dot"></span>
						<div
							class="dropdown-menu dropdown-menu-md  notificationtab useraction-dropdown dropdown-menu-right">
							<div class="px-3 py-2">
								<div class="row align-items-center">
									<div class="col-md-6">
										<h5 class="notificationbar-new-text">Notifications</h5>
									</div>
								</div>
							</div>

							<div class="notify-detil" id = "mainNotificationContainer">
							</div>

							<div class="border-bottom mt-3"></div>
							
						</div>

					</div>

				</div>

				<div onclick = "getChatList();" class="new-notification new-notificationgreen d-sm-block d-none ml-3 float-right  cursor-pointer"
					>
					<img src="{{asset('assets/img/chat-header.svg')}}" alt="">
					<span id = "chat-dot" class="top-notificationpink d-sm-block d-none greentop-notificationpink hidden_content"></span>
				</div>

			</div>

		</div>
	</div>
</div>

<div id = "chatListModalDiv">
</div>

<div class="modal fade filter-modal" id="user-profile-main" tabindex="-1" role="dialog"
		aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header border-0">
					<h5 class="modal-title" id="exampleModalLabel">Profile Overview</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<img src="{{asset('assets/img/filter-close.svg')}}" alt=""> close
					</button>
				</div>
				<div class="modal-body">
					<form action="/profile/update" role="post-data-profile" method="POST" enctype="multipart/form-data">
					@csrf

						<div class="profile-imgbox">
							<img id = "user_profile_preview_img" src="{{auth() -> user() -> profile_icon ?? asset('assets/img/user-profileimg.svg')}}" style = "max-width:150px; maxheight:150px;" alt="Profile Image">
						</div>

					<div class="text-center mt-2 mb-3">
						<div class="upload-btn-wrapper">
							<button class="uploadBtn">
								Change your Profile Pic
							</button>
							<input type="file" name = "user_profile_profile_img" id = "user_profile_img_input" accept="image/*">
						</div>
					</div>

					<div class="row pt-sm-4 pt-3">
						<div class="col-md-12">
							<div class="profileinput-box form-group position-relative">
								<label class="selext-label">Name</label>
								<input type="text" class="form-control user-profileinput padding-right"
									name = "user_profile_name" value = "{{auth() -> user() -> name}}">
								<img src="{{asset('assets/img/input-newuser.svg')}}" class="user-profileinptlogo" alt="">
							</div>
							<div class="profileinput-box form-group position-relative">
								<label class="selext-label">Username</label>
								<input type="text" class="form-control user-profileinput padding-right"
									placeholder="" name = "user_profile_username" value = "{{auth() -> user() -> username}}">
								<img src="{{asset('assets/img/input-newuser.svg')}}" class="user-profileinptlogo" alt="">
							</div>

							<div class="profileinput-box form-group position-relative">
								<label class="selext-label">Email Address</label>
								<input type="email" class="form-control user-profileinput padding-right"
									name = "user_profile_email" value = "{{auth() -> user() -> email}}">
								<img src="{{asset('assets/img/email-icon.svg')}}" class="user-profileinptlogo" alt="">
							</div>
							<div class="profileinput-box form-group position-relative">
								<label class="selext-label">Phone</label>
								<input type="text" class="form-control user-profileinput padding-right"
									name = "user_profile_mobile_no" value = "{{auth() -> user() -> mobile_no}}">
								<img src="{{asset('assets/img/call-icon.svg')}}" class="user-profileinptlogo" alt="">
							</div>

							<!-- <div class="profileinput-box d-flex justify-content-between align-items-center">
								<p>Reset Password?</p>
								<img src="img/input-rightarrow.svg" alt="">
							</div> -->

						</div>
					</div>

					<div>
						<button type="button" data-request="ajax-submit" data-target="[role=post-data-profile]" class="btn apply-btn btn-block">Submit</button>
					</div>
				</form>
				</div>
			</div>
		</div>
	</div>

<script>
	function getChatList()
	{
			fetch("{{route('chat.rooms.index')}}", {
            method : "GET",
            headers : {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
        }).then(response => response.json()).then(data => {
            const response = data;
			// Update modal content with the rendered HTML
            $('#chatListModalDiv').html(response.html);

            // Open the modal
            $('#chatModal').modal('show');
        }).catch(error => {
            console.log("Error : ", error);
        });
	}

	document.getElementById('user_profile_img_input').addEventListener('change', function() {
		const imageInput = document.getElementById('user_profile_img_input');
		try {
			const file = imageInput.files[0];
			if (!file) {
			flasher.warning('No file selected.');
			}
			// Check if the file type is an image
			if (!file.type.startsWith('image/')) {
				flasher.warning('Invalid file type. Please select an image.');
			}
			// Optional: Preview the selected image
			const reader = new FileReader();
			reader.onload = function(e) {
			const imgPreview = document.getElementById('user_profile_preview_img');
			imgPreview.src = e.target.result;
			};
			reader.readAsDataURL(file);

		} catch (error) {
			flasher.error(error.message);
		}
	});


</script>

