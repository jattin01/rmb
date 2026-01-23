<div class="col-md-5 order-sm-2 order-3">
    <ul class="nav nav-tabs resources-texttab" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{$active == 'home' ? 'active' : ''}}" id="home-tab"> <img
                    src="{{asset('assets/img/date.svg')}}" alt=""> Date</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{$active == 'profile' ? 'active' : ''}}" id="profile-tab">
                <img src="{{asset('assets/img/dark-menu.svg')}}" alt="">
                Orders</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{$active == 'contact' ? 'active' : ''}}" id="contact-tab"><img
                    src="{{asset('assets/img/dark-dash.svg')}}" alt=""> Resources</a>
        </li>
    </ul>
</div>

<script>
    var tab_id = "home-tab";

    function setTab(param_tab_id) {
        tab_id = param_tab_id;
        document.getElementById(param_tab_id).classList.add("active");
        if (param_tab_id === "home-tab") {
            document.getElementById("contact-tab").classList.remove("active");
            document.getElementById("profile-tab").classList.remove("active");
        } else if (param_tab_id === "profile-tab") {
            document.getElementById("contact-tab").classList.remove("active");
            document.getElementById("home-tab").classList.remove("active");
        } else if (param_tab_id === "contact-tab") {
            document.getElementById("home-tab").classList.remove("active");
            document.getElementById("profile-tab").classList.remove("active");
        }
    }
</script>