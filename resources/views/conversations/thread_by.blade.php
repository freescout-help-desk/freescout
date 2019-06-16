@if (empty($as_link))
	{{ $thread->getActionPerson() }}
@elseif ($thread->created_by_user_cached)
	<a href="{{ $thread->created_by_user_cached->url() }}">{{ $thread->getActionPerson() }}</a>
@endif