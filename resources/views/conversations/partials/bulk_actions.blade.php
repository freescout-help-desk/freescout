<div id="conversations-bulk-actions" class="text-center">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-default conv-checkbox-clear" title="{{ __("Clear") }}">
            <span class="glyphicon glyphicon-arrow-left"></span>
        </button>
        @if (!empty($mailbox))
            <div class="btn-group">
                <button type="button" class="btn btn-default" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __("Assignee") }}">
                    <span class="glyphicon glyphicon-user"></span><span class="caret"></span>
                </button>
                <ul class="dropdown-menu conv-user">
                    <li><a href="#" data-user_id="{{ Auth::user()->id }}">{{ __("Me") }}</a></li>
                    @foreach ($mailbox->usersHavingAccess(true) as $user)
                        @if ($user->id != Auth::user()->id)
                            <li><a href="#" data-user_id="{{ $user->id }}">{{ $user->getFullName() }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="btn-group">
            <button type="button" class="btn btn-default" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __("Status") }}">
                <span class="glyphicon glyphicon-flag"></span><span class="caret"></span>
            </button>
            <ul class="dropdown-menu conv-status">
                @foreach (App\Conversation::$statuses as $status => $dummy)
                    <li><a href="#" data-status="{{ $status }}">{{ App\Conversation::statusCodeToName($status) }}</a></li>
                @endforeach
            </ul>
        </div>
        {{--<div class="btn-group">
            <button type="button" class="btn btn-default" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __("Tag") }}">
                <span class="glyphicon glyphicon-tag"></span>
            </button>
            <ul class="dropdown-menu conv-tag">
            </ul>
        </div>--}}
        <button type="button" class="btn btn-default conv-delete" title="{{ __("Delete") }}">
            <span class="glyphicon glyphicon-trash"></span>
        </button>
    </div>
</div>

<div id="conversations-bulk-actions-delete-modal" class="hide">
    <div class="text-center">
    <div class="text-larger margin-top-10">{{ __("Delete the conversations?") }}</div>
        <div class="form-group margin-top">
            <button class="btn btn-primary delete-conversation-ok">{{ __("Delete") }}</button>
            <button class="btn btn-link" data-dismiss="modal">{{ __("Cancel") }}</button>
        </div>
    </div>
</div>