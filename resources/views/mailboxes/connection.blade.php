@extends('layouts.app')

@section('title_full', __('Connection Settings').' - '.$mailbox->name)

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

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal" method="POST" action="">
                    {{ csrf_field() }}

                    <div class="form-group margin-top">
                        <label for="email" class="col-sm-2 control-label">{{ __('Method') }}</label>

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
                        </div>
                    </div>

                    <div id="out_method_{{ App\Mailbox::OUT_METHOD_SMTP }}_options" class="out_method_options hidden margin-top">
                        <div class="form-group{{ $errors->has('out_server') ? ' has-error' : '' }}">
                            <label for="out_server" class="col-sm-2 control-label">{{ __('SMTP Server') }}</label>

                            <div class="col-md-6">
                                <input id="out_server" type="text" class="form-control input-sized" name="out_server" value="{{ old('out_server', $mailbox->out_server) }}" maxlength="255" required autofocus>

                                @include('partials/field_error', ['field'=>'out_server'])
                            </div>
                        </div>
                        <div class="form-group{{ $errors->has('out_port') ? ' has-error' : '' }}">
                            <label for="out_port" class="col-sm-2 control-label">{{ __('Port') }}</label>

                            <div class="col-md-6">
                                <input id="out_port" type="number" class="form-control input-sized" name="out_port" value="{{ old('out_port', $mailbox->out_port) }}" maxlength="5" required autofocus>

                                @include('partials/field_error', ['field'=>'out_port'])
                            </div>
                        </div>
                        <div class="form-group{{ $errors->has('out_username') ? ' has-error' : '' }}">
                            <label for="out_username" class="col-sm-2 control-label">{{ __('Username') }}</label>

                            <div class="col-md-6">
                                <input id="out_username" type="text" class="form-control input-sized" name="out_username" value="{{ old('out_username', $mailbox->out_username) }}" maxlength="100" required autofocus>

                                @include('partials/field_error', ['field'=>'out_username'])
                            </div>
                        </div>
                        <div class="form-group{{ $errors->has('out_password') ? ' has-error' : '' }}">
                            <label for="out_password" class="col-sm-2 control-label">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="out_password" type="password" class="form-control input-sized" name="out_password" value="{{ old('out_password', $mailbox->out_password) }}" maxlength="255" required autofocus>

                                @include('partials/field_error', ['field'=>'out_password'])
                            </div>
                        </div>
                        <div class="form-group{{ $errors->has('out_ssl') ? ' has-error' : '' }}">
                            <label for="out_ssl" class="col-sm-2 control-label">{{ __('Security') }}</label>

                            <div class="col-md-6">
                                <select id="out_ssl" class="form-control input-sized" name="out_ssl" required autofocus>
                                    <option value="{{ App\Mailbox::OUT_SSL_NONE }}" @if (old('out_ssl', $mailbox->out_ssl) == App\Mailbox::OUT_SSL_NONE)selected="selected"@endif>{{ __('None') }}</option>
                                    <option value="{{ App\Mailbox::OUT_SSL_SSL }}" @if (old('out_ssl', $mailbox->out_ssl) == App\Mailbox::OUT_SSL_SSL)selected="selected"@endif>{{ __('SSL') }}</option>
                                    <option value="{{ App\Mailbox::OUT_SSL_TLS }}" @if (old('out_ssl', $mailbox->out_ssl) == App\Mailbox::OUT_SSL_TLS)selected="selected"@endif>{{ __('TLS') }}</option>
                                </select>

                                @include('partials/field_error', ['field'=>'out_ssl'])
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group margin-bottom-0">
                        <label for="send_test" class="col-sm-2 control-label">{{ __('Improve Delivery') }}</label>

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
                    </div>

                    <div class="form-group">
                        <label for="send_test" class="col-sm-2 control-label">{{ __('Send Test') }}</label>

                        <div class="col-md-6">
                            <div class="input-group input-sized">
                                <input id="send_test" type="email" class="form-control" value="{{ old('email', $mailbox->email) }}" maxlength="128">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button">{{ __('Send Test') }}</button>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group margin-top">
                        <div class="col-md-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save Settings') }}
                            </button>
                        </div>
                    </div>

                    <div class="margin-top-40"></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    mailboxConnectionInit('{{ App\Mailbox::OUT_METHOD_SMTP }}');
@endsection