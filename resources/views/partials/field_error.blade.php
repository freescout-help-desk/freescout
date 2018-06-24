@if ($errors->has($field))
    <span class="help-block">
        <strong>{{ $errors->first($field) }}</strong>
    </span>
@endif