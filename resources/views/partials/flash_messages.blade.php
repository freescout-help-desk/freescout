@if (session('flash_success') || session('flash_success_unescaped'))
    <div class="alert alert-success">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session('flash_success') }}{!! session('flash_success_unescaped') !!}
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
@if (session('flash_error_unescaped'))
    <div class="alert alert-danger">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {!! session('flash_error_unescaped') !!}
    </div>
@endif

{{-- Floating flash messages are displayed in layout --}}
@php
    $flashes = \Eventy::filter('flash_messages.flashes', $flashes ?? []);
@endphp

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