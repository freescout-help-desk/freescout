<?php

namespace Modules\Workflows\Http\Controllers;

use App\Conversation;
use App\Mailbox;
use Modules\Workflows\Entities\Workflow;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Validator;

class WorkflowsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index($mailbox_id)
    {
        if (!\Workflow::canEditWorkflows()) {
            \Helper::denyAccess();
        }

        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        Workflow::checkAll();

        $automatic = Workflow::where('mailbox_id', $mailbox_id)
            ->where('type', Workflow::TYPE_AUTOMATIC)
            ->orderby('sort_order')
            ->get();

        $manual = Workflow::where('mailbox_id', $mailbox_id)
            ->where('type', Workflow::TYPE_MANUAL)
            ->orderby('sort_order')
            ->get();

        return view('workflows::index', [
            'mailbox'   => $mailbox,
            'automatic' => $automatic,
            'manual'    => $manual,
        ]);
    }

    public function create($mailbox_id)
    {
        if (!\Workflow::canEditWorkflows()) {
            \Helper::denyAccess();
        }
        
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $workflow = new Workflow();

        return view('workflows::update', [
            'mode'      => 'create',
            'mailbox'   => $mailbox,
            'workflow'   => $workflow,
        ]);
    }

    public function createSave($mailbox_id, Request $request)
    {
        if (!\Workflow::canEditWorkflows()) {
            \Helper::denyAccess();
        }

        $rules = [
            //'name'  => 'required|string|max:75|unique:workflows',
            'name' => [
                'required',
                'string',
                'max:75',
                Rule::unique('workflows')->where(function ($query) use ($mailbox_id) {
                    return $query->where('mailbox_id', $mailbox_id);
                })
            ],
            'type'  => 'required|integer',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('mailboxes.workflows.create', ['mailbox_id' => $mailbox_id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $workflow = new Workflow();
        $workflow->mailbox_id = $mailbox_id;
        $workflow->fill($request->all());
        $workflow->setSortOrderLast();

        $workflow->checkComplete();
        $workflow->save();

        Workflow::maybeProcessInBackground($workflow);

        \Session::flash('flash_success_floating', __('Workflow created'));

        return redirect()->route('mailboxes.workflows', ['mailbox_id' => $mailbox_id]);
    }

    public function update($mailbox_id, $id)
    {
        if (!\Workflow::canEditWorkflows()) {
            \Helper::denyAccess();
        }

        $mailbox = Mailbox::findOrFail($mailbox_id);

        $workflow = Workflow::findOrFail($id);

        return view('workflows::update', [
            'mode'      => 'update',
            'mailbox'   => $mailbox,
            'workflow'  => $workflow,
        ]);
    }

    public function updateSave($mailbox_id, $id, Request $request)
    {
        if (!\Workflow::canEditWorkflows()) {
            \Helper::denyAccess();
        }

        $mailbox = Mailbox::findOrFail($mailbox_id);

        $workflow = Workflow::findOrFail($id);

        $rules = [
            //'name'  => 'required|string|max:75',
            'name' => [
                'required',
                'string',
                'max:75',
                Rule::unique('workflows')->where(function ($query) use ($mailbox_id, $id) {
                    return $query->where('mailbox_id', $mailbox_id)->where('id', '!=', (int)$id);
                })
            ],
            'type'  => 'required|integer',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('mailboxes.workflows.update', ['mailbox_id' => $mailbox_id, 'id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $workflow->fill($request->all());
        $workflow->active = $request->active ?? false;
        $workflow->apply_to_prev = $request->apply_to_prev ?? false;
        $workflow->conditions = Workflow::formatConditions($request->conditions, $mailbox_id);

        $workflow->checkComplete();
        $workflow->save();

        Workflow::maybeProcessInBackground($workflow);

        \Session::flash('flash_success_floating', __('Workflow updated'));

        return redirect()->route('mailboxes.workflows', ['mailbox_id' => $mailbox_id]);
    }

    /**
     * Ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        switch ($request->action) {

            // Delete
            case 'delete':
               
                if (!Workflow::canEditWorkflows($user)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $workflow = Workflow::find($request->workflow_id);

                    if (!$workflow) {
                        $response['msg'] = __('Workflow not found');
                    }
                }

                if (!$response['msg']) {
                    $mailbox_id = $workflow->mailbox_id;
                    $workflow->delete();

                    $response['status'] = 'success';
                    $response['redirect_url'] = route('mailboxes.workflows', ['mailbox_id' => $mailbox_id]);
                    \Session::flash('flash_success_floating', __('Workflow deleted'));
                }
                break;

            case 'update_sort_order':
                
                if (!\Workflow::canEditWorkflows()) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {

                    $workflows = Workflow::whereIn('id', $request->workflows)->select('id', 'mailbox_id', 'sort_order')->get();

                    if (count($workflows)) {
                        foreach ($request->workflows as $i => $request_workflow_id) {
                            foreach ($workflows as $workflow) {
                                if ($workflow->id != $request_workflow_id) {
                                    continue;
                                }
                                $workflow->sort_order = $i+1;
                                $workflow->save();
                            }
                        }
                        $response['status'] = 'success';
                    }
                }
                break;

            case 'run':
                
                $workflow = Workflow::findOrFail($request->workflow_id);
                if (!auth()->user()->can('view', $workflow->mailbox)) {
                    \Helper::denyAccess();
                }

                if (!empty($request->conversation_id) && is_array($request->conversation_id)) {
                    // Remember mailbox.
                    $prev_mailbox_id = null;
                    $prev_folder_id = Conversation::getFolderParam();
                    foreach ($request->conversation_id as $conversation_id) {
                        $conversation = Conversation::findOrFail($conversation_id);
                        if ($conversation->mailbox_id != $workflow->mailbox_id) {
                            \Helper::denyAccess();
                        }

                        $prev_mailbox_id = $conversation->mailbox_id;

                        $workflow->runManually($conversation);
                        
                        // If conversation moved to another mailbox, check permissions.
                        if ($prev_mailbox_id != $conversation->mailbox_id) {
                            $conversation->load('mailbox');
                            if (!$conversation->mailbox->userHasAccess($user->id)) {
                                if (!empty($prev_folder_id)) {
                                    $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $prev_mailbox_id, 'folder_id' => $prev_folder_id]);
                                } else {
                                    $response['redirect_url'] = route('mailboxes.view', ['id' => $prev_mailbox_id]);
                                }
                            }
                        }
                    }

                    \Session::flash('flash_success_floating', __('Workflow :workflow has run', ['workflow' => '<strong>'.$workflow->name.'</strong>']));
                    $response['status'] = 'success';
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        $user = auth()->user();

        switch ($request->action) {
            case 'forward':
            case 'email_customer':
            case 'note':
                if (!\Workflow::canEditWorkflows()) {
                    \Helper::denyAccess();
                }
                $value = '';
                $mailbox = Mailbox::findOrFail($request->mailbox_id);
                if (!auth()->user()->can('view', $mailbox)) {
                    \Helper::denyAccess();
                }
                return view('workflows::partials/'.$request->action, [
                    'value' => $value,
                    'mailbox' => $mailbox,
                    // 'and_i' => $request->param1 ?? '',
                    // 'row_i' => $request->param2 ?? '',
                ]);
                break;

            case 'run':
                $mailbox = Mailbox::findOrFail($request->mailbox_id);
                if (!auth()->user()->can('view', $mailbox)) {
                    \Helper::denyAccess();
                }
                $workflows = Workflow::where('mailbox_id', $request->mailbox_id)
                    ->where('active', true)
                    ->where('type', Workflow::TYPE_MANUAL)
                    ->orderBy('sort_order')
                    ->get();
                    
                return view('workflows::partials/run', [
                    'workflows' => $workflows,
                ]);
                break;
        }

        abort(404);
    }
}
