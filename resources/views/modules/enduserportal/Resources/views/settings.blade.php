@extends('layouts.app')

@section('title_full', __('End-User Portal').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('End-User Portal') }}
    </div>

    <div class="col-xs-12">
        <form class="form-horizontal margin-top margin-bottom" method="POST" action="" autocomplete="off" id="eup-widget-form">
            {{ csrf_field() }}


            <div class="form-group">
                <label class="col-sm-2 control-label">URL</label>

                <div class="col-sm-6">
                        <a href="{{ route('enduserportal.submit', ['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox->id)]) }}" class="btn btn-primary" target="_blank"><i class="glyphicon glyphicon-new-window"></i> {{ __('End-User Portal') }}</a>   

                        <div class="margin-top-10">
                            <small style="word-break: break-all;" class="text-help">{{ route('enduserportal.submit', ['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox->id)]) }}</small>
                        </div>            

                </div>
            </div>

            <div class="form-group">

                <div class="col-sm-6 col-sm-offset-2">
                    <label class="checkbox">
                        <input type="checkbox" name="settings[existing]" value="1" @if (old('settings[existing]', $settings['existing']))checked="checked"@endif />
                        {{ __('Only existing customers having tickets are allowed to login to End-User Portal') }}
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Submit a Ticket') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control" name="settings[text_submit]" value="{{ $settings['text_submit'] ?: __('Submit a Ticket') }}" />
                </div>
            </div>

            @if (\Module::isActive('customfields'))
                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __("Custom Fields") }}</label>

                    <div class="col-sm-8">
                        @foreach(\CustomField::getMailboxCustomFields($mailbox->id) as $custom_field)
                            <label class="checkbox checkbox-inline" for="cf_{{ $custom_field->id }}">
                                <input type="checkbox" name="cf[]" value="{{ $custom_field->id }}" id="cf_{{ $custom_field->id }}" @if (!empty($widget_settings['cf']) && in_array($custom_field->id, $widget_settings['cf'])) checked @endif> {{ $custom_field->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Footer') }}</label>

                <div class="col-sm-6">
                    <textarea id="eup-settings-footer" class="form-control" name="settings[footer]" rows="3">{{ old('settings[footer]', $settings['footer']) }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Consent Checkbox') }}</label>

                <div class="col-sm-6">
                    <div class="controls">
                        <div class="onoffswitch-wrap">
                            <div class="onoffswitch">
                                <input type="checkbox" name="settings[consent]" value="1" id="eup_consent" class="onoffswitch-checkbox" @if (old('settings[consent]', $settings['consent']))checked="checked"@endif >
                                <label class="onoffswitch-label" for="eup_consent"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group @if (!old('settings[consent]', $settings['consent'])) hidden @endif" id="eup-privacy-container">
                <label class="col-sm-2 control-label">{{ __('Privacy Policy') }}</label>

                <div class="col-sm-6">
                    <textarea id="eup-privacy" class="form-control" name="settings[privacy]" rows="3">{{ old('settings[privacy]', $settings['privacy']) }}</textarea>
                </div>
            </div>

            <div class="form-group margin-top">
                <div class="col-sm-6 col-sm-offset-2">
                    <button type="submit" name="eup_action" value="save_settings" class="btn btn-primary">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>

            <h3 class="subheader">{{ __("Contact Form Widget") }}</h3>

            {{--<div class="form-group">
                <label class="col-sm-2 control-label"></label>

                <div class="col-sm-6 text-help">
                    {{ __("Use the code below to embed the contact form widget on your website.") }}
                </div>
            </div>--}}

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Main Color") }}</label>

                <div class="col-sm-6">
                    <div class="input-group input-sized input-sized-lg eup-colorpicker">
                        <input type="text" class="form-control" name="color" value="{{ $widget_settings['color'] ?? \EndUserPortal::getDefaultWidgetSettings()['color'] }}" />
                        <span class="input-group-addon" style="background-color: {{ $widget_settings['color'] ?? \EndUserPortal::getDefaultWidgetSettings()['color'] }};"></span>
                    </div>
                </div>
            </div>

            {{--<div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Form Title") }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized input-sized-lg" name="title" value="{{ $widget_settings['title'] ?? \EndUserPortal::getDefaultWidgetSettings()['title'] }}" />
                </div>
            </div>--}}

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Position") }}</label>

                <div class="col-sm-6">
                    <select name="position" class="form-control input-sized input-sized-lg">
                        <option value="br" @if (!empty($widget_settings['position']) && $widget_settings['position'] == 'br') selected @endif >{{ __("Bottom-right corner of the page") }}</option>
                        <option value="bl" @if (!empty($widget_settings['position']) && $widget_settings['position'] == 'bl') selected @endif>{{ __("Bottom-left corner of the page") }}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("Language") }}</label>

                <div class="col-sm-6">
                    <select name="locale" class="form-control input-sized input-sized-lg">
                        <option value=""></option>
                        @foreach($locales as $locale_code)
                            @php
                                $data = \Helper::getLocaleData($locale_code);
                                if (empty($data['name'])) {
                                    $data['name'] = $locale_code;
                                }
                            @endphp
                            <option value="{{ $locale_code }}" @if (!empty($widget_settings['locale']) && $widget_settings['locale'] == $locale_code) selected @endif >{{ $data['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @php
                if (isset($widget_settings['settings'])) {
                    unset($widget_settings['settings']);
                }
            @endphp
            <div class="form-group @if (empty($widget_settings)) hidden @endif" id="eup-widget-code-wrapper">

                <div class="col-sm-6 col-sm-offset-2">
                    <textarea rows="5" readonly class="disabled form-control" id="eup-widget-code">&lt;!-- FreeScout BEGIN --&gt;
&lt;script&gt;var FreeScoutW={s:{{ \Helper::jsonEncodeUtf8($widget_settings) }}@if ($prefill_test),f:{{ \Helper::jsonEncodeUtf8($prefill_test) }}@endif{{ '}' }};(function(d,e,s){if(d.getElementById(&quot;freescout-w&quot;))return;a=d.createElement(e);m=d.getElementsByTagName(e)[0];a.async=1;a.id=&quot;freescout-w&quot;;a.src=s;m.parentNode.insertBefore(a, m)})(document,&quot;script&quot;,&quot;{{ \EndUserPortal::getWidgetScriptUrl($mailbox->id, true) }}&quot;);&lt;/script&gt;
&lt;!-- FreeScout END --&gt;</textarea>
                    @if (!strstr(config('app.url'), 'https:'))
                        <p class="text-warning">
                            {{ __("If you are embedding the contact form widget on HTTPS website, your FreeScout must also use HTTPS.") }}
                        </p>
                    @endif
                    <p class="text-help">
                        {{ __("After making updates in the settings you need to update the code on your website.") }}
                    </p>
                    <p class="form-help">
                        <button type="button" class="btn btn-default" id="eup-show-preview"><small class="glyphicon glyphicon-search"></small> {{ __("Preview") }}</button>
                        <a href="{{ route('enduserportal.widget_form', array_merge(['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox->id, \EndUserPortal::WIDGET_SALT)], $widget_settings)) }}" target="_blank" class="btn btn-link">{{ __("Open in New Window") }}</a>
                    </p>
                </div>
            </div>

            <div class="form-group @if (!empty($widget_settings)) hidden @endif" id="eup-widget-save-wrapper">
                <div class="col-sm-6 col-sm-offset-2">
                    <button type="submit" class="btn btn-primary" name="eup_action" value="save_widget">{{ __("Get the Code") }}</button>
                </div>
            </div>

            {{--
            <input type="hidden" name="settings[dummy]" value="1" />

            <div class="form-group">
                <label for="twofactorauth_required" class="col-sm-2 control-label">{{ __("Required For All Users") }}</label>

                <div class="col-sm-6">
                    <div class="controls">
                        <div class="onoffswitch-wrap">
                            <div class="onoffswitch">
                                <input type="checkbox" name="settings[twofactorauth.required]" value="1" id="twofactorauth_required" class="onoffswitch-checkbox" @if (old('settings[twofactorauth.required]', $settings['twofactorauth.required']))checked="checked"@endif >
                                <label class="onoffswitch-label" for="twofactorauth_required"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group margin-top margin-bottom">
                <div class="col-sm-6 col-sm-offset-2">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
            --}}
        </form>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    eupInitSettings();
@endsection