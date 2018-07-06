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
                        <label for="email" class="col-sm-2 control-label">{{ __('Fetch Emails From') }}</label>

                        <div class="col-md-6 flexy">
                            <input id="email" type="email" class="form-control input-sized" name="email" value="{{ $mailbox->email }}" disabled="disabled">
                            <a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}#email" class="btn btn-link btn-sm" data-toggle="tooltip" title="{{ __('Change address in mailbox settings') }}">{{ __('Change') }}</a>
                        </div>

                        <div class="col-sm-offset-2 col-md-6">
                            <p class="help-block margin-bottom-0"><strong>{{ __('ATTENTION') }}:</strong> {{ __('All emails from this address will be deleted') }}</p>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('in_server') ? ' has-error' : '' }}">
                        <label for="in_server" class="col-sm-2 control-label">{{ __('POP Server') }}</label>

                        <div class="col-md-6">
                            <input id="in_server" type="text" class="form-control input-sized" name="in_server" value="{{ old('in_server', $mailbox->in_server) }}" maxlength="255" required autofocus>

                            @include('partials/field_error', ['field'=>'in_server'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('in_port') ? ' has-error' : '' }}">
                        <label for="in_port" class="col-sm-2 control-label">{{ __('Port') }}</label>

                        <div class="col-md-6">
                            <input id="in_port" type="number" class="form-control input-sized" name="in_port" value="{{ old('in_port', $mailbox->in_port) }}" maxlength="5" required autofocus>

                            @include('partials/field_error', ['field'=>'in_port'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('in_username') ? ' has-error' : '' }}">
                        <label for="in_username" class="col-sm-2 control-label">{{ __('Username') }}</label>

                        <div class="col-md-6">
                            <input id="in_username" type="text" class="form-control input-sized" name="in_username" value="{{ old('in_username', $mailbox->in_username) }}" maxlength="100" required autofocus>

                            @include('partials/field_error', ['field'=>'in_username'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('in_password') ? ' has-error' : '' }}">
                        <label for="in_password" class="col-sm-2 control-label">{{ __('Password') }}</label>

                        <div class="col-md-6">
                            <input id="in_password" type="password" class="form-control input-sized" name="in_password" value="{{ old('in_password', $mailbox->in_password) }}" maxlength="255" required autofocus>

                            @include('partials/field_error', ['field'=>'in_password'])
                        </div>
                    </div>
                    <div class="form-group margin-top">
                        <div class="col-md-6 col-sm-offset-2">
                            <button type="button" class="btn btn-default btn-sm">
                                {{ __('Check Connection') }}
                            </button>
                        </div>
                    </div>

                    <div class="form-group margin-top-2">
                        <div class="col-md-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save Settings') }}
                            </button>
                        </div>
                    </div>

                    <div class="margin-top-2"></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    mailboxConnectionInit('{{ App\Mailbox::OUT_METHOD_SMTP }}');
@endsection