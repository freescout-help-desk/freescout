@extends('layouts.app')

@section('title_full', __('Connection Settings').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading-noborder">
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
                        {!! __("You can read more about sending emails :%a_begin%here:%a_end%.", ['%a_begin%' => '<a href="https://github.com/freescout-helpdesk/freescout/wiki/Sending-emails" target="_blank">', '%a_end%' =>'</a>']) !!}

                        {!! __("To send system emails via webmail providers (Gmail, Yahoo, etc) use only SMTP method and make sure that SMTP username is equal to the mailbox email address (:%mailbox_email%), otherwise webmail provider won't send emails.", ['%mailbox_email%' => $mailbox->email]) !!}
                    </div>
                    <hr/>

                    <div class="form-group margin-top">
                        <label for="email" class="col-sm-2 control-label">{{ __('Method') }} {{--<a href="https://github.com/freescout-helpdesk/freescout/wiki/Sending-emails" target="blank" class="glyphicon glyphicon-info-sign help-icon" data-toggle="tooltip" title="{{ __("Click to read more about sending methods") }}"></a>--}}</label>

                        <div class="col-sm-6">
                            <div class="control-group">
                                <label class="radio" for="out_method_{{ App\Mailbox::OUT_METHOD_PHP_MAIL }}">
                                    <input type="radio" name="out_method" value="{{ App\Mailbox::OUT_METHOD_PHP_MAIL }}" id="out_method_{{ App\Mailbox::OUT_METHOD_PHP_MAIL }}" @if ($mailbox->out_method == App\Mailbox::OUT_METHOD_PHP_MAIL) checked="checked" @endif> {{ __("PHP's mail() function") }}
                                </label>
                            </div>
                            <div class="control-group">
                                <label class="radio" for="out_method_{{ App\Mailbox::OUT_METHOD_SENDMAIL }}">
                                    <input type="radio" name="out_method" value="{{ App\Mailbox::OUT_METHOD_SENDMAIL }}" id="out_method_{{ App\Mailbox::OUT_METHOD_SENDMAIL }}" @if ($mailbox->out_method == App\Mailbox::OUT_METHOD_SENDMAIL) checked="checked" @endif> {{ __("Sendmail") }}
                                </label>
                                <div id="out_method_{{ App\Mailbox::OUT_METHOD_SENDMAIL }}_options" class="radio out_method_options text-help padding-top-0 @if ($mailbox->out_method != App\Mailbox::OUT_METHOD_SENDMAIL) hidden @endif">
                                    <strong>{{ __("PHP sendmail path:") }}</strong> {{ $sendmail_path }}
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="radio" for="out_method_{{ App\Mailbox::OUT_METHOD_SMTP }}">
                                    <input type="radio" name="out_method" value="{{ App\Mailbox::OUT_METHOD_SMTP }}" id="out_method_{{ App\Mailbox::OUT_METHOD_SMTP }}" @if ($mailbox->out_method == App\Mailbox::OUT_METHOD_SMTP) checked="checked" @endif> {{ __("SMTP") }}
                                </label>
                            </div>
                            <div class="control-group text-help margin-top-10">
                                <ul>
                                    <li>
                                        <a href="https://aws.amazon.com/ses/pricing/" target="_blank">Amazon SES</a> - 
                                        <span class="text-help">{!! __("62,000 free emails per month from :%a_begin%Amazon EC2:%a_end% server.", ['%a_begin%' => '<a href="https://aws.amazon.com/ec2/" target="_blank">', '%a_end%' => '</a>']) !!}</span>
                                    </li>
                                    <li>
                                        <a href="https://www.mailgun.com" target="_blank">Mailgun</a> - 
                                        <span class="text-help">{{ __(":number free emails per month.", ['number' => '10,000']) }}</span>
                                    </li>
                                    <li>
                                        <a href="https://www.sendinblue.com/?tap_a=30591-fb13f0&tap_s=294678-0b81e5" target="_blank">SendinBlue</a> - 
                                        <span class="text-help">{{ __(":number free emails per month.", ['number' => '9,000']) }}</span>
                                    </li>
                                    <li>
                                        <a href="https://www.mailjet.com/?tap_a=25852-4bddf6&tap_s=545617-55177a&aff=545617-55177a" target="_blank">Mailjet</a> - 
                                        <span class="text-help">{{ __(":number free emails per month.", ['number' => '6,000']) }}</span>
                                    </li>
                                </ul>
                            </div>
                            {{--<div class="control-group">
                                <label class="radio disabled" for="out_method_elastic">
                                    <input type="radio" name="out_method" disabled value="elastic" id="out_method_elastic" @if ($mailbox->out_method == 'elastic') checked="checked" @endif> <a href="https://elasticemail.com/account#/create-account?r=bc0975e9-3d6b-462f-be7c-629e7672a4a8" target="_blank">Elastic Email</a><br/>
                                    <span class="text-help">{{ __("150,000 free emails per month") }}</span>
                                </label>
                            </div>--}}
                        </div>
                    </div>

                    <div id="out_method_{{ App\Mailbox::OUT_METHOD_SMTP }}_options" class="out_method_options @if ($mailbox->out_method != App\Mailbox::OUT_METHOD_SMTP) hidden @endif margin-top">
                        <hr/>
                        <div class="form-group{{ $errors->has('out_server') ? ' has-error' : '' }}">
                            <label for="out_server" class="col-sm-2 control-label">{{ __('SMTP Server') }}</label>

                            <div class="col-sm-6">
                                <input id="out_server" type="text" class="form-control input-sized" name="out_server" value="{{ old('out_server', $mailbox->out_server) }}" maxlength="255"  @if ($mailbox->out_method == App\Mailbox::OUT_METHOD_SMTP) required @endif autofocus>


                                @if (strstr($mailbox->out_server, '.gmail.'))
                                    <div class="form-help">
                                        {!! __("Make sure to :%link_start%enable less secure apps:%link_end% in your Google account to send emails from Gmail.", ['%link_start%' => '<a href="https://myaccount.google.com/lesssecureapps?pli=1" target="_blank">', '%link_end%' => '</a>']) !!}
                                    </div>
                                @endif

                                @include('partials/field_error', ['field'=>'out_server'])
                            </div>
                        </div>
                        <div class="form-group{{ $errors->has('out_port') ? ' has-error' : '' }}">
                            <label for="out_port" class="col-sm-2 control-label">{{ __('Port') }}</label>

                            <div class="col-sm-6">
                                <input id="out_port" type="number" class="form-control input-sized" name="out_port" value="{{ old('out_port', $mailbox->out_port) }}" maxlength="5" @if ($mailbox->out_method == App\Mailbox::OUT_METHOD_SMTP) required @endif autofocus>

                                @include('partials/field_error', ['field'=>'out_port'])
                            </div>
                        </div>
                        <div class="form-group{{ $errors->has('out_username') ? ' has-error' : '' }}">
                            <label for="out_username" class="col-sm-2 control-label">{{ __('Username') }}</label>

                            <div class="col-sm-6">
                                <input id="out_username" type="text" class="form-control input-sized" name="out_username" value="{{ old('out_username', $mailbox->out_username) }}" maxlength="100" @if ($mailbox->out_method == App\Mailbox::OUT_METHOD_SMTP) required @endif autofocus>

                                @include('partials/field_error', ['field'=>'out_username'])
                            </div>
                        </div>
                        <div class="form-group{{ $errors->has('out_password') ? ' has-error' : '' }}">
                            <label for="out_password" class="col-sm-2 control-label">{{ __('Password') }}</label>

                            <div class="col-sm-6">
                                <input id="out_password" type="password" class="form-control input-sized" name="out_password" value="{{ old('out_password', $mailbox->out_password) }}" maxlength="255" @if ($mailbox->out_method == App\Mailbox::OUT_METHOD_SMTP) required @endif autofocus>

                                @include('partials/field_error', ['field'=>'out_password'])
                            </div>
                        </div>
                        <div class="form-group{{ $errors->has('out_encryption') ? ' has-error' : '' }}">
                            <label for="out_encryption" class="col-sm-2 control-label">{{ __('Encryption') }}</label>

                            <div class="col-sm-6">
                                <select id="out_encryption" class="form-control input-sized" name="out_encryption" @if ($mailbox->out_method == App\Mailbox::OUT_METHOD_SMTP) required @endif autofocus>
                                    <option value="{{ App\Mailbox::OUT_ENCRYPTION_NONE }}" @if (old('out_encryption', $mailbox->out_encryption) == App\Mailbox::OUT_ENCRYPTION_NONE)selected="selected"@endif>{{ __('None') }}</option>
                                    <option value="{{ App\Mailbox::OUT_ENCRYPTION_SSL }}" @if (old('out_encryption', $mailbox->out_encryption) == App\Mailbox::OUT_ENCRYPTION_SSL)selected="selected"@endif>{{ __('SSL') }}</option>
                                    <option value="{{ App\Mailbox::OUT_ENCRYPTION_TLS }}" @if (old('out_encryption', $mailbox->out_encryption) == App\Mailbox::OUT_ENCRYPTION_TLS)selected="selected"@endif>{{ __('TLS') }}</option>
                                </select>

                                @include('partials/field_error', ['field'=>'out_encryption'])
                            </div>
                        </div>
                        <hr/>
                    </div>
                    
                    {{--<div class="form-group margin-bottom-0">
                        <label class="col-sm-2 control-label">{{ __('Improve Delivery') }}</label>

                        <div class="col-sm-6">
                            <div class="panel-group accordion">
                                <div class="panel panel-default panel-spf">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#collapse-spf">SPF 
                                                <i class="label label-success accordion-status">Active</i>
                                                <b class="caret"></b>
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="collapse-spf" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <p>todo</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel panel-default panel-ptr">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#collapse-ptr">PTR 
                                                <i class="label label-success accordion-status">Active</i>
                                                <b class="caret"></b>
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="collapse-ptr" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <p>todo</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel panel-default panel-dmarc">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#collapse-dmarc">DMARC 
                                                <i class="label label-success accordion-status">Active</i>
                                                <b class="caret"></b>
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="collapse-dmarc" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <p>todo</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>--}}

                    <div class="form-group">
                        <label for="send_test" class="col-sm-2 control-label">{{ __('Send Test To') }}</label>

                        <div class="col-sm-6">
                            <div class="input-group input-sized">
                                <input id="send_test" type="email" class="form-control" name="send_test_to" value="{{ old('email', \App\Option::get('send_test_to', $mailbox->email)) }}" maxlength="128" @if (!$mailbox->isOutActive()) disabled="disabled" @endif>
                                <span class="input-group-btn">
                                    <button id="send-test-trigger" class="btn btn-default" type="button" data-loading-text="{{ __('Sending') }}…" @if (!$mailbox->isOutActive()) disabled="disabled" @endif>{{ __('Send Test') }}</button> 
                                </span>
                            </div>
                            <div class="form-help">{!! __("Make sure to save settings before testing.") !!}</div>
                        </div>
                    </div>

                    <div class="form-group margin-top">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save Settings') }}
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
@endsection