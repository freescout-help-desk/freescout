@extends('layouts.app')

@section('title_full', __('Mailbox Permissions').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Permissions') }}
    </div>

    @include('partials/flash_messages')

    <div class="container form-container">
        <div class="row">
            <form method="POST" action="">
                <div class="col-xs-12">
                    <h3> {{ __('Selected Users have access to this mailbox:') }}</h3>
                    <p class="block-help">{{ __('Administrators have access to all mailboxes and are not listed here.') }}</p>
                </div>
                <div class="col-xs-12">
                    
                    {{ csrf_field() }}

                    <p><a href="javascript:void(0)" class="sel-all">{{ __('all') }}</a> / <a href="javascript:void(0)" class="sel-none">{{ __('none') }}</a></p>

                    <fieldset id="permissions-fields">
                        @foreach ($users as $user)
                            <div class="control-group">
                                <div class="controls">
                                    <label class="control-label checkbox" for="user-{{ $user->id }}">
                                        <input type="checkbox" name="users[]" id="user-{{ $user->id }}" value="{{ $user->id }}" @if ($mailbox_users->contains($user)) checked="checked" @endif> {{ $user->first_name }} {{ $user->last_name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </fieldset>

                </div>

                <div class="col-xs-12 margin-top">
                    <h3> {{ __('Administrators') }}:</h3>
                </div>
                <div class="col-md-11 col-lg-9">
                    
                    <table class="table">
                        <tr class="table-header-nb">
                            <th>&nbsp;</th>
                            <th class="text-center">{{ __('Hide from Assign list') }}</th>
                        </tr>
                        <fieldset id="permissions-fields">
                            @foreach ($admins as $admin)
                                <tr>
                                    <td>{{ $admin->getFullName() }}</td>
                                    <td class="text-center"><input type="checkbox" name="admins[{{ $admin->id }}][hide]" value="1" @if (!empty($admin->hide)) checked="checked" @endif></td>
                                </tr>
                            @endforeach
                        </fieldset>
                    </table>
                    <div class="form-group margin-top">
                        
                        <button type="submit" class="btn btn-primary">
                            {{ __('Save') }}
                        </button>
                    
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    permissionsInit();
@endsection