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
                            <form method="POST" action="{{ route('auth.forgot.password.submit') }}">
                            @csrf
                                <div class="login-box">
                                    <h1>Forgot Password?</h1>
                                    <p>Share your Email Id or Username</p>
                                    <div class="form-group mt-sm-4 mt-3 position-relative">
                                        <input name = "email" type="email" class="form-control sign-inpt padding-right @error('email') is-invalid @enderror" value="{{old('email')}}"
                                            placeholder="Enter your Email Id">
                                        @error('email')
										<span class="invalid-feedback" role="alert">
										    <strong>{{ $message }}</strong>
										</span>
										@enderror
                                    </div>
                                    <div class="form-group mt-sm-4 mt-3 position-relative">
                                        <input name = "username" type="text" class="form-control sign-inpt padding-right @error('username') is-invalid @enderror" value="{{old('username')}}"
                                            placeholder="Enter your Username">
                                        @error('username')
										<span class="invalid-feedback" role="alert">
										    <strong>{{ $message }}</strong>
										</span>
										@enderror
                                    </div>
                                    
                                    <div class="row mt-4 mt-sm-3 mb-sm-3 align-items-center">
                                        <div class="col-md-7 col-6">
                                            <button type="submit" class="btn sign-btn btn-block">Send OTP</button>
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
                console.log(this.value.length + '=== ' + this.maxLength)

                $('#n' + next).focus();
            }
        });
</script>
@endsection