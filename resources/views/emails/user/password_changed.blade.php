@extends('emails/user/layouts/system')

@section('content')
	<p style="color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
		{{ __('Hello :user_name', ['user_name' => $user->getFirstName()]) }},
		<br/><br/>
		{{ __("This is a quick note to let you know that your :company_name password has been successfully updated. If you didn't request this change, please contact the administrator.", ['company_name' => App\Option::getCompanyName()]) }}
	</p>
@endsection