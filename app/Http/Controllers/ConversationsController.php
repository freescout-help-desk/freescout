<?php

namespace App\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;

class ConversationsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * View conversation.
     */
    public function view($id)
    {
        $conversation = Conversation::findOrFail($id);

        $this->authorize('view', $conversation);

        return view('conversations/view', [
            'conversation' => $conversation,
            'mailbox'      => $conversation->mailbox,
            'customer'     => $conversation->customer,
            'threads'      => $conversation->threads,
            'folder'       => $conversation->folder,
            'folders'      => $conversation->mailbox->getAssesibleFolders(),
        ]);
    }

    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '',
        ];

        switch ($request->action) {
            case 'change_status':
                $conversation = Conversation::find($request->conversation_id);
                $new_status = (int) $request->status;
                if (!$conversation) {
                    $response['msg'] = 'Conversation not found';
                }
                if (!$response['msg'] && $conversation->status == $new_status) {
                    $response['msg'] = 'Status already set';
                }
                if (!$response['msg'] && !auth()->user()->can('update', $conversation)) {
                    $response['msg'] = 'Not enough permissions';
                }
                if (!$response['msg'] && !in_array((int) $request->status, array_keys(Conversation::$statuses))) {
                    $response['msg'] = 'Incorrect status';
                }
                if (!$response['msg']) {
                    // Next conversation has to be determined before updating status for current one
                    $next_conversation = $conversation->getNearby();

                    $conversation->status = $new_status;
                    $conversation->user_updated_at = date('Y-m-d H:i:s');
                    $conversation->save();
                    $response['status'] = 'success';

                    // Flash
                    $flash_message = __('Status updated');
                    if ($new_status != Conversation::STATUS_ACTIVE) {
                        $flash_message .= ' <a href="'.route('conversations.view', ['id' => $conversation->id]).'">'.__('View').'</a>';

                        if ($next_conversation) {
                            $response['redirect_url'] = route('conversations.view', ['id' => $next_conversation->id]);
                        } else {
                            // Show conversations list
                            $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $conversation->folder_id]);
                        }
                    }
                    \Session::flash('flash_success_floating', $flash_message);

                    $response['msg'] = __('Status updated');
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
}
