@extends('layouts.app')

@section('title_full', __('Notifications').' - '.$user->first_name.' '.$user->last_name)

@if ($user->id == Auth::user()->id)
    @section('body_attrs')@parent data-own_profile="true" @endsection
@endif

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Notifications') }}
    </div>

    @include('partials/flash_messages')

    <div class="row-container">
        <div class="row">
            <div class="col-md-11 col-lg-9">
                <form method="POST" action="" class="user-subscriptions">
                {{ csrf_field() }}
                <table class="table margin-top">
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
                    </tr>
                    <tr>
                        <td>{{ __('There is a new conversation') }}</td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_NEW_CONVERSATION]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_NEW_CONVERSATION }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_NEW_CONVERSATION]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_NEW_CONVERSATION }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_NEW_CONVERSATION]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_NEW_CONVERSATION }}"></td>
                    </tr>
                    <tr>
                        <td>
                            @if ($person)
                                {{ __('A conversation is assigned to :person', ['person' => $person]) }}
                            @else
                                {{ __('A conversation is assigned to me') }}
                            @endif
                        </td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"></td>
                    </tr>
                    <tr>
                        <td>{{ __('A conversation is assigned to someone else') }}</td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED }}"></td>
                    </tr>
                    <tr>
                        <td>
                            @if ($person)
                                {{ __('A conversation :person is following is updated', ['person' => $person]) }}
                            @else
                                {{ __("A conversation I'm following is updated") }}
                            @endif
                        </td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"></td>
                    </tr>
                    {{--<tr>
                        <td>
                            @if ($person)
                                {{ __(':person is @mentioned in a conversation', ['person' => $person]) }}
                            @else
                                {{ __("I'm @mentioned in a conversation") }}
                            @endif 
                            (todo)
                        </td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_I_AM_MENTIONED]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_I_AM_MENTIONED }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_I_AM_MENTIONED]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_I_AM_MENTIONED }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_I_AM_MENTIONED]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_I_AM_MENTIONED }}"></td>
                    </tr>
                    <tr>
                        <td>
                            @if ($person)
                                {{ __(":person's team is @mentioned in a conversation", ['person' => $person]) }}
                            @else
                                {{ __('My team is @mentioned in a conversation') }}
                            @endif 
                            (todo)
                        </td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_MY_TEAM_MENTIONED]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_MY_TEAM_MENTIONED }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_MY_TEAM_MENTIONED]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_MY_TEAM_MENTIONED }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_MY_TEAM_MENTIONED]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_MY_TEAM_MENTIONED }}"></td>
                    </tr>--}}
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
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"></td>
                    </tr>
                    <tr>
                        <td>
                            @if ($person)
                                {{ __("To one of :person's conversations", ['person' => $person]) }}
                            @else
                                {{ __('To one of my conversations') }}
                            @endif
                        </td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"></td>
                    </tr>
                    <tr>
                        <td>{{ __('To a conversation owned by someone else') }}</td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"></td>
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
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"></td>
                    </tr>
                    <tr>
                        <td>
                            @if ($person)
                                {{ __("To one of :person's conversations", ['person' => $person]) }}
                            @else
                                {{ __('To one of my conversations') }}
                            @endif
                        </td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_MY]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_MY }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_MY]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_MY }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_MY]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_USER_REPLIED_TO_MY }}"></td>
                    </tr>
                    <tr>
                        <td>{{ __('To a conversation owned by someone else') }}</td>
                        <td class="subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"></td>
                        <td class="subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"></td>
                        <td class="subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED]) name="subscriptions[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"></td>
                    </tr>
                </table>
                <div class="form-group margin-top">
                    
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save Notifications') }}
                    </button>
                
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    notificationsInit();
@endsection