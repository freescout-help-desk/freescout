@if (count($customer_fields))
    @foreach($customer_fields as $customer_field)
        <div class="col-sm-6 form-group @if (isset($filters[$customer_field->name])) active @endif" data-filter="{{ $customer_field->name }}">
            <label>{{ $customer_field->name }} <b class="remove" data-toggle="tooltip" title="{{ __('Remove filter') }}">×</b></label>

            @if ($customer_field->type == CustomerField::TYPE_DROPDOWN)
                <select name="f[{{ $customer_field->name }}]" class="form-control" @if (empty($filters[$customer_field->name])) disabled @endif>
                    <option value=""></option>
                    @if (is_array($customer_field->options))
                        @foreach ($customer_field->options as $option_id => $option_name)
                            <option value="{{ $option_id }}" @if (!empty($filters[$customer_field->name]) && $filters[$customer_field->name] == $option_id)selected="selected"@endif>{{ $option_name }}</option>
                        @endforeach
                    @endif
                </select>
            @else
                <input name="f[{{ $customer_field->name }}]" value="{{ $filters[$customer_field->name] ?? '' }}" class="form-control @if ($customer_field->type == CustomerField::TYPE_DATE) input-date @endif" @if (empty($filters[$customer_field->name])) disabled @endif />
            @endif
        </div>
    @endforeach
@endif