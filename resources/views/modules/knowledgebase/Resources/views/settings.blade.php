@extends('layouts.app')

@section('title_full', __('Knowledge Base').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading margin-bottom">
        {{ __('Knowledge Base') }}
    </div>

    <div class="col-xs-12">
        @include('knowledgebase::partials/settings_tab')

        <form class="form-horizontal margin-top margin-bottom-30" method="POST" action="" autocomplete="off">
            {{ csrf_field() }}

            <div class="form-group">
                <label class="col-sm-2 control-label">URL</label>

                <div class="col-sm-6">
                        <a href="{{ \Kb::getKbUrl($mailbox) }}" class="btn btn-primary" target="_blank"><i class="glyphicon glyphicon-new-window"></i> {{ __('Knowledge Base') }}</a>   

                        <div class="margin-top-10">
                            <small style="word-break: break-all;" class="text-help">{{ \Kb::getKbUrl($mailbox) }}</small>
                        </div>            

                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Site Name') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control" name="settings[site_name]" value="{{ $settings['site_name'] }}">
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Custom Domain') }}</label>

                <div class="col-sm-6">
                    <div class="input-group">
                        <span class="input-group-addon">{{ parse_url(config('app.url'), PHP_URL_SCHEME) }}://</span>
                        <input type="text" class="form-control" name="settings[domain]" value="{{ $settings['domain'] }}" pattern="[^/]*">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Footer') }}</label>

                <div class="col-sm-6">
                    <textarea id="kb-settings-footer" class="form-control" name="settings[footer]" rows="3">{{ old('settings[footer]', $settings['footer']) }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Menu Buttons') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control" name="settings[menu]" value="{{ $settings['menu'] }}">
                    <p class="form-help">
                        <strong>{{ __('Format') }}</strong>: [Button 1](https://button1-url.com)[Button 2](https://button2-url.com)
                        <br/>
                        <strong>{{ __('Example') }}</strong>: [Contact Us](https://demo.freescout.net/chat/widget/form/2312787837)
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Languages') }}</label>

                <div class="col-sm-6">
                    <select class="form-control" id="kb-locales" name="settings[locales][]" multiple>
                        @foreach ($settings['locales'] as $locale_code)
                            <option value="{{ $locale_code }}" selected="selected">[{{ strtoupper($locale_code) }}] {{ \Helper::getLocaleData($locale_code)['name'] }}</option>
                        @endforeach
                        @foreach (\Helper::$locales as $locale_code => $locale_data)
                            @if (!in_array($locale_code, $settings['locales']))
                                <option value="{{ $locale_code }}">[{{ strtoupper($locale_code) }}] {{ $locale_data['name'] }}</option>
                            @endif
                        @endforeach
                    </select>
                    <p class="form-help">
                        {{ __('Minimum 2 languages. The first language is the primary language.') }}
                    </p>
                </div>
            </div>

            <div class="form-group margin-top">
                <div class="col-sm-6 col-sm-offset-2">
                    <button type="submit" name="kb_action" value="save_settings" class="btn btn-primary">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>


            <div id="kb-widget-form">
                <h3 class="subheader">{{ __("Widget") }}</h3>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __("Main Color") }}</label>

                    <div class="col-sm-6">
                        <div class="input-group input-sized input-sized-lg kb-colorpicker">
                            <input type="text" class="form-control" name="widget[color]" value="{{ $widget_settings['color'] ?? \Kb::getDefaultWidgetSettings()['color'] }}" />
                            <span class="input-group-addon" style="background-color: {{ $widget_settings['color'] ?? \Kb::getDefaultWidgetSettings()['color'] }};"></span>
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
                    <label class="col-sm-2 control-label">{{ __("Show Categories") }}</label>

                    <div class="col-sm-6">
                        <div class="controls">
                            <label class="checkbox inline plain">
                                <input type="checkbox" name="widget[show_categories]" value="1" id="show_categories" @if (!empty($widget_settings['show_categories'])) checked @endif />
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group @if (empty($widget_settings)) hidden @endif" id="kb-widget-code-wrapper">

                    <div class="col-sm-6 col-sm-offset-2">
                        <textarea rows="5" readonly class="disabled form-control" id="kb-widget-code">&lt;!-- FreeScout BEGIN --&gt;
&lt;script&gt;var FreeScoutW={s:{{ \Helper::jsonEncodeUtf8($widget_settings) }}{{ '}' }};(function(d,e,s){if(d.getElementById(&quot;freescout-w&quot;))return;a=d.createElement(e);m=d.getElementsByTagName(e)[0];a.async=1;a.id=&quot;freescout-w&quot;;a.src=s;m.parentNode.insertBefore(a, m)})(document,&quot;script&quot;,&quot;{{ \Kb::getWidgetScriptUrl($mailbox->id, true) }}&quot;);&lt;/script&gt;
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
                            <button type="button" class="btn btn-default" id="kb-show-preview"><small class="glyphicon glyphicon-search"></small> {{ __("Preview") }}</button>
                        </p>
                    </div>
                </div>

                <div class="form-group @if (!empty($widget_settings)) hidden @endif" id="kb-widget-save-wrapper">
                    <div class="col-sm-6 col-sm-offset-2">
                        <button type="submit" class="btn btn-primary" name="kb_action" value="save_widget">{{ __("Get the Code") }}</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    kbInitSettings();
@endsection