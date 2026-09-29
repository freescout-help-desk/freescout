<?php

namespace Modules\Workflows\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mailbox;
use App\User;
use Illuminate\Http\Request;
use Modules\Workflows\Entities\ConversationWorkflow;
use Modules\Workflows\Entities\Workflow;
use Modules\Workflows\Http\Requests\WorkflowRequest;
use Modules\Workflows\Services\ConditionCatalog;
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
        $mailbox = Mailbox::findOrFail($id);
        $workflows = Workflow::where('mailbox_id', $mailbox->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('workflows::index', [
            'mailbox' => $mailbox,
            'workflows' => $workflows,
        ]);
    }

    /**
     * @param mixed $id
     */
    public function create($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        $workflow = new Workflow();
        $workflow->type = 'automatic';
        $workflow->active = true;
        $workflow->apply_to_previous = false;
        $workflow->max_executions = 1;
        $workflow->setAttribute('match', 'all');
        $workflow->setRelation('conditions', collect());
        $workflow->setRelation('actions', collect());

        return view('workflows::edit', $this->editorView($mailbox, $workflow));
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
        $mailbox = Mailbox::findOrFail($id);
        $model = Workflow::where('mailbox_id', $mailbox->id)
            ->where('id', $workflow)
            ->with(['conditions', 'actions'])
            ->firstOrFail();

        return view('workflows::edit', $this->editorView($mailbox, $model));
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
     * Rewrite this mailbox's sort_order as 0, 1, 2, ... after one move.
     *
     * @param mixed   $id
     * @param Request $request
     */
    public function sort($id, Request $request)
    {
        $ids = Workflow::where('mailbox_id', $id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $ordered = self::reordered($ids, $request->input('workflow'), $request->input('direction'));

        foreach ($ordered as $position => $workflowId) {
            Workflow::where('mailbox_id', $id)
                ->where('id', $workflowId)
                ->update(['sort_order' => $position]);
        }

        return $this->redirectToList($id);
    }

    /**
     * Swap one id with its neighbor. The result is ids, not sort numbers.
     *
     * @param array $idsInOrder
     * @param mixed $workflowId
     * @param mixed $direction
     * @return array
     */
    public static function reordered(array $idsInOrder, $workflowId, $direction): array
    {
        $ids = array_values($idsInOrder);
        if ($direction !== 'up' && $direction !== 'down') {
            return $ids;
        }

        $index = null;
        foreach ($ids as $position => $id) {
            if (self::sameWorkflowId($id, $workflowId)) {
                $index = $position;
                break;
            }
        }

        if ($index === null) {
            return $ids;
        }

        $neighbor = $direction === 'up' ? $index - 1 : $index + 1;
        if ($neighbor < 0 || $neighbor >= count($ids)) {
            return $ids;
        }

        $current = $ids[$index];
        $ids[$index] = $ids[$neighbor];
        $ids[$neighbor] = $current;

        return $ids;
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
     * Int 3 and string "3" are the same workflow. Other values stay strict.
     *
     * @param mixed $left
     * @param mixed $right
     * @return bool
     */
    private static function sameWorkflowId($left, $right): bool
    {
        if (self::integerId($left) && self::integerId($right)) {
            return (int) $left === (int) $right;
        }

        return $left === $right;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private static function integerId($value): bool
    {
        if (is_int($value)) {
            return true;
        }

        return is_string($value) && preg_match('/^-?\d+$/', $value) === 1;
    }

    /**
     * @param Mailbox  $mailbox
     * @param Workflow $workflow
     * @return array
     */
    private function editorView(Mailbox $mailbox, Workflow $workflow): array
    {
        return [
            'mailbox' => $mailbox,
            'workflow' => $workflow,
            'users' => User::where('status', '!=', User::STATUS_DELETED)->get(),
            'conditionGroups' => ConditionCatalog::configured((int) $mailbox->id),
            'actionTypes' => [
                'stop',
                'change_status',
                'assign',
                'add_note',
                'move_deleted',
                'delete_forever',
                'move_mailbox',
                'reply',
                'email_customer',
                'forward',
                'notification',
                'disable_auto_reply',
                'trigger_webhook',
            ],
        ];
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
