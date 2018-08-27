@if ($thread->created_by_user->id == Auth::user()->id)
    {{ __("you") }}
@else
	@if (empty($as_link))
    	{{ $thread->created_by_user->getFullName() }}
    @else
    	<a href="{{ $thread->created_by_user->url() }}">{{ $thread->created_by_user->getFullName() }}</a>
    @endif
@endif