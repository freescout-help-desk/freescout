@section('stylesheets')
	@parent
    <link href="{{ asset('js/summernote/summernote.css') }}" rel="stylesheet">
@endsection

@section('javascripts')
	@parent
	{!! Minify::javascript(array('/js/summernote/summernote.js')) !!}
@endsection