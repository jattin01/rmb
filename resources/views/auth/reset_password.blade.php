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
                            <form method="POST" action="{{ route('auth.reset.password.submit') }}">
                            @csrf
                                <div class="login-box">
                                    <h1>Reset Password?</h1>
                                    <p>Reset your account password</p>
                                    <div class="form-group position-relative mt-2">
                                        <input id = "password_input" name = "password" type="password" class="form-control sign-inpt padding-right @error('password') is-invalid @enderror" value="{{old('password')}}"
                                            placeholder="Enter Password">
                                        <img src="{{asset('assets/img/close-eyes.svg')}}" onClick = "togglePasswordView();" id = "password_toggle_m" class="close-eyesimg" alt="">
                                        @error('password')
										<span class="invalid-feedback" role="alert">
											<strong>{{ $message }}</strong>
										</span>
										@enderror
                                    </div>
                                    <div class="form-group position-relative">
                                        <input id = "password_input_conf" name = "password_confirmation" type="password" class="form-control sign-inpt padding-right @error('password_confirmation') is-invalid @enderror" value="{{old('password_confirmation')}}"
                                            placeholder="Enter Password">
                                        <img src="{{asset('assets/img/close-eyes.svg')}}" onClick = "togglePasswordView();" id = "password_toggle_c" class="close-eyesimg" alt="">
                                        @error('password_confirmation')
										<span class="invalid-feedback" role="alert">
											<strong>{{ $message }}</strong>
										</span>
										@enderror
                                    </div>
                                    <input id="token_hidden_input" name="token" type="hidden" value = "{{request() -> token}}">

                                    <div class="row mt-4 mt-sm-3 mb-sm-3 align-items-center">
                                        <div class="col-md-7 col-6">
                                            <button type="submit" class="btn sign-btn btn-block">Submit</button>
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
    const passwordInputMain = document.getElementById('password_input');
    const passwordInputConfirmation = document.getElementById('password_input_conf');
    const passwordToggleM = document.getElementById('password_toggle_m');
    const passwordToggleC = document.getElementById('password_toggle_c');
    const emailIconInput = document.getElementById('emailIcon');

    function togglePasswordView()
    {
        if (passwordInputMain.type === "text") {
            passwordInputMain.type = "password";
            passwordInputConfirmation.type = "password";
            passwordToggleM.src = "{{asset('assets/img/close-eyes.svg')}}"
            passwordToggleC.src = "{{asset('assets/img/close-eyes.svg')}}"
        } else {
            passwordInputMain.type = "text";
            passwordInputConfirmation.type = "text";
            passwordToggleM.src = "{{asset('assets/img/eyes-gray.svg')}}"
            passwordToggleC.src = "{{asset('assets/img/eyes-gray.svg')}}"
        }
    }

    $('.otpInput').keyup(function(e) {
        if (this.value.length === this.maxLength) {
            let next = $(this).data('next');
            $('#n' + next).focus();
        }
    });
</script>
@endsection