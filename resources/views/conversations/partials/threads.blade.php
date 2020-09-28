@foreach ($threads as $thread_index => $thread)
	@if (\Helper::isPrint() && app('request')->input('print_thread_id') && app('request')->input('print_thread_id') != $thread->id)
		@continue
	@endif
    @include('conversations/partials/thread')
@endforeach