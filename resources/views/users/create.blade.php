@extends('layouts.app')

@section('title', __('New User'))

@section('content')
<div class="container">
	<div class="row">
	    <div class="col-md-8 col-md-offset-2">
	        <div class="panel panel-default panel-wizard">
	            <div class="panel-body">
	            	<div class="wizard-header">
		            	<h1>{{ __('Create a New User') }}</h1>
		            </div>
		            <div class="wizard-body">
					 	@include('partials/flash_messages')

				        <div class="row">
				            <div class="col-xs-12">
				                <form class="form-horizontal margin-top" method="POST" action="">
				                    {{ csrf_field() }}

				                    <div class="form-group{{ $errors->has('role') ? ' has-error' : '' }}">
				                        <label for="role" class="col-sm-4 control-label">{{ __('Role') }}</label>

				                        <div class="col-md-6">
				                        	<div class="flexy">
					                            <select id="role" type="text" class="form-control input-sized" name="role" required autofocus>
					                                <option value="{{ App\User::ROLE_USER }}" @if (old('role') == App\User::ROLE_USER)selected="selected"@endif>{{ __('User') }}</option>
					                                <option value="{{ App\User::ROLE_ADMIN }}" @if (old('role') == App\User::ROLE_ADMIN)selected="selected"@endif>{{ __('Administrator') }}</option>
					                            </select>

					                            <i class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="ture" data-placement="left" data-title="{{ __('Roles') }}" data-content="{{ __('<strong>Administrators</strong> can create new users and have access to all mailboxes and settings <br><br><strong>Users</strong> have access to the mailbox(es) specified in their permissions') }}"></i>
					                        </div>

				                            @include('partials/field_error', ['field'=>'role'])
				                        </div>
				                    </div>

									<div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
				                        <label for="first_name" class="col-sm-4 control-label">{{ __('First Name') }}</label>

				                        <div class="col-md-6">
				                            <input id="first_name" type="text" class="form-control input-sized" name="first_name" value="{{ old('first_name') }}" maxlength="20" required autofocus>

				                            @include('partials/field_error', ['field'=>'first_name'])
				                        </div>
				                    </div>

				                    <div class="form-group{{ $errors->has('last_name') ? ' has-error' : '' }}">
				                        <label for="last_name" class="col-sm-4 control-label">{{ __('Last Name') }}</label>

				                        <div class="col-md-6">
				                            <input id="last_name" type="text" class="form-control input-sized" name="last_name" value="{{ old('last_name') }}" maxlength="30" required autofocus>

				                            @include('partials/field_error', ['field'=>'last_name'])
				                        </div>
				                    </div>

				                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
				                        <label for="email" class="col-sm-4 control-label">{{ __('Email') }}</label>

				                        <div class="col-md-6">
				                            <input id="email" type="email" class="form-control input-sized" name="email" value="{{ old('email') }}" maxlength="100" required autofocus>

				                            @include('partials/field_error', ['field'=>'email'])
				                        </div>
				                    </div>

				                    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}  @if (!empty(old('send_invite')) || empty(old('role'))) hidden @endif no-send-invite">
				                        <label for="password" class="col-sm-4 control-label">{{ __('Password') }}</label>

				                        <div class="col-md-6">
				                            <input id="password" type="password" class="form-control input-sized" name="password" value="{{ old('password') }}" maxlength="255" required autofocus>

				                            @include('partials/field_error', ['field'=>'password'])
				                        </div>
				                    </div>

				                    @if (count($mailboxes))
					                    <div class="form-group{{ $errors->has('users') ? ' has-error' : '' }} margin-bottom-0">
					                        <label for="users" class="col-sm-4 control-label">{{ __('Which mailboxes will user use?') }}</label>

					                        <div class="col-md-6 control-padded">
					                            <div><a href="javascript:void(0)" class="sel-all">{{ __('all') }}</a> / <a href="javascript:void(0)" class="sel-none">{{ __('none') }}</a></div>

					                            <fieldset id="permissions-fields">
							                        @foreach ($mailboxes as $mailbox)
							                            <div class="control-group">
							                                <div class="controls">
							                                    <label class="control-label checkbox" for="mailbox-{{ $mailbox->id }}">
							                                        <input type="checkbox" name="mailboxes[]" id="mailbox-{{ $mailbox->id }}" value="{{ $mailbox->id }}" @if (is_array(old('mailboxes')) && in_array($mailbox->id, old('mailboxes'))) checked="checked" @endif> {{ $mailbox->name }}
							                                    </label>
							                                </div>
							                            </div>
							                        @endforeach
							                    </fieldset>

					                            @include('partials/field_error', ['field'=>'mailboxes'])
					                        </div>
					                    </div>
				                    @endif

									<div class="col-md-6 col-sm-offset-4">
										<div class="input-sized">
											<hr class="form-divider">
										</div>
									</div>

									<div class="form-group">
										<div class="controls send-email">
											<div class="col-md-8 col-sm-offset-4">
												<label class="checkbox">
													<input type="checkbox" name="send_invite" id="send_invite" value="1" @if (!empty(old('send_invite')) || empty(old('role'))) checked="checked" @endif>
													{{ __('Send an invite email') }}
												</label>

												<span class="text-help">{{ __("An invite can be sent later if you aren't ready") }}</span>
											</div>
										</div>
							        </div>

				                    <div class="form-group">
				                        <div class="col-md-6 col-sm-offset-4">
				                            <button type="submit" class="btn btn-primary">
				                                {{ __('Create User') }}
				                            </button>

				                            <a href="{{ route('users') }}" class="btn btn-link">{{ __('Cancel') }}</a>
				                        </div>
				                    </div>

				                </form>
				            </div>
				        </div>
					    
	                </div>
	                <div class="wizard-footer">
	                	
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
</div>
@endsection

@section('javascript')
    @parent
    permissionsInit();
    userCreateInit();
@endsection