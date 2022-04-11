@extends('layouts.app')

@section('title_full', __('Chat').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Chat') }}
    </div>

    <div class="col-xs-12">
        <form class="form-horizontal margin-top margin-bottom" method="POST" action="" autocomplete="off">
            {{ csrf_field() }}

            <div id="chat-widget-form">
                <h3 class="subheader">{{ __("Widget") }}</h3>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __("Main Color") }}</label>

                    <div class="col-sm-6">
                        <div class="input-group input-sized input-sized-lg chat-colorpicker">
                            <input type="text" class="form-control" name="widget[color]" value="{{ $widget_settings['color'] ?? \Chat::getDefaultWidgetSettings()['color'] }}" />
                            <span class="input-group-addon" style="background-color: {{ $widget_settings['color'] ?? \Chat::getDefaultWidgetSettings()['color'] }};"></span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __("Position") }}</label>

                    <div class="col-sm-6">
                        <select name="widget[position]" class="form-control input-sized input-sized-lg">
                            <option value="br" @if (!empty($widget_settings['position']) && $widget_settings['position'] == 'br') selected @endif >{{ __("Bottom-right corner of the page") }}</option>
                            <option value="bl" @if (!empty($widget_settings['position']) && $widget_settings['position'] == 'bl') selected @endif>{{ __("Bottom-left corner of the page") }}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __("Language") }}</label>

                    <div class="col-sm-6">
                        <select name="widget[locale]" class="form-control input-sized input-sized-lg">
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

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __("Visitor") }}</label>

                    <div class="col-sm-2">
                        <div class="input-sized input-sized-lg">
                            <input type="text" class="form-control" name="widget[visitor_name]" placeholder="{{ __('Name') }}" value="{{ $widget_settings['visitor_name'] ?? \Chat::getDefaultWidgetSettings()['visitor_name'] }}" />
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="input-sized input-sized-lg">
                            <input type="email" class="form-control" name="widget[visitor_email]" placeholder="{{ __('Email') }}" value="{{ $widget_settings['visitor_email'] ?? \Chat::getDefaultWidgetSettings()['visitor_email'] }}" />
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="input-sized input-sized-lg">
                            <input type="text" class="form-control" name="widget[visitor_phone]" placeholder="{{ __('Phone') }}" value="{{ $widget_settings['visitor_phone'] ?? \Chat::getDefaultWidgetSettings()['visitor_phone'] }}" />
                        </div>
                    </div>
                    <div class="col-sm-10 col-sm-offset-2">
                        <p class="form-help">
                            {{ __('Visitor can be optionally set to pass visitor info to the widget on the website.') }}
                        </p>
                    </div>
                </div>

                <div class="form-group @if (empty($widget_settings)) hidden @endif" id="chat-widget-code-wrapper">

                    <div class="col-sm-6 col-sm-offset-2">
                        <textarea rows="5" readonly class="disabled form-control" id="chat-widget-code">&lt;!-- FreeScout BEGIN --&gt;
&lt;script&gt;var FreeScoutW={s:{{ \Helper::jsonEncodeUtf8($widget_settings) }}{{ '}' }};(function(d,e,s){if(d.getElementById(&quot;freescout-w&quot;))return;a=d.createElement(e);m=d.getElementsByTagName(e)[0];a.async=1;a.id=&quot;freescout-w&quot;;a.src=s;m.parentNode.insertBefore(a, m)})(document,&quot;script&quot;,&quot;{{ \Chat::getWidgetScriptUrl($mailbox->id, true) }}&quot;);&lt;/script&gt;
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
                            <button type="button" class="btn btn-default" id="chat-show-preview"><small class="glyphicon glyphicon-search"></small> {{ __("Preview") }}</button>
                            <a href="{{ route('chat.widget_form', array_merge(['mailbox_id' => \Chat::encodeMailboxId($mailbox->id)], $widget_settings)) }}" target="_blank" class="btn btn-link">{{ __("Open in New Window") }}</a>
                        </p>
                    </div>
                </div>

                <div class="form-group @if (!empty($widget_settings)) hidden @endif" id="chat-widget-save-wrapper">
                    <div class="col-sm-6 col-sm-offset-2">
                        <button type="submit" class="btn btn-primary" name="chat_action" value="save_widget">{{ __("Get the Code") }}</button>
                    </div>
                </div>

            </div>

            <h3 class="subheader">{{ __("Chat Operating Hours") }}</h3>

            <div class="form-group">

                <div class="col-sm-6 col-sm-offset-2">
                    <p class="text-help">{{ __("Chat operating hours allow to display 'Contact Us' form instead of a chat when your support agents are not working.") }} {!! __("Chat operating hours are based on your :%a_begin%company timezone:%a_end%.", ['%a_begin%' => '<a href="'.route('settings').'" target="_blank">', '%a_end%' => '</a> ('.config('app.timezone').')']) !!}</p>

                    @if (!\Module::isActive('enduserportal'))
                        <div class="alert alert-info">{!! __(":%a_begin%End-User Portal Module:%a_end% is required.", ['%a_begin%' => '<strong><a href="https://freescout.net/module/end-user-portal/" target="_blank">', '%a_end%' => '</strong></a>']) !!}</div>
                    @endif
                </div>
            </div>

            @if (\Module::isActive('enduserportal'))
                @php
                    $eup_update = false;
                    $eup_module = \Module::findByAlias('enduserportal');
                    if ($eup_module && !version_compare($eup_module->get('version'), '1.0.9', '>=')) {
                        $eup_update = true;
                    }
                @endphp
                @if ($eup_update)
                    <div class="form-group">
                        <div class="col-sm-6 col-sm-offset-2">
                            <div class="alert alert-info">{!! __(":%a_begin%End-User Portal Module:%a_end% is required.", ['%a_begin%' => '<strong><a href="https://freescout.net/module/end-user-portal/" target="_blank">', '%a_end%' => ' (> v1.0.9)</strong></a>']) !!}</div>
                        </div>
                    </div>
                @else
                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __("Monday") }}</label>

                        <div class="col-sm-6 form-inline">
                            @include('chat::admin/partials/chat_hours_select', ['day' => 1])
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __("Tuesday") }}</label>

                        <div class="col-sm-6 form-inline">
                            @include('chat::admin/partials/chat_hours_select', ['day' => 2])
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __("Wednesday") }}</label>

                        <div class="col-sm-6 form-inline">
                            @include('chat::admin/partials/chat_hours_select', ['day' => 3])
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __("Thursday") }}</label>

                        <div class="col-sm-6 form-inline">
                            @include('chat::admin/partials/chat_hours_select', ['day' => 4])
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __("Friday") }}</label>

                        <div class="col-sm-6 form-inline">
                            @include('chat::admin/partials/chat_hours_select', ['day' => 5])
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __("Saturday") }}</label>

                        <div class="col-sm-6 form-inline">
                            @include('chat::admin/partials/chat_hours_select', ['day' => 6])
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __("Sunday") }}</label>

                        <div class="col-sm-6 form-inline">
                            @include('chat::admin/partials/chat_hours_select', ['day' => 0])
                        </div>
                    </div>

                    <div class="form-group margin-top margin-bottom-30">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary" name="chat_action" value="save_chat_hours">{{ __("Save") }}</button>
                        </div>
                    </div>
                @endif
            @endif
            
        </form>
    </div>
@endsection

@section('javascript')
    @parent
    chatInitSettings();
@endsection