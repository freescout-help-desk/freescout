<form class="form-horizontal margin-top margin-bottom" method="POST" action="" id="slack_form">
    {{ csrf_field() }}

    {{--<div class="descr-block">
        <p>{{ __("These settings are used to send system emails (alerts to admin and invitation emails to users).") }}</p>
    </div>--}}

    <div class="form-group{{ $errors->has('settings.slack.api_token') ? ' has-error' : '' }} margin-bottom-10">
        <label for="slack.api_token" class="col-sm-2 control-label">{{ __('API Token') }}</label>

        <div class="col-sm-6">
            <input id="slack.api_token" type="text" class="form-control input-sized-lg" name="settings[slack.api_token]" value="{{ old('settings.slack.api_token', $settings['slack.api_token']) }}">
            <a href="{{ config('app.freescout_url') }}/slack/" target="_blank" class="small">{{ __('Get API Token') }}</a>
            @if ($token_error)
                <div class="alert alert-danger alert-narrow margin-bottom-0">
                    <strong>{{ __('Invalid API Token') }}</strong> ({{ $token_error }})
                </div>
            @endif
            @include('partials/field_error', ['field'=>'settings.slack.api_token'])
        </div>
    </div>

    <div class="form-group">
        <label for="" class="col-sm-2 control-label">{{ __('Events') }}</label>

        <div class="col-sm-6">
            @foreach ($events as $event_code => $event_title)
                <div class="control-group">
                    <label class="checkbox" for="event_{{ $event_code }}">
                        <input type="checkbox" name="settings[slack.events][]" value="{{ $event_code }}" id="event_{{ $event_code }}" @if (in_array($event_code, old('settings[slack.events]', $settings['slack.events']))) checked="checked" @endif @if (!$active) disabled @endif> {{ $event_title }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="form-group">
        <label for="" class="col-sm-2 control-label">{{ __('Channels Mapping') }}</label>

        <div class="col-sm-6">
            @if ($channels_mapping)
                @if ($channels_error && !$token_error)
                    <div class="alert alert-warning alert-striped alert-narrow">
                        {{ __('Error occured retrieving data from Slack API.') }} (<a href="{{ route('logs', ['name' => 'slack']) }}" target="_blank">{{ __("View logs") }})</a>
                    </div>
                @endif
                @if (count($channels_mapping))
                    <div class="row margin-bottom-10" style="margin-top: 4px;">
                        <div class="col-xs-4">
                            <strong>{{ __('Mailbox') }}</strong>
                        </div>
                        <div class="col-xs-8">
                            <strong>{{ __('Channel') }}</strong>
                        </div>
                    </div>
                @endif
                @foreach ($channels_mapping as $mailbox_id => $mapping)
                    <div class="row margin-bottom-10">
                        <div class="col-xs-4">
                            {{ $mapping['mailbox']->name }}
                        </div>
                        <div class="col-xs-8">
                            <select class="form-control input-sized" name="settings[slack.channels_mapping][{{ $mailbox_id }}]" @if (!$active) disabled @endif>
                                <option value=""></option>
                                @foreach ($channels as $channel)
                                    <option value="{{ $channel['id'] }}" @if (old('settings.slack.channels_mapping.'.$mailbox_id, $settings['slack.channels_mapping'][$mailbox_id] ?? '') == $channel['id'])selected="selected"@endif>#{{ $channel['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="alert alert-info alert-striped alert-narrow">
                    {{ __('There are no mailboxes yet.') }} <a href="{{ route('mailboxes.create') }}">{{ __("Create Mailbox") }}</a>
                </div>
            @endif
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>