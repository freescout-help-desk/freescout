<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Name') }}</label>

    <div class="col-sm-10">
        <input class="form-control" name="name" value="{{ $customer_field->name }}" maxlength="75" required/>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Type') }}</label>

    <div class="col-sm-10">
    	<select name="type" class="form-control" @if ($mode != 'create') disabled @endif>
    		@foreach (\CustomerField::$types as $type_key => $type_name)
    			<option value="{{ $type_key }}" @if ($type_key == $customer_field->type) selected @endif>{{ __($type_name) }}</option>
    		@endforeach
    	</select>
    </div>
</div>

<div class="form-group cf-type cf-type-1 @if ($customer_field->type != \CustomerField::TYPE_DROPDOWN) hidden @endif">
    <label class="col-sm-2 control-label">{{ __('Options') }}</label>

    <div class="col-sm-10">
        @if ($mode != 'create')
            @if (empty($customer_field->options))
                @php
                    $customer_field->options = [1 => ''];
                @endphp
            @endif
            @if (is_array($customer_field->options))
                <div class="cf-options">
                    @foreach ($customer_field->options as $option_id => $option_title)
                        <div class="input-group cf-option">
                            <span class="input-group-addon cf-option-handle">
                                <i class="glyphicon glyphicon-menu-hamburger"></i>
                            </span>
                            <input type="text" name="options[{{ $option_id }}]" data-option-id="{{ $option_id }}" class="form-control" value="{{ $option_title }}" @if ($customer_field->type != \CustomerField::TYPE_DROPDOWN) disabled @endif">
                            <span class="input-group-btn cf-option-restore">
                                <button class="btn btn-default" type="button"><small>{{ __('Undo') }}</small></button>
                            </span>
                            <span class="input-group-btn cf-option-remove">
                                <button class="btn btn-default" type="button">–</button>
                            </span>
                            <span class="input-group-btn cf-option-add">
                                <button class="btn btn-default" type="button">+</button>
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <textarea name="options" class="form-control" rows="4" placeholder="{{ __('One option per line') }}" @if ($customer_field->type != \CustomerField::TYPE_DROPDOWN) disabled @endif"></textarea>
        @endif
    </div>
</div>

<div class="form-group cf-type cf-type-2 @if ($customer_field->type != \CustomerField::TYPE_SINGLE_LINE) hidden @endif">
    <label class="col-sm-2 control-label">{{ __('Autosuggest') }}</label>

    <div class="col-sm-10">
        <div class="controls">
            <div class="onoffswitch-wrap">
                <div class="onoffswitch">
                    <input type="checkbox" name="options[autosuggest]" value="1" id="autosuggest_{{ $customer_field->id }}" class="onoffswitch-checkbox" @if (!empty($customer_field->options['autosuggest']))checked="checked"@endif @if ($customer_field->type != \CustomerField::TYPE_SINGLE_LINE) disabled @endif">
                    <label class="onoffswitch-label" for="autosuggest_{{ $customer_field->id }}"></label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-group cf-type cf-type-6 @if ($customer_field->type != \CustomerField::TYPE_LINK) hidden @endif">
    <label class="col-sm-2 control-label">URL</label>

    <div class="col-sm-10">
        <input type="url" class="form-control" name="options[link_url]" value="{{ $customer_field->options['link_url'] ?? '' }}" placeholder="https://example.org/customer/{%email%}"  @if ($customer_field->type != \CustomerField::TYPE_LINK) disabled @endif"/>
        <p class="form-help">
            <strong>{{ __('Available URL placeholders') }}:</strong> <br/>
            <code>{%id%}, {%email%}, {%phone%}, {%first_name%}, {%last_name%}, {%company%}, {%job_title%}, {%website%}, {%address%}, {%city%}, {%state%}, {%zip%}, {%country%}, {%cf_CUSTOMER_FIELD_ID%}</code>
        </p>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Required') }}</label>

    <div class="col-sm-10">
        <div class="controls">
            <div class="onoffswitch-wrap">
                <div class="onoffswitch">
                    <input type="checkbox" name="required" value="1" id="required_{{ $customer_field->id }}" class="onoffswitch-checkbox" @if ($customer_field->required)checked="checked"@endif >
                    <label class="onoffswitch-label" for="required_{{ $customer_field->id }}"></label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Display in Profile') }}</label>

    <div class="col-sm-10">
        <div class="controls">
            <div class="onoffswitch-wrap">
                <div class="onoffswitch">
                    <input type="checkbox" name="display" value="1" id="display_{{ $customer_field->id }}" class="onoffswitch-checkbox" @if ($customer_field->display)checked="checked"@endif >
                    <label class="onoffswitch-label" for="display_{{ $customer_field->id }}"></label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Display in Conv. List') }}</label>

    <div class="col-sm-10">
        <div class="controls">
            <div class="onoffswitch-wrap">
                <div class="onoffswitch">
                    <input type="checkbox" name="conv_list" value="1" id="conv_list_{{ $customer_field->id }}" class="onoffswitch-checkbox" @if ($customer_field->conv_list)checked="checked"@endif >
                    <label class="onoffswitch-label" for="conv_list_{{ $customer_field->id }}"></label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-group hidden">
    <label class="col-sm-2 control-label">{{ __('Display to Customer') }}</label>

    <div class="col-sm-10">
        <div class="controls">
            <div class="onoffswitch-wrap">
                <div class="onoffswitch">
                    <input type="checkbox" name="customer_can_view" value="1" id="customer_can_view_{{ $customer_field->id }}" class="onoffswitch-checkbox" @if ($customer_field->customer_can_view)checked="checked"@endif >
                    <label class="onoffswitch-label" for="customer_can_view_{{ $customer_field->id }}"></label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-group hidden">
    <label class="col-sm-2 control-label">{{ __('Customer Can Edit') }}</label>

    <div class="col-sm-10">
        <div class="controls">
            <div class="onoffswitch-wrap">
                <div class="onoffswitch">
                    <input type="checkbox" name="customer_can_edit" value="1" id="customer_can_edit_{{ $customer_field->id }}" class="onoffswitch-checkbox" @if ($customer_field->customer_can_edit)checked="checked"@endif >
                    <label class="onoffswitch-label" for="customer_can_edit_{{ $customer_field->id }}"></label>
                </div>
            </div>
        </div>
    </div>
</div>