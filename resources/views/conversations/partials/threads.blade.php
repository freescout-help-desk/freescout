@foreach ($threads as $thread_index => $thread)
    @include('conversations/partials/thread')
@endforeach