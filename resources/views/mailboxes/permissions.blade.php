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

                    <div class="form-group margin-top">

                        <button type="submit" class="btn btn-primary">
                            {{ __('Save') }}
                        </button>

                    </div>

                </div>


                <div class="col-xs-12 margin-top">
                    <h3> {{ __('Access Settings') }}:</h3>
                </div>
                <div class="col-md-11 col-lg-9">

                    <table class="table">
                        <tr class="table-header-nb">
                            <th>&nbsp;</th>
                            <th class="text-center"> {{ __('Hide from Assign list') }}</th>
                            @foreach (\App\Mailbox::$access_permissions as $perm)
                                <th class="text-center"> {{ \App\Mailbox::getAccessPermissionName($perm) }}</th>
                            @endforeach
                        </tr>
                        <fieldset id="permissions-fields">
                            @foreach ($managers as $mailbox_user)
                                <tr>
                                    <td>
                                        {{ $mailbox_user->getFullName() }}
                                        @if ($mailbox_user->isAdmin())
                                            ({{ __('Administrator') }})
                                        @endif
                                    </td>
                                    <td class="text-center"><input type="checkbox" name="managers[{{ $mailbox_user->id }}][hide]" value="1" @if (!empty($mailbox_user->hide)) checked="checked" @endif></td>
                                            
                                    @foreach (\App\Mailbox::$access_permissions as $perm)
                                        <td class="text-center">
                                            @if (!$mailbox_user->isAdmin())
                                                <input type="checkbox" name="managers[{{ $mailbox_user->id }}][access][{{ $perm }}]" value="{{ $perm }}" @if (count($managers) > 10) data-toggle="tooltip" title="{{ \App\Mailbox::getAccessPermissionName($perm) }}" @endif @if (!empty($mailbox_user->access) && in_array($perm, json_decode($mailbox_user->access))) checked="checked" @endif @if (Auth::id() == $mailbox_user->id && !Auth::user()->isAdmin()) disabled @endif/> 
                                            @else
                                                <input type="checkbox" name="" value="" checked="checked" disabled />
                                            @endif
                                        </td>
                                    @endforeach
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
