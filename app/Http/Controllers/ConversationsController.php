<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Folder;
use App\Mailbox;
use App\Thread;
use Illuminate\Http\Request;
use Validator;

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

        // Detect folder
        if ($conversation->user_id == auth()->user()->id) {
            $folder = $conversation->mailbox->folders()->where('type', Folder::TYPE_MINE)->first();
        } else {
            $folder = $conversation->folder;
        }

        return view('conversations/view', [
            'conversation' => $conversation,
            'mailbox'      => $conversation->mailbox,
            'customer'     => $conversation->customer,
            'threads'      => $conversation->threads()->orderBy('created_at', 'desc')->get(),
            'folder'       => $folder,
            'folders'      => $conversation->mailbox->getAssesibleFolders(),
        ]);
    }

    /**
     * New conversation.
     */
    public function create($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorize('view', $mailbox);

        $conversation = new Conversation();
        $conversation->body = '';

        $folder = $mailbox->folders()->where('type', Folder::TYPE_DRAFTS)->first();

        return view('conversations/create', [
            'conversation' => $conversation,
            'mailbox'      => $mailbox,
            'folder'       => $folder,
            'folders'      => $mailbox->getAssesibleFolders(),
        ]);
    }

    /**
     * Conversation draft.
     */
    public function draft($id)
    {
        $conversation = Conversation::findOrFail($id);

        $this->authorize('view', $conversation);

        return view('conversations/create', [
            'conversation' => $conversation,
            'mailbox'      => $conversation->mailbox,
            'folder'       => $conversation->folder,
            'folders'      => $conversation->mailbox->getAssesibleFolders(),
        ]);
    }

    /**
     * Save new conversation.
     */
    /*public function createSave($mailbox_id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorize('view', $mailbox);

        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'subject'  => 'required|string|max:998',
            'body'  => 'required|string',
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('conversations.create', ['mailbox_id' => $mailbox_id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $to_array = Conversation::sanitizeEmails($request->to);

        // Check if there are any emails
        if (!$to_array) {
            return redirect()->route('conversations.create', ['mailbox_id' => $mailbox_id])
                        ->withErrors(['to' => __('Incorrect recipients')])
                        ->withInput();
        }

        $now = date('Y-m-d H:i:s');
        $customer = Customer::create($to_array[0]);

        $conversation = new Conversation();
        $conversation->type = Conversation::TYPE_EMAIL;
        $conversation->status = $request->status;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = $request->subject;
        $conversation->setCc($request->cc);
        $conversation->setBcc($request->bcc);
        $conversation->setPreview($request->body);
        // todo: attachments
        //$conversation->has_attachments = ;
        // Set folder id
        $conversation->mailbox_id = $mailbox_id;
        if ((int)$request->user_id != -1) {
            // Check if user has access to the current mailbox
            if ($mailbox->userHasAccess($request->user_id)) {
                $conversation->user_id = $request->user_id;
            }
        }

        $conversation->customer_id = $customer->id;
        $conversation->created_by_user_id = auth()->user()->id;
        $conversation->source_via = Conversation::PERSON_USER;
        $conversation->source_type = Conversation::SOURCE_TYPE_WEB;
        $conversation->user_updated_at = $now;
        $conversation->last_reply_at = $now;
        $conversation->last_reply_from = Conversation::PERSON_USER;
        $conversation->updateFolder();
        $conversation->save();

        // Create thread
        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->user_id = auth()->user()->id;
        $thread->type = Thread::TYPE_MESSAGE;
        $thread->status = $request->status;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->body = $request->body;
        $thread->setTo($request->to);
        $thread->setCc($request->cc);
        $thread->setBcc($request->bcc);
        $thread->source_via = Thread::PERSON_USER;
        $thread->source_type = Thread::SOURCE_TYPE_WEB;
        $thread->customer_id = $customer->id;
        $thread->created_by_user_id = auth()->user()->id;
        $thread->save();

        return redirect()->route('conversations.view', ['id' => $conversation->id]);
    }*/

    /**
     * Conversations ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '',
        ];

        $user = auth()->user();

        switch ($request->action) {

            // Change conversation user
            case 'conversation_change_user':
                $conversation = Conversation::find($request->conversation_id);

                $new_user_id = (int) $request->user_id;

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && $conversation->user_id == $new_user_id) {
                    $response['msg'] = __('Assignee already set');
                }
                if (!$response['msg'] && !$user->can('update', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }
                if (!$response['msg'] && (int) $new_user_id != -1 && !in_array($new_user_id, $conversation->mailbox->userIdsHavingAccess())) {
                    $response['msg'] = __('Incorrect user');
                }
                if (!$response['msg']) {
                    // Next conversation has to be determined before updating status for current one
                    $next_conversation = $conversation->getNearby();

                    $conversation->setUser($new_user_id);
                    $conversation->save();

                    // Create lineitem thread
                    $thread = new Thread();
                    $thread->conversation_id = $conversation->id;
                    $thread->user_id = $conversation->user_id;
                    $thread->type = Thread::TYPE_LINEITEM;
                    $thread->state = Thread::STATE_PUBLISHED;
                    $thread->status = Thread::STATUS_NOCHANGE;
                    $thread->action_type = Thread::ACTION_TYPE_USER_CHANGED;
                    $thread->source_via = Thread::PERSON_USER;
                    // todo: this need to be changed for API
                    $thread->source_type = Thread::SOURCE_TYPE_WEB;
                    $thread->customer_id = $conversation->customer_id;
                    $thread->created_by_user_id = $user->id;
                    $thread->save();

                    event(new ConversationUserChanged($conversation, $user));

                    $response['status'] = 'success';

                    // Flash
                    $flash_message = __('Assignee updated');
                    if ($new_user_id != $user->id) {
                        $flash_message .= ' <a href="'.route('conversations.view', ['id' => $conversation->id]).'">'.__('View').'</a>';

                        if ($next_conversation) {
                            $response['redirect_url'] = route('conversations.view', ['id' => $next_conversation->id]);
                        } else {
                            // Show conversations list
                            $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $conversation->folder_id]);
                        }
                    }
                    \Session::flash('flash_success_floating', $flash_message);

                    $response['msg'] = __('Assignee updated');
                }
                break;

            // Change conversation status
            case 'conversation_change_status':
                $conversation = Conversation::find($request->conversation_id);

                $new_status = (int) $request->status;

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && $conversation->status == $new_status) {
                    $response['msg'] = __('Status already set');
                }
                if (!$response['msg'] && !$user->can('update', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }
                if (!$response['msg'] && !in_array((int) $request->status, array_keys(Conversation::$statuses))) {
                    $response['msg'] = __('Incorrect status');
                }
                if (!$response['msg']) {
                    // Next conversation has to be determined before updating status for current one
                    $next_conversation = $conversation->getNearby();

                    $conversation->setStatus($new_status, $user);
                    $conversation->save();

                    // Create lineitem thread
                    $thread = new Thread();
                    $thread->conversation_id = $conversation->id;
                    $thread->user_id = $conversation->user_id;
                    $thread->type = Thread::TYPE_LINEITEM;
                    $thread->state = Thread::STATE_PUBLISHED;
                    $thread->status = $conversation->status;
                    $thread->action_type = Thread::ACTION_TYPE_STATUS_CHANGED;
                    $thread->source_via = Thread::PERSON_USER;
                    // todo: this need to be changed for API
                    $thread->source_type = Thread::SOURCE_TYPE_WEB;
                    $thread->customer_id = $conversation->customer_id;
                    $thread->created_by_user_id = $user->id;
                    $thread->save();

                    event(new ConversationStatusChanged($conversation));

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

             // Send reply or new conversation
            case 'send_reply':

                $mailbox = Mailbox::findOrFail($request->mailbox_id);

                if (!$response['msg'] && !$user->can('view', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                $conversation = null;
                if (!$response['msg'] && !empty($request->conversation_id)) {
                    $conversation = Conversation::find($request->conversation_id);
                    if ($conversation && !$user->can('view', $conversation)) {
                        $response['msg'] = __('Not enough permissions');
                    }
                }

                $new = false;
                if (empty($request->conversation_id)) {
                    $new = true;
                }

                if (!$response['msg']) {
                    $validator = Validator::make($request->all(), [
                        'to'       => 'required|string',
                        'subject'  => 'required|string|max:998',
                        'body'     => 'required|string',
                        'cc'       => 'nullable|string',
                        'bcc'      => 'nullable|string',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors() as $errors) {
                            foreach ($errors as $field => $message) {
                                $response['msg'] .= $message.' ';
                            }
                        }
                        // return redirect()->route('conversations.create', ['mailbox_id' => $mailbox_id])
                        //             ->withErrors($validator)
                        //             ->withInput();
                    }
                }

                if (!$response['msg'] && $new) {
                    $to_array = Conversation::sanitizeEmails($request->to);

                    // Check if there are any emails
                    if (!$to_array) {
                        $response['msg'] .= __('Incorrect recipients');
                        // return redirect()->route('conversations.create', ['mailbox_id' => $mailbox_id])
                        //             ->withErrors(['to' => __('Incorrect recipients')])
                        //             ->withInput();
                    }
                }

                if (!$response['msg']) {
                    $now = date('Y-m-d H:i:s');
                    $status_changed = false;
                    $user_changed = false;
                    if ($new) {
                        // New conversation
                        $customer = Customer::create($to_array[0]);

                        $conversation = new Conversation();
                        $conversation->type = Conversation::TYPE_EMAIL;
                        $conversation->status = $request->status;
                        $conversation->state = Conversation::STATE_PUBLISHED;
                        $conversation->subject = $request->subject;
                        $conversation->setCc($request->cc);
                        $conversation->setBcc($request->bcc);
                        $conversation->setPreview($request->body);
                        // todo: attachments
                        //$conversation->has_attachments = ;
                        // Set folder id
                        $conversation->mailbox_id = $request->mailbox_id;
                        $conversation->customer_id = $customer->id;
                        $conversation->created_by_user_id = auth()->user()->id;
                        $conversation->source_via = Conversation::PERSON_USER;
                        $conversation->source_type = Conversation::SOURCE_TYPE_WEB;
                    } else {
                        $customer = $conversation->customer;

                        if ((int) $request->status != (int) $conversation->status) {
                            $status_changed = true;
                        }
                    }
                    $conversation->status = $request->status;
                    if ((int) $request->user_id != -1) {
                        // Check if user has access to the current mailbox
                        if ((int) $conversation->user_id != (int) $request->user_id && $mailbox->userHasAccess($request->user_id)) {
                            $conversation->user_id = $request->user_id;
                            $user_changed = true;
                        }
                    } else {
                        $conversation->user_id = null;
                    }
                    $conversation->last_reply_at = $now;
                    $conversation->last_reply_from = Conversation::PERSON_USER;
                    $conversation->user_updated_at = $now;
                    $conversation->updateFolder();
                    $conversation->save();

                    // Fire events
                    if (!$new) {
                        if ($status_changed) {
                            event(new ConversationStatusChanged($conversation));
                        }
                        if ($user_changed) {
                            event(new ConversationUserChanged($conversation, $user));
                        }
                    }

                    // Create thread
                    $thread = new Thread();
                    $thread->conversation_id = $conversation->id;
                    $thread->user_id = auth()->user()->id;
                    $thread->type = Thread::TYPE_MESSAGE;
                    $thread->status = $request->status;
                    $thread->state = Thread::STATE_PUBLISHED;
                    $thread->body = $request->body;
                    $thread->setTo($request->to);
                    $thread->setCc($request->cc);
                    $thread->setBcc($request->bcc);
                    $thread->source_via = Thread::PERSON_USER;
                    $thread->source_type = Thread::SOURCE_TYPE_WEB;
                    $thread->customer_id = $customer->id;
                    $thread->created_by_user_id = auth()->user()->id;
                    $thread->save();

                    if ($new) {
                        event(new UserCreatedConversation($conversation, $thread));
                    } else {
                        event(new UserReplied($conversation));
                    }

                    $response['status'] = 'success';
                    $response['redirect_url'] = route('conversations.view', ['id' => $conversation->id]);

                    $flash_message = __(
                        ':%tag_start%Email Sent:%tag_end% :%view_start%View:%a_end% or :%undo_start%Undo:%a_end%',
                        ['%tag_start%' => '<strong>', '%tag_end%' => '</strong>', '%view_start%' => '&nbsp;<a href="'.route('conversations.view', ['id' => $conversation->id]).'">', '%a_end%' => '</a>&nbsp;', '%undo_start%' => '&nbsp;<a href="'.route('conversations.draft', ['id' => $conversation->id]).'" class="text-danger">']
                    );

                    \Session::flash('flash_success_floating', $flash_message);
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
