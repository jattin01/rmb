<div class="modal fade chat-modal" id="chatModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
		aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content chat-box-modal">
				<div class="modal-header border-0">
					<h5 class="modal-title" id="exampleModalLabel">Chat</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<img src="{{asset('assets/img/close-pop.svg')}}" class="close-img" alt="">
					</button>
				</div>
				<div class="modal-body">
					<div class="form-group position-relative" style = "text-align:center;">
						<img src="{{asset('assets/img/search-gray.svg')}}" class="search-popicon" alt="">
						<input id = "search-project-input" type="text" class="form-control pop-input" placeholder="Search Customers/ Drivers/ Projects">
						<!-- <label class="selext-label-tab">All</label>
						<label class="selext-label-tab">Drivers</label>
						<label class="selext-label-tab">Customers</label> -->
					</div>

                    {{-- <div class="row pt-3">
                        <div class="col-md-12">
                            <nav>
                                <div class="nav nav-tabs chatmodal-tab" id="nav-tab" role="tablist">
                                  <button class="nav-link active" id="nav-home-tab" data-toggle="tab" data-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true" onclick="filterProjects('Customer')">Customer</button>
                                  <button class="nav-link" id="nav-profile-tab" data-toggle="tab" data-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false" onclick="filterProjects('Driver')">Driver</button>
                                </div>
                              </nav>

                        </div>
                    </div> --}}

                    <div class = "chat-box-container">
                    @foreach ($rooms as $room)
                    <div data-project = "{{$room -> project ?-> name . $room -> entity ?-> name}}" data-type = "{{ $room -> entity_type}}" class = "row project-chat-bar">
						<div class="col-md-12">
							<div class="support-boxchat mt-3" onclick = "openMainChatScreen({{$room -> id}})">
								<div class="row align-items-center">
									<div class="col-md-9 col-8">
										<div class="d-flex align-items-center">
											<div class="mr-3">
												<div class="chat-userbox" style = "background-color : {{App\Helpers\CommonHelper::getBackgroundColorForChatUser()}} !important;">
													{{App\Helpers\CommonHelper::getNameInititals($room -> entity ?-> name ?? 'a')}}
												</div>
											</div>
											<div>
												<h2>{{$room -> entity ?-> name . " (" . $room -> entity_type . ")"}}</h2>
												<p>{{$room -> project ?-> name}}</p>
											</div>
										</div>
									</div>
									<div id = "chat_room{{$room -> id}}_main_section" class="col-md-3 col-4 text-right">
										<h4 id = "chat_room{{$room -> id}}_time_section"></h4>
										<span id = "chat_room{{$room -> id}}_unread_count_section" class="dark-badge hidden_content"></span>
									</div>

								</div>
							</div>
						</div>
					</div>

                    @endforeach
                    </div>

				</div>
			</div>
		</div>
	</div>

    <div id = "mainChatWindow">

    </div>

    <script>
        function openMainChatScreen(roomId)
        {
			fetch("{{url('chat/get/room/details')}}" + "/" + roomId, {
            method : "GET",
            headers : {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
        }).then(response => response.json()).then(data => {
            const response = data;
			unsubscribeFirestore();
			// Update modal content with the rendered HTML
            $('#mainChatWindow').html(response.html);

            // Open the modal
            $('#main-chat-box').modal('show');
            $('#chatModal').modal('hide');
        }).catch(error => {
            console.log("Error : ", error);
			unsubscribeFirestore();
        });
		}

		var unsubscribe = [];

		listenToProjectsChatEvent();

		function listenToProjectsChatEvent()
		{
			const myRooms = @json($rooms);
			const currentUserId = "{{auth() -> user() -> id}}";
			myRooms.forEach(room => {
				let currentUnsubscribe = myFirebaseDb.collection('chat_rooms').doc(room.project_id + "_" + room.entity_type + "_" + room.entity_id).collection('chats').orderBy('created_at', 'desc')
                    .onSnapshot((snapshot) => {
						let unreadCount = 0;
						let unreadTime = null;
						snapshot.forEach((doc) => {
							if (doc.exists) {
								const data = doc.data();
								if (data.readBy && !data.readBy.includes(currentUserId)) {
									unreadCount += 1;
								}
								if (!unreadTime) {
									unreadTime = moment(data.created_at.toDate()).format('h:mm A');
								}
							}
						});
						if (unreadCount > 0) {
							const unreadElement = document.getElementById('chat_room' + room.id + '_unread_count_section');
							if (unreadElement) {
								unreadElement.classList.remove('hidden_content');
								unreadElement.textContent = unreadCount;
							}
						}
						const timeElement = document.getElementById('chat_room' + room.id + '_time_section');
						if (timeElement) {
							timeElement.textContent = unreadTime;
						}
                    }, function (error) {
                        console.log("Error getting document:", error);
                	});
					unsubscribe.push(currentUnsubscribe)
			});
		}

		function unsubscribeFirestore()
		{
			if (unsubscribe && unsubscribe.length > 0) {
				unsubscribe.forEach(singleUnsubscribe => {
					console.log('Firestore listener stopped');
					singleUnsubscribe();
				});
            }
		}

		$('#chatModal').on('hidden.bs.modal', function () {
			unsubscribeFirestore();
        });

		document.getElementById('search-project-input').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') { // Check if the pressed key is Enter
                searchProjects(event)
            }
        });

		function searchProjects(event)
		{
			const searchValue = (event.target.value).toLowerCase();
			const allElements = document.getElementsByClassName('project-chat-bar');

			if (searchValue.trim() !== "") {
				for (let idx = 0; idx < allElements.length; idx++)
				{
					if (allElements[idx].dataset.project.toLowerCase().includes(searchValue)) {
						allElements[idx].classList.remove('hidden_content');
					} else {
						allElements[idx].classList.add('hidden_content');
					}
				}
			} else {
				if (allElements && allElements.length > 0) {
					for (let idx = 0; idx < allElements.length; idx++) {
						allElements[idx].classList.remove('hidden_content');
					}
				}
			}
		}
		// function filterProjects(type)
		// {

		// 	const allElements = document.getElementsByClassName('project-chat-bar');

		// 	if (true) {
		// 		for (let idx = 0; idx < allElements.length; idx++)
		// 		{
		// 			if (allElements[idx].dataset.type==type) {
		// 				allElements[idx].classList.remove('hidden_content');
		// 			} else {
		// 				allElements[idx].classList.add('hidden_content');
		// 			}
		// 		}
		// 	} else {
		// 		if (allElements && allElements.length > 0) {
		// 			for (let idx = 0; idx < allElements.length; idx++) {
		// 				allElements[idx].classList.remove('hidden_content');
		// 			}
		// 		}
		// 	}
		// }
    </script>






