@extends('emails/user/layouts/system')

@section('content')
	<p style="color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
		Hi Team,
	</p>
	<p style="color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
		Please find the attached PDF for {{$name}}.
	</p>
@endsection