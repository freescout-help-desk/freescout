@if (count($custom_fields))
    @php
        $has_hidden = false;
        if (empty($on_create)) {
            $on_create = false;
        }
    @endphp
    @if (!$on_create)
        <div class="conv-top-block clearfix">
            <form class="form-horizontal" method="POST" action="" id="custom-fields-form">
    @else
            <div id="custom-fields-form" class="cf-mode-create">
    @endif
        
            @foreach($custom_fields as $custom_field)
                @if (!$on_create)
                    <div class="cf-group custom-field" id="custom-field-{{ $custom_field->id }}">
                        <div class="text-help">
                            {{$custom_field->name}} @if ($custom_field->required) <i class="required-asterisk"></i>@endif
                        </div>
                @else
                    <div class="form-group custom-field @if ($on_create && !$custom_field->required && !$custom_field->value) @php $has_hidden = true; @endphp hidden @endif" id="custom-field-{{ $custom_field->id }}">
                        <label class="col-sm-2 control-label">{{$custom_field->name}} @if ($custom_field->required) <i class="required-asterisk"></i>@endif</label>
                        <div class="col-sm-9">
                @endif

                    @if ($custom_field->type == CustomField::TYPE_DROPDOWN)
                        <select class="form-control @if (!$custom_field->required && !$custom_field->value) placeholdered @endif"
                                name="{{ $custom_field->id }}"  @if ($custom_field->required) required @endif>
                            @if (!$custom_field->required)
                                <option value="" @if (!$custom_field->value) selected @endif>{{ __('(optional)') }}</option>
                            @else
                                <option value=""></option>
                            @endif
                            
                            @if (is_array($custom_field->options))
                                @foreach($custom_field->options as $option_key => $option_name)
                                    <option value="{{ $option_key }}" {{ ($custom_field->value == $option_key) ? 'selected' : '' }}>{{ $option_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    @elseif ($custom_field->type == CustomField::TYPE_MULTI_LINE)
                        <textarea name="{{ $custom_field->id }}" class="form-control" rows="3" @if ($custom_field->required) required @else placeholder="{{ __('(optional)') }}" @endif>{{ $custom_field->value }}</textarea>
                    @elseif ($custom_field->type == CustomField::TYPE_SINGLE_LINE && !empty($custom_field->options['autosuggest']))
                        <select class="form-control @if (!$custom_field->required && !$custom_field->value) placeholdered @endif  @if (!empty($custom_field->options['autosuggest'])) cf-autosuggest @endif"
                                name="{{ $custom_field->id }}"  @if ($custom_field->required) required @endif>
                            @if (!$custom_field->required)
                                <option value="" @if (!$custom_field->value) selected @endif>{{ __('(optional)') }}</option>
                            @else
                                <option value=""></option>
                            @endif
                            
                            @if ($custom_field->value)
                                <option value="{{ $custom_field->value }}" selected="selected">{{ $custom_field->value }}</option>
                            @endif
                        </select>
                    @else
                        <input name="{{ $custom_field->id }}" class="form-control @if ($custom_field->type == CustomField::TYPE_DATE) cf-type-date @endif" value="{{ $custom_field->value }}"
                            @if ($custom_field->type == CustomField::TYPE_NUMBER)
                                type="number"
                            @else
                                type="text"
                            @endif

                            @if ($custom_field->required) required @else placeholder="{{ __('(optional)') }}" @endif 
                        />
                    @endif
                </div>
                @if ($on_create)
                    </div>
                @endif
            @endforeach
            <input type="submit" class="hidden"/>
        
    @if (!$on_create)
            </form>
        </div>
    @else
        @if ($has_hidden)
            <div class="col-sm-9 col-sm-offset-2 toggle-field">
                <a href="#" class="cf-show-fields">{{ __('Show all fields') }}</a>
            </div>
        @endif
        </div>
    @endif
    @include('partials/include_datepicker')
@endif