@php
    if (!isset($errors)) {
        $errors = collect([]);
    }
    $values = request()->get('f');
@endphp
@if (request()->get('success') && empty($conversation->id))
    <div class="alert alert-success text-center">
        <strong>{{ __('Your message has been sent!') }}</strong>
        @if (\EndUserPortal::authCustomer() && !empty(request()->get('ticket_id')))
             <a href="{{ route('enduserportal.ticket', ['mailbox_id' => EndUserPortal::encodeMailboxId($mailbox->id), 'conversation_id' => request()->get('ticket_id')])  }}">({{ __('View') }})</a>
        @endif
    </div>
    <div class="text-center margin-bottom">
        {{-- request()->url() does not return HTTPS protocol --}}
        <a href="{{ parse_url(request()->url(), PHP_URL_PATH) }}?{{ http_build_query(array_merge(request()->all(), ['success' => '', 'message' => ''])) }}">{{ __('Submit another message') }}</a>
    </div>
@else
    @if (request()->get('success') && !empty($conversation->id))
        <div class="alert alert-success text-center">
            <strong>{{ __('Your message has been sent!') }}</strong>
        </div>
    @endif
    <form class="" method="POST" action="{{ $form_action ?? '' }}" id="eup-ticket-form">
        <div id="eup-submit-form-main-area">
            {{ csrf_field() }}
            <input type="hidden" name="conversation_id" value="{{ $conversation->id }}"/>
            {{--<input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}"/>--}}
            {{--<input type="hidden" name="is_create" value="@if (empty($conversation->id)){{ '1' }}@endif"/>--}}

            {{-- Spam protection --}}
            <div class="form-group hidden">
                <input type="text" class="form-control" name="age" value="" />
            </div>

            @if (!\EndUserPortal::authCustomer())
                <div class="form-group {{ $errors->has('name') ? ' has-error' : '' }}">
                    <input type="text" class="form-control eup-remember input-md" name="name" value="{{ old('name', $values['name'] ?? '') }}" placeholder="{{ __('Your Name') }}" @if (!empty($values['name'])) data-prefilled="1" @endif />

                    @include('partials/field_error', ['field'=>'customer_name'])
                </div>
            @endif

            {{--<div class="form-group {{ $errors->has('phone') ? ' has-error' : '' }}">
                <input type="text" class="form-control" name="phone" value="{{ old('phone') }}" placeholder="{{ __('Phone Number') }}" />

                @include('partials/field_error', ['field'=>'phone'])
            </div>--}}

            @if (!\EndUserPortal::authCustomer())
                <div class="form-group {{ $errors->has('email') ? ' has-error' : '' }}">
                    <input type="email" class="form-control eup-remember input-md" name="email" value="{{ old('email', $values['email'] ?? '') }}" placeholder="{{ __('Email Address') }}*" @if (!empty($values['email'])) data-prefilled="1" @endif required autofocus />

                    @include('partials/field_error', ['field'=>'email'])
                </div>
            @endif

            @php
                if (empty($mailbox_id)) {
                    $mailbox_id = \EndUserPortal::decodeMailboxId($mailbox->id, \EndUserPortal::WIDGET_SALT);
                }
            @endphp

            @if (\Module::isActive('customfields'))
                @php
                    
                    // We do not check request string as:
                    // - Submit another ticket button does not work in this case
                    // - In the Portal there is not request parameters.
                    // $cf = Request::get('cf');
                    // if (empty($cf) || !is_array($cf)) {
                    $widget_settings = \EndUserPortal::getWidgetSettings($mailbox_id);
                    $cf = $widget_settings['cf'] ?? [];
                    //}
                @endphp
                @if (!empty($cf) && is_array($cf))
                    @php
                        $custom_fields = \CustomField::getMailboxCustomFields($mailbox_id);
                        $add_calendar = false;
                    @endphp
                    @foreach($cf as $custom_field_id)
                        @foreach($custom_fields as $custom_field)
                            @php
                                $custom_field_value = $values['cf_'.$custom_field->id] ?? '';
                                $prefilled = false;
                            @endphp
                            @if ($custom_field->id == $custom_field_id)
                                @if ($custom_field->type == CustomField::TYPE_DATE) @php $add_calendar = true @endphp @endif
                                <div class="form-group">
                                    @if ($custom_field->type == CustomField::TYPE_DROPDOWN)
                                        @foreach($custom_field->options as $option_key => $option_name)
                                            @php
                                                if ($option_key == $custom_field_value || $option_name == $custom_field_value) {
                                                    $prefilled = true;
                                                    break;
                                                }
                                            @endphp
                                        @endforeach
                                        <select class="form-control eup-remember" name="cf[{{ $custom_field->id }}]" @if ($prefilled) data-prefilled="1" @endif @if ($custom_field->required) required @endif>
                                            <option value="">{{ $custom_field->name }}@if ($custom_field->required)*@endif</option>
                                            
                                            @if (is_array($custom_field->options))
                                                @foreach($custom_field->options as $option_key => $option_name)
                                                    <option value="{{ $option_key }}" @if ($option_key == $custom_field_value || $option_name == $custom_field_value) selected @endif>{{ $option_name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    @else
                                        <input name="cf[{{ $custom_field->id }}]" class="form-control eup-remember @if ($custom_field->type == CustomField::TYPE_DATE) eup-type-date @endif" value="{{ $custom_field_value }}" @if ($custom_field_value) data-prefilled="1" @endif placeholder="{{ $custom_field->name }}@if ($custom_field->required)*@endif" @if ($custom_field->required) required @endif 
                                            @if ($custom_field->type == CustomField::TYPE_NUMBER)
                                                type="number"
                                            @else
                                                type="text"
                                            @endif
                                        />
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                @endif
            @endif

            <div class="form-group{{ $errors->has('message') ? ' has-error' : '' }}">

                <textarea class="form-control eup-remember" name="message" rows="13" placeholder="{{ __('Message') }}*" @if (!empty($values['message'])) data-prefilled="1" @endif required autofocus>{{ old('message', $values['message'] ?? '') }}</textarea>

                @include('partials/field_error', ['field'=>'message'])

            </div>

            @if ((int)\EndUserPortal::getMailboxParam($mailbox, 'consent'))
                <div class="form-group">
                    <label class="checkbox" for="eup_consent">
                        <input type="checkbox" value="1" id="eup_consent" required="required"> {!! __('I accept :what', ['what' => '<a href="'.route('enduserportal.ajax_html', ['mailbox_id' => EndUserPortal::encodeMailboxId($mailbox_id, \EndUserPortal::WIDGET_SALT), 'action' => 'privacy_policy']).'" data-trigger="modal" data-modal-title="'.__('Privacy Policy').'">'.__('Privacy Policy').'</a>']) !!}</a>
                    </label>
                </div>
            @endif

            <div class="form-group">
                <div class="attachments-upload" id="eup-uploaded-attachments">
                    <ul></ul>
                </div>
                <div class="eup-att-dropzone">
                    <i class="glyphicon glyphicon-paperclip"></i> {{ __('Add attachments') }}
                </div>
            </div>
        </div>

        <div id="eup-submit-form-bottom">
            <div class="form-group">
                <input type="submit" class="btn btn-block btn-primary btn-lg eup-btn-ticket-submit" {!! $submit_btn_attrs ?? '' !!} data-loading-text="@if (empty($conversation->id)){{ __('Send') }}@else{{ __('Reply') }}@endif…" value="@if (empty($conversation->id)){{ __('Send') }}@else{{ __('Reply') }}@endif"/>
            </div>
            {!! $submit_area_append ?? '' !!}
        </div>

    </form>

    @if (!empty($add_calendar))

        @section('eup_stylesheets')
            @parent
            <link href="{{ asset('js/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
        @endsection

        @section('eup_javascripts')
            @parent
            {!! Minify::javascript(['/js/flatpickr/flatpickr.min.js', '/js/flatpickr/l10n/'.strtolower(Config::get('app.locale')).'.js']) !!}
        @endsection
    @endif
@endif