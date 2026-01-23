@extends('layouts.auth.app')
@section('content')
<section class="content">
	<div class="row">

		this is a loading page ..this is a loading page ..this is a loading page ..this is a loading page ..this is a loading page ..this is a loading page ..this is a loading page ..this is a loading page ..
		<div class="col-md-12">
			<form id="" >
		        @csrf
		        <input type="hidden" name="schedule_date" value="{{ session('schedule_data.schedule_date') }}">
		        <input type="hidden" name="company_id" value="{{ session('schedule_data.company_id') }}">
		        <input type="hidden" name="orders" value="{{ json_encode(session('schedule_data.orders')) }}">
		    </form>
	    </div>
	</div>
</section>

<script>


	 $(document).ready(function() {
        $.ajax({
            url: "generate-schedule-step-3",  // Make sure this is the correct route
            type: "POST",
            data: $('#redirectForm').serialize(), // Serialize form data
            success: function(response) {
                // Handle success
                console.log("Form submitted successfully:", response);
                // You can redirect or show a message based on the response
                window.location.href = "{{ route('orders.schedule.view') }}";  // Example redirect after success
            },
            error: function(xhr, status, error) {
                // Handle error
                console.error("Error occurred while submitting form:", error);
                alert("There was an error submitting the form.");
            // console.log('here'); return false;
            }
        });
    });
</script>
@endsection
