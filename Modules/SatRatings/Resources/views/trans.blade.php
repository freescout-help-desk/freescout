@extends('layouts.app')

@section('title_full', __('Satisfaction Translations').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading-noborder">
        {{ __('Satisfaction Ratings') }}
    </div>

    @include('satratings::partials/tabs')

 	<div class="row-container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top margin-bottom-40" method="POST" action="" id="form-trans">
                    {{ csrf_field() }}

                    <div class="form-group{{ $errors->has('trans.title') ? ' has-error' : '' }}">
                        <label for="trans_title" class="col-sm-2 control-label">{{ __('Page Title') }}</label>

                        <div class="col-sm-6">
                            <input id="trans_title" class="form-control input-sized" name="trans[title]" value="{{ old('trans.title', $trans['title'])  }}" required="required" autofocus="autofocus" data-default="{{ $default['title'] }}">
                            <div class="{{ $errors->has('trans.title') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'trans.title'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('trans.success_title') ? ' has-error' : '' }}">
                        <label for="trans_success_title" class="col-sm-2 control-label">{{ __('Header') }}</label>

                        <div class="col-sm-6">
                            <input id="trans_success_title" class="form-control input-sized" name="trans[success_title]" value="{{ old('trans.success_title', $trans['success_title'])  }}" required="required" autofocus="autofocus" data-default="{{ $default['success_title'] }}">
                            <div class="{{ $errors->has('trans.success_title') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'trans.success_title'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('trans.level_great') ? ' has-error' : '' }}">
                        <label for="trans_level_great" class="col-sm-2 control-label">{{ __('Great') }}</label>

                        <div class="col-sm-6">
                            <input id="trans_level_great" class="form-control input-sized" name="trans[level_great]" value="{{ old('trans.level_great', $trans['level_great'])  }}" required="required" autofocus="autofocus" data-default="{{ $default['level_great'] }}">
                            <div class="{{ $errors->has('trans.level_great') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'trans.level_great'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('trans.level_okay') ? ' has-error' : '' }}">
                        <label for="trans_level_okay" class="col-sm-2 control-label">{{ __('Okay') }}</label>

                        <div class="col-sm-6">
                            <input id="trans_level_okay" class="form-control input-sized" name="trans[level_okay]" value="{{ old('trans.level_okay', $trans['level_okay'])  }}" required="required" autofocus="autofocus" data-default="{{ $default['level_okay'] }}">
                            <div class="{{ $errors->has('trans.level_okay') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'trans.level_okay'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('trans.level_bad') ? ' has-error' : '' }}">
                        <label for="trans_level_bad" class="col-sm-2 control-label">{{ __('Not Good') }}</label>

                        <div class="col-sm-6">
                            <input id="trans_level_bad" class="form-control input-sized" name="trans[level_bad]" value="{{ old('trans.level_bad', $trans['level_bad'])  }}" required="required" autofocus="autofocus" data-default="{{ $default['level_bad'] }}">
                            <div class="{{ $errors->has('trans.level_bad') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'trans.level_bad'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('trans.comment') ? ' has-error' : '' }}">
                        <label for="trans_comment" class="col-sm-2 control-label">{{ __('Comment Box') }}</label>

                        <div class="col-sm-6">
                            <textarea id="trans_comment" class="form-control input-sized" name="trans[comment]" rows="2" required="required" autofocus="autofocus" data-default="{{ $default['comment'] }}">{{ old('trans.comment', $trans['comment'])  }}</textarea>
                            <div class="{{ $errors->has('trans.comment') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'trans.comment'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('trans.comment_placeholder') ? ' has-error' : '' }}">
                        <label for="trans_comment_placeholder" class="col-sm-2 control-label">{{ __('Comment Placeholder') }}</label>

                        <div class="col-sm-6">
                            <textarea id="trans_comment_placeholder" class="form-control input-sized" name="trans[comment_placeholder]" rows="2" required="required" autofocus="autofocus" data-default="{{ $default['comment_placeholder'] }}">{{ old('trans.comment_placeholder', $trans['comment_placeholder'])  }}</textarea>
                            <div class="{{ $errors->has('trans.comment_placeholder') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'trans.comment_placeholder'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('trans.submit') ? ' has-error' : '' }}">
                        <label for="trans_submit" class="col-sm-2 control-label">{{ __('Send Button') }}</label>

                        <div class="col-sm-6">
                            <input id="trans_submit" class="form-control input-sized" name="trans[submit]" value="{{ old('trans.submit', $trans['submit'])  }}" required="required" autofocus="autofocus" data-default="{{ $default['submit'] }}">
                            <div class="{{ $errors->has('trans.submit') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'trans.submit'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('trans.success_message') ? ' has-error' : '' }}">
                        <label for="trans_success_message" class="col-sm-2 control-label">{{ __('Send Confirmation') }}</label>

                        <div class="col-sm-6">
                            <textarea id="trans_success_message" class="form-control input-sized" name="trans[success_message]" rows="2" required="required" autofocus="autofocus" data-default="{{ $default['success_message'] }}">{{ old('trans.success_message', $trans['success_message'])  }}</textarea>
                            <div class="{{ $errors->has('trans.success_message') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'trans.success_message'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group margin-top-30">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save') }}
                            </button>

                            <a href="#" class="btn btn-link reset-trigger">{{ __('Reset to defaults') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
  
@endsection

@section('javascript')
    @parent
    initSatRatingsTrans('{{ __('Reset to default values?') }}');
@endsection