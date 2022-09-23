<?php
    if (!isset($subscriptions_formname)) $subscriptions_formname = "subscriptions";
?>
<table class="table" style="margin-bottom: 0;">
    <tr class="table-header">
        <th>
            @if ($person)
                {{ __('Notify :person when…', ['person' => $person]) }}
            @else
                {{ __('Notify me when…') }}
            @endif
        </th>
        <td class="text-center">{{ __('Email') }}<br/><input type="checkbox" class="sel-all" value="email"></td>
        <td class="text-center">{{ __('Browser') }}<br/><input type="checkbox" class="sel-all" value="browser"></td>
        <td class="text-center">{{ __('Mobile') }}<br/><small>(<a href="https://freescout.net/module/mobile-notifications/" target="_blank">Android</a> / <a href="https://freescout.net/module/mobile-notifications/" target="_blank">iOS</a>)</small><br/><input type="checkbox" class="sel-all" @if (!$mobile_available) disabled="disabled" @endif value="mobile"></td>
        @action('notifications_table.th')
    </tr>
    <tr>
        <td>{{ __('There is a new conversation') }}</td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_NEW_CONVERSATION]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_NEW_CONVERSATION }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_NEW_CONVERSATION]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_NEW_CONVERSATION }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_NEW_CONVERSATION]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_NEW_CONVERSATION }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_NEW_CONVERSATION)
    </tr>
    <tr>
        <td>
            @if ($person)
                {{ __('A conversation is assigned to :person', ['person' => $person]) }}
            @else
                {{ __('A conversation is assigned to me') }}
            @endif
        </td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME)
    </tr>
    <tr>
        <td>{{ __('A conversation is assigned to someone else') }}</td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CONVERSATION_ASSIGNED)
    </tr>
    <tr>
        <td>
            @if ($person)
                {{ __('A conversation :person is following is updated', ['person' => $person]) }}
            @else
                {{ __("A conversation I'm following is updated") }}
            @endif
        </td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED)
    </tr>
    @action('notifications_table.general.append', ['person' => $person, 'subscriptions' => $subscriptions, 'mobile_available' => $mobile_available, 'subscriptions_formname' => $subscriptions_formname])
    <tr class="table-header">
        <th>
            @if ($person)
                {{ __("Notify :person when a customer replies…", ['person' => $person]) }}
            @else
                {{ __('Notify me when a customer replies…') }}
            @endif
        </th>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
    </tr>
    <tr>
        <td>{{ __('To an unassigned conversation') }}</td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED)
    </tr>
    <tr>
        <td>
            @if ($person)
                {{ __("To one of :person's conversations", ['person' => $person]) }}
            @else
                {{ __('To one of my conversations') }}
            @endif
        </td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY)
    </tr>
    <tr>
        <td>{{ __('To a conversation owned by someone else') }}</td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED)
    </tr>
    <tr class="table-header">
        <th>
            @if ($person)
                {{ __("Notify :person when another :app_name user replies or adds a note…", ['person' => $person, 'app_name' => config('app.name')]) }}
            @else
                {{ __('Notify me when another :app_name user replies or adds a note…', ['app_name' => config('app.name')]) }}
            @endif
        </th>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
    </tr>
    <tr>
        <td>{{ __('To an unassigned conversation') }}</td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED)
    </tr>
    <tr>
        <td>
            @if ($person)
                {{ __("To one of :person's conversations", ['person' => $person]) }}
            @else
                {{ __('To one of my conversations') }}
            @endif
        </td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_MY }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_MY }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_USER_REPLIED_TO_MY }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_USER_REPLIED_TO_MY)
    </tr>
    <tr>
        <td>{{ __('To a conversation owned by someone else') }}</td>
        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"></td>
        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"></td>
        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED)
    </tr>
</table>
