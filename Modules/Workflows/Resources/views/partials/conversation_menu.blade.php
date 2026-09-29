@foreach ($workflows as $workflow)
    @php
        $needsConfirm = \Modules\Workflows\Http\Controllers\WorkflowsController::manualPayload($workflow)['confirm'];
    @endphp
    <li>
        <form method="POST" action="{{ route('conversations.workflow.run', ['id' => $conversation->id, 'workflow' => $workflow->id]) }}" data-confirm="{{ $needsConfirm ? '1' : '0' }}">
            {{ csrf_field() }}
            <button type="submit"><i class="glyphicon glyphicon-random"></i> {{ __('workflows::messages.run') }} {{ $workflow->name }}</button>
        </form>
    </li>
@endforeach
