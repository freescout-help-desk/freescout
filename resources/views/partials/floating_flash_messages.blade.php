@if (session('flash_success_floating'))
    @section('body_bottom')
        <div class="alert alert-success alert-floating">
            <i class="glyphicon glyphicon-ok"></i>
            <div>{!! session('flash_success_floating') !!}</div>
        </div>
    @endsection
@endif
@if (session('flash_error_floating'))
    @section('body_bottom')
        <div class="alert alert-danger alert-floating">
            <i class="glyphicon glyphicon-exclamation-sign"></i>
            <div>{!! session('flash_error_floating') !!}</div>
        </div>
    @endsection
@endif
@if (session('flash_warning_floating'))
    @section('body_bottom')
        <div class="alert alert-warning alert-floating">
            <div>{!! session('flash_warning_floating') !!}</div>
        </div>
    @endsection
@endif