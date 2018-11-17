<?php

namespace App\Http\Controllers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\UserAddedNote;
use App\Events\UserCreatedConversation;
use App\Events\UserCreatedConversationDraft;
use App\Events\UserCreatedThreadDraft;
use App\Events\UserReplied;
use App\Folder;
use App\Mailbox;
use App\MailboxUser;
use App\SendLog;
use App\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Validator;

class ConversationsController extends Controller
{
    const PREV_CONVERSATIONS_LIMIT = 5;

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
    public function view(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);
        $this->authorize('view', $conversation);

        $mailbox = $conversation->mailbox;
        $customer = $conversation->customer;
        $user = auth()->user();

        // Mark notifications as read
        if (!empty($request->mark_as_read)) {
            $mark_read_result = $user->unreadNotifications()->where('id', $request->mark_as_read)->update(['read_at' => now()]);
            $user->clearWebsiteNotificationsCache();
        } else {
            $mark_read_result = $user->unreadNotifications()->where('data', 'like', '%"conversation_id":'.$conversation->id.'%')->update(['read_at' => now()]);
        }
        if ($mark_read_result) {
            $user->clearWebsiteNotificationsCache();
        }

        // Detect folder and redirect if needed
        $folder = null;
        if (Conversation::getFolderParam()) {
            $folder = $conversation->mailbox->folders()->where('folders.id', Conversation::getFolderParam())->first();

            if ($folder) {
                // Check if conversation can be located in the passed folder_id
                if (!$conversation->isInFolderAllowed($folder)) {

                    // Without reflash green flash will not be displayed on assignee change
                    \Session::reflash();
                    //$request->session()->reflash();
                    return redirect()->away($conversation->url($conversation->folder_id));
                }
                // If conversation assigned to user, select Mine folder instead of Assigned
                if ($folder->type == Folder::TYPE_ASSIGNED && $conversation->user_id == $user->id) {
                    $folder = $conversation->mailbox->folders()
                        ->where('type', Folder::TYPE_MINE)
                        ->where('user_id', $user->id)
                        ->first();

                    \Session::reflash();

                    return redirect()->away($conversation->url($folder->id));
                }
            }
        }

        // Add folder if empty
        if (!$folder) {
            if ($conversation->user_id == $user->id) {
                $folder = $conversation->mailbox->folders()
                    ->where('type', Folder::TYPE_MINE)
                    ->where('user_id', $user->id)
                    ->first();
            } else {
                $folder = $conversation->folder;
            }

            \Session::reflash();

            return redirect()->away($conversation->url($folder->id));
        }

        $after_send = $conversation->mailbox->getUserSettings($user->id)->after_send;

        // Detect customers and emails to which user can reply
        $to_customers = [];
        // Add all customer emails
        $customer_emails = [];
        if ($customer) {
            $customer_emails = $customer->emails;
        }
        $distinct_emails = [];
        if (count($customer_emails) > 1) {
            foreach ($customer_emails as $customer_email) {
                $to_customers[] = [
                    'customer' => $customer,
                    'email'    => $customer_email->email,
                ];
                $distinct_emails[] = $customer_email->email;
            }
        }
        // Add emails of customers from whom there were replies in the conversation
        $prev_customers_emails = [];
        if ($conversation->customer_email) {
            $prev_customers_emails = Thread::select('from', 'customer_id')
                ->where('conversation_id', $id)
                ->where('type', Thread::TYPE_CUSTOMER)
                ->where('from', '<>', $conversation->customer_email)
                ->groupBy(['from', 'customer_id'])
                ->get();
        }

        foreach ($prev_customers_emails as $prev_customer) {
            if (!in_array($prev_customer->from, $distinct_emails) && $prev_customer->customer && $prev_customer->from) {
                $to_customers[] = [
                    'customer' => $prev_customer->customer,
                    'email'    => $prev_customer->from,
                ];
            }
        }

        // Previous conversations
        $prev_conversations = [];
        if ($customer) {
            $prev_conversations = $mailbox->conversations()
                                    ->where('customer_id', $customer->id)
                                    ->where('id', '<>', $conversation->id)
                                    //->limit(self::PREV_CONVERSATIONS_LIMIT)
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(self::PREV_CONVERSATIONS_LIMIT);
        }

        $template = 'conversations/view';
        if ($conversation->state == Conversation::STATE_DRAFT) {
            $template = 'conversations/create';
        }

        return view($template, [
            'conversation'       => $conversation,
            'mailbox'            => $conversation->mailbox,
            'customer'           => $customer,
            'threads'            => $conversation->threads()->orderBy('created_at', 'desc')->get(),
            'folder'             => $folder,
            'folders'            => $conversation->mailbox->getAssesibleFolders(),
            'after_send'         => $after_send,
            'to_customers'       => $to_customers,
            'prev_conversations' => $prev_conversations,
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
        $conversation->mailbox = $mailbox;

        $folder = $mailbox->folders()->where('type', Folder::TYPE_DRAFTS)->first();

        $after_send = $mailbox->getUserSettings(auth()->user()->id)->after_send;

        return view('conversations/create', [
            'conversation' => $conversation,
            'mailbox'      => $mailbox,
            'folder'       => $folder,
            'folders'      => $mailbox->getAssesibleFolders(),
            'after_send'   => $after_send,
        ]);
    }

    /**
     * Conversation draft.
     */
    // public function draft($id)
    // {
    //     $conversation = Conversation::findOrFail($id);

    //     $this->authorize('view', $conversation);

    //     return view('conversations/create', [
    //         'conversation' => $conversation,
    //         'mailbox'      => $conversation->mailbox,
    //         'folder'       => $conversation->folder,
    //         'folders'      => $conversation->mailbox->getAssesibleFolders(),
    //     ]);
    // }

    /**
     * Conversations ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
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
                if (!$response['msg'] && (int) $new_user_id != -1 && !$conversation->mailbox->userHasAccess($new_user_id)) {
                    $response['msg'] = __('Incorrect user');
                }
                if (!$response['msg']) {
                    // Determine redirect
                    // Must be done before updating current conversation's status or assignee.
                    $redirect_same_page = false;
                    if ($new_user_id == $user->id) {
                        // If user assigned conversation to himself, stay on the current page
                        $response['redirect_url'] = $conversation->url();
                        $redirect_same_page = true;
                    } else {
                        $response['redirect_url'] = $this->getRedirectUrl($request, $conversation, $user);
                    }

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
                    \Eventy::action('conversation.user_changed', $conversation, $user);

                    $response['status'] = 'success';

                    // Flash
                    $flash_message = __('Assignee updated');
                    if (!$redirect_same_page || $response['redirect_url'] != $conversation->url()) {
                        $flash_message .= ' &nbsp;<a href="'.$conversation->url().'">'.__('View').'</a>';
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
                    // Determine redirect
                    // Must be done before updating current conversation's status or assignee.
                    $redirect_same_page = false;
                    // if ($new_status == Conversation::STATUS_ACTIVE) {
                    //     // If status is ACTIVE, stay on the current page
                    //     $response['redirect_url'] = $conversation->url();
                    //     $redirect_same_page = true;
                    // } else {
                    $response['redirect_url'] = $this->getRedirectUrl($request, $conversation, $user);
                    //}

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
                    \Eventy::action('conversation.status_changed', $conversation);

                    $response['status'] = 'success';
                    // Flash
                    $flash_message = __('Status updated');
                    if (!$redirect_same_page || $response['redirect_url'] != $conversation->url()) {
                        $flash_message .= ' &nbsp;<a href="'.$conversation->url().'">'.__('View').'</a>';
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

                $is_note = false;
                if (!empty($request->is_note)) {
                    $is_note = true;
                }

                // If reply is being created from draft, there is already thread created
                $thread = null;
                $from_draft = false;
                if (!$is_note && !$response['msg'] && !empty($request->thread_id)) {
                    $thread = Thread::find($request->thread_id);
                    if ($thread && (!$conversation || $thread->conversation_id != $conversation->id)) {
                        $response['msg'] = __('Incorrect thread');
                    } else {
                        $from_draft = true;
                    }
                }

                // Validate form
                if (!$response['msg']) {
                    if ($new) {
                        $validator = Validator::make($request->all(), [
                            'to'       => 'required|string',
                            'subject'  => 'required|string|max:998',
                            'body'     => 'required|string',
                            'cc'       => 'nullable|string',
                            'bcc'      => 'nullable|string',
                        ]);
                    } else {
                        $validator = Validator::make($request->all(), [
                            'body'     => 'required|string',
                            'cc'       => 'nullable|string',
                            'bcc'      => 'nullable|string',
                        ]);
                    }

                    if ($validator->fails()) {
                        foreach ($validator->errors()->getMessages()as $errors) {
                            foreach ($errors as $field => $message) {
                                $response['msg'] .= $message.' ';
                            }
                        }
                    }
                }

                $to_array = Conversation::sanitizeEmails($request->to);
                // Check To
                if (!$response['msg'] && $new) {
                    if (!$to_array) {
                        $response['msg'] .= __('Incorrect recipients');
                    }
                }

                if (!$response['msg']) {

                    // Get attachments info
                    $attachments_info = $this->processReplyAttachments($request);

                    // Determine redirect.
                    // Must be done before updating current conversation's status or assignee.
                    if (!$new) {
                        $response['redirect_url'] = $this->getRedirectUrl($request, $conversation, $user);
                    }

                    // Conversation
                    $now = date('Y-m-d H:i:s');
                    $status_changed = false;
                    $user_changed = false;
                    if ($new) {
                        // New conversation
                        $conversation = new Conversation();
                        $conversation->type = Conversation::TYPE_EMAIL;
                        $conversation->subject = $request->subject;
                        $conversation->setPreview($request->body);
                        if ($attachments_info['has_attachments']) {
                            $conversation->has_attachments = true;
                        }
                        $conversation->mailbox_id = $request->mailbox_id;
                        $conversation->created_by_user_id = auth()->user()->id;
                        $conversation->source_via = Conversation::PERSON_USER;
                        $conversation->source_type = Conversation::SOURCE_TYPE_WEB;
                    } else {
                        // Reply or note
                        if ((int) $request->status != (int) $conversation->status) {
                            $status_changed = true;
                        }
                    }

                    // Customer can be empty in existing conversation if this is a draft
                    if (!$conversation->customer_id) {
                        $customer_email = $to_array[0];
                        $customer = Customer::create($customer_email);
                        $conversation->customer_id = $customer->id;
                        $conversation->customer_email = $customer_email;
                    } else {
                        $customer = $conversation->customer;
                    }

                    $conversation->status = $request->status;
                    // We need to set state, as it may have been a draft
                    $conversation->state = Conversation::STATE_PUBLISHED;
                    // Set assignee
                    if ((int) $request->user_id != -1) {
                        // Check if user has access to the current mailbox
                        if ((int) $conversation->user_id != (int) $request->user_id && $mailbox->userHasAccess($request->user_id)) {
                            $conversation->user_id = $request->user_id;
                            $user_changed = true;
                        }
                    } else {
                        $conversation->user_id = null;
                    }

                    // To is a single email string
                    $to = '';
                    if (!empty($request->to)) {
                        $to = $request->to;
                    } else {
                        $to = $conversation->customer_email;
                    }

                    if (!$is_note) {
                        // Save extra recipients to CC
                        $conversation->setCc(array_merge(Conversation::sanitizeEmails($request->cc), [$to]));
                        $conversation->setBcc($request->bcc);
                        $conversation->last_reply_at = $now;
                        $conversation->last_reply_from = Conversation::PERSON_USER;
                        $conversation->user_updated_at = $now;
                        $conversation->updateFolder();
                    }
                    if ($from_draft) {
                        // Increment number of replies in conversation
                        $conversation->threads_count++;
                    }
                    $conversation->save();

                    if ($new) {
                        $response['redirect_url'] = $this->getRedirectUrl($request, $conversation, $user);
                    }

                    // Fire events
                    if (!$new) {
                        if ($status_changed) {
                            event(new ConversationStatusChanged($conversation));
                            \Eventy::action('conversation.status_changed', $conversation);
                        }
                        if ($user_changed) {
                            event(new ConversationUserChanged($conversation, $user));
                            \Eventy::action('conversation.user_changed', $conversation, $user);
                        }
                    }

                    // Create thread
                    if (!$thread) {
                        $thread = new Thread();
                        $thread->conversation_id = $conversation->id;
                        if ($is_note) {
                            $thread->type = Thread::TYPE_NOTE;
                        } else {
                            $thread->type = Thread::TYPE_MESSAGE;
                        }
                        $thread->source_via = Thread::PERSON_USER;
                        $thread->source_type = Thread::SOURCE_TYPE_WEB;
                    } else {
                        $thread->type = Thread::TYPE_MESSAGE;
                        $thread->created_at = $now;
                    }
                    if ($new) {
                        $thread->first = true;
                    }
                    $thread->user_id = $conversation->user_id;
                    $thread->status = $request->status;
                    $thread->state = Thread::STATE_PUBLISHED;
                    $thread->customer_id = $customer->id;
                    $thread->created_by_user_id = auth()->user()->id;
                    $thread->edited_by_user_id = null;
                    $thread->edited_at = null;
                    $thread->body = $request->body;
                    $thread->setTo($to);
                    // We save CC and BCC as is and filter emails when sending replies
                    $thread->setCc($request->cc);
                    $thread->setBcc($request->bcc);
                    if ($attachments_info['has_attachments']) {
                        $thread->has_attachments = true;
                    }
                    if (!empty($request->saved_reply_id)) {
                        $thread->saved_reply_id = $request->saved_reply_id;
                    }
                    $thread->save();

                    // If thread has been created from draft, remove the draft
                    // if ($request->thread_id) {
                    //     $draft_thread = Thread::find($request->thread_id);
                    //     if ($draft_thread) {
                    //         $draft_thread->delete();
                    //     }
                    // }

                    if ($from_draft) {
                        // Remove conversation from drafts folder if needed
                        $conversation->maybeRemoveFromDrafts();
                    }

                    // Update folders counters
                    $conversation->mailbox->updateFoldersCounters();

                    $response['status'] = 'success';

                    // Set thread_id for uploaded attachments
                    if ($attachments_info['attachments']) {
                        Attachment::whereIn('id', $attachments_info['attachments'])->update(['thread_id' => $thread->id]);
                    }

                    if ($new) {
                        event(new UserCreatedConversation($conversation, $thread));
                        \Eventy::action('conversation.created_by_user_can_undo', $conversation, $thread);
                        // After Conversation::UNDO_TIMOUT period trigger final event.
                        \App\Jobs\TriggerAction::dispatch('conversation.created_by_user', [$conversation, $thread])
                            ->delay(now()->addSeconds(Conversation::UNDO_TIMOUT))
                            ->onQueue('default');
                    } elseif ($is_note) {
                        event(new UserAddedNote($conversation, $thread));
                        \Eventy::action('conversation.user_added_note', $conversation, $thread);
                    } else {
                        event(new UserReplied($conversation, $thread));
                        \Eventy::action('conversation.user_replied_can_undo', $conversation, $thread);
                        // After Conversation::UNDO_TIMOUT period trigger final event.
                        \App\Jobs\TriggerAction::dispatch('conversation.user_replied', [$conversation, $thread])
                            ->delay(now()->addSeconds(Conversation::UNDO_TIMOUT))
                            ->onQueue('default');
                    }

                    if (!empty($request->after_send) && $request->after_send == MailboxUser::AFTER_SEND_STAY) {
                        // Message without View link
                        if ($is_note) {
                            $flash_type = 'warning';
                            $flash_message = '<strong>'.__('Note added').'</strong>';
                        } else {
                            $flash_type = 'success';
                            $flash_message = __(
                                ':%tag_start%Email Sent:%tag_end% :%undo_start%Undo:%a_end%',
                                ['%tag_start%' => '<strong>', '%tag_end%' => '</strong>', '%view_start%' => '&nbsp;<a href="'.$conversation->url().'">', '%a_end%' => '</a>&nbsp;', '%undo_start%' => '&nbsp;<a href="'.route('conversations.undo', ['thread_id' => $thread->id]).'" class="text-danger">']
                            );
                        }
                    } else {
                        if ($is_note) {
                            $flash_type = 'warning';
                            $flash_message = __(
                                ':%tag_start%Note added:%tag_end% :%view_start%View:%a_end%',
                                ['%tag_start%' => '<strong>', '%tag_end%' => '</strong>', '%view_start%' => '&nbsp;<a href="'.$conversation->url().'">', '%a_end%' => '</a>&nbsp;']
                            );
                        } else {
                            $flash_type = 'success';
                            $flash_message = __(
                                ':%tag_start%Email Sent:%tag_end% :%view_start%View:%a_end% or :%undo_start%Undo:%a_end%',
                                ['%tag_start%' => '<strong>', '%tag_end%' => '</strong>', '%view_start%' => '&nbsp;<a href="'.$conversation->url().'">', '%a_end%' => '</a>&nbsp;', '%undo_start%' => '&nbsp;<a href="'.route('conversations.undo', ['thread_id' => $thread->id]).'" class="text-danger">']
                            );
                        }
                    }

                    \Session::flash('flash_'.$flash_type.'_floating', $flash_message);
                }
                break;

            // Save draft (automatically or by click)
            case 'save_draft':

                $mailbox = Mailbox::findOrFail($request->mailbox_id);

                if (!$response['msg'] && !$user->can('view', $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                $conversation = null;
                $new = true;
                if (!$response['msg'] && !empty($request->conversation_id)) {
                    $conversation = Conversation::find($request->conversation_id);
                    if ($conversation && !$user->can('view', $conversation)) {
                        $response['msg'] = __('Not enough permissions');
                    } else {
                        $new = false;
                    }
                }

                $thread = null;
                $new_thread = true;
                if (!$response['msg'] && !empty($request->thread_id)) {
                    $thread = Thread::find($request->thread_id);
                    if ($thread && (!$conversation || $thread->conversation_id != $conversation->id)) {
                        $response['msg'] = __('Incorrect thread');
                    } else {
                        $new_thread = false;
                    }
                }

                // Validation is not needed on draft create, fields can be empty

                if (!$response['msg']) {

                    // Get attachments info
                    $attachments_info = $this->processReplyAttachments($request);

                    // Conversation
                    $now = date('Y-m-d H:i:s');

                    if ($new) {
                        $conversation = new Conversation();
                    }

                    if ($new || !empty($request->is_create)) {
                        // New conversation
                        $customer_email = '';
                        $to_array = Conversation::sanitizeEmails($request->to);
                        if (count($to_array)) {
                            $customer_email = $to_array[0];
                        }
                        $customer = Customer::create($customer_email);

                        $conversation->type = Conversation::TYPE_EMAIL;
                        $conversation->state = Conversation::STATE_DRAFT;
                        $conversation->status = $request->status;
                        $conversation->subject = $request->subject;
                        $conversation->setPreview($request->body);
                        if ($attachments_info['has_attachments']) {
                            $conversation->has_attachments = true;
                        }
                        $conversation->mailbox_id = $request->mailbox_id;
                        // Customer may be empty in draft
                        if ($customer) {
                            $conversation->customer_id = $customer->id;
                        }
                        $conversation->customer_email = $customer_email;
                        $conversation->created_by_user_id = auth()->user()->id;
                        $conversation->source_via = Conversation::PERSON_USER;
                        $conversation->source_type = Conversation::SOURCE_TYPE_WEB;
                    } else {
                        // Reply
                        $customer = $conversation->customer;
                    }

                    // New draft conversation is not assigned to anybody
                    //$conversation->user_id = null;

                    // To is a single email string
                    $to = '';
                    if (!empty($request->to)) {
                        $to = $request->to;
                    } else {
                        $to = $conversation->customer_email;
                    }

                    // Save extra recipients to CC
                    $conversation->setCc(array_merge(Conversation::sanitizeEmails($request->cc), [$to]));
                    $conversation->setBcc($request->bcc);
                    // $conversation->last_reply_at = $now;
                    // $conversation->last_reply_from = Conversation::PERSON_USER;
                    // $conversation->user_updated_at = $now;
                    $conversation->updateFolder();

                    $conversation->save();

                    // Create thread
                    if (empty($thread)) {
                        $thread = new Thread();
                        $thread->conversation_id = $conversation->id;
                        $thread->user_id = auth()->user()->id;
                        $thread->type = Thread::TYPE_MESSAGE;
                        if ($new) {
                            $thread->first = true;
                        }
                        //$thread->status = $request->status;
                        $thread->state = Thread::STATE_DRAFT;

                        $thread->source_via = Thread::PERSON_USER;
                        $thread->source_type = Thread::SOURCE_TYPE_WEB;
                        if ($customer) {
                            $thread->customer_id = $customer->id;
                        }
                        $thread->created_by_user_id = auth()->user()->id;
                    }
                    if ($attachments_info['has_attachments']) {
                        $thread->has_attachments = true;
                    }
                    $thread->body = $request->body;
                    $thread->setTo($to);
                    // We save CC and BCC as is and filter emails when sending replies
                    $thread->setCc($request->cc);
                    $thread->setBcc($request->bcc);
                    // Set edited info
                    if ($thread->created_by_user_id != $user->id) {
                        $thread->edited_by_user_id = $user->id;
                        $thread->edited_at = $now;
                    }
                    $thread->save();

                    $conversation->addToFolder(Folder::TYPE_DRAFTS);

                    $response['conversation_id'] = $conversation->id;
                    $response['thread_id'] = $thread->id;
                    $response['number'] = $conversation->number;

                    $response['status'] = 'success';

                    // Set thread_id for uploaded attachments
                    if ($attachments_info['attachments']) {
                        Attachment::whereIn('id', $attachments_info['attachments'])->update(['thread_id' => $thread->id]);
                    }

                    // Update folder coutner
                    $conversation->mailbox->updateFoldersCounters(Folder::TYPE_DRAFTS);

                    if ($new) {
                        event(new UserCreatedConversationDraft($conversation, $thread));
                    } elseif ($new_thread) {
                        event(new UserCreatedThreadDraft($conversation, $thread));
                    }

                    $response['status'] = 'success';
                }

                // Reflash session data - otherwise on reply flash alert is not displayed
                // https://stackoverflow.com/questions/37019294/laravel-ajax-call-deletes-session-flash-data
                \Session::reflash();

                break;

            // Discard draft (from new conversation, from reply or conversation)
            case 'discard_draft':

                $thread = Thread::find($request->thread_id);

                if (!$thread) {
                    // Discarding nont saved yet draft
                    $response['status'] = 'success';
                    break;
                    //$response['msg'] = __('Thread not found');
                }
                if (!$response['msg'] && !$user->can('view', $thread->conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $conversation = $thread->conversation;

                    if ($conversation->state == Conversation::STATE_DRAFT) {
                        // New conversation draft being discarded
                        $folder_id = $conversation->getCurrentFolder();
                        $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $folder_id]);

                        $conversation->removeFromFolder(Folder::TYPE_DRAFTS);
                        $conversation->mailbox->updateFoldersCounters(Folder::TYPE_DRAFTS);
                        $conversation->deleteThreads();
                        $conversation->delete();

                        $flash_message = __('Deleted draft');
                        \Session::flash('flash_success_floating', $flash_message);
                    } else {
                        // Just remove the thread, no need to reload the page
                        $thread->deleteThread();
                        // Remove conversation from drafts folder if needed
                        $removed_from_folder = $conversation->maybeRemoveFromDrafts();
                        if ($removed_from_folder) {
                            $conversation->mailbox->updateFoldersCounters(Folder::TYPE_DRAFTS);
                        }
                    }

                    $response['status'] = 'success';
                }
                break;

            // Save draft (automatically or by click)
            case 'load_draft':
                $thread = Thread::find($request->thread_id);
                if (!$thread) {
                    $response['msg'] = __('Thread not found');
                } elseif ($thread->state != Thread::STATE_DRAFT) {
                    $response['msg'] = __('Thread is not in a draft state');
                } else {
                    if (!$user->can('view', $thread->conversation)) {
                        $response['msg'] = __('Not enough permissions');
                    }
                }

                if (!$response['msg']) {
                    $response['data'] = [
                        'thread_id' => $thread->id,
                        'to'        => $thread->getToFirst(),
                        'cc'        => $thread->getCcString(),
                        'bcc'       => $thread->getBccString(),
                        'body'      => $thread->body,
                    ];
                    $response['status'] = 'success';
                }
                break;

            // Save default redirect
            case 'save_after_send':
                $mailbox = Mailbox::find($request->mailbox_id);
                if (!$mailbox) {
                    $response['msg'] .= __('Mailbox not found');
                } elseif (!$mailbox->userHasAccess($user->id)) {
                    $response['msg'] .= __('Action not authorized');
                }
                if (!$response['msg']) {
                    $mailbox_user = $user->mailboxes()->where('mailbox_id', $request->mailbox_id)->first();
                    if (!$mailbox_user) {
                        // Admin may not be connected to the mailbox yet
                        $user->mailboxes()->attach($request->mailbox_id);
                        // $mailbox_user = new MailboxUser();
                        // $mailbox_user->mailbox_id = $mailbox->id;
                        // $mailbox_user->user_id = $user->id;
                        $mailbox_user = $user->mailboxes()->where('mailbox_id', $request->mailbox_id)->first();
                    }
                    $mailbox_user->settings->after_send = $request->value;
                    $mailbox_user->settings->save();

                    $response['status'] = 'success';
                }
                break;

            // Conversations navigation
            case 'conversations_pagination':
                if (!empty($request->filter)) {
                    $response = $this->ajaxConversationsFilter($request, $response, $user);
                } else {
                    $response = $this->ajaxConversationsPagination($request, $response, $user);
                }
                break;

            // Change conversation customer
            case 'conversation_change_customer':
                $conversation = Conversation::find($request->conversation_id);
                $customer_email = $request->customer_email;

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && !$user->can('update', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }
                if (!$response['msg'] && !$conversation->mailbox->userHasAccess($user->id)) {
                    $response['msg'] = __('Incorrect user');
                }

                $conversation->changeCustomer($customer_email, null, $user);

                $response['status'] = 'success';
                \Session::flash('flash_success_floating', __('Customer changed'));

                break;

            // Star/unstar conversation
            case 'star_conversation':
                $conversation = Conversation::find($request->conversation_id);
                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                } elseif (!$user->can('view', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    if ($request->sub_action == 'star') {
                        $conversation->addToFolder(Folder::TYPE_STARRED);
                    } else {
                        $conversation->removeFromFolder(Folder::TYPE_STARRED);
                    }
                    Conversation::clearStarredByUserCache($user->id);
                    $conversation->mailbox->updateFoldersCounters(Folder::TYPE_STARRED);
                    $response['status'] = 'success';
                }
                break;

            // Delete conversation (move to DELETED folder)
            case 'delete_conversation':
                $conversation = Conversation::find($request->conversation_id);
                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                } elseif (!$user->can('delete', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $folder_id = $conversation->folder_id;
                    $conversation->state = Conversation::STATE_DELETED;
                    $conversation->user_updated_at = date('Y-m-d H:i:s');
                    $conversation->updateFolder();
                    $conversation->save();

                    // Recalculate only old and new folders
                    $conversation->mailbox->updateFoldersCounters();

                    $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $folder_id]);

                    $response['status'] = 'success';

                    \Session::flash('flash_success_floating', __('Conversation deleted'));
                }
                break;

            // Change conversations user
            case 'bulk_conversation_change_user':

                $conversations = Conversation::findMany($request->conversation_id);

                $new_user_id = (int) $request->user_id;

                if (!$response['msg']) {
                    foreach ($conversations as $conversation) {
                        if (!$user->can('update', $conversation)) {
                            continue;
                        }
                        if (!$conversation->mailbox->userHasAccess($new_user_id)) {
                            continue;
                        }

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
                        \Eventy::action('conversation.user_changed', $conversation, $user);
                    }

                    $response['status'] = 'success';
                    // Flash
                    $flash_message = __('Assignee updated');
                    \Session::flash('flash_success_floating', $flash_message);

                    $response['msg'] = __('Assignee updated');
                }
                break;

            // Change conversations status
            case 'bulk_conversation_change_status':
                $conversations = Conversation::findMany($request->conversation_id);

                $new_status = (int) $request->status;

                if (!in_array((int) $request->status, array_keys(Conversation::$statuses))) {
                    $response['msg'] = __('Incorrect status');
                }

                if (!$response['msg']) {
                    foreach ($conversations as $conversation) {
                        if (!$user->can('update', $conversation)) {
                            continue;
                        }

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
                        \Eventy::action('conversation.status_changed', $conversation);
                    }

                    $response['status'] = 'success';
                    // Flash
                    $flash_message = __('Status updated');
                    \Session::flash('flash_success_floating', $flash_message);

                    $response['msg'] = __('Status updated');
                }
                break;

            // delete converations
            case 'bulk_delete_conversation':
                $conversations = Conversation::findMany($request->conversation_id);

                foreach ($conversations as $conversation) {
                    if (!$user->can('delete', $conversation)) {
                        continue;
                    }

                    $folder_id = $conversation->folder_id;
                    $conversation->state = Conversation::STATE_DELETED;
                    $conversation->user_updated_at = date('Y-m-d H:i:s');
                    $conversation->updateFolder();
                    $conversation->save();

                    // Recalculate only old and new folders
                    $conversation->mailbox->updateFoldersCounters();

                    $response['status'] = 'success';

                    \Session::flash('flash_success_floating', __('Conversations deleted'));
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
     * Conversations ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'send_log':
                return $this->ajaxHtmlSendLog();
            case 'show_original':
                return $this->ajaxHtmlShowOriginal();
            case 'change_customer':
                return $this->ajaxHtmlChangeCustomer();
        }

        abort(404);
    }

    /**
     * Send log.
     */
    public function ajaxHtmlSendLog()
    {
        $thread_id = Input::get('thread_id');
        if (!$thread_id) {
            abort(404);
        }

        $thread = Thread::find($thread_id);
        if (!$thread) {
            abort(404);
        }

        $user = auth()->user();

        if (!$user->can('view', $thread->conversation)) {
            abort(403);
        }

        // Get send log
        $log_records = SendLog::where('thread_id', $thread_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $customers_log = [];
        $users_log = [];
        foreach ($log_records as $log_record) {
            if ($log_record->user_id) {
                $users_log[$log_record->email][] = $log_record;
            } else {
                $customers_log[$log_record->email][] = $log_record;
            }
        }

        return view('conversations/ajax_html/send_log', [
            'customers_log' => $customers_log,
            'users_log'     => $users_log,
        ]);
    }

    /**
     * Show original message headers.
     */
    public function ajaxHtmlShowOriginal()
    {
        $thread_id = Input::get('thread_id');
        if (!$thread_id) {
            abort(404);
        }

        $thread = Thread::find($thread_id);
        if (!$thread) {
            abort(404);
        }

        $user = auth()->user();

        if (!$user->can('view', $thread->conversation)) {
            abort(403);
        }

        return view('conversations/ajax_html/show_original', [
            'thread' => $thread,
        ]);
    }

    /**
     * Change conversation customer.
     */
    public function ajaxHtmlChangeCustomer()
    {
        $conversation_id = Input::get('conversation_id');
        if (!$conversation_id) {
            abort(404);
        }

        $conversation = Conversation::find($conversation_id);
        if (!$conversation) {
            abort(404);
        }

        $user = auth()->user();

        if (!$user->can('view', $conversation)) {
            abort(403);
        }

        return view('conversations/ajax_html/change_customer', [
            'conversation' => $conversation,
        ]);
    }

    /**
     * Get redirect URL after performing an action.
     */
    public function getRedirectUrl($request, $conversation, $user)
    {
        // If conversation is a draft, we always display Drafts folder
        if ($conversation->state == Conversation::STATE_DRAFT) {
            return route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $conversation->folder_id]);
        }

        if (!empty($request->after_send)) {
            $after_send = $request->after_send;
        } else {
            $after_send = $conversation->mailbox->getUserSettings($user->id)->after_send;
        }
        if (!empty($after_send)) {
            switch ($after_send) {
                case MailboxUser::AFTER_SEND_STAY:
                default:
                    $redirect_url = $conversation->url();
                    break;
                case MailboxUser::AFTER_SEND_FOLDER:
                    $redirect_url = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $conversation->folder_id]);
                    break;
                case MailboxUser::AFTER_SEND_NEXT:
                    $redirect_url = $conversation->urlNext(Conversation::getFolderParam());
                    break;
            }
        } else {
            // If something went wrong and after_send not set, just show the reply
            $redirect_url = $conversation->url();
        }

        return $redirect_url;
    }

    /**
     * Upload files and images.
     */
    public function upload(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        if (!$user) {
            $response['msg'] = __('Please login to upload file');
        }

        if (!$request->hasFile('file') || !$request->file('file')->isValid() || !$request->file) {
            $response['msg'] = __('Error occured uploading file');
        }

        if (!$response['msg']) {
            $embedded = true;

            if (!empty($request->attach) && (int) $request->attach) {
                $embedded = false;
            }

            $attachment = Attachment::create(
                $request->file->getClientOriginalName(),
                $request->file->getMimeType(),
                null,
                '',
                $request->file,
                $embedded,
                null,
                $user->id
            );

            if ($attachment) {
                $response['status'] = 'success';
                $response['url'] = $attachment->url();
                $response['attachment_id'] = $attachment->id;
            } else {
                $response['msg'] = __('Error occured uploading file');
            }
        }

        return \Response::json($response);
    }

    /**
     * Ajax conversation navigation.
     */
    public function ajaxConversationsPagination(Request $request, $response, $user)
    {
        $mailbox = Mailbox::find($request->mailbox_id);
        $folder = null;
        $conversations = [];

        if (!$mailbox) {
            $response['msg'] = __('Mailbox not found');
        }

        if (!$response['msg'] && !$user->can('view', $mailbox)) {
            $response['msg'] = __('Not enough permissions');
        }

        if (!$response['msg']) {
            $folder = Folder::find($request->folder_id);
            if (!$folder) {
                $response['msg'] = __('Folder not found');
            }
        }
        if (!$response['msg'] && !$user->can('view', $folder)) {
            $response['msg'] = __('Not enough permissions');
        }

        if (!$response['msg']) {
            $query_conversations = Conversation::getQueryByFolder($folder, $user->id);
            $conversations = $folder->queryAddOrderBy($query_conversations)->paginate(Conversation::DEFAULT_LIST_SIZE, ['*'], 'page', $request->page);
        }

        $response['status'] = 'success';

        $response['html'] = view('conversations/conversations_table', [
            'folder'        => $folder,
            'conversations' => $conversations,
        ])->render();

        return $response;
    }

    /**
     * Search.
     */
    public function search(Request $request)
    {
        $user = auth()->user();

        $conversations = $this->searchQuery($request, $user);

        // Dummy folder
        $folder = $this->getSearchFolder($conversations);

        return view('conversations/search', [
            'folder'        => $folder,
            'q'             => $request->q,
            'conversations' => $conversations,
        ]);
    }

    public function searchQuery($request, $user)
    {
        // Get IDs of mailboxes to which user has access
        $mailbox_ids = $user->mailboxesIdsCanView();

        // Filters
        $filters = $request->f ?? [];

        // Search query
        $q = '';
        if (!empty($request->q)) {
            $q = $request->q;
        } elseif (!empty($request->filter) && !empty($request->filter['q'])) {
            $q = $request->filter['q'];
        }

        $like = '%'.mb_strtolower($q).'%';

        $query_conversations = Conversation::select('conversations.*')
            ->whereIn('conversations.mailbox_id', $mailbox_ids)
            ->join('threads', function ($join) {
                $join->on('conversations.id', '=', 'threads.id');
            })
            ->where(function ($query) use ($like) {
                $query->where('conversations.subject', 'like', $like)
                    ->orWhere('threads.body', 'like', $like)
                    ->orWhere('threads.to', 'like', $like)
                    ->orWhere('threads.cc', 'like', $like)
                    ->orWhere('threads.bcc', 'like', $like);
            });

        $query_conversations = \Eventy::filter('search.apply_filters', $query_conversations, $filters);

        $query_conversations->orderBy('conversations.last_reply_at');

        return $query_conversations->paginate(Conversation::DEFAULT_LIST_SIZE);
    }

    /**
     * Get dummy folder for search.
     */
    public function getSearchFolder($conversations)
    {
        $folder = new Folder();
        $folder->type = Folder::TYPE_ASSIGNED;
        // todo: use select([\DB::raw('SQL_CALC_FOUND_ROWS *')]) to count records
        //$folder->total_count = $conversations->count();

        return $folder;
    }

    /**
     * Ajax conversations search.
     */
    public function ajaxConversationsFilter(Request $request, $response, $user)
    {
        if (!empty($request->filter['q'])) {
            $conversations = $this->searchQuery($request, $user);
        } else {
            $conversations = $this->conversationsFilterQuery($request, $user);
        }

        $response['status'] = 'success';

        $response['html'] = view('conversations/conversations_table', [
            'conversations' => $conversations,
        ])->render();

        return $response;
    }

    public function conversationsFilterQuery($request, $user)
    {
        // Get IDs of mailboxes to which user has access
        $mailbox_ids = $user->mailboxesIdsCanView();

        $query_conversations = Conversation::whereIn('conversations.mailbox_id', $mailbox_ids)
            ->orderBy('conversations.last_reply_at');

        foreach ($request->filter as $field => $value) {
            switch ($field) {
                case 'customer_id':
                    $query_conversations->where('customer_id', $value);
                    break;
            }
        }

        return $query_conversations->paginate(Conversation::DEFAULT_LIST_SIZE);
    }

    /**
     * Process attachments on reply, new conversation, saving draft.
     */
    public function processReplyAttachments($request)
    {
        $has_attachments = false;
        $attachments = [];
        if (!empty($request->attachments_all)) {
            $embeds = [];
            if (!empty($request->attachments)) {
                $attachments = $request->attachments;
            }
            if (!empty($request->embeds)) {
                $embeds = $request->embeds;
            }
            if (count($attachments) != count($embeds)) {
                $has_attachments = true;
            }
            $attachments_to_remove = array_diff($request->attachments_all, $attachments);
            $attachments_to_remove = array_diff($attachments_to_remove, $embeds);
            Attachment::deleteByIds($attachments_to_remove);
        }

        return [
            'has_attachments' => $has_attachments,
            'attachments'     => $attachments,
        ];
    }

    /**
     * Undo reply.
     */
    public function undoReply(Request $request, $thread_id)
    {
        $thread = Thread::findOrFail($thread_id);

        if (!$thread) {
            abort(404);
        }

        $conversation = $thread->conversation;
        $this->authorize('view', $conversation);

        // Check undo timeout
        if ($thread->created_at->diffInSeconds(now()) > Conversation::UNDO_TIMOUT) {
            \Session::flash('flash_error_floating', __('Sending can not be undone'));

            return redirect()->away($conversation->url($conversation->folder_id));
        }

        // Convert reply into draft
        $thread->state = Thread::STATE_DRAFT;
        $thread->save();

        // Get penultimate reply
        $last_thread = $conversation->threads()
            ->where('id', '<>', $thread->id)
            ->whereIn('type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE])
            ->orderBy('created_at', 'desc')
            ->first();

        $folder_id = $conversation->folder_id;

        // Restore conversation data from penultimate thread
        if ($last_thread) {
            $conversation->setCc($last_thread->cc);
            $conversation->setBcc($last_thread->bcc);
            $conversation->last_reply_at = $last_thread->created_at;
            $conversation->last_reply_from = $last_thread->source_via;
            $conversation->user_updated_at = date('Y-m-d H:i:s');
        }
        if ($thread->first) {
            // This was a new conversation, move it to drafts
            $conversation->state = Thread::STATE_DRAFT;
            $conversation->updateFolder();
            $conversation->mailbox->updateFoldersCounters();
            $folder_id = null;
        }
        $conversation->save();

        return redirect()->away($conversation->url($folder_id, null, ['show_draft' => $thread->id]));
    }
}
