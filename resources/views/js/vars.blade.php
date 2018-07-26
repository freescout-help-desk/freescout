{{-- 
    Languages strings and custom variables passed to JS.
    After changing this file make sure to run:
        php artisan freescout:generate-vars-js
--}}

{{-- Global vars for JS. Set in /app/Console/Commands/GenerateJs.php --}}
var Vars = {
    
};

{{-- 
    Localized JS strings.
    Usage:
        Lang.get('messages.ajax_error');
        Lang.get('messages.ajax_error', { name: 'Joe' });
--}}
var lang_messages = {
    @foreach ($locales as $locale)
        "{{ $locale }}.messages": {
            {{-- Add here strings which you need to be translated in JS--}}
            "ajax_error": "{{ __("Error occured. Please check your internet connection and try again.") }}"
        }@if (!$loop->last) @endif
    @endforeach
};

(function () {
    Lang = new Lang();
    Lang.setMessages(lang_messages);
})();