@if (session('flash_success'))
    <div class="alert alert-success">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session('flash_success') }}
    </div>
@endif
@if (session('flash_warning'))
    <div class="alert alert-warning">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session('flash_warning') }}
    </div>
@endif
@if (session('flash_error'))
    <div class="alert alert-danger">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session('flash_error') }}
    </div>
@endif
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
@if (!empty($flashes) && is_array($flashes))
    @foreach ($flashes as $flash)
     	<div class="alert alert-{{ $flash['type'] }}">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
     		@if (!empty($flash['unescaped']))
        		{!! $flash['text'] !!}
        	@else
        		{{ $flash['text'] }}
        	@endif
    	</div>
    @endforeach
@endif