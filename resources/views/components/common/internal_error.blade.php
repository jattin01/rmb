@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="px-sm-4">
            <div class="row">
                <div class="col-md-12 text-center">
                    <img src = "{{asset('assets/img/cancelled-imgpop.svg')}}" />
                    <h6 style = "margin : 1rem;">Oops ! Something went wrong on our end. Please try again later.</h6>
                    <a href="{{route('dashboard.index')}}" class="btn back-btn">Home</a>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    console.error("{{isset($message) ? 'INTERNAL SERVER ERROR - ' . $message : ''}}");
</script>
@endsection