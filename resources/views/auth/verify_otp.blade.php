@extends('layouts.guest.app')
@section('content')
<section class="loginbg py-sm-5 py-4">
    <div class="down-rightarrowsignin">
        <img src="{{asset('assets/img/down-rightarrow.svg')}}" alt="">
    </div>
    <div class="container">
        <div class="row mb-sm-0 mb-3">
            <div class="col-md-12">
                <img src="{{asset('assets/img/logo.svg')}}" alt="">
            </div>
        </div>
        <div class="row align-items-center">
            <div class="col-md-6 mb-sm-0 mb-2">
                <div class="login-boxrightcontent">
                    <h4>
                        <span class="expolre-textclr">Explore must</span> <br />
                        <span class="see-places">see places</span>
                    </h4>
                    <p>Many desktop publishing packages and web page editors now <br class="d-sm-block d-none" /> use Lorem Ipsum as their
                                default model text...
                    </p>
                </div>   
            </div>   
            <div class="col-md-6">
                <div class="row justify-content-center">
                    <div class="col-md-9">
                        <div class="login-contentboxbg">
                            <form method="POST" action="{{ route('auth.forgot.password.otp.submit') }}">
                            @csrf
                                <div class="login-box">
                                    <h1>Forgot Password?</h1>
                                    <p>Verify OTP sent on phone number</p>
                                    <div class="form-group mt-sm-4 mt-3 d-flex justify-content-center otp-input">
                                        <input class="numberonly otpInput" autocomplete="off" data-next="2" id="n1"
                                            name="otp_1" type="text" maxlength="1" minlength="1" placeholder="*">
                                        <input class="numberonly otpInput" autocomplete="off" data-next="3" id="n2"
                                            name="otp_2" type="text" maxlength="1" minlength="1" placeholder="*">
                                        <input class="numberonly otpInput" autocomplete="off" data-next="4" id="n3"
                                            name="otp_3" type="text" maxlength="1" minlength="1" placeholder="*">
                                        <input class="numberonly" autocomplete="off" data-next="5" id="n4"
                                            name="otp_4" type="text" maxlength="1" minlength="1" placeholder="*">
                                    </div>
                                    <div class="row mt-4 mt-sm-3 mb-sm-3 justify-content-center">
                                        <div class="col-md-7 col-6">
                                            <span class = "">Didn't recieve OTP? <a style = "cursor:pointer;" onclick = "resendOTP();">Resend</a></span>
                                        </div>
                                    </div>
                                    <input id="user_id_hidden_input" name="user_id" type="hidden" value = "{{request() -> id}}">
                                    <div class="row mt-4 mt-sm-3 mb-sm-3 justify-content-center">
                                        <div class="col-md-7 col-6">
                                            <button type="submit" class="btn sign-btn btn-block">Verify</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-sm-5 mt-4">
            <div class="col-md-12">
                <h5>Ant Fast Order Management System © . All rights reserved.</h5>
            </div>
         </div>
    </div>
</section>

<script>
    const passwordInput = document.getElementById('password_input');
    const passwordToggle = document.getElementById('password_toggle');
    const emailIconInput = document.getElementById('emailIcon');

    function togglePasswordView()
    {
        if (passwordInput.type === "text") {
            passwordInput.type = "password";
            passwordToggle.src = "{{asset('assets/img/close-eyes.svg')}}"
        } else {
            passwordInput.type = "text";
            passwordToggle.src = "{{asset('assets/img/eyes-gray.svg')}}"
        }
    }

    
    $('.otpInput').keyup(function(e) {
            if (this.value.length === this.maxLength) {
                let next = $(this).data('next');
                $('#n' + next).focus();
            }
        });

    function resendOTP()
    {
        $.ajax({
           url  : "{{route('auth.forgot.password.otp.resend')}}",
           type : "POST",
           data : {
                _token: "{{ csrf_token() }}",
                id : "{{request() -> id}}",
               },
           
            success	: function(response){
               flasher.success(response.message);
            },
           error : function($response){
            if ($response.status === 422) {
                flasher.warning($response.responseJSON.message);
            } else {
                flasher.error($response.responseJSON.message);
            }
           },
       });
    }
</script>
@endsection