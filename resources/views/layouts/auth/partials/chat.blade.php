<div class="modal fade chat-modal new-chatmodel" id="main-chat-box" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            {{-- <div class="modal-header border-0">
					<div class="d-flex align-items-center">
						<span class="chat-headtext">{{App\Helpers\CommonHelper::getNameInititals($room -> entity ?-> name)}}</span>
					<h6 class="modal-title" id="exampleModalLabel">{{$room -> entity ?-> name . " (" . $room -> entity_type . ")"}} <br>
						 <span class="small-textchat">{{$room -> project ?-> name}}</span>
					</h6>
					</div>

					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<img src="{{asset('assets/img/close-pop.svg')}}" class="close-img float-right" alt="">
					</button>
				</div> --}}

            <div class="modal-header border-0">
                <div class="d-flex">
                    <div>
                        <span
                            class="chat-headtext">{{ App\Helpers\CommonHelper::getNameInititals($room->entity?->name) }}</span>

                    </div>
                    <div>
                        <h5 class="modal-title" id="exampleModalLabel">
                            {{ $room->entity?->name . ' (' . $room->entity_type . ')' }} <br> <span
                                class="small-textchat">{{ $room->project?->name }}</span></h5>

                        <div class="row pt-3">
                            <div class="col-md-12">
                                <nav>
                                    <div class="nav nav-tabs chatmodal-tab" id="nav-tab" role="tablist">
                                        <button class="nav-link active" id="nav-home-tab" data-toggle="tab"
                                            data-target="#nav-home" type="button" role="tab"
                                            aria-controls="nav-home" aria-selected="true"
                                            onclick="changeTab('Customer')">Customer</button>
                                        <button class="nav-link" id="nav-profile-tab" data-toggle="tab"
                                            data-target="#nav-profile" type="button" role="tab"
                                            aria-controls="nav-profile" aria-selected="false"
                                            onclick="changeTab('Driver')">Driver</button>
                                    </div>
                                </nav>

                            </div>
                        </div>

                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <img src="img/close-pop.svg" class="close-img float-right" alt="">
                </button>

            </div>
            <div id="chat-header" class="px-3 pt-2">
            </div>
            <div class="modal-body chat-box-container" id="messages-container">

            </div>
            <form id="chatForm">
                <div class="chat-input position-relative">
                    <input type="text" class="form-control chatpadding-right" placeholder="Message..."
                        id = "message-input">
                    <canvas id="audioVisualizer"
                        style="display: none; height: 2rem; border-radius : 1rem; width: 90%;"></canvas>
                    <img src="{{ asset('assets/img/attachment.svg') }}" id = "attachment-icon" class="input-attachment"
                        alt="" onclick="document.getElementById('file-input').click();">
                    <img src="{{ asset('assets/img/microphone.svg') }}" id = "recorder-icon"
                        class="input-attachment-recorder" alt="" onclick = "voiceMessage();">
                    <img src="{{ asset('assets/img/microphone_live.svg') }}" id = "recorder-stop-icon"
                        style = "display:none;" class="input-attachment-recorder" alt=""
                        onclick = "stopMedia();">

                    <input type="file" id="file-input" class="hidden-file-custom-input"
                        onchange = "checkFileSize(this);">
                    <img src="{{ asset('assets/img/chat_send.svg') }}" class="input-attachment-send"
                        onclick = "sendChat();">
                </div>
            </form>

        </div>
    </div>
</div>


<script>
    var scriptInitialization = false;
    var unsubscribe;

    // Function to send a message
    function prepareMessage(message) {
        documentImage = document.getElementById('file-input').files[0];
        if (documentImage) {
            uploadFile(documentImage, message);
        } else {
            sendMessage(message);
        }

    }

    function uploadFile(file, message) {
        let file_name = file.name ? file.name : "DOC_" + Date.now() + "_" + "{{ auth()->user()->id }}.mp3";
        // Create a storage reference
        var storageRef = firebase.storage().ref('chat-uploads/' + file_name);

        // Upload the file
        var uploadTask = storageRef.put(file);

        uploadTask.on('state_changed', function(snapshot) {
            // Observe state change events such as progress, pause, and resume
            var progress = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
            console.log('Upload is ' + progress + '% done');
        }, function(error) {
            // Handle unsuccessful uploads
            console.error('Upload failed:', error);
        }, function() {
            // Handle successful uploads on complete
            uploadTask.snapshot.ref.getDownloadURL().then(function(downloadURL) {
                sendMessage(message, downloadURL, file.type);
            });
        });
    }

    function sendMessage(message, documentUrl = "", fileType = "") {

        let docId = localStorage.getItem('firebaseDocumentId') ? localStorage.getItem('firebaseDocumentId') :
            '{{ $room->firebaseDocumentId() }}'


        myFirebaseDb.collection("chat_rooms").doc(docId).collection("chats").add({
            text: message,
            created_at: firebase.firestore.FieldValue.serverTimestamp(),
            timestamp: firebase.firestore.FieldValue.serverTimestamp(),
            date: moment().format('MMMM D, YYYY'),
            file: documentUrl,
            message_type: documentUrl ? "file" : 'text',
            user_name: "{{ auth()->user()->name }}",
            user_id: "{{ auth()->user()->id }}",
            user_type: "{{ auth()->user()->user_type }}",
            readBy: ["{{ auth()->user()->id }}"]
        }).then(() => {
            document.getElementById('message-input').value = '';
            document.getElementById('attachment-icon').style.display = "block";
            document.getElementById('recorder-icon').style.display = "block";
            document.getElementById('message-input').style.display = "block";
            document.getElementById('audioVisualizer').style.display = "none";
            voiceMessageState = false;
        });
    }

    // Function to render messages
    function renderMessages(snapshot) {
        var messages = document.getElementById('messages-container');

        let messagesHtml = ``;
        let prevMessageDate = null;
        snapshot.forEach((doc) => {
            if (doc.data().readBy && !doc.data().readBy.includes("{{ auth()->user()->id }}")) {
                myFirebaseDb.collection("chat_rooms").doc("{{ $room->firebaseDocumentId() }}").collection(
                    "chats").doc(doc.id).update({
                    readBy: firebase.firestore.FieldValue.arrayUnion("{{ auth()->user()->id }}")
                });
            }
            let currentMessageDate = (doc.data().created_at?.toDate())?.setHours(0, 0, 0, 0);
            let sameUser = doc.data().user_id == "{{ auth()->user()->id }}";
            let rowClassName = sameUser ? 'row mt-3 justify-content-end' : 'row mt-3';
            let divClassName = sameUser ? 'white-cahtbox' : 'dark-cahtbox';
            let imageUi = ``;
            let chatDetailUi = ``;
            let dateDividerUi = ``;
            if (prevMessageDate == null || prevMessageDate < currentMessageDate) {
                dateDividerUi = `
                            <div class="row mt-4 mb-4">
                                <div class="col-md-12 text-center">
                                    <span class="chat-timingbadge">${moment(doc.data().created_at?.toDate())?.format("ddd, DD MMM YYYY")}</span>
                                </div>
                            </div>`;
            }
            if (sameUser) {
                chatDetailUi = `
                            <div class = "row">
                                <div class = "col-6">
                                    <p style = "text-align: left; color: #000; font-size: 11px; margin-bottom: 0px; padding-top: 4px;">${moment(doc.data().created_at?.toDate())?.format('h:mm A')}</p>
                                </div>
                                <div class = "col-6">
                                <p style = "text-align: right; color: #000; font-size: 11px; margin-bottom: 0px; padding-top: 4px;">You</p>
                                </div>
                            </div>
                        `;
            } else {
                chatDetailUi = `
                        <div class = "row">
                                <div class = "col-6">
                                    <p style = "text-align: left; color: #000; font-size: 11px; margin-bottom: 0px; padding-top: 4px;">${doc.data().user_name}</p>
                                </div>
                                <div class = "col-6">
                                <p style = "text-align: right; color: #000; font-size: 11px; margin-bottom: 0px; padding-top: 4px;">${moment(doc.data().created_at?.toDate())?.format('h:mm A')}</p>
                                </div>
                            </div>
                        `;
            }
            if (doc.data().file) {
                if (checkImage(doc.data().file)) {
                    imageUi = `<a href = "${doc.data().file}" target = "_blank" >
                            <img src = "${doc.data().file}" class = "chat-message-file" />
                            </a>
                            `;
                } else if (checkAudio(doc.data().file)) {
                    imageUi =
                        `<audio id="audioPlaybackCustom" src = '${doc.data().file}' controls style = "width : 100%;" ></audio>`;
                } else {
                    imageUi = `<a href = "${doc.data().file}" target = "_blank" >
                            <i class = "fa fa-file fa-2x mb-2" class = "chat-message-file" />
                            </a>
                            `;
                }
            }
            if (checkAudio(doc.data().file)) {
                messagesHtml += `
                        ${dateDividerUi}
                        <div class="${rowClassName}">
                            <div class="col-md-7 col-9">
                                    ${imageUi}
                                    <h6>${doc.data().text}</h6>
                                ${chatDetailUi}
                            </div>
                        </div>
                        `;
            } else {
                messagesHtml += `
                        ${dateDividerUi}
                        <div class="${rowClassName}">
                            <div class="col-md-7 col-9">
                                <div class="${divClassName}">
                                    ${imageUi}
                                    <h6>${doc.data().text}</h6>
                                </div>
                                ${chatDetailUi}
                            </div>
                        </div>
                        `;
            }
            prevMessageDate = (doc.data().created_at)?.toDate();
        });
        messages.innerHTML = messagesHtml + '<div id = "file_preview"> </div>';
    }
    // start driver list

    function renderDriverList(data) {
        let driverListHtml = '';
        let firstDocId = '';
        data.forEach((element, index) => {
            if (index == 0) {
                firstDocId = element.firebase_doc_id
            }
            driverListHtml += `
            <div class="col-md-5 col-7">
                <div class="user-chatcards" id="${element.firebase_doc_id}" onclick="handleCardClick('${element.firebase_doc_id}', this)">
                    <div class="d-flex align-items-center">
                        <div>
                            <span class="modal-chatuser">{{ App\Helpers\CommonHelper::getNameInititals($room->entity?->name) }}
                            </span>
                        </div>
                        <div>
                            <h5>${element.entity.name}</h5>
                            <h6>${element.project.name}</h6>
                        </div>
                    </div>
                </div>
            </div>`;
        });

        console.log(driverListHtml);
        $('#chat-header').html('<div class="row noflexwrap">' + driverListHtml + '</div>');
        if (firstDocId) {
            handleCardClick(firstDocId ,document.getElementById(firstDocId))
        }
    }

    function changeTab(type) {

        if (type == 'Customer') {
            localStorage.setItem('firebaseDocumentId', '')
            $('#chat-header').html('');

            enableScript();
        } else {
            fetch('/chat/get/driver/chatroom/' + '{{ $room->project_id }}', {
                method: "GET",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
            }).then(response => response.json()).then(data => {

                renderDriverList(data.data)

            }).catch(error => {
                console.log("Error : ", error);
            })

        }
    }

    // Handle  driver card click
    function handleCardClick(firebaseDocumentId, th) {
        $('.user-chatcards').removeClass('active');
        localStorage.setItem('firebaseDocumentId', firebaseDocumentId);
        $(th).addClass('active');

        enableScript(firebaseDocumentId);
    }


    //end driver list

    function enableScript(firebaseDocumentId = '') {
        let currentDocId = firebaseDocumentId ? firebaseDocumentId : '{{ $room->firebaseDocumentId() }}'

        // Fetch existing messages on page load and set up real-time listener
        myFirebaseDb.collection("chat_rooms").doc(currentDocId).collection("chats").orderBy(
            "created_at").get().then((querySnapshot) => {
            renderMessages(querySnapshot);
            scrollIntoLastChat();
        });

        // Real-time listener for new messages
        unsubscribe = myFirebaseDb.collection("chat_rooms").doc(currentDocId).collection(
            "chats").orderBy("created_at").onSnapshot((snapshot) => {
            renderMessages(snapshot);
            scrollIntoLastChat();
        });
    }

    function sendChat() {
        if (voiceMessageState == true) {
            stopMedia();
        } else {
            var message = document.getElementById('message-input').value;
            if (message.trim() !== '') {
                prepareMessage(message);
            }
        }
    }

    // Handle form submission
    document.getElementById('chatForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        var message = document.getElementById('message-input').value;
        if (message.trim() !== '') {
            prepareMessage(message);
        }
    });

    function sendVoiceMessage() {
        var messageVal = document.getElementById('message-input').value;

        if (audioBlob) {
            uploadFile(audioBlob, messageVal);
        } else {
            sendMessage(messageVal);
        }
    }

    enableScript();

    $('#main-chat-box').on('hidden.bs.modal', function() {
        if (unsubscribe) {
            unsubscribe();
            console.log('Firestore listener stopped');
        }
        $('#chatModal').modal('show');
    });

    function checkFileSize(element) {
        if (element.files[0].size > 10485760) {
            flasher.info('Please select a file less than 10 MB')
            element.value = "";
        } else {
            addFilePreview(element.files[0]);
        }
    }

    function addFilePreview(file) {
        var filePreview = document.getElementById('file_preview');

        const reader = new FileReader();
        reader.onload = function(e) {
            var htmlData = ``;

            if (file.type.startsWith('audio/')) {}

            if (!file.type.startsWith('image/')) {
                htmlData = `
                            <div class="col-md-2">
                                <div class="upload-image">
                                    <i class = "fa fa-file fa-2x mb-2" style = "padding: 1.5rem;"/>
                                    <span class="upload-imagecross" on click = "removeFile();"><i class="fa fa-times" aria-hidden="true"></i></span>
                                </div>
                                ${file.name}
                            </div>
                        `;
            } else {
                htmlData = `
                            <div class="col-md-2">
                                <div class="upload-image">
                                    <img src="${e.target.result}" alt="">
                                    <span class="upload-imagecross" onclick = "removeFile();"><i class="fa fa-times" aria-hidden="true"></i></span>
                                </div>
                                ${file.name}
                            </div>
                        `;
            }

            filePreview.innerHTML = htmlData;
        }

        reader.readAsDataURL(file);
    }

    function scrollIntoLastChat() {
        var chatListDiv = document.getElementById('messages-container');
        chatListDiv.scrollTo(0, chatListDiv.scrollHeight);
    }

    function checkImage(url) {
        let path = url.split('?')[0].split('#')[0];
        return (path.match(/\.(jpeg|jpg|gif|png)$/i) != null);
    }

    function checkAudio(url) {
        let path = url.split('?')[0].split('#')[0];
        return (path.match(/\.(mp4)$/i) != null);
    }

    function removeFile() {
        document.getElementById('file-input').value = '';
        document.getElementById('file_preview').innerHTML = ``;
    }

    var mediaRecorder;
    var audioChunks = [];
    var audioBlob;
    var audioContext;
    var analyser;
    var dataArray;
    var bufferLength;
    var visualizerContext;
    var visualizerCanvas = document.getElementById('audioVisualizer');
    var visualizerContext = visualizerCanvas.getContext('2d');
    var voiceMessageState = false;

    async function voiceMessage() {

        const stream = await navigator.mediaDevices.getUserMedia({
            audio: true
        });
        mediaRecorder = new MediaRecorder(stream);

        audioChunks = []; // Clear any previous recordings
        audioBlob = null;

        mediaRecorder.ondataavailable = event => {
            audioChunks.push(event.data);
        };

        voiceMessageState = true;

        document.getElementById('attachment-icon').style.display = "none";
        document.getElementById('recorder-icon').style.display = "none";
        document.getElementById('message-input').style.display = "none";

        // Create AudioContext and analyser node for visualization
        audioContext = new(window.AudioContext || window.webkitAudioContext)();
        const source = audioContext.createMediaStreamSource(stream);
        analyser = audioContext.createAnalyser();
        source.connect(analyser);
        analyser.fftSize = 2048;
        bufferLength = analyser.frequencyBinCount;
        dataArray = new Uint8Array(bufferLength);

        // Start visualizing
        visualizerCanvas.style.display = 'block';

        drawVisualizer();

        mediaRecorder.onstop = () => {
            audioBlob = new Blob(audioChunks, {
                type: 'audio/mp3'
            });
            const audioUrl = URL.createObjectURL(audioBlob);
            if (voiceMessageState == true) {
                if (audioBlob) {
                    uploadFile(audioBlob, "");
                } else {
                    sendMessage("");
                }
            }
        };

        mediaRecorder.start();
    }

    function stopMedia() {
        if (mediaRecorder) {
            mediaRecorder.stop();
        }
    }

    function drawVisualizer() {
        requestAnimationFrame(drawVisualizer);
        analyser.getByteTimeDomainData(dataArray);
        visualizerContext.fillStyle = 'rgb(200, 200, 200)';
        visualizerContext.fillRect(0, 0, visualizerCanvas.width, visualizerCanvas.height);

        visualizerContext.lineWidth = 2;
        visualizerContext.strokeStyle = 'rgb(0, 0, 0)';

        visualizerContext.beginPath();
        const sliceWidth = visualizerCanvas.width * 1.0 / bufferLength;
        let x = 0;

        for (let i = 0; i < bufferLength; i++) {
            const v = dataArray[i] / 128.0;
            const y = v * visualizerCanvas.height / 2;

            if (i === 0) {
                visualizerContext.moveTo(x, y);
            } else {
                visualizerContext.lineTo(x, y);
            }

            x += sliceWidth;
        }

        visualizerContext.lineTo(visualizerCanvas.width, visualizerCanvas.height / 2);
        visualizerContext.stroke();
    }
</script>
