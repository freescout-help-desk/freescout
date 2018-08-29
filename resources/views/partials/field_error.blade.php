@if ($errors->has($field))
    <span class="help-block has-error">
    	@if (empty($unescaped))
        	<strong>{{ $errors->first($field) }}</strong>
        @else
        	<strong>{!! $errors->first($field) !!}</strong>
        @endif
    </span>
@endif