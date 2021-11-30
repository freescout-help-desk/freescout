<?php

namespace App\Http\Controllers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Email;
use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\UserAddedNote;
use App\Events\UserCreatedConversation;
use App\Events\UserCreatedConversationDraft;
use App\Events\UserCreatedThreadDraft;
use App\Events\UserReplied;
use App\Folder;
use App\Follower;
use App\Mailbox;
use App\MailboxUser;
use App\SendLog;
use App\Thread;
use App\User;
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
        $this->authorize('viewCached', $conversation);

        $mailbox = $conversation->mailbox;
        $customer = $conversation->customer_cached;
        $user = auth()->user();

        // To let other parts of the app easily access.
        \Helper::setGlobalEntity('conversation', $conversation);
        \Helper::setGlobalEntity('mailbox', $mailbox);

        if ($user->isAdmin()) {
            $mailbox->fetchUserSettings($user->id);
        }

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

        //$after_send = $conversation->mailbox->getUserSettings($user->id)->after_send;
        $after_send = $user->mailboxSettings($conversation->mailbox_id)->after_send;

        // Detect customers and emails to which user can reply
        $to_customers = [];
        // Add all customer emails
        $customer_emails = [];
        $distinct_emails = [];

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
                $distinct_emails[] = $prev_customer->from;
            }
        }

        // Add customer email(s) if there more than one or if there are other emails in threads.
        if ($customer) {
            $customer_emails = $customer->emails;
        }
        if (count($customer_emails) > 1 || count($to_customers)) {
            foreach ($customer_emails as $customer_email) {
                $to_customers[] = [
                    'customer' => $customer,
                    'email'    => $customer_email->email,
                ];
                $distinct_emails[] = $customer_email->email;
            }
        }

        // To (for new conversation draft only).
        $to = [];
        $emails = Conversation::sanitizeEmails($conversation->customer_email);
        // Get customers info for emails.
        if (count($emails)) {
            $to = Customer::emailsToCustomers($emails);
        }

        // Exclude mailbox emails from $to_customers.
        $mailbox_emails = $mailbox->getEmails();
        foreach ($to_customers as $key => $to_customer) {
            if (in_array($to_customer['email'], $mailbox_emails)) {
                unset($to_customers[$key]);
            }
        }

        // Previous conversations
        $prev_conversations = [];
        if ($customer) {
            $prev_conversations = $mailbox->conversations()
                                    ->where('customer_id', $customer->id)
                                    ->where('id', '<>', $conversation->id)
                                    ->where('status', '!=', Conversation::STATUS_SPAM)
                                    ->where('state', Conversation::STATE_PUBLISHED)
                                    //->limit(self::PREV_CONVERSATIONS_LIMIT)
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(self::PREV_CONVERSATIONS_LIMIT);
        }

        $template = 'conversations/view';
        if ($conversation->state == Conversation::STATE_DRAFT) {
            $template = 'conversations/create';
        }

        $exclude_array = $conversation->getExcludeArray($mailbox);

        // Get data for creating a phone conversation.
        $name = [];
        $phone = '';
        $to_email = [];
        if ($customer) {
            if ($customer->getFullName()) {
                $name = [$customer->id => $customer->getFullName()];
            }
            $last_phone = array_last($customer->getPhones());
            if (!empty($last_phone)) {
                $phone = $last_phone['value'];
            }

            if ($conversation->customer_email) {
                $customer_email = $conversation->customer_email;
            } else {
                $customer_email = $customer->getMainEmail();
            }
            if ($customer_email) {
                $to_email = [$customer_email];
            }
        }

        // Notify other users that current user is viewing conversation.
        // Eventually notification data will be saved in polycast_events table and processes
        // in JS in users browsers.

        // $notification = new \App\Notifications\UserViewingConversationNotification(
        //     $conversation, $user, false
        // );

        // This broadcasts to specific users.
        // \Notification::send($mailbox->usersHavingAccess(), $notification);

        // Notification is sent to all via public channel: conview
        // If we send notification to each user, applications having thouthans of users
        // will be overloaded.
        // // https://laravel.com/docs/5.5/broadcasting#broadcasting-events
        \App\Events\RealtimeConvView::dispatchSelf($conversation->id, $user, false);

        // Get viewers.
        $viewers = [];
        $conv_view = \Cache::get('conv_view');
        if ($conv_view && !empty($conv_view[$conversation->id])) {
            $viewing_users = User::whereIn('id', array_keys($conv_view[$conversation->id]))->get();
            foreach ($viewing_users as $viewer) {
                if (isset($conv_view[$conversation->id][$viewer->id]['r']) && $viewer->id != $user->id) {
                    $viewers[] = [
                        'user'     => $viewer,
                        'replying' => (int)$conv_view[$conversation->id][$viewer->id]['r']
                    ];
                }
            }
            // Show replying first.
            usort($viewers, function($a, $b) {
                return $b['replying'] <=> $a['replying'];
            });
        }

        $is_following = $conversation->isUserFollowing($user->id);

        \Eventy::action('conversation.view.start', $conversation);

        return view($template, [
            'conversation'       => $conversation,
            'mailbox'            => $conversation->mailbox,
            'customer'           => $customer,
            'threads'            => $conversation->threads()->orderBy('created_at', 'desc')->get(),
            'folder'             => $folder,
            'folders'            => $conversation->mailbox->getAssesibleFolders(),
            'after_send'         => $after_send,
            'to'                 => $to,
            'to_customers'       => $to_customers,
            'prev_conversations' => $prev_conversations,
            'cc'                 => $conversation->getCcArray($exclude_array),
            'bcc'                => [], //$conversation->getBccArray($exclude_array),
            // Data for creating a phone conversation.
            'name'               => $name,
            'phone'              => $phone,
            'to_email'           => $to_email,
            'viewers'            => $viewers,
            'is_following'       => $is_following,
        ]);
    }

    /**
     * New conversation.
     */
    public function create(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorize('view', $mailbox);

        $conversation = new Conversation();
        $conversation->body = '';
        $conversation->mailbox = $mailbox;

        $folder = $mailbox->folders()->where('type', Folder::TYPE_DRAFTS)->first();

        // todo: use $user->mailboxSettings()
        $after_send = $mailbox->getUserSettings(auth()->user()->id)->after_send;

        // Create conversation from thread
        $thread = null;
        if (!empty($request->from_thread_id)) {
            $orig_thread = Thread::find($request->from_thread_id);
            if ($orig_thread) {
                $conversation->subject = $orig_thread->conversation->subject;
                $conversation->subject = preg_replace('/^Fwd:/i', 'Re: ', $conversation->subject);

                $thread = new \App\Thread();
                $thread->body = $orig_thread->body;
                // If this is a forwarded message, try to fetch From
                preg_match_all("/From:[^<\n]+<([^<\n]+)>/m", html_entity_decode(strip_tags($thread->body)), $m);

                if (!empty($m[1])) {
                    foreach ($m[1] as $value) {
                        if (\MailHelper::validateEmail($value)) {
                            $thread->to = json_encode([$value]);
                            break;
                        }
                    }
                }
            }
        }

        $to = [];

        $prefill_to = \App\Email::sanitizeEmail($request->get('to'));
        if ($prefill_to) {
            $to = [$prefill_to => $prefill_to];
        }

        return view('conversations/create', [
            'conversation' => $conversation,
            'thread'       => $thread,
            'mailbox'      => $mailbox,
            'folder'       => $folder,
            'folders'      => $mailbox->getAssesibleFolders(),
            'after_send'   => $after_send,
            'to'           => $to,
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
                    $response['msg'] = __('Not enough permissions');
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

                    $conversation->changeUser($new_user_id, $user);

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

                if ($request->status == 'not_spam') {
                    // Find previous status in threads
                    $new_status = $conversation
                        ->threads()
                        ->orderBy('created_at', 'desc')
                        ->where('status', '!=', Thread::STATUS_SPAM)
                        ->where('type', Thread::TYPE_LINEITEM)
                        ->where('action_type', Thread::ACTION_TYPE_STATUS_CHANGED)
                        ->value('status');
                    if (!$new_status) {
                        $new_status = Thread::STATUS_ACTIVE;
                    }
                } else {
                    $new_status = (int) $request->status;
                }

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && $conversation->status == $new_status) {
                    $response['msg'] = __('Status already set');
                }
                if (!$response['msg'] && !$user->can('update', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }
                if (!$response['msg'] && !in_array((int) $new_status, array_keys(Conversation::$statuses))) {
                    $response['msg'] = __('Incorrect status');
                }
                if (!$response['msg']) {
                    // Determine redirect
                    // Must be done before updating current conversation's status or assignee.
                    $redirect_same_page = false;
                    if ($request->status == 'not_spam') {
                        // Stay on the current page
                        $response['redirect_url'] = $conversation->url();
                        $redirect_same_page = true;
                    } else {
                        $response['redirect_url'] = $this->getRedirectUrl($request, $conversation, $user);
                    }

                    $conversation->changeStatus($new_status, $user);

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

            // Send reply, new conversation, add note or forward
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

                // Conversation type.
                $type = Conversation::TYPE_EMAIL;
                if (!empty($request->type)) {
                    $type = (int)$request->type;
                }

                $is_phone = false;
                if ($type == Conversation::TYPE_PHONE) {
                    $is_phone = true;
                }

                $is_create = false;
                if (!empty($request->is_create)) {
                    //if ($new || ($from_draft && $conversation->threads_count == 1)) {
                    $is_create = $request->is_create;
                }

                $is_forward = false;
                if (!empty($request->subtype) && (int)$request->subtype == Thread::SUBTYPE_FORWARD) {
                    $is_forward = true;
                }

                $is_multiple = false;
                if (!empty($request->multiple_conversations)) {
                    $is_multiple = true;
                }

                // If reply is being created from draft, there is already thread created
                $thread = null;
                $from_draft = false;
                if ((!$is_note || $is_phone) && !$response['msg'] && !empty($request->thread_id)) {
                    $thread = Thread::find($request->thread_id);
                    if ($thread && (!$conversation || $thread->conversation_id != $conversation->id)) {
                        $response['msg'] = __('Incorrect thread');
                    } else {
                        $from_draft = true;
                    }
                }

                if (!$response['msg']) {
                    if ($thread && $from_draft && $thread->state == Thread::STATE_PUBLISHED) {
                        $response['msg'] = __('Message has been already sent. Please discard this draft.');
                    }
                }

                // Validate form
                if (!$response['msg']) {
                    if ($new) {
                        if ($type == Conversation::TYPE_EMAIL) {
                            $validator = Validator::make($request->all(), [
                                'to'       => 'required|array',
                                'subject'  => 'required|string|max:998',
                                'body'     => 'required|string',
                                'cc'       => 'nullable|array',
                                'bcc'      => 'nullable|array',
                            ]);
                        } else {
                            // Phone conversation.
                            $validator = Validator::make($request->all(), [
                                'name'     => 'required|string',
                                'subject'  => 'required|string|max:998',
                                'body'     => 'required|string',
                                'phone'    => 'nullable|string',
                                'to_email' => 'nullable|string',
                            ]);
                        }
                    } else {
                        $validator = Validator::make($request->all(), [
                            'body'     => 'required|string',
                            'cc'       => 'nullable|array',
                            'bcc'      => 'nullable|array',
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

                // List of emails.
                $to_array = [];
                if ($is_forward) {
                    $to_array = Conversation::sanitizeEmails($request->to_email);
                } else {
                    $to_array = Conversation::sanitizeEmails($request->to);
                }
                // Check To
                if (!$response['msg'] && $new && !$is_phone) {
                    if (!$to_array) {
                        $response['msg'] .= __('Incorrect recipients');
                    }
                }

                if (!$response['msg']) {

                    // Get attachments info
                    // Delete removed attachments.
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
                        $conversation->type = $type;
                        $conversation->subject = $request->subject;
                        $conversation->setPreview($request->body);
                        $conversation->mailbox_id = $request->mailbox_id;
                        $conversation->created_by_user_id = auth()->user()->id;
                        $conversation->source_via = Conversation::PERSON_USER;
                        $conversation->source_type = Conversation::SOURCE_TYPE_WEB;
                    } else {
                        // Reply or note
                        if ((int) $request->status != (int) $conversation->status) {
                            $status_changed = true;
                        }
                        if (!empty($request->subject)) {
                            $conversation->subject = $request->subject;
                        }
                        // When switching from regular message to phone and message sent
                        // without saving a draft type need to be saved here.
                        $conversation->type = $type;
                    }

                    if ($attachments_info['has_attachments']) {
                        $conversation->has_attachments = true;
                    }

                    // Customer can be empty in existing conversation if this is a draft.
                    $customer_email = '';
                    $customer = null;

                    if ($is_phone) {
                        // Phone.
                        $phone_customer_data = $this->processPhoneCustomer($request);

                        $customer_email = $phone_customer_data['customer_email'];
                        $customer = $phone_customer_data['customer'];
                    } else {
                        // Email.
                        if (!empty($to_array)) {
                            $customer_email = $to_array[0];
                        }
                        if (!$conversation->customer_id) {
                            $customer = Customer::create($customer_email);
                            $conversation->customer_id = $customer->id;
                        } else {
                            $customer = $conversation->customer;
                        }
                    }
                    if ($customer_email && !$is_note && !$is_forward) {
                        $conversation->customer_email = $customer_email;
                    }

                    $prev_status = $conversation->status;

                    $conversation->status = $request->status;
                    if ($prev_status != $conversation->status && $conversation->status == Conversation::STATUS_CLOSED) {
                        $conversation->closed_by_user_id = $user->id;
                        $conversation->closed_at = date('Y-m-d H:i:s');
                    }

                    // We need to set state, as it may have been a draft
                    $conversation->state = Conversation::STATE_PUBLISHED;
                    // Set assignee
                    $prev_user_id = $conversation->user_id;
                    if ((int) $request->user_id != -1) {
                        // Check if user has access to the current mailbox
                        if ((int) $conversation->user_id != (int) $request->user_id && $mailbox->userHasAccess($request->user_id)) {
                            $conversation->user_id = $request->user_id;
                            $user_changed = true;
                        }
                    } else {
                        $conversation->user_id = null;
                    }

                    // To is a single email string.
                    $to = '';
                    // List of emails.
                    $to_list = [];
                    if ($is_forward) {
                        $to = $request->to_email[0];
                    } else {
                        if (!empty($request->to)) {
                            // When creating a new conversation, to is a list of emails.
                            if (is_array($request->to)) {
                                $to = $request->to[0];
                            } else {
                                $to = $request->to;
                            }
                        } else {
                            $to = $conversation->customer_email;
                        }
                    }

                    if (!$is_note && !$is_forward) {
                        // Save extra recipients to CC
                        if ($is_create && !$is_multiple && count($to_array) > 1) {
                            $conversation->setCc(array_merge(Conversation::sanitizeEmails($request->cc), $to_array));
                        } else {
                            $conversation->setCc(array_merge(Conversation::sanitizeEmails($request->cc), [$to]));
                        }
                        $conversation->setBcc($request->bcc);
                        $conversation->last_reply_at = $now;
                        $conversation->last_reply_from = Conversation::PERSON_USER;
                        $conversation->user_updated_at = $now;
                    }
                    if ($conversation->isPhone() && $is_note) {
                        $conversation->last_reply_at = $now;
                        $conversation->last_reply_from = Conversation::PERSON_USER;
                    }
                    $conversation->updateFolder();
                    if ($from_draft) {
                        // Increment number of replies in conversation
                        $conversation->threads_count++;
                        // We need to set preview here as when conversation is created from draft,
                        // ThreadObserver::created() method is not called.
                        $conversation->setPreview($request->body);
                    }
                    $conversation->save();

                    if ($new) {
                        $response['redirect_url'] = $this->getRedirectUrl($request, $conversation, $user);
                    }

                    // Fire events
                    if (!$new) {
                        if ($status_changed) {
                            event(new ConversationStatusChanged($conversation));
                            \Eventy::action('conversation.status_changed', $conversation, $user, $changed_on_reply = true, $prev_status);
                        }
                        if ($user_changed) {
                            event(new ConversationUserChanged($conversation, $user));
                            \Eventy::action('conversation.user_changed', $conversation, $user, $prev_user_id);
                        }
                    }

                    // Create thread
                    if (!$thread) {
                        $thread = new Thread();
                        $thread->conversation_id = $conversation->id;
                        if ($is_note || $is_forward) {
                            $thread->type = Thread::TYPE_NOTE;
                        } else {
                            $thread->type = Thread::TYPE_MESSAGE;
                        }
                        $thread->source_via = Thread::PERSON_USER;
                        $thread->source_type = Thread::SOURCE_TYPE_WEB;
                    } else {
                        if ($is_forward || $is_phone) {
                            $thread->type = Thread::TYPE_NOTE;
                        } else {
                            $thread->type = Thread::TYPE_MESSAGE;
                        }
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
                    if ($is_create && !$is_multiple && count($to_array) > 1) {
                        $thread->setTo($to_array);
                    } else {
                        $thread->setTo($to);
                    }
                    // We save CC and BCC as is and filter emails when sending replies
                    $thread->setCc($request->cc);
                    $thread->setBcc($request->bcc);
                    if ($attachments_info['has_attachments'] && !$is_forward) {
                        $thread->has_attachments = true;
                    }
                    if (!empty($request->saved_reply_id)) {
                        $thread->saved_reply_id = $request->saved_reply_id;
                    }
                    
                    $forwarded_conversations = [];
                    $forwarded_threads = [];

                    if ($is_forward) {
                        // Create forwarded conversations.
                        foreach ($to_array as $recipient_email) {
                            $forwarded_conversation = $conversation->replicate();
                            $forwarded_conversation->type = Conversation::TYPE_EMAIL;
                            $forwarded_conversation->setPreview($thread->body);
                            $forwarded_conversation->created_by_user_id = auth()->user()->id;
                            $forwarded_conversation->source_via = Conversation::PERSON_USER;
                            $forwarded_conversation->source_type = Conversation::SOURCE_TYPE_WEB;
                            $forwarded_conversation->threads_count = 0; // Counter will be incremented in ThreadObserver.
                            $forwarded_customer = Customer::create($recipient_email);
                            $forwarded_conversation->customer_id = $forwarded_customer->id;
                            // Reload customer object, otherwise it stores previous customer.
                            $forwarded_conversation->load('customer');
                            $forwarded_conversation->customer_email = $recipient_email;
                            $forwarded_conversation->subject = 'Fwd: '.$forwarded_conversation->subject;
                            //$forwarded_conversation->setCc(array_merge(Conversation::sanitizeEmails($request->cc), [$to]));
                            $forwarded_conversation->setCc(Conversation::sanitizeEmails($request->cc));
                            $forwarded_conversation->setBcc($request->bcc);
                            $forwarded_conversation->last_reply_at = $now;
                            $forwarded_conversation->last_reply_from = Conversation::PERSON_USER;
                            $forwarded_conversation->user_updated_at = $now;
                            if ($attachments_info['has_attachments']) {
                                $forwarded_conversation->has_attachments = true;
                            }
                            $forwarded_conversation->updateFolder();
                            $forwarded_conversation->save();

                            $forwarded_thread = $thread->replicate();

                            $forwarded_conversations[] = $forwarded_conversation;
                            $forwarded_threads[] = $forwarded_thread;
                        }

                        // Set forwarding meta data.
                        // todo: store array of numbers and IDs.
                        $thread->subtype = Thread::SUBTYPE_FORWARD;
                        $thread->setMeta('forward_child_conversation_number', $forwarded_conversation->number);
                        $thread->setMeta('forward_child_conversation_id', $forwarded_conversation->id);
                    }

                    // Conversation history.
                    if (!empty($request->conv_history)) {
                        if ($request->conv_history != 'global') {
                            if ($is_forward && !empty($forwarded_threads)) {
                                foreach ($forwarded_threads as $forwarded_thread) {
                                    $forwarded_thread->setMeta(Thread::META_CONVERSATION_HISTORY, $request->conv_history);
                                }
                            } else {
                                $thread->setMeta(Thread::META_CONVERSATION_HISTORY, $request->conv_history);
                            }
                        }
                    }

                    \Eventy::action('thread.before_save_from_request', $thread, $request);
                    $thread->save();

                    // Save forwarded thread.
                    if ($is_forward) {
                        foreach ($forwarded_conversations as $i => $forwarded_conversation) {
                            $forwarded_thread = $forwarded_threads[$i];

                            $forwarded_thread->conversation_id = $forwarded_conversation->id;
                            $forwarded_thread->type = Thread::TYPE_MESSAGE;
                            $forwarded_thread->subtype = null;
                            if ($attachments_info['has_attachments']) {
                                $forwarded_thread->has_attachments = true;
                            }
                            $forwarded_thread->setMeta('forward_parent_conversation_number', $conversation->number);
                            $forwarded_thread->setMeta('forward_parent_conversation_id', $conversation->id);
                            $forwarded_thread->setMeta('forward_parent_thread_id', $thread->id);
                            $forwarded_thread->save();
                        }
                    }

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
                        if ($is_forward) {
                            // Copy attachments for each thread.
                            if (count($forwarded_threads) > 1) {
                                $attachments = Attachment::whereIn('id', $attachments_info['attachments'])->get();
                            }
                            foreach ($forwarded_threads as $i => $forwarded_thread) {
                                if ($i == 0) {
                                    Attachment::whereIn('id', $attachments_info['attachments'])->update(['thread_id' => $forwarded_thread->id]);
                                } else {
                                    foreach ($attachments as $attachment) {
                                        $attachment->duplicate($forwarded_thread->id);
                                    }
                                }
                            }
                        } else {
                            Attachment::whereIn('id', $attachments_info['attachments'])->update(['thread_id' => $thread->id]);
                        }
                    }

                    // When user creates a new conversation it may be saved as draft first.
                    if ($is_create) {
                        // New conversation.
                        event(new UserCreatedConversation($conversation, $thread));
                        \Eventy::action('conversation.created_by_user_can_undo', $conversation, $thread);
                        // After Conversation::UNDO_TIMOUT period trigger final event.
                        \Helper::backgroundAction('conversation.created_by_user', [$conversation, $thread], now()->addSeconds(Conversation::UNDO_TIMOUT));
                    } elseif ($is_forward) {
                        // Forward.
                        // Notifications to users not sent.
                        event(new UserAddedNote($conversation, $thread));
                        foreach ($forwarded_conversations as $i => $forwarded_conversation) {
                            $forwarded_thread = $forwarded_threads[$i];

                            // To send email with forwarded conversation.
                            event(new UserReplied($forwarded_conversation, $forwarded_thread));
                            \Eventy::action('conversation.user_forwarded_can_undo', $conversation, $thread, $forwarded_conversation, $forwarded_thread);
                            // After Conversation::UNDO_TIMOUT period trigger final event.
                            \Helper::backgroundAction('conversation.user_forwarded', [$conversation, $thread, $forwarded_conversation, $forwarded_thread], now()->addSeconds(Conversation::UNDO_TIMOUT));
                        }
                    } elseif ($is_note) {
                        // Note.
                        event(new UserAddedNote($conversation, $thread));
                        \Eventy::action('conversation.note_added', $conversation, $thread);
                    } else {
                        // Reply.
                        event(new UserReplied($conversation, $thread));
                        \Eventy::action('conversation.user_replied_can_undo', $conversation, $thread);
                        // After Conversation::UNDO_TIMOUT period trigger final event.
                        \Helper::backgroundAction('conversation.user_replied', [$conversation, $thread], now()->addSeconds(Conversation::UNDO_TIMOUT));
                    }

                    // Send new conversation separately to each customer.
                    if ($is_create && count($to_array) > 1 && $is_multiple) {
                        $prev_customers_ids = [];
                        foreach ($to_array as $i => $customer_email) {
                            // Skip first email, as conversation has already been created for it.
                            if ($i == 0) {
                                continue;
                            }
                            // Get customer by email.
                            $customer_tmp = Customer::getByEmail($customer_email);
                            // Skip same customers.
                            if ($customer_tmp && in_array($customer_tmp->id, $prev_customers_ids)) {
                                continue;
                            }

                            if (!$customer_tmp) {
                                $customer_tmp = Customer::create($customer_email);
                            }

                            $prev_customers_ids[]  = $customer_tmp->id;

                            // Copy conversation and thread.
                            $conversation_copy = $conversation->replicate();
                            $thread_copy = $thread->replicate();

                            // Save conversation.
                            $conversation_copy->threads_count = 0;
                            $conversation_copy->customer_id = $customer_tmp->id;
                            $conversation_copy->customer_email = $customer_email;
                            $conversation_copy->push();

                            $thread_copy->conversation_id = $conversation_copy->id;
                            $thread_copy->customer_id = $customer_tmp->id;
                            $thread_copy->setTo($customer_email);
                            $thread_copy->push();

                            // Events.
                            // todo: allow to undo all emails
                            event(new UserCreatedConversation($conversation_copy, $thread_copy));
                            \Eventy::action('conversation.created_by_user_can_undo', $conversation_copy, $thread_copy);
                            // After Conversation::UNDO_TIMOUT period trigger final event.
                            \Helper::backgroundAction('conversation.created_by_user', [$conversation_copy, $thread_copy], now()->addSeconds(Conversation::UNDO_TIMOUT));
                        }
                    }

                    // Compose flash message.
                    $show_view_link = true;
                    if (!empty($request->after_send) && $request->after_send == MailboxUser::AFTER_SEND_STAY) {
                        $show_view_link = false;
                    }

                    if ($is_phone) {
                        $flash_type = 'warning';
                        if ($show_view_link) {
                            $flash_text = ':%tag_start%Conversation created:%tag_end% :%view_start%View:%a_end% or :%undo_start%Undo:%a_end%';
                        } else {
                            $flash_text = '<strong>'.__('Conversation created').'</strong>';
                        }
                    } elseif ($is_note) {
                        $flash_type = 'warning';
                        if ($show_view_link) {
                            $flash_text = ':%tag_start%Note added:%tag_end% :%view_start%View:%a_end%';
                        } else {
                            $flash_text = '<strong>'.__('Note added').'</strong>';
                        }
                    } else {
                        $flash_type = 'success';
                        if ($show_view_link) {
                            $flash_text = ':%tag_start%Email Sent:%tag_end% :%view_start%View:%a_end% or :%undo_start%Undo:%a_end%';
                        } else {
                            $flash_text = ':%tag_start%Email Sent:%tag_end% :%undo_start%Undo:%a_end%';
                        }
                    }
                    $flash_message = __(
                        $flash_text,
                        ['%tag_start%' => '<strong>', '%tag_end%' => '</strong>', '%view_start%' => '&nbsp;<a href="'.$conversation->url().'">', '%a_end%' => '</a>&nbsp;', '%undo_start%' => '&nbsp;<a href="'.route('conversations.undo', ['thread_id' => $thread->id]).'" class="text-danger">']
                    );

                    \Session::flash('flash_'.$flash_type.'_floating', $flash_message);
                }
                break;

            // Save draft (automatically or by click) of a new conversation or reply.
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

                $is_create = false;
                if (!empty($request->is_create)) {
                    $is_create = true;
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

                // Check if thread has been sent (in other window for example).
                if (!$response['msg']) {
                    if ($thread && $thread->state == Thread::STATE_PUBLISHED) {
                        $response['msg'] = __('Message has been already sent. Please discard this draft.');
                    }
                }

                // To prevent creating draft after reply has been created.
                if (!$response['msg'] && $conversation) {
                    // Check if the last thread has same content as the new one.
                    $last_thread = $conversation->getLastThread([Thread::TYPE_MESSAGE, Thread::TYPE_NOTE]);

                    if ($last_thread
                        && $last_thread->created_by_user_id == $user->id
                        && $last_thread->body == $request->body
                    ) {
                        //\Log::error("You've already sent this message just recently.");
                        $response['msg'] = __("You've already sent this message just recently.");
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

                    if ($new || $is_create) {
                        // New conversation
                        $customer_email = '';
                        $customer = null;

                        $type = Conversation::TYPE_EMAIL;
                        if (!empty($request->type)) {
                            $type = (int)$request->type;
                        }

                        if ($type == Conversation::TYPE_PHONE) {
                            // Phone.
                            $phone_customer_data = $this->processPhoneCustomer($request);

                            $customer_email = $phone_customer_data['customer_email'];
                            $customer = $phone_customer_data['customer'];
                        } else {
                            // Email.
                            $to_array = Conversation::sanitizeEmails($request->to);
                            if (count($to_array)) {
                                if (count($to_array) == 1) {
                                    $customer_email = array_first($to_array);
                                    $customer = Customer::create($customer_email);
                                } else {
                                    // Creating a conversation to multiple customers
                                    // In customer_email temporary store a list of customer emails
                                    $customer_email = implode(',', $to_array);
                                    // Keep $customer as null.
                                    // When conversation will be sent, separate conversation
                                    // will be created for each customer.
                                    $customer = null;
                                }
                            }
                        }

                        $conversation->type = $type;
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
                    if (empty($request->to) || !is_array($request->to)) {
                        if (!empty($request->to)) {
                            $to = $request->to;
                        } else {
                            $to = $conversation->customer_email;
                        }
                    }

                    // Conversation type.
                    if (!empty($request->type) && array_key_exists((int)$request->type, Conversation::$types)) {
                        $conversation->type = (int)$request->type;
                    }

                    // Save extra recipients to CC
                    if ($is_create) {
                        $conversation->setCc(array_merge(Conversation::sanitizeEmails($request->cc), [$to]));
                        $conversation->setBcc($request->bcc);
                    }
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
                        //$thread->type = Thread::TYPE_MESSAGE;
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
                        // User is forwarding a conversation.
                        if (!empty($request->subtype) && (int)$request->subtype) {
                            $thread->subtype = $request->subtype;
                        }
                    }
                    if ($attachments_info['has_attachments']) {
                        $thread->has_attachments = true;
                    }
                    // Thread type.
                    if ($is_create && !empty($request->is_note)) {
                        $thread->type = Thread::TYPE_NOTE;
                    } else {
                        $thread->type = Thread::TYPE_MESSAGE;
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
                    $response['customer_id'] = $conversation->customer_id;
                    $response['thread_id'] = $thread->id;
                    $response['number'] = $conversation->number;

                    $response['status'] = 'success';

                    // Set thread_id for uploaded attachments
                    if ($attachments_info['attachments']) {
                        Attachment::whereIn('id', $attachments_info['attachments'])->update(['thread_id' => $thread->id]);
                    }

                    // Update folder counter.
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

                    // Discarding a new conversation being created from thread
                    if (!empty($request->from_thread_id)) {
                        $original_thread = Thread::find($request->from_thread_id);
                        if ($original_thread && $original_thread->conversation_id) {
                            // Open original conversation
                            $response['redirect_url'] = route('conversations.view', ['id' => $original_thread->conversation_id]);
                        }
                    }
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

                    // Build attachments list.
                    $attachments = [];
                    foreach ($thread->attachments as $attachment) {
                        $attachments[] = [
                            'id'   => $attachment->id,
                            'name' => $attachment->file_name,
                            'size' => $attachment->size,
                            'url'  => $attachment->url(),
                        ];
                    }

                    $response['data'] = [
                        'thread_id'   => $thread->id,
                        'to'          => $thread->getToFirst(),
                        'cc'          => $thread->getCcArray(),
                        'bcc'         => $thread->getBccArray(),
                        'body'        => $thread->body,
                        'is_forward'  => (int)$thread->isForward(),
                        'attachments' => $attachments,
                    ];
                    $response['status'] = 'success';
                }
                break;

            // Load attachments from all threads in conversation 
            // when forwarding or creating a new conversation.
            case 'load_attachments':
                $conversation = Conversation::find($request->conversation_id);
                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                } else {
                    if (!$user->can('view', $conversation)) {
                        $response['msg'] = __('Not enough permissions');
                    }
                }

                if (!$response['msg']) {
                    // Build attachments list.
                    $attachments = [];

                    if ($conversation->has_attachments) {
                        foreach ($conversation->threads as $thread) {
                            if ($thread->has_attachments) {
                                foreach ($thread->attachments as $attachment) {
                                    if ($request->is_forwarding == 'true') {
                                        $attachment_copy = $attachment->duplicate($thread->id);
                                    } else {
                                        $attachment_copy = $attachment;
                                    }

                                    $attachments[] = [
                                        'id'   => $attachment_copy->id,
                                        'name' => $attachment_copy->file_name,
                                        'size' => $attachment_copy->size,
                                        'url'  => $attachment_copy->url(),
                                    ];
                                }
                            }
                        }
                    }

                    $response['data'] = [
                        'attachments' => $attachments,
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
                    $mailbox_user = $user->mailboxesWithSettings()->where('mailbox_id', $request->mailbox_id)->first();
                    if (!$mailbox_user) {
                        // Admin may not be connected to the mailbox yet
                        $user->mailboxes()->attach($request->mailbox_id);
                        // $mailbox_user = new MailboxUser();
                        // $mailbox_user->mailbox_id = $mailbox->id;
                        // $mailbox_user->user_id = $user->id;
                        $mailbox_user = $user->mailboxesWithSettings()->where('mailbox_id', $request->mailbox_id)->first();
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
                    $response['msg'] = __('Not enough permissions');
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
                        $conversation->addToFolder(Folder::TYPE_STARRED, $user->id);
                    } else {
                        $conversation->removeFromFolder(Folder::TYPE_STARRED, $user->id);
                    }
                    Conversation::clearStarredByUserCache($user->id, $conversation->mailbox_id);
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
                    $folder_id = $conversation->getCurrentFolder();

                    $conversation->deleteToFolder($user);

                    $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $folder_id]);

                    $response['status'] = 'success';

                    \Session::flash('flash_success_floating', __('Conversation deleted'));
                }
                break;

            // Delete conversation forever
            case 'delete_conversation_forever':
                $conversation = Conversation::find($request->conversation_id);
                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                } elseif (!$user->can('delete', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $folder_id = $conversation->getCurrentFolder();
                    $mailbox = $conversation->mailbox;

                    $conversation->deleteForever();

                    // Recalculate only old and new folders
                    $mailbox->updateFoldersCounters();

                    $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $folder_id]);

                    $response['status'] = 'success';

                    \Session::flash('flash_success_floating', __('Conversation deleted'));
                }
                break;

            // Restore conversation
            case 'restore_conversation':
                $conversation = Conversation::find($request->conversation_id);
                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                } elseif (!$user->can('delete', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $folder_id = $conversation->folder_id;
                    $conversation->state = Conversation::STATE_PUBLISHED;
                    $conversation->user_updated_at = date('Y-m-d H:i:s');
                    $conversation->updateFolder();
                    $conversation->save();

                    // Create lineitem thread
                    $thread = new Thread();
                    $thread->conversation_id = $conversation->id;
                    $thread->user_id = $conversation->user_id;
                    $thread->type = Thread::TYPE_LINEITEM;
                    $thread->state = Thread::STATE_PUBLISHED;
                    $thread->status = Thread::STATUS_NOCHANGE;
                    $thread->action_type = Thread::ACTION_TYPE_RESTORE_TICKET;
                    $thread->source_via = Thread::PERSON_USER;
                    // todo: this need to be changed for API
                    $thread->source_type = Thread::SOURCE_TYPE_WEB;
                    $thread->customer_id = $conversation->customer_id;
                    $thread->created_by_user_id = $user->id;
                    $thread->save();

                    // Recalculate only old and new folders
                    $conversation->mailbox->updateFoldersCounters();

                    $response['status'] = 'success';

                    \Session::flash('flash_success_floating', __('Conversation restored'));
                }
                break;

            // Load data to edit thread.
            case 'load_edit_thread':
                $thread = Thread::find($request->thread_id);
                if (!$thread) {
                    $response['msg'] = __('Thread not found');
                } elseif (!$user->can('edit', $thread)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $data = [
                        'thread' => $thread
                    ];
                    $response['html'] = \View::make('conversations/partials/edit_thread')->with($data)->render();

                    $response['status'] = 'success';
                }
                break;

            // Load data to edit thread.
            case 'save_edit_thread':
                $thread = Thread::find($request->thread_id);
                if (!$thread) {
                    $response['msg'] = __('Conversation not found');
                } elseif (!$user->can('edit', $thread)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    if (!$thread->body_original) {
                        $thread->body_original = $thread->body;
                    }
                    $thread->body = $request->body;
                    $thread->edited_by_user_id = $user->id;
                    $thread->edited_at = date('Y-m-d H:i:s');
                    $response['body'] = $thread->getCleanBody();

                    if (strip_tags($response['body'])) {
                        $thread->save();

                        $response['status'] = 'success';
                    } else {
                        $response['msg'] = __('Message cannot be empty');
                    }
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
                        if ((int) $new_user_id != -1 && !$conversation->mailbox->userHasAccess($new_user_id)) {
                            continue;
                        }

                        $conversation->changeUser($new_user_id, $user);
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

                        $conversation->changeStatus($new_status, $user);
                    }

                    $response['status'] = 'success';
                    // Flash
                    $flash_message = __('Status updated');
                    \Session::flash('flash_success_floating', $flash_message);

                    $response['msg'] = __('Status updated');
                }
                break;

            // Delete converations.
            case 'bulk_delete_conversation':
                // At first, check if this user is able to delete conversations
                if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission(\App\User::PERM_DELETE_CONVERSATIONS)) {
                    $response['msg'] = __('Not enough permissions');
                    //\Session::flash('flash_success_floating', __('Conversations deleted'));

                    return \Response::json($response);
                }

                $conversations = Conversation::findMany($request->conversation_id);
                $mailboxes_to_recalculate = [];

                foreach ($conversations as $conversation) {
                    if (!$user->can('delete', $conversation)) {
                        continue;
                    }

                    if ($conversation->state != Conversation::STATE_DELETED) {
                        // Move to Deleted folder.
                        $conversation->state = Conversation::STATE_DELETED;
                        $conversation->user_updated_at = date('Y-m-d H:i:s');
                        $conversation->updateFolder();
                        $conversation->save();

                        // Create lineitem thread
                        $thread = new Thread();
                        $thread->conversation_id = $conversation->id;
                        $thread->user_id = $conversation->user_id;
                        $thread->type = Thread::TYPE_LINEITEM;
                        $thread->state = Thread::STATE_PUBLISHED;
                        $thread->status = Thread::STATUS_NOCHANGE;
                        $thread->action_type = Thread::ACTION_TYPE_DELETED_TICKET;
                        $thread->source_via = Thread::PERSON_USER;
                        $thread->source_type = Thread::SOURCE_TYPE_WEB;
                        $thread->customer_id = $conversation->customer_id;
                        $thread->created_by_user_id = $user->id;
                        $thread->save();

                        // Remove conversation from drafts folder.
                        $conversation->removeFromFolder(Folder::TYPE_DRAFTS);
                    } else {
                        // Delete forever
                        $conversation->deleteForever();
                    }

                    if (!array_key_exists($conversation->mailbox_id, $mailboxes_to_recalculate)) {
                        $mailboxes_to_recalculate[$conversation->mailbox_id] = $conversation->mailbox;
                    }
                }
                // Recalculate folders counters for mailboxes.
                foreach ($mailboxes_to_recalculate as $mailbox) {
                    $mailbox->updateFoldersCounters();
                }

                $response['status'] = 'success';
                \Session::flash('flash_success_floating', __('Conversations deleted'));
                break;

            // Delete converations.
            case 'empty_trash':
                // At first, check if this user is able to delete conversations
                if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission(\App\User::PERM_DELETE_CONVERSATIONS)) {
                    $response['msg'] = __('Not enough permissions');
                    return \Response::json($response);
                }

                $folder = Folder::find($request->folder_id ?? '');

                if (!$folder) {
                    $response['msg'] = __('Folder not found');
                }

                if (!$response['msg']) {
                    $conversation_ids = Conversation::where('folder_id', $folder->id)->pluck('id')->toArray();
                    Conversation::deleteConversationsForever($conversation_ids);
                    $folder->updateCounters();
                }

                $response['status'] = 'success';
                \Session::flash('flash_success_floating', __('Conversations deleted'));
                break;

            // Move conversation to another mailbox.
            case 'conversation_move':
                $conversation = Conversation::find($request->conversation_id);

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && !$user->can('update', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }
                if (!$response['msg'] && !$conversation->mailbox->userHasAccess($user->id)) {
                    $response['msg'] = __('Not enough permissions');
                }

                $mailbox = null;
                if (!$response['msg']) {
                    if (!empty($request->mailbox_email)) {
                        $mailbox = Mailbox::where('email', $request->mailbox_email)->first();
                    } else {
                        $mailbox = Mailbox::find($request->mailbox_id);
                    }

                    if (!$mailbox) {
                        $response['msg'] = __('Mailbox not found');
                    }
                }

                if (!$response['msg']) {
                    $prev_folder_id = Conversation::getFolderParam();
                    $prev_mailbox_id = $conversation->mailbox_id;

                    $conversation->moveToMailbox($mailbox, $user);

                    // If user does not have access to the new mailbox,
                    // redirect to the previous mailbox.
                    if (!$mailbox->userHasAccess($user->id)) {
                        if (!empty($prev_folder_id)) {
                            $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $prev_mailbox_id, 'folder_id' => $prev_folder_id]);
                        } else {
                            $response['redirect_url'] = route('mailboxes.view', ['id' => $prev_mailbox_id]);
                        }
                    }

                    $response['status'] = 'success';
                    \Session::flash('flash_success_floating', __('Conversation moved'));
                }

                break;

            // Merge conversations
            case 'conversation_merge':
                $conversation = Conversation::find($request->conversation_id);

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && !$user->can('view', $conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                $merge_conversation = Conversation::find($request->merge_conversation_id);

                if (!$merge_conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && !$user->can('view', $merge_conversation)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    $conversation->mergeConversations($merge_conversation, $user);

                    $response['status'] = 'success';
                    \Session::flash('flash_success_floating', __('Conversations merged'));
                }

                break;

            // Follow conversation
            case 'follow':
            case 'unfollow':
                $conversation = Conversation::find($request->conversation_id);

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && !$conversation->mailbox->userHasAccess($user->id)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if ($request->action == 'follow') {
                    try {
                        $follower = new Follower();
                        $follower->conversation_id = $request->conversation_id;
                        $follower->user_id = $user->id;
                        $follower->save();
                    } catch (\Exception $e) {
                        // Already exists
                    }
                } else {
                    $follower = Follower::where('conversation_id', $request->conversation_id)
                        ->where('user_id', $user->id)
                        ->first();
                    if ($follower) {
                        $follower->delete();
                    }
                }

                if (!$response['msg']) {
                    $response['status'] = 'success';
                    if ($request->action == 'follow') {
                        $response['msg_success'] = __('Following');
                    } else {
                        $response['msg_success'] = __('Unfollowed');
                    }
                }

                break;

            case 'update_subject':
                $conversation = Conversation::find($request->conversation_id);

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && !$conversation->mailbox->userHasAccess($user->id)) {
                    $response['msg'] = __('Not enough permissions');
                }

                $subject = $request->value ?? '';
                $subject = trim($subject);

                if (!$response['msg'] && $subject) {
                    $conversation->subject = $subject;
                    $conversation->save();

                    $response['status'] = 'success';
                }

                break;

            case 'merge_search':
                $conversation = Conversation::where('number', $request->number)->first();

                if (!$conversation) {
                    $response['msg'] = __('Conversation not found');
                }
                if (!$response['msg'] && !$user->can('view', $conversation)) {
                    $response['msg'] = __('Conversation not found');
                }

                if (!$response['msg']) {
                    $response['html'] = \View::make('conversations/partials/merge_search_result')->with([
                            'conversation' => $conversation
                        ])->render();
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
            case 'move_conv':
                return $this->ajaxHtmlMoveConv();
            case 'merge_conv':
                return $this->ajaxHtmlMergeConv();
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

        // Try to fetch original body by imap
        $body_preview = $thread->body;
        $body_imap = $thread->fetchBody();
        if ($body_imap) {
            $body_preview = $body_imap;
        }

        return view('conversations/ajax_html/show_original', [
            'thread' => $thread,
            'body_preview' => $body_preview,
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
     * Move conversation to other mailbox.
     */
    public function ajaxHtmlMoveConv()
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

        return view('conversations/ajax_html/move_conv', [
            'conversation' => $conversation,
            'mailboxes'    => $user->mailboxesCanView(),
        ]);
    }
    
    /**
     * Merge conversations.
     */
    public function ajaxHtmlMergeConv()
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
            \Helper::denyAccess();
        }

        $prev_conversations = [];

        if ($conversation->customer_id) {
            $prev_conversations = $conversation->mailbox->conversations()
                                    ->where('customer_id', $conversation->customer_id)
                                    ->where('id', '<>', $conversation->id)
                                    ->where('status', '!=', Conversation::STATUS_SPAM)
                                    ->where('state', Conversation::STATE_PUBLISHED)
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(500);
        }

        return view('conversations/ajax_html/merge_conv', [
            'conversation' => $conversation,
            'prev_conversations' => $prev_conversations,

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
            // todo: use $user->mailboxSettings()
            $after_send = $conversation->mailbox->getUserSettings($user->id)->after_send;
        }
        if (!empty($after_send)) {
            switch ($after_send) {
                case MailboxUser::AFTER_SEND_STAY:
                default:
                    $redirect_url = $conversation->url();
                    break;
                case MailboxUser::AFTER_SEND_FOLDER:
                    $folder_id = Conversation::getFolderParam();
                    if (!$folder_id) {
                        $folder_id = $conversation->folder_id;
                    }
                    $redirect_url = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $folder_id]);
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
        //$mailbox = Mailbox::find($request->mailbox_id);
        $folder = null;
        $conversations = [];

        if (!$response['msg']) {
            $folder = \Eventy::filter('conversations.ajax_pagination_folder', Folder::find($request->folder_id), $request, $response, $user);
            if (!$folder) {
                $response['msg'] = __('Folder not found');
            }
        }
        if (!$response['msg'] && !$user->can('view', $folder)) {
            $response['msg'] = __('Not enough permissions');
        }

        // We should not use mailbox_id from the request, as it can be changed.
        if (!$response['msg'] && !$user->can('view', $folder->mailbox)) {
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
        $conversations = [];
        $customers = [];

        $mode = $this->getSearchMode($request);

        // Search query
        $q = $this->getSearchQuery($request);

        // Filters.
        $filters = $this->getSearchFilters($request);
        $filters_data = [];
        // Modify filters is needed.
        if (!empty($filters['customer'])) {
            // Get customer name.
            $filters_data['customer'] = Customer::find($filters['customer']);
        }
        //$filters = \Eventy::filter('search.filters', $filters, $filters_data, $mode, $q);

        // Remember recent query.
        $recent_search_queries = session('recent_search_queries') ?? [];
        if ($q && !in_array($q, $recent_search_queries)) {
            array_unshift($recent_search_queries, $q);
            $recent_search_queries = array_slice($recent_search_queries, 0, 4);
            session()->put('recent_search_queries', $recent_search_queries);
        }

        $conversations = [];
        if (\Eventy::filter('search.is_needed', true, 'conversations')) {
            $conversations = $this->searchQuery($request, $user, $q, $filters);
        }
        $customers = $this->searchCustomers($request, $user);

        // Dummy folder
        $folder = $this->getSearchFolder($conversations);

        // List of available filters.
        if ($mode == Conversation::SEARCH_MODE_CONV) {
            $filters_list = \Eventy::filter('search.filters_list', Conversation::$search_filters, $mode, $filters, $q);
        } else {
            $filters_list = \Eventy::filter('search.filters_list_customers', Customer::$search_filters, $mode, $filters, $q);
        }

        $mailboxes = \Cache::remember('search_filter_mailboxes_'.$user->id, 5, function () use ($user) {
            return $user->mailboxesCanView();
        });
        $users = \Cache::remember('search_filter_users_'.$user->id, 5, function () use ($user, $mailboxes) {
            return $user->whichUsersCanView($mailboxes);
        });

        return view('conversations/search', [
            'folder'        => $folder,
            'q'             => $request->q,
            'filters'       => $filters,
            'filters_list'  => $filters_list,
            'filters_data'  => $filters_data,
            //'filters_list_all'  => $filters_list_all,
            'mode'          => $mode,
            'conversations' => $conversations,
            'customers'     => $customers,
            'recent'        => session('recent_search_queries'),
            'users'         => $users,
            'mailboxes'     => $mailboxes,
        ]);
    }

    /**
     * Search conversations.
     */
    public function getSearchMode($request)
    {
        $mode = Conversation::SEARCH_MODE_CONV;
        if (!empty($request->mode) && $request->mode == Conversation::SEARCH_MODE_CUSTOMERS) {
            $mode = Conversation::SEARCH_MODE_CUSTOMERS;
        }
        return $mode;
    }

    /**
     * Search conversations.
     */
    public function searchQuery($request, $user, $q, $filters)
    {
        // Get IDs of mailboxes to which user has access
        $mailbox_ids = $user->mailboxesIdsCanView();

        // Filters
        //$filters = $request->f ?? [];

        // Search query
        //$q = $this->getSearchQuery($request);

        // Like is case insensitive.
        $like = '%'.mb_strtolower($q).'%';

        $query_conversations = Conversation::select('conversations.*')
            // https://github.com/laravel/framework/issues/21242
            // https://github.com/laravel/framework/pull/27675
            ->groupby('conversations.id')
            ->whereIn('conversations.mailbox_id', $mailbox_ids)
            ->join('threads', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });
        if ($q) {
            $query_conversations->where(function ($query) use ($like, $filters, $q) {
                $query->where('conversations.subject', 'like', $like)
                    ->orWhere('conversations.customer_email', 'like', $like)
                    ->orWhere('conversations.number', (int)$q)
                    ->orWhere('conversations.id', (int)$q)
                    ->orWhere('threads.body', 'like', $like)
                    ->orWhere('threads.from', 'like', $like)
                    ->orWhere('threads.to', 'like', $like)
                    ->orWhere('threads.cc', 'like', $like)
                    ->orWhere('threads.bcc', 'like', $like);

                $query = \Eventy::filter('search.conversations.or_where', $query, $filters, $q);
            });
        }

        // Apply search filters.
        if (!empty($filters['assigned'])) {
            $query_conversations->where('conversations.user_id', $filters['assigned']);
        }
        if (!empty($filters['customer'])) {
            $customer_id = $filters['customer'];
            $query_conversations->where(function ($query) use ($customer_id) {
                $query->where('conversations.customer_id', '=', $customer_id)
                    ->orWhere('threads.created_by_customer_id', '=', $customer_id);
            });
        }
        if (!empty($filters['mailbox'])) {
            $query_conversations->where('conversations.mailbox_id', '=', $filters['mailbox']);
        }
        if (!empty($filters['status'])) {
            if (count($filters['status']) == 1) {
                // = is faster than IN.
                $query_conversations->where('conversations.status', '=', $filters['status'][0]);
            } else {
                $query_conversations->whereIn('conversations.status', $filters['status']);
            }
        }
        if (!empty($filters['state'])) {
            if (count($filters['state']) == 1) {
                // = is faster than IN.
                $query_conversations->where('conversations.state', '=', $filters['state'][0]);
            } else {
                $query_conversations->whereIn('conversations.state', $filters['state']);
            }
        }
        if (!empty($filters['subject'])) {
            $query_conversations->where('conversations.subject', 'like', '%'.mb_strtolower($filters['subject']).'%');
        }
        if (!empty($filters['attachments'])) {
            $has_attachments = ($filters['attachments'] == 'yes' ? true : false);
            $query_conversations->where('conversations.has_attachments', '=', $has_attachments);
        }
        if (!empty($filters['type'])) {
            $query_conversations->where('conversations.has_attachments', '=', $filters['type']);
        }
        if (!empty($filters['body'])) {
            $query_conversations->where('threads.body', 'like', '%'.mb_strtolower($filters['body']).'%');
        }
        if (!empty($filters['number'])) {
            $query_conversations->where('conversations.number', '=', $filters['number']);
        }
        if (!empty($filters['following'])) {
            if ($filters['following'] == 'yes') {
                $query_conversations->join('followers', function ($join) {
                    $join->on('followers.conversation_id', '=', 'conversations.id');
                    $join->where('followers.user_id', auth()->user()->id);
                });
            }
        }
        if (!empty($filters['id'])) {
            $query_conversations->where('conversations.id', '=', $filters['id']);
        }
        if (!empty($filters['after'])) {
            $query_conversations->where('conversations.created_at', '>=', date('Y-m-d 00:00:00', strtotime($filters['after'])));
        }
        if (!empty($filters['before'])) {
            $query_conversations->where('conversations.created_at', '<=', date('Y-m-d 23:59:59', strtotime($filters['before'])));
        }

        $query_conversations = \Eventy::filter('search.conversations.apply_filters', $query_conversations, $filters, $q);

        $query_conversations->orderBy('conversations.last_reply_at', 'DESC');

        return $query_conversations->paginate(Conversation::DEFAULT_LIST_SIZE);
    }

    /**
     * Get and format search query.
     */
    public function getSearchQuery($request)
    {
        $q = '';
        if (!empty($request->q)) {
            $q = $request->q;
        } elseif (!empty($request->filter) && !empty($request->filter['q'])) {
            $q = $request->filter['q'];
        }

        return trim($q);
    }

    /**
     * Get and format search filters.
     */
    public function getSearchFilters($request)
    {
        $filters = [];

        if (!empty($request->f)) {
            $filters = $request->f;
        } elseif (!empty($request->filter) && !empty($request->filter['f'])) {
            $filters = $request->filter['f'];
        }

        foreach ($filters as $filter => $value) {
            switch ($filter) {
                case 'after':
                case 'before':
                    $value = date('Y-m-d', strtotime($value));
                    break;
            }
        }

        $filters = \Eventy::filter('search.filters', $filters, $this->getSearchMode($request), $request);

        return $filters;
    }

    /**
     * Search conversations.
     */
    public function searchCustomers($request, $user)
    {
        // Get IDs of mailboxes to which user has access
        $mailbox_ids = $user->mailboxesIdsCanView();

        // Filters
        $filters = $this->getSearchFilters($request);;

        // Search query
        $q = $this->getSearchQuery($request);

        // Like is case insensitive.
        $like = '%'.mb_strtolower($q).'%';

        $query_customers = Customer::select(['customers.*', 'emails.email'])
            ->groupby('customers.id')
            ->leftJoin('emails', function ($join) {
                $join->on('customers.id', '=', 'emails.customer_id');
            })
            ->where(function ($query) use ($like) {
                $query->where('customers.first_name', 'like', $like)
                    ->orWhere('customers.last_name', 'like', $like)
                    ->orWhere('customers.company', 'like', $like)
                    ->orWhere('customers.job_title', 'like', $like)
                    ->orWhere('customers.phones', 'like', $like)
                    ->orWhere('customers.websites', 'like', $like)
                    ->orWhere('customers.social_profiles', 'like', $like)
                    ->orWhere('customers.address', 'like', $like)
                    ->orWhere('customers.city', 'like', $like)
                    ->orWhere('customers.state', 'like', $like)
                    ->orWhere('customers.zip', 'like', $like)
                    ->orWhere('customers.zip', 'like', $like)
                    ->orWhere('emails.email', 'like', $like);
            });

        if (!empty($filters['mailbox'])) {
            $query_customers->join('conversations', function ($join) use ($filters) {
                $join->on('conversations.customer_id', '=', 'customers.id');
                //$join->on('conversations.mailbox_id', '=', $filters['mailbox']);
            });
            $query_customers->where('conversations.mailbox_id', '=', $filters['mailbox']);
        }

        $query_customers = \Eventy::filter('search.customers.apply_filters', $query_customers, $filters, $q);

        return $query_customers->paginate(50);
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
        if (array_key_exists('q', $request->filter)) {
            // Search
            $conversations = $this->searchQuery($request, $user, $this->getSearchQuery($request), $this->getSearchFilters($request));
        } else {
            // Filters
            $conversations = $this->conversationsFilterQuery($request, $user);
        }

        $response['status'] = 'success';

        $response['html'] = view('conversations/conversations_table', [
            'conversations' => $conversations,
            'params' => $request->params ?? [],
        ])->render();

        return $response;
    }

    /**
     * Filter conversations according to the request.
     */
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
     * Process attachments on reply, new conversation, saving draft and forwarding.
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

        // If forwarding cas been undone, we need to remove newly created conversation.
        // No need to remove notifications, as they won't work if conversation does not exist.
        if ($thread->isForward()) {
            $forwarded_conversation = $thread->getForwardChildConversation();
            if ($forwarded_conversation) {
                $forwarded_conversation->threads()->delete();
                // todo: maybe perform soft delete of the conversation.
                $forwarded_conversation->delete();
            }
        }

        return redirect()->away($conversation->url($folder_id, null, ['show_draft' => $thread->id]));
    }

    /**
     * Find or create customer when creating a Phone conversation.
     */
    public function processPhoneCustomer($request)
    {
        $customer_data = [];
        $customer_email = '';
        $customer = null;

        // Check to prevent creating empty customers.
        if (trim($request->name) || trim($request->phone)) {
            $request->name = trim($request->name);
            $request->phone = trim($request->phone);

            $name_parts = explode(' ', $request->name);
            $customer_data['first_name'] = $name_parts[0];
            if (!empty($name_parts[1])) {
                $customer_data['last_name'] = $name_parts[1];
            }
            $customer_data['phones'] = [$request->phone];
        }

        // Check if name field contains ID of the customer.
        if (!$request->customer_id && is_numeric($request->name)) {
            // Try to find customer by ID.
            $customer = Customer::find($request->name);
        }

        if (!$customer && $request->to_email) {
            // Try to get customer by email.
            $customer = Customer::getByEmail($request->to_email);
            if ($customer) {
                $customer_email = $request->to_email;
            }
        }

        // Try to find customer by phone.
        if (!$customer && $request->phone) {
            $customer = Customer::findByPhone($request->phone);
            if ($customer) {
                $customer_email = $customer->getMainEmail();
            }
        }

        if (!$customer) {
            // Create customer with passed name, email and phone
            if (Email::sanitizeEmail($request->to_email)) {
                $customer_email = $request->to_email;
                // If new email entered, attach email to the current customer
                // instead of creating a new customer
                if ($request->customer_id) {
                    $customer = Customer::find($request->customer_id);
                    if ($customer) {
                        // Add email to customer.
                        $customer->addEmail($customer_email, true);
                    } else {
                        $customer = Customer::create($customer_email, $customer_data);
                    }
                } else {
                    $customer = Customer::create($customer_email, $customer_data);
                }
            } elseif ($customer_data) {
                if ($request->customer_id) {
                    $customer = Customer::find($request->customer_id);
                    if ($customer) {
                        $customer->setData($customer_data, false, true);
                    }
                }

                if (!$customer) {
                    $customer = Customer::createWithoutEmail($customer_data);
                }
            }
        } else {
            $customer->setData($customer_data, false, true);
            // Add email to customer.
            if (Email::sanitizeEmail($request->to_email)) {
                $customer->addEmail($request->to_email, true);
            }
        }

        return [
            'customer' => $customer,
            'customer_email' => $customer_email,
        ];
    }
}
