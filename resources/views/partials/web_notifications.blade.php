@foreach ($web_notifications_info['data'] as $web_notification_data)
    @if ($loop->first || \App\User::dateFormat($web_notifications_info['data'][$loop->index-1]['created_at'], 'M j, Y') != \App\User::dateFormat($web_notification_data['created_at'], 'M j, Y'))

        <li class="web-notification-date">
            @if (\App\User::dateFormat($web_notification_data['created_at'], 'M j, Y') == \App\User::dateFormat(\Carbon\Carbon::now(), 'M j, Y'))
                {{ __('Today') }}
            @else
                {{ \App\User::dateFormat($web_notification_data['created_at'], 'M j, Y') }}
            @endif
        </li>
    @endif
    <li class="web-notification @if (!$web_notification_data['notification']->read_at) is-unread @endif">
        <a href="{{ $web_notification_data['conversation']->url(null, $web_notification_data['thread']->id) }}" title="{{ __('View conversation') }}">
        	<div class="web-notification-img">
                @include('partials/person_photo', ['person' => $web_notification_data['thread']->getPerson(true)])
            </div>
            <div class="web-notification-msg">
                <div class="web-notification-msg-header">
                    {!! $web_notification_data['thread']->getActionDescription($web_notification_data['conversation']->number) !!}
                </div>
                <div class="web-notification-msg-preview">
                    {{ App\Misc\Helper::textPreview($web_notification_data['last_thread_body']) }}
                </div>
            </div>
        </a>
    </li>
@endforeach