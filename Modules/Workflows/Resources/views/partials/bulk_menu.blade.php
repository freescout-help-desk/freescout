@if (count($workflows))
    @foreach ($workflows as $workflow)
        @php
            $needsConfirm = \Modules\Workflows\Http\Controllers\WorkflowsController::manualPayload($workflow)['confirm'];
        @endphp
        <form method="POST" class="workflow-bulk-run" style="display:inline" action="{{ route('conversations.workflow.bulk', ['workflow' => $workflow->id]) }}" data-confirm="{{ $needsConfirm ? '1' : '0' }}">
            {{ csrf_field() }}
            <button type="submit" class="btn btn-default"><i class="glyphicon glyphicon-random"></i> {{ __('workflows::messages.run') }} {{ $workflow->name }}</button>
        </form>
    @endforeach
    <script>
        var workflowBulkForms = document.querySelectorAll('form.workflow-bulk-run');
        for (var f = 0; f < workflowBulkForms.length; f++) {
            workflowBulkForms[f].addEventListener('submit', function () {
                var stale = this.querySelectorAll('input[name="conversation_id[]"]');
                for (var j = 0; j < stale.length; j++) {
                    stale[j].parentNode.removeChild(stale[j]);
                }
                var boxes = document.querySelectorAll('input.conv-checkbox:checked');
                for (var i = 0; i < boxes.length; i++) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'conversation_id[]';
                    input.value = boxes[i].value;
                    this.appendChild(input);
                }
            });
        }
    </script>
@endif
