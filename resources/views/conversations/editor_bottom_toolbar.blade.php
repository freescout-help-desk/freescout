<div id="editor_bottom_toolbar" style="display:none">
	{{ __('Status') }}: 
	<select name="status" class="form-control" data-parsley-exclude="true">
        <option value="{{ App\Mailbox::TICKET_STATUS_ACTIVE }}" @if (old('status', $mailbox->ticket_status) == App\Mailbox::TICKET_STATUS_ACTIVE)selected="selected"@endif>{{ __('Active') }}</option>
        <option value="{{ App\Mailbox::TICKET_STATUS_PENDING }}" @if (old('status', $mailbox->ticket_status) == App\Mailbox::TICKET_STATUS_PENDING)selected="selected"@endif>{{ __('Pending') }}</option>
        <option value="{{ App\Mailbox::TICKET_STATUS_CLOSED }}" @if (old('status', $mailbox->ticket_status) == App\Mailbox::TICKET_STATUS_CLOSED)selected="selected"@endif>{{ __('Closed') }}</option>
    </select> 
    <small class="glyphicon glyphicon-chevron-right note-bottom-div"></small> 
    {{ __('Asisgn to') }}: 
    <select name="user_id" class="form-control" data-parsley-exclude="true">
    	<option value="-1" @if ((int)old('user_id') == -1 || (!old('user_id') && $mailbox->ticket_assignee == App\Mailbox::TICKET_ASSIGNEE_ANYONE))selected="selected"@endif>{{ __('Anyone') }}</option>
    	<option value="{{ Auth::user()->id }}" @if (old('user_id') == Auth::user()->id || (!old('user_id') && (!$conversation->user_id && $mailbox->ticket_assignee == App\Mailbox::TICKET_ASSIGNEE_REPLYING_UNASSIGNED) || $mailbox->ticket_assignee == App\Mailbox::TICKET_ASSIGNEE_REPLYING))selected="selected"@endif>{{ __('Me') }}</option>
        @foreach ($mailbox->usersHavingAccess() as $user)
            @if ($user->id != Auth::user()->id)
            	<option value="{{ $user->id }}" @if (old('user_id') == $user->id)selected="selected"@endif>{{ $user->getFullName() }}</option>
            @endif
        @endforeach
    </select> 

    <div class="btn-group btn-group-send">
    	<button class="hidden"></button>
        <button type="button" class="btn btn-primary btn-send-text" data-loading-text="{{ __('Sending…') }}">{{ __('Send') }}</button>
        <button type="button" class="btn btn-primary btn-send-menu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><small class="glyphicon glyphicon-chevron-down"></small></button>
        <ul class="dropdown-menu dropdown-menu-right dropdown-after-send">
            <li @if (0) class="active" @endif><a href="javascript:void(0)" class="after-send-stay">{{ __('Send and stay on page') }}</a></li>
            <li @if (0) class="active" @endif><a href="#" class="after-send-folder">{{ __('Send and back to folder') }}</a></li>
            <li @if (0) class="active" @endif><a href="#" class="after-send-next">{{ __('Send and next active') }}</a></li>
            @if (empty($is_reply))
            	<li class="divider"></li>
            	<li @if (0) class="active" @endif><a href="#" class="after-send-change">{{ __('Change default redirect') }}</a></li>
            @endif
        </ul>
    </div>
</div>
