@foreach ($web_notifications_info_data as $web_notification_data)
    @if ($loop->first || \App\User::dateFormat($web_notifications_info_data[$loop->index-1]['created_at'], 'M j, Y') != \App\User::dateFormat($web_notification_data['created_at'], 'M j, Y'))


        @php
            $notification_date = \App\User::dateFormat($web_notification_data['created_at'], 'M j, Y');
        @endphp
        <li class="web-notification-date" data-date="{{ $notification_date }}">
            @if ($notification_date == \App\User::dateFormat(\Carbon\Carbon::now(), 'M j, Y'))
                {{ __('Today') }}
            @else
                {{ $notification_date }}
            @endif
        </li>
    @endif
    <li class="web-notification @if (empty($web_notification_data['notification']->read_at)) is-unread @endif" data-notification_id="{{ $web_notification_data['notification']->id }}">
        @php
            $conv_params = [];
            if (!$web_notification_data['notification']->read_at) {
                $conv_params['mark_as_read'] = $web_notification_data['notification']->id;
            }
        @endphp
        <a href="{{ $web_notification_data['conversation']->url(null, $web_notification_data['thread']->id, $conv_params) }}" title="{{ __('View conversation') }}">
        	<div class="web-notification-img">
                @include('partials/person_photo', ['person' => \Eventy::filter('web_notification.person', $web_notification_data['thread']->getPerson(true), $web_notification_data)])
            </div>
            <div class="web-notification-msg">
                <div class="web-notification-msg-header">
                    {!! \Eventy::filter('web_notification.header', $web_notification_data['thread']->getActionDescription($web_notification_data['conversation']->number, true, Auth::user()), $web_notification_data) !!}
                </div>
                <div class="web-notification-msg-preview">
                    {{ App\Misc\Helper::textPreview($web_notification_data['last_thread_body']) }}
                </div>
                @action('web_notification.after_body', $web_notification_data)
            </div>
        </a>
    </li>
@endforeach
