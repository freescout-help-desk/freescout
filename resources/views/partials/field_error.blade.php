@if ($errors->has($field) && $errors->first($field) != 'dummy')
    <span class="help-block has-error">
        <strong>{{ $errors->first($field) }}</strong>
    </span>
@endif