@if (session('flash_success'))
    <div class="alert alert-success alert-floating">
        {{ session('flash_success') }}
    </div>
@endif
@if (session('flash_warning'))
    <div class="alert alert-warning">
        {{ session('flash_warning') }}
    </div>
@endif
@if (session('flash_error'))
    <div class="alert alert-danger">
        {{ session('flash_error') }}
    </div>
@endif
@if (!empty($flashes) && is_array($flashes))
    @foreach ($flashes as $flash)
     	<div class="alert alert-{{ $flash['type'] }}">
     		@if (!empty($flash['unescaped']))
        		{!! $flash['text'] !!}
        	@else
        		{{ $flash['text'] }}
        	@endif
    	</div>
    @endforeach
@endif