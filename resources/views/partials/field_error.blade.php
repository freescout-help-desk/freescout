@if (isset($scope) ? $errors->$scope->has($field) : $errors->has($field))
    <span class="help-block has-error">
        @if (empty($unescaped))
            <strong>{{ isset($scope) ? $errors->$scope->first($field) : $errors->first($field) }}</strong>
        @else
            <strong>{!! isset($scope) ? $errors->$scope->first($field) : $errors->first($field) !!}</strong>
        @endif
    </span>
@endif
