@if (count($custom_fields))
    @foreach($custom_fields as $custom_field)
        <div class="col-sm-6 form-group @if (isset($filters[$custom_field->name])) active @endif" data-filter="{{ $custom_field->name }}">
            <label>{{ $custom_field->name }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>

            @if ($custom_field->type == CustomField::TYPE_DROPDOWN)
                <select name="f[{{ $custom_field->name }}]" class="form-control" @if (empty($filters[$custom_field->name])) disabled @endif>
                    <option value=""></option>
                    @if (is_array($custom_field->options))
                        @foreach ($custom_field->options as $option_id => $option_name)
                            <option value="{{ $option_id }}" @if (!empty($filters[$custom_field->name]) && $filters[$custom_field->name] == $option_id)selected="selected"@endif>{{ $option_name }}</option>
                        @endforeach
                    @endif
                </select>
            @else
                <input name="f[{{ $custom_field->name }}]" value="{{ $filters[$custom_field->name] ?? '' }}" class="form-control @if ($custom_field->type == CustomField::TYPE_DATE) input-date @endif" @if (empty($filters[$custom_field->name])) disabled @endif />
            @endif
        </div>
    @endforeach
@endif