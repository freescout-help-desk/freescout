@if (count($customer_fields))
    @foreach ($customer_fields as $customer_field)
        <div class="form-group{{ $errors->has($customer_field->getNameEncoded()) ? ' has-error' : '' }}">
            <label for="{{ $customer_field->getNameEncoded() }}" class="col-sm-2 control-label">{{ $customer_field->name }}@if ($customer_field->required) <i class="required-asterisk"></i>@endif</label>

            <div class="col-sm-6">

                @if ($customer_field->type == CustomerField::TYPE_DROPDOWN)
                    <select class="form-control input-sized-lg @if (!$customer_field->required && !$customer_field->value) placeholdered @endif"
                            name="{{ $customer_field->getNameEncoded() }}"  @if ($customer_field->required) required @endif>
                        @if (!$customer_field->required)
                            <option value="" @if (!$customer_field->value) selected @endif>{{ __('(optional)') }}</option>
                        @else
                            <option value=""></option>
                        @endif
                        
                        @if (is_array($customer_field->options))
                            @foreach($customer_field->options as $option_key => $option_name)
                                <option value="{{ $option_key }}" {{ ($customer_field->value == $option_key) ? 'selected' : '' }}>{{ $option_name }}</option>
                            @endforeach
                        @endif
                    </select>
                @elseif ($customer_field->type == CustomerField::TYPE_MULTI_LINE)
                    <textarea class="form-control input-sized-lg" name="{{ $customer_field->getNameEncoded() }}" rows="2" @if ($customer_field->required) required @else placeholder="{{ __('(optional)') }}" @endif @if ($customer_field->required) required @endif>{{ old($customer_field->getNameEncoded(), $customer_field->value) }}</textarea>
                @elseif ($customer_field->type == CustomerField::TYPE_SINGLE_LINE && !empty($customer_field->options['autosuggest']))
                    <select class="form-control input-sized-lg @if (!$customer_field->required && !$customer_field->value) placeholdered @endif  @if (!empty($customer_field->options['autosuggest'])) crm-cf-autosuggest @endif"
                            name="{{ $customer_field->getNameEncoded() }}"  @if ($customer_field->required) required @endif>
                        @if (!$customer_field->required)
                            <option value="" @if (!$customer_field->value) selected @endif>{{ __('(optional)') }}</option>
                        @else
                            <option value=""></option>
                        @endif
                        
                        @if ($customer_field->value)
                            <option value="{{ $customer_field->value }}" selected="selected">{{ $customer_field->value }}</option>
                        @endif
                    </select>
                @elseif ($customer_field->type == CustomerField::TYPE_LINK)
                    @php
                        $customer_field_link = $customer_field->getLink();
                    @endphp
                    @if ($customer_field_link)
                        <label class="control-label">
                            <a href="{{ $customer_field_link }}" target="_blank"/>{{ CustomerField::shortenLink($customer_field_link) }}</a>
                        </label>
                    @endif
                @else
                    <input name="{{ $customer_field->getNameEncoded() }}" class="form-control input-sized-lg @if ($customer_field->type == CustomerField::TYPE_DATE) crm-cf-type-date @endif" value="{{ $customer_field->value }}"
                        @if ($customer_field->type == CustomerField::TYPE_NUMBER)
                            type="number"
                        @else
                            type="text"
                        @endif

                        @if ($customer_field->required) required @else placeholder="{{ __('(optional)') }}" @endif 
                    />
                @endif

                @include('partials/field_error', ['field'=>$customer_field->getNameEncoded()])
            </div>
        </div>
    @endforeach
    @include('partials/include_datepicker')

    @section('javascript')
        @parent
        crmInitCustomerFields();
    @endsection
@endif