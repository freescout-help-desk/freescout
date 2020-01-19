@section('stylesheets')
    <link href="{{ asset('js/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
@endsection

@section('javascripts')
    @parent
    {!! Minify::javascript(['/js/flatpickr/flatpickr.min.js', '/js/flatpickr/l10n/'.strtolower(Config::get('app.locale')).'.js']) !!}
@endsection