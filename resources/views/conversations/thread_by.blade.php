@if (empty($as_link))
	{{ $thread->getActionPerson() }}
@else
	<a href="{{ $thread->created_by_user_cached->url() }}">{{ $thread->getActionPerson() }}</a>
@endif