$(document).ready(function () {

    $('.numberonly').keypress(function (e) {

        var charCode = (e.which) ? e.which : event.keyCode

        if (String.fromCharCode(charCode).match(/[^0-9]/g))

            return false;
    });

    /*-----ADD & UPDATE DATA--------*/
    $(document).on("click", '[data-request="ajax-submit"]', function () {
        /*REMOVING PREVIOUS ALERT AND ERROR CLASS*/
        $(".is-invalid").removeClass("is-invalid");
        $(".help-block").remove();
        var $this = $(this);
        var $target = $this.data("target");
        var $url = $(this).data("action") ? $(this).data("action") : $($target).attr("action");
        var $method = $(this).data("action") ? "POST" : $($target).attr("method");
        var $redirect = $($target).attr("redirect");
        var $reload = $($target).attr("reload");
        var $callback = $this.data("callback");
        var $data = new FormData($($target)[0]);
        if (!$method) {
            $method = "get";
        }
        $this.prop('disabled', true);
        $.ajax({
            url: app_url + $url,
            data: $data,
            cache: false,
            type: $method,
            dataType: "JSON",
            contentType: false,
            processData: false,
            beforeSend: function () {
                $('#loaderDiv').show();
                $('.help-block').remove();
            },
            success: function ($response) {
                $('#loaderDiv').hide();
                if ($response.status === 200) {
                    // $($target).trigger("reset");
                    $this.prop('disabled', false);
                    toast("success", $response.message);
                    if ($callback) {
                        data = $response.data;
                        eval($callback);
                    }
                    setTimeout(function () {
                        if($response.redirect_url){
                            window.location.href = app_url + $response.redirect_url;
                        }
                        else if ($redirect) {
                            window.location.href = app_url + $redirect;
                        } else if ($reload) {
                            location.reload();
                        }
                    }, 2200);
                }
            },
            error: function ($response) {

                $('#loaderDiv').hide();
                $this.prop('disabled', false);
                if ($response.status === 422) {
                    if (
                        Object.size($response.responseJSON) > 0 &&
                        Object.size($response.responseJSON.errors) > 0
                    ) {
                        show_validation_error($response.responseJSON.errors);
                    }
                } else {
                    toast("warning", $response.responseJSON.message);
                    setTimeout(function () { }, 1200);
                }
            },
        });
    });

    /*-----DELETE DATA--------*/
    $(document).on("click", '[data-request="remove"]', function () {
        var $this = $(this);
        var $message = $this.attr("data-message");
        var $url = $this.attr("data-url");
        var $redirect = $this.attr("data-redirect");
        var $reload = $this.attr("data-reload");
        var $callback = $this.attr("data-callback");
        console.log($redirect);
        Swal.fire({
            title: "Alert! ",
            text: $message ? $message : "Are you sure you want to delete ?",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, please!",
        }).then((result) => {
            if (result.value) {
            $('#loaderDiv').show();

                $.ajax({
                    url: app_url + $url,
                    type: "DELETE",
                    success: function (data) {
                        toast("success", data.message);
                        $('#loaderDiv').hide();

                        $this.closest('tr')
                            .children('td')
                            .animate({
                                padding: 0
                            })
                            .wrapInner('<div/>')
                            .children()
                            .slideToggle(function () {
                                $(this).closest('tr').remove();
                        });

                        $this.closest('.remove-div').remove();


                        setTimeout(function () {
                            if ($redirect) {
                                window.location.href = app_url + $redirect;
                            }
                            else{
                                location.reload();
                            }
                        }, 1000);
                    },
                    error: function (data) {
                        $('#loaderDiv').hide();
                        toast("warning", data.responseJSON.message);
                        setTimeout(function () { }, 1200);
                    },
                });
            }
        });
    });

    // Ajax Save & Update with Confirmation
    $(document).on("click", '[data-request="ajax-submit-confirm"]', function () {
        $(".is-invalid").removeClass("is-invalid");
        $(".help-block").remove();
        var $this = $(this);
        var $message = $this.attr("data-message");
        var $target = $this.data("target");
        var $url = $this.attr("data-url");
        var $method = $(this).data("action") ? "POST" : $($target).attr("method");
        var $redirect = $($target).attr("redirect");
        var $reload = $this.attr("data-reload");
        if (!$method) {
            $method = "get";
        }
        $this.prop('disabled', true);
        Swal.fire({
            title: "Alert! ",
            text: $message ? $message : "Are you sure ?",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, please!",
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: app_url + $url,
                    type: "POST",
                    beforeSend: function () {
                        $('#loaderDiv').show();
                    },
                    success: function ($response) {
                        $('#loaderDiv').hide();
                        if ($response.status === 200) {
                            $($target).trigger("reset");
                            $this.prop('disabled', false);
                            toast("success", $response.message);

                            setTimeout(function () {
                                if ($redirect) {
                                    window.location.href = app_url + $redirect;
                                } else if ($reload) {
                                    location.reload();
                                }
                            }, 2200);
                        }
                    },
                    error: function ($response) {
                        $('#loaderDiv').hide();
                        $this.prop('disabled', false);
                        if ($response.status === 422) {
                            if (
                                Object.size($response.responseJSON) > 0 &&
                                Object.size($response.responseJSON.errors) > 0
                            ) {
                                show_validation_error($response.responseJSON.errors);
                            }
                        } else {
                            toast("warning", $response.responseJSON.message);
                            setTimeout(function () { }, 1200);
                        }
                    },
                });
            } else{
                $this.prop('disabled', false);
            }
        });
    });
});

function show_validation_error(msg) {
    if ($.isPlainObject(msg)) {
        $data = msg;
    } else {
        $data = $.parseJSON(msg);
    }

    $(".card-header").removeAttr("style");
    $.each($data, function (index, value) {
        value = value[0];
        var name = index.replace(/\./g, "][");

        if (index.indexOf(".") !== -1) {
            name = name + "]";
            name = name.replace("]", "");
        }
        if (name.indexOf("[]") !== -1) {
            console.log('1');
            $('form [name="' + name + '"]').closest('.collapse').prev('.card-header').attr("style", "background-color: #bf8888ba;");
            $('form [name="' + name + '"]')
                .last()
                .closest("")
                .addClass("is-invalid error");
            $('form [name="' + name + '"]')
                .last()
                .closest(".input-group")
                .find("")
                .append(
                    '<span class="help-block text-danger">' +
                    value +
                    "</span>"
                );
        } else if ($('form [name="' + name + '[]"]').length > 0) {
            console.log('2');
            console.log(name);
            if($('form [name="' + name + '[]"]').length > 1) {
                console.log("ok");
                $('form [name="' + name + '"]').closest('.collapse').prev('.card-header').attr("style", "background-color: #bf8888ba;");
                $('form [name="' + name + '[]"]')
                    .closest(".input-group")
                    .addClass("is-invalid error");
                    $('form [name="' + name + '[]"]:input:last')
                    .parent()
                    .after(
                        '<span class="help-block text-danger">' +
                        value +
                        "</span>"
                    );
            } else{
                $('form [name="' + name + '"]').closest('.collapse').prev('.card-header').attr("style", "background-color: #bf8888ba;");
                $('form [name="' + name + '[]"]')
                    .closest(".input-group")
                    .addClass("is-invalid error");
                $('form [name="' + name + '[]"]')
                    .parent()
                    .after(
                        '<span class="help-block text-danger">' +
                        value +
                        "</span>"
                    );
            }

        } else {
            if (
                $('form [name="' + name + '"]').attr("type") == "checkbox" ||
                $('form [name="' + name + '"]').attr("type") == "radio"
            ) {
                if (

                    $('form [name="' + name + '"]').attr("type") == "checkbox"
                ) {
                    console.log('3');
                    $('form [name="' + name + '"]').closest('.collapse').prev('.card-header').attr("style", "background-color: #bf8888ba;");
                    $('form [name="' + name + '"]')
                        .closest(".input-group")
                        .addClass("is-invalid error");
                    $('form [name="' + name + '"]')
                        .parent()
                        .after(
                            '<span class="help-block text-danger">' +
                            value +
                            "</span>"
                        );
                } else {
                    console.log('4');
                    $('form [name="' + name + '"]').closest('.collapse').prev('.card-header').attr("style", "background-color: #bf8888ba;");
                    $('form [name="' + name + '"]')
                        .closest(".input-group")
                        .addClass("is-invalid error");
                    $('form [name="' + name + '"]')
                        .parent()
                        .parent()
                        .append(
                            '<span class="help-block text-danger">' +
                            value +
                            "</span>"
                        );
                }
            } else if ($('form [name="' + name + '"]').get(0)) {
                if (
                    $('form [name="' + name + '"]').get(0).tagName == "SELECT"
                ) {
                    console.log('5');
                    $('form [name="' + name + '"]').closest('.collapse').prev('.card-header').attr("style", "background-color: #bf8888ba;");
                    $('form [name="' + name + '"]')
                        .closest(".input-group")
                        .addClass("is-invalid error");
                    $('form [name="' + name + '"]')
                        .parent()
                        .after(
                            '<span class="help-block text-danger">' +
                            value +
                            "</span>"
                        );
                } else if (
                    $('form [name="' + name + '"]').attr("type") ==
                    "password" &&
                    $('form [name="' + name + '"]').hasClass(
                        "hideShowPassword-field"
                    )
                ) {
                    console.log('6');
                    $('form [name="' + name + '"]').closest('.collapse').prev('.card-header').attr("style", "background-color: #bf8888ba;");
                    $('form [name="' + name + '"]')
                        .closest(".input-group")
                        .addClass("is-invalid error");
                    $('form [name="' + name + '"]')
                        .parent()
                        .after(
                            '<span class="help-block text-danger">' +
                            value +
                            "</span>"
                        );
                } else {
                    console.log('7');
                    $('form [name="' + name + '"]').closest('.collapse').prev('.card-header').attr("style", "background-color: #bf8888ba;");
                    $('form [name="' + name + '"]')
                        .closest(".input-group")
                        .addClass("is-invalid");
                    $('form [name="' + name + '"]').after(
                        '<span class="help-block text-danger" role="alert">' +
                        value +
                        "</span>"
                    );
                }
            } else {
                console.log('8',name);
                console.log('8',value);
                // console.log('here');
                $('form [name="' + name + '"]').closest('.collapse').prev('.card-header').attr("style", "background-color: #bf8888ba;");
                $('form [name="' + name + '"]')
                    .closest(".input-group")
                    .addClass("is-invalid error");
                $('form [name="' + name + '"]').after(
                    '<span class="help-block text-danger">' +
                    value +
                    "</span>"
                );

                name = name.replace(/\[\d+\]$/, '');
                if($(`[name='${name}[]']`).length) {
                    $('form [name="' + name + '"]')
                    .closest(".input-group")
                    .addClass("is-invalid error");
                    $('form [name="' + name +'[]'+ '"]').after(
                        '<span class="help-block text-danger">' +
                        value +
                        "</span>"
                    );
                }

            }
        }
        // $('.error-message').html($('.error-message').text().replace(".,",". "));
    });

    /*SCROLLING TO THE INPUT BOX*/
    scroll();
}

Object.size = function (obj) {
    var size = 0,
        key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

// ############### Set Dropdown ###############
function dropdown(url, selected_id, selected_value) {
    $.ajax({
        beforeSend: function (xhr) { },
        url: app_url + url,
        method: "GET",
        dataType: "json",
        success: function (response) {
            $("#" + selected_id + "").empty("");
            var options = '<option value="">select</option>';
            if (response.status === 200) {
                var option_list = response.data;
                $.each(option_list, function (index, value) {
                    let selected =
                        parseInt(selected_value) === value.id ? "selected" : "";
                    let name = value.name;
                    options += '<option value="' + value.id + '" ' + selected + ">" + name + "</option>";
                });
                $("#" + selected_id + "").append(options);
                // $('.select2').trigger(); // Notify only Select2 of changes

            }
        },
    });
}

function dropdownSelect2(url, selected_id, selected_value) {
    $.ajax({
        beforeSend: function (xhr) { },
        url: app_url + url,
        method: "GET",
        dataType: "json",
        success: function (response) {
            $("#" + selected_id + "").empty("");
            var options = '<option value="">select</option>';
            if (response.status === 200) {
                var option_list = response.data;
                $.each(option_list, function (index, value) {
                    let selected =
                        parseInt(selected_value) === value.id ? "selected" : "";
                    let name = value.name;
                    options += '<option value="' + value.id + '" ' + selected + ">" + name + "</option>";
                });
                $("#" + selected_id + "").append(options);
                $('.select2').trigger(); // Notify only Select2 of changes
            }
        },
    });
}

$('.fileInput').bind('change', function () {
    var size = this.files[0].size;
    var file_size = size / 1000;
    if (file_size > 7000) {
        setTimeout(function () {
            $(".fileInput").val(null);
            toast("warning", "Document Size is greater than 7MB.");
        }, 1000);
    }
});


function toast(icon, message) {
    if (icon === "success") {
        flasher.success(message);
    } else if (icon === "warning") {
        flasher.warning(message)
    } else {
        flasher.info(message);
    }
}

function renderImage(input, render_place_id, render_link_id = null) {
    if (input.files && input.files[0]) {
        var fileType = input.files[0].type;
        var size = input.files[0].size;

        if(((size / 1024)/1024) > 5){
            input.value = '';
            $('#' + render_place_id + '').attr('src', '');
            Swal.fire(
                'Warning!', 'You can upload maximum of 5 MB each file.<br>Kindly select again.', 'warning',{
                    html: true,
            });
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
    }
}

function removeFile(value_id, render_id) {
    $('#' + value_id + '').val('');
    $('#' + render_id + '').attr('src', '/assets/img/edit-user.png');
}

    // function showPassword() {
    //     var password = document.getElementById("password");
    //     var password_icon = document.getElementById("password-icon");
    //     if (password.type === "password") {
    //         password.type = "text";
    //         password_icon.src = app_url + "/assets/img/lock.svg";
    //     } else {
    //         password.type = "password";
    //         password_icon.src = app_url + "/assets/img/Unlock.svg";
    //     }
    // }

    function showPassword(tabModule) {
        $('#' + tabModule + 'InputPassword').attr('type', 'text');
        $('#' + tabModule + 'ShowPassword').hide();
        $('#' + tabModule + 'HidePassword').show();
    }

    function hidePassword(tabModule) {
        $('#' + tabModule + 'InputPassword').attr('type', 'password');
        $('#' + tabModule + 'ShowPassword').show();
        $('#' + tabModule + 'HidePassword').hide();
    }

    // let nav = document.querySelector("header");
    //     window.onscroll = function () {
    //     if(document.documentElement.scrollTop > 20){
    //     nav.classList.add("scroll-on");
    //     }else{
    //         nav.classList.remove("scroll-on");
    //     }
    // }

    function viewUserType(userType){
        switch(userType){
            case 'dde':
            case 'mcc':
                userType = userType.toUpperCase();
                break;
            case 'farmer':
            case 'supplier':
                userType = userType.charAt(0).toUpperCase() + userType.slice(1).toLowerCase();
                break;
        }

        return userType;
    }

    function changeDropdownOptions(mainDropdownElement, dependentDropdownIds, dataKeyNames, routeUrl, resetDropdowns = null, resetDropdownIdsArray = [],extraId = null, extraKey='')
    {

        const mainDropdown = mainDropdownElement;
        const secondDropdowns = [];
        const dataKeysForApi = [];
        if (Array.isArray(dependentDropdownIds)) {
            dependentDropdownIds.forEach(elementId => {
                if (elementId.type && elementId.type == "class") {
                    const multipleUiDropDowns = document.getElementsByClassName(elementId.value);
                    const secondDropdownInternal = [];
                    for (let idx = 0; idx < multipleUiDropDowns.length; idx++) {
                        secondDropdownInternal.push(document.getElementById(multipleUiDropDowns[idx].id));
                    }
                    secondDropdowns.push(secondDropdownInternal);
                } else {
                    secondDropdowns.push(document.getElementById(elementId));
                }
            });
        } else {
            secondDropdowns.push(document.getElementById(dependentDropdownIds))
        }

        if (Array.isArray(dataKeyNames)) {
            dataKeyNames.forEach(key => {
                dataKeysForApi.push(key);
            })
        } else {
            dataKeysForApi.push(dataKeyNames);
        }

        if (dataKeysForApi.length !== secondDropdowns.length) {
            console.log("Dropdown function error");
            return;
        }

        if (resetDropdowns) {
            const resetDropdownsElement = document.getElementsByClassName(resetDropdowns);
            for (let index = 0; index < resetDropdownsElement.length; index++) {
                resetDropdownsElement[index].innerHTML = `<option value = '0'>Select</option>`;
            }
        }

        if (resetDropdownIdsArray) {
            if (Array.isArray(resetDropdownIdsArray)) {
                resetDropdownIdsArray.forEach(elementId => {
                    let currentResetElement = document.getElementById(elementId);
                    if (currentResetElement) {
                        currentResetElement.innerHTML = `<option value = '0'>Select</option>`;
                    }
                });
            } else {
                const singleResetElement = document.getElementById(resetDropdownIdsArray);
                if (singleResetElement) {
                    singleResetElement.innerHTML = `<option value = '0'>Select</option>`;
                }
            }
        }

        const apiRequestValue = mainDropdown?.value;
        var apiUrl = routeUrl + apiRequestValue;
        if (extraKey && extraId){
            apiUrl = apiUrl+"?"+extraKey+"="+extraId

        }
        fetch(apiUrl, {
            method : "GET",
            headers : {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
        }).then(response => response.json()).then(data => {
            secondDropdowns.forEach((currentElement, idx) => {
                if (Array.isArray(currentElement)) {
                    currentElement.forEach(currentElementInternal => {
                        currentElementInternal.innerHTML = `<option value = '0'>Select</option>`;
                        const response = data.data;
                        response?.[dataKeysForApi[idx]]?.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.value;
                            option.textContent = item.label;
                            currentElementInternal.appendChild(option);
                        })
                    });
                } else {
                    currentElement.innerHTML = `<option value = '0'>Select</option>`;
                    const response = data.data;
                    response?.[dataKeysForApi[idx]]?.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.value;
                        option.textContent = item.label;
                        currentElement.appendChild(option);
                    })
                }
            });
        }).catch(error => {
            console.log("Error : ", error);
        })
    }

    function goBackWithReload() {
        window.location.href = document.referrer;
    }

    document.querySelectorAll('input[type=number]').forEach(function (input) {
        input.addEventListener('wheel', function (event) {
            event.preventDefault();
        });
    });

    function getLightColorFromInitials(name) {
        // Step 1: Extract initials
        const initials = name.split(' ').map(word => word[0]).join('').toUpperCase();

        // Step 2: Convert initials to numerical value
        let hash = 0;
        for (let i = 0; i < initials.length; i++) {
            hash = initials.charCodeAt(i) + ((hash << 5) - hash);
        }

        // Step 3: Generate color code
        let color = '#';
        for (let i = 0; i < 3; i++) {
            let value = (hash >> (i * 8)) & 0xFF;
            // Step 4: Adjust to light color
            value = Math.min(value + 150, 255); // Ensuring the color is light
            color += ('00' + value.toString(16)).substr(-2);
        }

        return color;
    }
