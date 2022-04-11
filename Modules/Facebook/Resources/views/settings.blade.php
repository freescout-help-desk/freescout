@extends('layouts.app')

@section('title_full', __('Facebook').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading margin-bottom">
        {{ __('Facebook') }}
    </div>

    <div class="col-xs-12">
  
        <form class="form-horizontal margin-bottom" method="POST" action="" autocomplete="off">
            {{ csrf_field() }}

            <div class="form-group">
                <label class="col-sm-2 control-label"></label>

                <div class="col-sm-6">
                    <label class="control-label">
                        <a href="https://freescout.net/module/facebook/" target="_blank">{{ __('Instruction') }} <small class="glyphicon glyphicon-share"></small></a>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Page Access Token') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[token]" value="{{ $settings['token'] ?? '' }}">
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('App Secret') }}</label>

                <div class="col-sm-6">
                    <input type="text" class="form-control input-sized-lg" name="settings[app_secret]" value="{{ $settings['app_secret'] ?? '' }}">
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Callback URL') }}</label>

                <div class="col-sm-6">
                    <label class="control-label">
                        <span class="text-help">{{ route('facebook.webhook', ['mailbox_id' => $mailbox->id, 'mailbox_secret' => \Facebook::getMailboxSecret($mailbox->id)]) }}</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Verify Token') }}</label>

                <div class="col-sm-6">
                    <label class="control-label">
                        <span class="text-help">{{ \Facebook::getMailboxVerifyToken($mailbox->id) }}</span>
                    </label>
                </div>
            </div>

            <div class="form-group margin-top">
                <div class="col-sm-6 col-sm-offset-2">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection