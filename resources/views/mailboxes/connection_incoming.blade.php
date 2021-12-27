@extends('layouts.app')

@section('title_full', __('Connection Settings').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading margin-bottom">
        {{ __('Connection Settings') }}
    </div>

    @include('mailboxes/connection_menu')

    @include('partials/flash_messages')

    <div class="container form-container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal" method="POST" action="">
                    {{ csrf_field() }}

                    <div class="descr-block">
                        {!! __("You can read more about fetching emails :%a_begin%here:%a_end%.", ['%a_begin%' => '<a href="https://github.com/freescout-helpdesk/freescout/wiki/Fetching-Emails" target="_blank">', '%a_end%' =>'</a>']) !!}
                    </div>

                    <div class="form-group margin-top">
                        <label for="email" class="col-sm-2 control-label">{{ __('Status') }}</label>

                        <div class="col-sm-6">
                            <label class="control-label">
                                @if ($mailbox->isInActive())
                                    <span class="text-success"><i class="glyphicon glyphicon-ok"></i> {{ __('Active') }}</span>
                                @else
                                    <span class="text-warning"><i class="glyphicon glyphicon-ok"></i> {{ __('Inactive') }}</span>
                                @endif
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="col-sm-2 control-label">{{ __('Fetch From') }}</label>

                        <div class="col-sm-6 flexy">
                            <input id="email" type="email" class="form-control input-sized" name="email" value="{{ $mailbox->email }}" disabled="disabled">
                            <a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}#email" class="btn btn-link btn-sm" data-toggle="tooltip" title="{{ __('Change address in mailbox settings') }}">{{ __('Change') }}</a>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('in_protocol') ? ' has-error' : '' }}">
                        <label for="in_protocol" class="col-sm-2 control-label">{{ __('Protocol') }}</label>

                        <div class="col-sm-6">
                            <div class="flexy">
                                <select id="in_protocol" class="form-control input-sized" name="in_protocol" required autofocus>
                                    @foreach($mailbox->getInProtocolDisplayNames() as $id => $name)
                                        <option value="{{$id}}" @if (old('in_protocol', $mailbox->in_protocol) == $id)selected="selected"@endif>{{$name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            @include('partials/field_error', ['field'=>'in_protocol'])
                        </div>
                    </div>

                    <div data-in-protocol="default">
                        <div class="form-group{{ $errors->has('in_server') ? ' has-error' : '' }}">
                            <label for="in_server" class="col-sm-2 control-label">{{ __('Server') }}</label>

                            <div class="col-sm-6">
                                <input id="in_server" type="text" class="form-control input-sized" name="in_server" value="{{ old('in_server', $mailbox->in_server) }}" maxlength="255">

                                {{--@include('partials/field_error', ['field'=>'in_server'])--}}
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('in_port') ? ' has-error' : '' }}">
                            <label for="in_port" class="col-sm-2 control-label">{{ __('Port') }}</label>

                            <div class="col-sm-6">
                                <input id="in_port" type="number" class="form-control input-sized" name="in_port" value="{{ old('in_port', $mailbox->in_port) }}" maxlength="5" required autofocus>

                                {{--@include('partials/field_error', ['field'=>'in_port'])--}}
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('in_username') ? ' has-error' : '' }}">
                            <label for="in_username" class="col-sm-2 control-label">{{ __('Username') }}</label>

                            <div class="col-sm-6">
                                <input id="in_username" type="text" class="form-control input-sized" name="in_username" value="{{ old('in_username', $mailbox->in_username) }}" maxlength="100" {{-- This added to prevent autocomplete in Chrome --}}autocomplete="new-password">

                                {{--@include('partials/field_error', ['field'=>'in_username'])--}}
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('in_password') ? ' has-error' : '' }}">
                            <label for="in_password" class="col-sm-2 control-label">{{ __('Password') }}</label>

                            <div class="col-sm-6">
                                <input id="in_password" type="password" class="form-control input-sized" name="in_password" value="{{ old('in_password', $mailbox->inPasswordSafe()) }}" maxlength="255" {{-- This added to prevent autocomplete in Chrome --}}autocomplete="new-password">

                                {{--@include('partials/field_error', ['field'=>'in_password'])--}}
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('in_encryption') ? ' has-error' : '' }}">
                            <label for="in_encryption" class="col-sm-2 control-label">{{ __('Encryption') }}</label>

                            <div class="col-sm-6">
                                <select id="in_encryption" class="form-control input-sized" name="in_encryption" @if ($mailbox->out_method == App\Mailbox::OUT_METHOD_SMTP) required @endif autofocus>
                                    <option value="{{ App\Mailbox::IN_ENCRYPTION_NONE }}" @if (old('in_encryption', $mailbox->in_encryption) == App\Mailbox::IN_ENCRYPTION_NONE)selected="selected"@endif>{{ __('None') }}</option>
                                    <option value="{{ App\Mailbox::IN_ENCRYPTION_SSL }}" @if (old('in_encryption', $mailbox->in_encryption) == App\Mailbox::IN_ENCRYPTION_SSL)selected="selected"@endif>SSL</option>
                                    <option value="{{ App\Mailbox::IN_ENCRYPTION_TLS }}" @if (old('in_encryption', $mailbox->in_encryption) == App\Mailbox::IN_ENCRYPTION_TLS)selected="selected"@endif>TLS</option>
                                </select>

                                @include('partials/field_error', ['field'=>'in_encryption'])
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('in_imap_folders') ? ' has-error' : '' }}">
                            <label for="in_imap_folders" class="col-sm-2 control-label">{{ __('IMAP Folders') }}</label>

                            <div class="col-sm-6 flexy">
                                <select id="in_imap_folders" class="form-control input-sized" name="in_imap_folders[]" multiple>
                                    @foreach ($mailbox->getInImapFolders() as $imap_folder)
                                        <option value="{{ $imap_folder }}" selected="selected">{{ $imap_folder }}</option>
                                    @endforeach
                                </select>

                                <a href="#" class="btn btn-link btn-sm" data-toggle="tooltip" title="{{ __('Retrieve a list of available IMAP folders from the server') }}" id="retrieve-imap-folders" data-loading-text="{{ __('Retrieving') }}…">{{ __('Get folders') }}</a>

                                @include('partials/field_error', ['field'=>'in_imap_folders'])
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('in_validate_cert') ? ' has-error' : '' }}">
                            <label for="in_validate_cert" class="col-sm-2 control-label">{{ __('Validate Certificate') }}</label>

                            <div class="col-sm-6">
                                <div class="controls">
                                    <div class="onoffswitch-wrap">
                                        <div class="onoffswitch">
                                            <input type="checkbox" name="in_validate_cert" value="1" id="in_validate_cert" class="onoffswitch-checkbox" @if (old('in_validate_cert', $mailbox->in_validate_cert))checked="checked"@endif >
                                            <label class="onoffswitch-label" for="in_validate_cert"></label>
                                        </div>

                                        <i class="glyphicon glyphicon-info-sign icon-info icon-info-inline" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="{{ __('Disable certificate validation if receiving "Certificate failure" error.') }}"></i>
                                    </div>
                                </div>

                                @include('partials/field_error', ['field'=>'in_validate_cert'])

                                <div class="form-help">{!! __("Make sure to save settings before checking connection.") !!}</div>
                            </div>
                        </div>

                        <div>
                            <div class="form-group">
                                <label for="imap_sent_folder" class="col-sm-2 control-label">{{ __('IMAP Folder To Save Outgoing Replies') }}</label>

                                <div class="col-sm-6">
                                    <input id="imap_sent_folder" type="text" class="form-control input-sized" name="imap_sent_folder" value="{{ old('imap_sent_folder', $mailbox->imap_sent_folder) }}" maxlength="255" placeholder="Sent">
                                    <div class="form-help">{!! __("Enter IMAP folder name to save outgoing replies if your mail service provider does not do it automatically (Gmail does it), otherwise leave it blank.") !!}</div>
                                </div>
                            </div>
                            <hr/>
                        </div>
                    </div>

                    @foreach($mailbox->getInProtocols() as $key => $name)
                        {{-- Skip the default protocols --}}
                        @if(isset(\App\Mailbox::$in_protocols[$key]))
                            @continue
                        @endif

                        <div data-in-protocol="{{$key}}" style="display: none;">
                            @include(\Eventy::filter('mailbox.connection_incoming.settings', null, $key, $name))
                        </div>
                    @endforeach

                    <div class="form-group margin-top-2">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save Settings') }}
                            </button>
                            &nbsp;
                            <button type="button" class="btn btn-default btn-sm" id="check-connection" data-loading-text="{{ __('Connecting') }}…" @if (!$mailbox->isOutActive()) disabled="disabled" @endif>
                                {{ __('Check Connection') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    mailboxConnectionInit('{{ App\Mailbox::OUT_METHOD_SMTP }}');
    mailboxConnectionIncomingInit();
@endsection
