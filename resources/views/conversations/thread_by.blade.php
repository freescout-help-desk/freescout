@if ($thread->created_by_user_cached)
	@if ($thread->created_by_user_id && $thread->created_by_user_cached->id == Auth::user()->id)
	    {{ __("you") }}
	@else
		@if (empty($as_link))
	    	{{ $thread->created_by_user_cached->getFullName() }}
	    @else
	    	<a href="{{ $thread->created_by_user_cached->url() }}">{{ $thread->created_by_user_cached->getFullName() }}</a>
	    @endif
	@endif
@endif