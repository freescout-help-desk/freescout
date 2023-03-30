<?php

namespace Modules\EndUserPortal\Http\Controllers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use App\Events\ConversationCustomerChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Validator;

class EndUserPortalController extends Controller
{
    /**
     * Settings.
     */
    public function settings($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        $meta_settings = $mailbox->meta['eup'] ?? [];

        $default_settings = \EndUserPortal::getDefaultPortalSettings();

        $settings = [
            'existing' => $meta_settings['existing'] ?? $default_settings['existing'],
            'text_submit' => $meta_settings['text_submit'] ?? $default_settings['text_submit'],
            'footer' => $meta_settings['footer'] ?? $default_settings['footer'],
            'consent' => $meta_settings['consent'] ?? $default_settings['consent'],
            'privacy' => $meta_settings['privacy'] ?? $default_settings['privacy'],
        ];

        $widget_settings = \EndUserPortal::getWidgetSettings($mailbox_id);

        if (!empty($widget_settings)) {
            //$widget_settings['url'] = route('enduserportal.widget_form', ['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox_id, \EndUserPortal::WIDGET_SALT)]);
            $widget_settings['id'] = \EndUserPortal::encodeMailboxId($mailbox_id, \EndUserPortal::WIDGET_SALT);
        }

        // Test prefilling widget fields.
        $prefill_test = '';
        // $prefill_test = [
        //     'name' => 'Widget John',
        //     'email' => 'widget-john@example.org',
        //     'message' => 'Widget Text',
        // ];

        return view('enduserportal::settings', [
            'mailbox'  => $mailbox,
            'settings' => $settings,
            'locales'  => \Helper::getAllLocales(),
            'widget_settings' => $widget_settings,
            'prefill_test'   => $prefill_test,
        ]);
    }

    /**
     * Settings save.
     */
    public function settingsSave(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        if (!empty($request->eup_action) && $request->eup_action == 'save_settings') {
            $settings = $request->settings;
            if ($settings['text_submit'] == \EndUserPortal::getDefaultPortalSettings('text_submit')) {
                unset($settings['text_submit']);
            }
            $mailbox->setMetaParam('eup', $settings);
            $mailbox->save();

            \Session::flash('flash_success_floating', __('Settings updated'));
        }

        if (!empty($request->eup_action) == 'save_widget') {
            $settings = $request->all();

            unset($settings['_token']);
            unset($settings['eup_action']);

            if (array_key_exists('locale', $settings) && !$settings['locale']) {
                unset($settings['locale']);
            }
            
            // if (empty($settings['title'])) {
            //     $settings['title'] = __('Contact us');
            // }
            if (empty($settings['color'])) {
                $settings['color'] = '#0068bd';
            }

            try {
                \EndUserPortal::saveWidgetSettings($mailbox_id, $settings);

                \Session::flash('flash_success_floating', __('Settings updated'));
            } catch (\Exception $e) {
                \Session::flash('flash_error_floating', $e->getMessage());
            }
        }

        return redirect()->route('enduserportal.settings', ['mailbox_id' => $mailbox_id]);
    }

    /**
     * Login.
     */
    public function login(Request $request, $mailbox_id = null)
    {
        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }

        if (\EndUserPortal::authCustomer()) {
            return redirect()->route('enduserportal.tickets', ['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox->id)]);
        }

        return view('enduserportal::login', [
            'mailbox' => $mailbox,
        ]);
    }

    /**
     * Process log in form.
     */
    public function loginProcess(Request $request, $mailbox_id = null)
    {
        $result = [
            'result' => 'success',
            'message' => '',
        ];

        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('enduserportal.login', ['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox->id)])
                        ->withErrors($validator)
                        ->withInput();
        }

        $email = \App\Email::sanitizeEmail($request->email);

        $meta_settings = $mailbox->meta['eup'] ?? [];

        // Custom must exist in the DB in order to login.
        if (!empty($meta_settings['existing'])) {
            $customer = Customer::getByEmail($email);

            if (!$customer) {
                $result['result'] = 'error';
                $result['message'] = __('There is no tickets belonging to the specified email address.');
            }
        } else {

            $customer = Customer::create($email);
            if (!$customer) {
                $result['result'] = 'error';
                $result['message'] = __('Invalid Email Address');
            }
        }

        // Send email to the customer.
        if (!$result['message']) {

            try {
                \MailHelper::setMailDriver($mailbox);

                \Mail::to([['email' => $request->email]])->send(new \Modules\EndUserPortal\Mail\Login($mailbox, $customer));

                $result['message'] = __('Email with the authentication link has been sent to <strong>:email</strong>', ['email' => htmlspecialchars($request->email)]);
            } catch (\Exception $e) {
                // We come here in case SMTP server unavailable for example.
                // But Mail does not throw an exception if you specify incorrect SMTP details for example.
                \Helper::logException($e, '[End-User Portal');
                $result['result'] = 'error';
                $result['message'] = __('Error occured sending email to <strong>:email</strong>', ['email' => htmlspecialchars($request->email)]);
            }

            if (\Mail::failures()) {
                $result['result'] = 'error';
                $result['message'] = __('Error occured sending email to <strong>:email</strong>', ['email' => htmlspecialchars($request->email)]);
            }
        }

        return view('enduserportal::login', [
            'mailbox' => $mailbox,
            'result' => $result,
        ]);
    }

    /**
     * Login from email.
     */
    public function loginFromEmail(Request $request, $mailbox_id, $customer_id)
    {
        $result = [
            'result' => 'error',
            'message' => __('Invalid authentication link'),
        ];

        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }

        // Authenticate customer.
        $customer_id = decrypt($customer_id);
        $auth_result = \EndUserPortal::authenticate($customer_id, $mailbox->id);
        if ($auth_result) {
            return $auth_result;
        }

        return view('enduserportal::login', [
            'mailbox' => $mailbox,
            'result' => $result,
        ]);
    }

    /**
     * Logout.
     */
    public function logout(Request $request, $mailbox_id = null)
    {
        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }

        return redirect()->route('enduserportal.submit', ['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox->id)])
                        ->withCookie(cookie('enduserportal_auth', null, 0));
    }

    /**
     * Tickets.
     */
    public function tickets(Request $request, $mailbox_id = null)
    {
        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }

        $customer = \EndUserPortal::authCustomer();
        if (!$customer) {
            return redirect()->route('enduserportal.login', ['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox->id)]);
        }

        $tickets = Conversation::where('mailbox_id', $mailbox->id)
            ->where('customer_id', $customer->id)
            //->orderBy('last_reply_at', 'desc')
            ->get();

        // Remove unneeded types.
        //TYPE_EMAIL
        foreach ($tickets as $i => $ticket) {
            if (!in_array($ticket->type, [Conversation::TYPE_EMAIL/*, Conversation::TYPE_CHAT*/])) {
                unset($tickets[$i]);
            }
        }

        // Load threads for conversations to determine unread threads
        // and sort tickets
        if (count($tickets)) {
            $conversation_ids = $tickets->pluck('id')->unique()->toArray();

            $threads = Thread::whereIn('conversation_id', $conversation_ids)
                ->whereIn('type', [Thread::TYPE_MESSAGE, Thread::TYPE_CUSTOMER])
                ->get();
            
            $threads = $threads->sortByDesc('id');
            
            $send_later_active = \Module::isActive('sendlater');

            if ($threads) {
                foreach ($tickets as $i => $ticket) {
                    $tickets[$i]->has_new_replies = false;
                    foreach ($threads as $thread) {
                        if ($ticket->id == $thread->conversation_id) {

                            // Skip scheduled.
                            if ($send_later_active 
                                && $ticket->scheduled
                                && $thread->getMeta(\SendLater::META_NAME) !== null
                            ) {
                                continue;
                            }
                            
                            // Update preview as preview in DB contains previews of notes too.
                            $tickets[$i]->preview = \Helper::textPreview($thread->body, Conversation::PREVIEW_MAXLENGTH);

                            if ($thread->type == Thread::TYPE_MESSAGE && !$thread->opened_at) {
                                $tickets[$i]->has_new_replies = true;
                            }

                            break;
                        }
                    }
                }
            }
        }

        //$tickets = $tickets->sortBy([['has_new_replies', 'desc']]);
        $tickets = $tickets->sortByDesc(function ($ticket, $key) {
            return (int)$ticket->has_new_replies.$ticket->last_reply_at;
        });

        return view('enduserportal::tickets', [
            'mailbox' => $mailbox,
            'tickets' => $tickets,
        ]);
    }

    /**
     * View ticket.
     */
    public function ticket(Request $request, $mailbox_id, $conversation_id)
    {
        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }

        $customer = \EndUserPortal::authCustomer();
        if (!$customer) {
            return redirect()->route('enduserportal.login', ['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox->id)]);
        }

        $conversation = Conversation::find($conversation_id);
        if ($conversation->customer_id != $customer->id) {
            abort(404);
        }

        // Mark threads as open.
        $threads = $conversation->getReplies();

        $now = date('Y-m-d H:i:s');

        $send_later_active = \Module::isActive('sendlater');

        foreach ($threads as $i => $thread) {
            // Skip scheduled.
            if ($send_later_active 
                && $conversation->scheduled
                && $thread->getMeta(\SendLater::META_NAME) !== null
            ) {
                $threads->forget($i);
            }
            if ($thread->type == Thread::TYPE_MESSAGE && !$thread->opened_at) {
                $thread->opened_at = $now;
                $thread->save();
            }
        }

        return view('enduserportal::ticket', [
            'mailbox' => $mailbox,
            'conversation' => $conversation,
            'threads' => $threads,
        ]);
    }

    /**
     * Submit a ticket.
     */
    public function submit(Request $request, $mailbox_id = null)
    {
        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }
        $conversation = new Conversation();
        $conversation->body = '';
        $conversation->mailbox = $mailbox;

        return view('enduserportal::submit', [
            'mailbox' => $mailbox,
            'conversation' => $conversation,
            'thread' => new Thread(),
        ]);
    }

    // todo: mailbox should be active.
    public function processMailboxId($mailbox_id, $extra_salt = '')
    {
        try {
            $mailbox_id = \EndUserPortal::decodeMailboxId($mailbox_id, $extra_salt);

            if ($mailbox_id) {
                $mailbox = Mailbox::findOrFail($mailbox_id);
            } /* else {
                $mailbox = Mailbox::first();
            }*/
        } catch (\Exception $e) {
            return null;
        }

        if (empty($mailbox)) {
            return null;
        }

        return $mailbox;
    }

    /**
     * Process submitted ticket.
     */
    public function submitProcess(Request $request, $mailbox_id)
    {
        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }

        if (!\EndUserPortal::authCustomer()) {
            $rules = [
                'email' => 'required|email',
                'message'  => 'required|string',
            ];
        } else {
            $rules = [
                'message'  => 'required|string',
            ];
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails() || !empty($request->age)) {
            return redirect()->route('enduserportal.submit', ['mailbox_id' => $mailbox_id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $new_conversation = $this->submitMessage($request, $mailbox->id);

        $ticket_id = '';
        if ($new_conversation) {
            $ticket_id = $new_conversation->id;
        }

        return redirect()->route('enduserportal.submit', ['mailbox_id' => $mailbox_id, 'success' => 1, 'ticket_id' => $ticket_id]);
    }

    /**
     * Reply to conversation.
     */
    public function submitReply(Request $request, $mailbox_id, $conversation_id)
    {
        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }

        if (!\EndUserPortal::authCustomer()) {
            return redirect()->route('enduserportal.login', ['mailbox_id' => \EndUserPortal::encodeMailboxId($mailbox->id)]);
        }

        $rules = [
            'message'  => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails() || !empty($request->age)) {
            return redirect()->route('enduserportal.ticket', ['mailbox_id' => $mailbox_id, 'conversation_id' => $conversation_id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $this->submitMessage($request, $mailbox->id);

        return redirect()->route('enduserportal.ticket', ['mailbox_id' => $mailbox_id, 'conversation_id' => $conversation_id, 'success' => 1]);
    }

    public function submitMessage($request, $mailbox_id)
    {
        // Get or create a customer.
        $customer = \EndUserPortal::authCustomer();

        if ($customer) {
            $customer_email = $customer->getMainEmail();
        } else {
            $customer_email = \App\Email::sanitizeEmail($request->email);
        }

        $message = htmlspecialchars($request->message);

        // Check such messages from this customer.
        $message_id = \MailHelper::generateMessageId($customer_email, $message.\Str::random(25));
        if (Thread::where('message_id', $message_id)->first()) {
            return false;
        }

        $new = false;
        $conversation = null;
        if (empty($request->conversation_id)) {
            $new = true;
        } else {
            $conversation = Conversation::find($request->conversation_id);

            if (!$conversation) {
                $new = true;
            }
        }

        // Get attachments info
        // Delete removed attachments.
        $attachments_info = $this->processReplyAttachments($request);

        // Conversation
        $now = date('Y-m-d H:i:s');

        if ($new) {
            if (!$customer) {
                $customer_data = Customer::parseName($request->name);
                $customer = Customer::create($customer_email, $customer_data);
            }

            $subject = implode(' ', array_slice(explode(' ', $request->message), 0, 10));

            // New conversation.
            $conversation = new Conversation();
            $conversation->type = Conversation::TYPE_EMAIL;
            $conversation->subject = $subject;
            $conversation->setPreview($message);
            $conversation->mailbox_id = $mailbox_id;
            $conversation->customer_id = $customer->id;
            $conversation->created_by_customer_id = $customer->id;
            $conversation->source_via = Conversation::PERSON_CUSTOMER;
            $conversation->source_type = Conversation::SOURCE_TYPE_WEB;
        } else {
            $customer = $conversation->customer;
            $customer_email = $customer->getMainEmail();
        }

        if ($attachments_info['has_attachments']) {
            $conversation->has_attachments = true;
        }

        if ($customer_email) {
            $conversation->customer_email = $customer_email;
        }

        // Reply from customer makes conversation active.
        $conversation->status = Conversation::STATUS_ACTIVE;
        $conversation->last_reply_at = $now;
        $conversation->last_reply_from = Conversation::PERSON_CUSTOMER;
        // Reply from customer to deleted conversation should undelete it.
        //if ($conversation->state == Conversation::STATE_DELETED) {
        $conversation->state = Conversation::STATE_PUBLISHED;
        //}
        // Set folder id.
        $conversation->updateFolder();
        $conversation->save();

        // Create thread.
        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->user_id = $conversation->user_id;
        $thread->type = Thread::TYPE_CUSTOMER;
        $thread->status = $conversation->status;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->body = $message;
        $thread->from = $customer_email;
        $thread->message_id = $message_id;
        $thread->source_via = Thread::PERSON_CUSTOMER;
        $thread->source_type = Thread::SOURCE_TYPE_WEB;
        $thread->customer_id = $customer->id;
        $thread->created_by_customer_id = $customer->id;
        if ($new) {
            $thread->first = true;
        }
        if ($attachments_info['has_attachments']) {
            $thread->has_attachments = true;
        }
        $thread->save();

        // Update conversation here if needed.
        if ($new) {
            $conversation = \Eventy::filter('conversation.created_by_customer', $conversation, $thread, $customer);
        } else {
            $conversation = \Eventy::filter('conversation.customer_replied', $conversation, $thread, $customer);
        }
        // save() will check if something in the model has changed. If it hasn't it won't run a db query.
        $conversation->save();

        // Custom fields.
        if (\Module::isActive('customfields') && !empty($request->cf)) {
            foreach ($request->cf as $custom_field_id => $custom_field_value) {
                \CustomField::setValue($conversation->id, $custom_field_id, $custom_field_value);
            }
        }

        // Update folders counters
        $conversation->mailbox->updateFoldersCounters();

        if ($new) {
            event(new CustomerCreatedConversation($conversation, $thread));
            \Eventy::action('conversation.created_by_customer', $conversation, $thread, $customer);
        } else {
            event(new CustomerReplied($conversation, $thread));
            \Eventy::action('conversation.customer_replied', $conversation, $thread, $customer);
        }

        // Ignore this.
        // Conversation customer changed
        // if ($prev_customer_id) {
        //     event(new ConversationCustomerChanged($conversation, $prev_customer_id, $prev_customer_email, null, $customer));
        // }

        // Set thread_id for uploaded attachments
        if ($attachments_info['attachments']) {
            Attachment::whereIn('id', $attachments_info['attachments'])->update(['thread_id' => $thread->id]);
        }

        return $conversation;
    }

    /**
     * Process attachments on reply, new conversation.
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
     * Upload files and images.
     */
    public function upload(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $mailbox_salt = '';
        if (!empty($request->is_widget)) {
            $mailbox_salt = \EndUserPortal::WIDGET_SALT;
        }

        $mailbox = $this->processMailboxId($request->mailbox_id, $mailbox_salt);

        if (!$mailbox) {
            $response['msg'] = __('Error occured uploading file');
        }

        if (!$response['msg']) {
            if (!$request->hasFile('file') || !$request->file('file')->isValid() || !$request->file) {
                $response['msg'] = __('Error occured uploading file');
            }

            if (!$response['msg']) {

                $attachment = Attachment::create(
                    $request->file->getClientOriginalName(),
                    $request->file->getMimeType(),
                    null,
                    '',
                    $request->file,
                    false,
                    null,
                    null
                );

                if ($attachment) {
                    $response['status'] = 'success';
                    $response['url'] = $attachment->url();
                    $response['attachment_id'] = $attachment->id;
                } else {
                    $response['msg'] = __('Error occured uploading file');
                }
            }
        }

        return \Response::json($response);
    }

    /**
     * Widget form.
     */
    public function widgetForm(Request $request, $mailbox_id = null)
    {        
        // Set locale if needed.
        if (!empty($request->locale)) {
            app()->setLocale($request->locale);
        }

        //$mailbox = new Mailbox();
        // We need to fetch the mailbox in order to get metas.
        $mailbox = $this->processMailboxId($mailbox_id, \EndUserPortal::WIDGET_SALT);

        if (!$mailbox) {
            abort(404);
        }
        $mailbox->id = $mailbox_id;

        $conversation = new Conversation();
        $conversation->body = '';
        $conversation->mailbox = $mailbox;

        return view('enduserportal::widget_form', [
            'mailbox' => $mailbox,
            'conversation' => $conversation,
            'thread' => new Thread(),
        ]);
    }

    /**
     * Process submitted ticket.
     */
    public function widgetFormProcess(Request $request, $mailbox_id)
    {
        // Set locale if needed.
        if (!empty($request->locale)) {
            app()->setLocale($request->locale);
        }

        $mailbox = $this->processMailboxId($mailbox_id, \EndUserPortal::WIDGET_SALT);

        if (!$mailbox) {
            abort(404);
        }

        $rules = [
            'message'  => 'required|string',
        ];
        if (!\EndUserPortal::authCustomer()) {
            $rules['email'] = 'required|email';
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails() || !empty($request->age)) {
            return redirect()->route('enduserportal.widget_form', array_merge($request->all(), ['mailbox_id' => $mailbox_id, 'message' => '']))
                        ->withErrors($validator)
                        ->withInput();
        }

        $result = $this->submitMessage($request, $mailbox->id);

        //if ($result) {
        //\Session::flash(EUP_MODULE.'.submitted', 1);
        //}

        return redirect()->route('enduserportal.widget_form', array_merge($request->all(), ['mailbox_id' => $mailbox_id, 'message' => '', 'success' => 1]));
    }

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        //$user = auth()->user();
        
        switch ($request->action) {

            case 'privacy_policy':
 
                $mailbox = $this->processMailboxId($request->mailbox_id, \EndUserPortal::WIDGET_SALT);

                if (!$mailbox) {
                    abort(404);
                }

                $html = \EndUserPortal::getMailboxParam($mailbox, 'privacy');
                
                return $html;
                break;
        }

        abort(404);
    }
}
