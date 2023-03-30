@section('stylesheets')
	@parent
    <link href="{{ url('js/summernote/summernote.css') }}" rel="stylesheet">
@endsection

@section('javascripts')
	@parent
	{!! Minify::javascript(array('/js/summernote/summernote.js')) !!}
@endsection