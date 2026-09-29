<?php

namespace Modules\Workflows\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Workflows\Entities\ConversationWorkflow;
use Modules\Workflows\Entities\Workflow;
use Modules\Workflows\Http\Requests\WorkflowRequest;
use Modules\Workflows\Services\WorkflowAuthorizer;

class WorkflowsController extends Controller
{
    /**
     * allows() returns before Option::get when the user is an admin.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!WorkflowAuthorizer::allows($request->user())) {
                abort(403);
            }

            return $next($request);
        });
    }

    /**
     * @param mixed $id
     */
    public function index($id)
    {
        abort(404);
    }

    /**
     * @param mixed $id
     */
    public function create($id)
    {
        abort(404);
    }

    /**
     * @param mixed   $id
     * @param Request $request
     */
    public function store($id, Request $request)
    {
        $clean = WorkflowRequest::sanitize($request->all());
        if ($clean['errors']) {
            return redirect()->back();
        }

        $model = new Workflow();
        $model->mailbox_id = $id;
        $model->sort_order = 0;
        $this->saveWorkflow($model, $clean);

        return $this->redirectToList($id);
    }

    /**
     * @param mixed $id
     * @param mixed $workflow
     */
    public function edit($id, $workflow)
    {
        abort(404);
    }

    /**
     * Save the edited type and rows. Does not run the workflow.
     *
     * @param mixed   $id
     * @param mixed   $workflow
     * @param Request $request
     */
    public function update($id, $workflow, Request $request)
    {
        $model = Workflow::where('id', $workflow)->where('mailbox_id', $id)->firstOrFail();
        $clean = WorkflowRequest::sanitize($request->all());
        if ($clean['errors']) {
            return redirect()->back();
        }

        $this->saveWorkflow($model, $clean);

        return $this->redirectToList($id);
    }

    /**
     * @param mixed $id
     * @param mixed $workflow
     */
    public function delete($id, $workflow)
    {
        $model = Workflow::where('id', $workflow)->where('mailbox_id', $id)->firstOrFail();
        $model->conditions()->delete();
        $model->actions()->delete();
        ConversationWorkflow::where('workflow_id', $model->id)->delete();
        $model->delete();

        return $this->redirectToList($id);
    }

    /**
     * Swap sort_order with the neighbor when direction is up or down.
     *
     * @param mixed   $id
     * @param Request $request
     */
    public function sort($id, Request $request)
    {
        $direction = $request->input('direction');
        $workflowId = $request->input('workflow');

        if (($direction === 'up' || $direction === 'down') && $workflowId !== null && $workflowId !== '') {
            $this->swapSortOrder($id, $workflowId, $direction);
        }

        return $this->redirectToList($id);
    }

    /**
     * Manual runs are wired later. This only redirects.
     *
     * @param mixed $id
     * @param mixed $workflow
     */
    public function run($id, $workflow)
    {
        return redirect()->back();
    }

    /**
     * @param Workflow $model
     * @param array    $clean
     * @return void
     */
    private function saveWorkflow(Workflow $model, array $clean)
    {
        $workflow = $clean['workflow'];
        $model->name = $workflow['name'];
        $model->type = $workflow['type'];
        $model->active = $workflow['active'];
        $model->apply_to_previous = $workflow['apply_to_previous'];
        $model->max_executions = $workflow['max_executions'];
        // match is reserved in SQL; setAttribute quotes the column.
        $model->setAttribute('match', $workflow['match']);
        $model->save();

        $model->conditions()->delete();
        foreach (array_values($clean['conditions']) as $index => $row) {
            $model->conditions()->create([
                'type' => $row['type'],
                'operator' => $row['operator'],
                'value' => $row['value'],
                'sort_order' => $index,
            ]);
        }

        $model->actions()->delete();
        foreach (array_values($clean['actions']) as $index => $row) {
            $model->actions()->create([
                'type' => $row['type'],
                'value' => $row['value'],
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param mixed  $mailboxId
     * @param mixed  $workflowId
     * @param string $direction
     * @return void
     */
    private function swapSortOrder($mailboxId, $workflowId, $direction)
    {
        $ordered = Workflow::where('mailbox_id', $mailboxId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = null;
        foreach ($ordered as $position => $row) {
            if ((string) $row->id === (string) $workflowId) {
                $index = $position;
                break;
            }
        }

        if ($index === null) {
            return;
        }

        $neighborIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if (!isset($ordered[$neighborIndex])) {
            return;
        }

        $current = $ordered[$index];
        $neighbor = $ordered[$neighborIndex];
        $currentOrder = (int) $current->sort_order;
        $neighborOrder = (int) $neighbor->sort_order;

        if ($currentOrder === $neighborOrder) {
            $neighbor->sort_order = $direction === 'up' ? $currentOrder + 1 : $currentOrder - 1;
        } else {
            $current->sort_order = $neighborOrder;
            $neighbor->sort_order = $currentOrder;
        }

        $current->save();
        $neighbor->save();
    }

    /**
     * @param mixed $id
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectToList($id)
    {
        return redirect()->route('mailboxes.workflows', ['id' => $id]);
    }
}
