@php
    $priority_style = \App\Conversation::getPriorityButtonStyle($conversation->getPriorityColor());
@endphp
<div class="btn-group" id="conv-priority" data-toggle="tooltip" title="{{ __('Priority') }}: {{ $conversation->getPriorityName() }}">
    <button type="button" class="btn btn-default dropdown-toggle conv-info-icon conv-priority-btn @if ($priority_style) conv-priority-colored @endif" @if ($priority_style) style="{{ $priority_style }}" @endif data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('Priority') }}: {{ $conversation->getPriorityName() }}"><i class="glyphicon glyphicon-flag"></i> <span class="caret"></span></button>
    <ul class="dropdown-menu conv-priority-list">
        @foreach (\App\Conversation::getPriorityLevels() as $priority_key => $priority_level)
            <li @if ($conversation->getPriority() == $priority_key) class="active" @endif><a href="#" data-priority="{{ $priority_key }}"><span class="conv-priority-dot" @if ($priority_level['color']) style="background-color: #{{ $priority_level['color'] }}" @endif></span>{{ __($priority_level['name']) }}</a></li>
        @endforeach
    </ul>
</div>
