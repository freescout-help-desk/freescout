@section('stylesheets')
    <link href="{{ asset('js/summernote/summernote.css') }}" rel="stylesheet">
@endsection

@section('javascripts')
	{!! Minify::javascript(array('/js/summernote/summernote.js')) !!}
@endsection