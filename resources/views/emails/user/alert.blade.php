@extends('emails/user/layouts/system')

@section('content')

	<div style="color:#2a3b47;font:500 20px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif">
		@if (!empty($title))
			{!! $title !!}
		@else
			{{ __('System Alert') }}
		@endif
		 - {{ \Helper::getDomain() }}
	</div>

	<p style="color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
		{!! $text !!}
	</p>

	<p style="color:#72808e;font:400 12px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:10px 0 0;">
		{!! __('You can adjust alert settings :%a_begin%here:a_end', ['%a_begin%' => '<a href="'.route('settings', ['section' => 'alerts']).'">', 'a_end' => '<a/>']) !!}
	</p>
@endsection