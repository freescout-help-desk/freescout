@if ($thread->created_by_user->id == Auth::user()->id)
    {{ __("you") }}
@else
    {{ $thread->created_by_user->getFullName() }}
@endif