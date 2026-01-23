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
                            <form method="POST" action="{{ route('auth.login.submit') }}">
                            @csrf
                                <div class="login-box">
                                    <h1>Welcome back!</h1>
                                    <p>Ant Fast Order Management System</p>
                                            
                                    <div class="form-group mt-sm-4 mt-3 position-relative">
                                        <input name = "username" type="text" class="form-control sign-inpt padding-right @error('username') is-invalid @enderror" value="{{old('username')}}"
                                            placeholder="Enter your Email/ Username">
                                        @error('username')
										<span class="invalid-feedback" role="alert">
										    <strong>{{ $message }}</strong>
										</span>
										@enderror
                                    </div>
                                    <div class="form-group position-relative">
                                        <input id = "password_input" name = "password" type="password" class="form-control sign-inpt padding-right @error('password') is-invalid @enderror" value="{{old('password')}}"
                                            placeholder="Enter Password">
                                        <img src="{{asset('assets/img/close-eyes.svg')}}" onClick = "togglePasswordView();" id = "password_toggle" class="close-eyesimg" alt="">
                                        @error('password')
										<span class="invalid-feedback" role="alert">
											<strong>{{ $message }}</strong>
										</span>
										@enderror
                                    </div>
                                    <h6>By login you agree to our <a href="{{route('privacy_policy')}}" target = "_blank">Terms & Conditions</a></h6>
                                    <div class="row mt-4 mt-sm-5 align-items-center">
                                        <div class="col-md-7 col-6">
                                            <button type="submit" class="btn sign-btn btn-block">Sign in</button>
                                        </div>
                                        <div class="col-md-5 col-6">
                                            <h4><a href="{{route('auth.forgot.password.view')}}">Forgot Password?</a></h4>
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
</script>
@endsection