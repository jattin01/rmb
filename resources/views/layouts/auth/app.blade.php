<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ config('app.name', 'Ant Fast') }}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/jquery-ui.min.css') }}">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"rel="stylesheet"/>

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css">
    <link href="{{ asset('assets/css/font-awesome.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/fullcalendar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app-calendar.css') }}">

    <script src = "{{asset('assets/js/flash.min.js')}}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owl.theme.default.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/flash.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dev.css') }}">

    <link href="{{ asset('assets/css/material.css') }}" rel="stylesheet">

    <script href="{{asset('assets/js/flasher-js.min.js')}}"></script>

    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/fontawesome-free/css/flasher.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/stylesheet.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/responsive.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-select.css') }}" rel="stylesheet">

    <script src="{{ asset('assets/js/lottie_player.js') }}"></script>

    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>

    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-firestore.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-storage.js"></script>



    <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.google_map_key')}}&libraries=marker,places&v=weekly&loading=async" async defer></script>


    <style>
        .pac-container {
            z-index: 1051 !important; /* Ensure it's higher than the modal's z-index */
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed sidebar-collapse">
    <div class="wrapper">
        <!-- BEGIN: Sidebar-->
        @include('layouts.auth.sidebar')
        <!-- END: Sidebar-->
        <div class="content-wrapper">
            @include('layouts.auth.header')
            <!-- BEGIN: Content-->
            @yield('content')
            <!-- END: Content-->
            @include('layouts.auth.footer')
        </div>
    </div>
    @include('partials.scripts')
    <div class="loaderDiv" id="loaderDiv" style="display:none;">
        <!-- Loader -->
        <div class="loader"></div>
    </div>
    <script>
        var app_url = "{{ config('app.url') }}";
        var bearer_token = 'Bearer <?= session('auth_access_token') ?>';
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajaxSetup({
            headers: {
                'Access-Control-Allow-Origin': '*',
                'Authorization': bearer_token,
                'X-CSRF-TOKEN': csrfToken
            }
        });

		const firebaseConfig = {
    		apiKey: "{{config('app.google_map_key')}}",
		    authDomain: "{{config('app.firebase_auth_domain')}}",
		    projectId: "{{config('app.firebase_project_id')}}",
		    storageBucket: "{{config('app.firebase_storage_bucket')}}",
		    messagingSenderId: "{{config('app.firebase_messaging_sender_id')}}",
		    appId: "{{config('app.firebase_app_id')}}"
        };

        // Initialize Firebase
        var myFirebaseApp = firebase.initializeApp(firebaseConfig);
        var myFirebaseDb = firebase.firestore();

        enableScript();

        let liveOrders = [];

        let notificationContainer = document.getElementById('mainNotificationContainer');

	function enableScript()
    {
        requestNotificationPermission();

        // Fetch existing messages on page load and set up real-time listener
        myFirebaseDb.collection("live_orders").get().then((querySnapshot) => {
            console.log("First time render");
			setLiveOrders(querySnapshot);
        });

        // Real-time listener for new messages
        myFirebaseDb.collection("live_orders").onSnapshot((snapshot) => {
            console.log("Real time render");
            setLiveOrders(snapshot);
        });


        //For Notifications
        myFirebaseDb.collection("notifications").doc("{{auth() -> user() -> id}}").collection('reminders').orderBy('created_at', 'desc').onSnapshot((snapshot) => {
            let notifcationHtml = ``;
            let notificationDotFlag = false;
            snapshot.forEach((doc) => {
				if (doc.exists) {
					const data = doc.data();
                    const docID = doc.id;
					if (!data.is_read) {
						notificationDotFlag = true;
					}
                    let unreadHtml = ``;
                    let onMouseHover = ``;
                    if (!data.is_read) {
                        unreadHtml = `
                        <div class="col-md-1 col-1 text-right">
							<i class="fa fa-circle" aria-hidden="true"></i>
						</div>
                        `;
                        onMouseHover = `
                        onmouseover = "markAsRead(this);"`;
                    }
                    notifcationHtml += `
                    <div class="new-notificationcontentbox mt-4 mb-4" data-docid = "${docID}" ${onMouseHover} >
									<div class="row">
										<div class="col-md-2 col-3">
												<img src="{{asset('assets/img/transit_mixer_icon.svg')}}" alt="">
										</div>
										<div class="col-md-9 col-8">
											<h6>${data.body}</h6>
											<h4>${moment(data?.created_at?.toDate()).format('DD MMM, YYYY h:mm A')}</h4>
										</div>
										${unreadHtml}
									</div>
								</div>
                    `;
				}
			});
            if (notificationDotFlag) {
                document.getElementById('notify-dot').classList.remove('hidden_content');
            } else {
                document.getElementById('notify-dot').classList.add('hidden_content');
            }
            notificationContainer.innerHTML = notifcationHtml;
        });

        checkChatDot();
    }

    function setLiveOrders(snapshot)
    {
        let liveOrdersTemp = [];
        snapshot.docChanges().forEach((liveTrip) => {
            let liveTripData = liveTrip.doc.data();
            liveTripData.docId = liveTrip.doc.id;
            liveOrdersTemp.push(liveTripData);
        })
        liveOrders = liveOrdersTemp;
    }

    function checkLiveOrders()
    {
        liveOrders.forEach(liveTripData => {
            if (!liveTripData.has_notified) {
                const tripStartTime = new Date(liveTripData.planned_loading_start);
                const now = new Date();
                if (now.getTime() >= tripStartTime.getTime())
                {
                    myFirebaseDb.collection("live_orders").doc(liveTripData.docId).update({
                        has_notified : true
                    });
                    showAlert(liveTripData);
                }
            }
        });
    }

    setInterval(checkLiveOrders, 1000);

    function showAlert(trip)
    {
        if (Notification.permission === "granted") {
            let notification = new Notification("Trip Start Reminder", {
                body : "Order #" + trip.order_no + " - Trip " + trip.trip + " is scheduled to start now ( " + moment(trip?.created_at).format('DD MMM, YYYY h:mm A') + ")",
            })

            // Optional: Add an event listener for when the user clicks the notification
            notification.onclick = function(event) {
                event.preventDefault(); // Prevent the browser default behavior
                window.open("{{route('web.order.live.schedule')}}"); // Open a URL when the notification is clicked
            };
        }

        myFirebaseDb.collection("notifications").doc("{{auth() -> user() -> id}}").collection('reminders').add({
            title: "Trip Start Reminder",
            body : "Order #" + trip.order_no + " - Trip " + trip.trip + " is scheduled to start now ( " + moment(trip?.created_at).format('DD MMM, YYYY h:mm A') + ")",
            created_at: firebase.firestore.FieldValue.serverTimestamp(),
            user_name : "{{auth() -> user() -> name}}",
            user_id : "{{auth() -> user() -> id}}",
            is_read : false,
            redirect_web_url : "{{route('web.order.live.schedule')}}"
        }).then(() => {

        });
    }

    function markAsRead(element)
    {
        myFirebaseDb.collection("notifications").doc("{{auth() -> user() -> id}}").collection('reminders').doc(element.dataset.docid).update({
            is_read : true
        });
    }

    // Function to request notification permission
    function requestNotificationPermission() {
            if (Notification.permission === "default") {
                Notification.requestPermission().then(function(permission) {
                    if (permission === "granted") {
                        console.log("Notification permission granted.");
                    } else {
                        console.log("Notification permission denied.");
                    }
                });
            }
    }

    function checkChatDot()
    {
        fetch("{{route('chat.rooms.get.ids')}}", {
            method : "GET",
            headers : {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
        }).then(response => response.json()).then(data => {
            const rooms = data.rooms;
            rooms.forEach(room => {
				myFirebaseDb.collection('chat_rooms').doc(room.project_id.toString() + "_" + room.entity_type + "_" + room.entity_id).collection('chats').orderBy('created_at', 'desc')
                    .onSnapshot((snapshot) => {
                        let chatDotFlag = false;
						snapshot.forEach((doc) => {
							if (doc.exists) {
								const data = doc.data();
								if (data.readBy && !data.readBy.includes("{{auth() -> user() -> id}}")) {
									chatDotFlag = true;
								}
							}
						});
                        if (chatDotFlag) {
                            document.getElementById('chat-dot').classList.remove('hidden_content');
                        } else {
                            document.getElementById('chat-dot').classList.add('hidden_content');
                        }
                    }, function (error) {
                        console.log("Error getting document:", error);
                	});
			});
        }).catch(error => {
            console.log("Error : ", error);
        })
    }

    </script>
    <script src="{{ asset('assets/js/common-scripts.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert.js') }}"></script>
    <script src="{{ asset('assets/js/toast.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <script>
        // $('.dropdown-menu').on('click', function(event){
        //     event.stopPropagation();
        // });
    </script>
    <!-- BEGIN: Script-->
    @yield('scripts')
    <!-- END: Script-->
</body>

</html>
