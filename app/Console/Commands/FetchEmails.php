<?php

namespace App\Console\Commands;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Email;
use App\Events\ConversationCustomerChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\UserReplied;
use App\Mailbox;
use App\Misc\Mail;
use App\Option;
use App\SendLog;
use App\Subscription;
use App\Thread;
use App\User;
use Illuminate\Console\Command;
use Webklex\IMAP\Client;

class FetchEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:fetch-emails {--days=3} {--unseen=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch emails from mailboxes addresses';

    /**
     * Current mailbox.
     *
     * Used to process emails sent to multiple mailboxes.
     */
    public $mailbox;

    /**
     * Used to process emails sent to multiple mailboxes.
     */
    public $mailboxes;

    public $extra_import = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $now = time();
        $successfully = true;
        Option::set('fetch_emails_last_run', $now);

        $this->line('['.date('Y-m-d H:i:s').'] Fetching '.($this->option('unseen') ? 'UNREAD' : 'ALL').' emails for the last '.$this->option('days').' days.');

        $this->extra_import = [];

        if (Mailbox::getInProtocols() === Mailbox::$in_protocols) {
            $this->mailboxes = Mailbox::get();
        } else {
            // Get active mailboxes with the default in_protocols
            $this->mailboxes = Mailbox::whereIn('in_protocol', array_keys(Mailbox::$in_protocols))->get();
        }

        foreach ($this->mailboxes as $mailbox) {
            if (!$mailbox->isInActive()) {
                continue;
            }
            $this->info('['.date('Y-m-d H:i:s').'] Mailbox: '.$mailbox->name);

            $this->mailbox = $mailbox;

            try {
                $this->fetch($mailbox);
            } catch (\Exception $e) {
                $successfully = false;
                $this->logError('Error: '.$e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')').')';
            }
        }

        // Import emails sent to several mailboxes at once.
        if (count($this->extra_import)) {
            $this->line('['.date('Y-m-d H:i:s').'] Importing emails sent to several mailboxes at once: '.count($this->extra_import));
            foreach ($this->extra_import as $i => $extra_import) {
                $this->line('['.date('Y-m-d H:i:s').'] '.($i+1).') '.$extra_import['message']->getSubject());
                $this->processMessage($extra_import['message'], $extra_import['message_id'], $extra_import['mailbox'], [], true);
            }
        }

        if ($successfully && count($this->mailboxes)) {
            Option::set('fetch_emails_last_successful_run', $now);
        }

        // Middleware Terminate handler is not launched for commands,
        // so we need to run processing subscription events manually
        Subscription::processEvents();

        $this->info('['.date('Y-m-d H:i:s').'] Fetching finished');

        $this->extra_import = [];
        $this->mailbox = null;
        $this->mailboxes = [];
    }

    public function fetch($mailbox)
    {
        $no_charset = false;

        $client = \MailHelper::getMailboxClient($mailbox);

        // Connect to the Server
        $client->connect();

        $folders = [];

        // Fetch emails from custom IMAP folders.
        if ($mailbox->in_protocol == Mailbox::IN_PROTOCOL_IMAP) {
            $imap_folders = $mailbox->getInImapFolders();

            foreach ($imap_folders as $folder_name) {
                $folder = null;
                try {
                    $folder = $client->getFolder($folder_name);
                } catch (\Exception $e) {
                    // Just log error and continue.
                    $this->error('['.date('Y-m-d H:i:s').'] Could not get mailbox IMAP folder: '.$folder_name);
                }

                if ($folder) {
                    $folders[] = $folder;
                }
            }
            // try {
            //     //$folders = $client->getFolders();
            // } catch (\Exception $e) {
            //     // Do nothing
            // }
        }

        $unseen = \Eventy::filter('fetch_emails.unseen', $this->option('unseen'), $mailbox);
        if ($unseen != $this->option('unseen')) {
            $this->line('['.date('Y-m-d H:i:s').'] Fetching: '.($unseen ? 'UNREAD' : 'ALL'));
        }

        foreach ($folders as $folder) {
            $this->line('['.date('Y-m-d H:i:s').'] Folder: '.$folder->name);

            // Get unseen messages for a period
            $last_error = '';
            try {    
                $messages = $folder->query()->since(now()->subDays($this->option('days')))->leaveUnread();
                if ($unseen) {
                    $messages->unseen();
                }
                if ($no_charset) {
                    $messages->setCharset(null);
                }
                $messages = $messages->get();

                if (method_exists($client, 'getLastError')) {
                    $last_error = $client->getLastError();
                }
            } catch (\Exception $e) {
                $last_error = $e->getMessage();
            }

            if ($last_error && stristr($last_error, 'The specified charset is not supported')) {
                $errors_count = count($client->getErrors());
                // Solution for MS mailboxes.
                // https://github.com/freescout-helpdesk/freescout/issues/176
                $messages = $folder->query()->since(now()->subDays($this->option('days')))->leaveUnread()->setCharset(null);
                if ($unseen) {
                    $messages->unseen();
                }
                $messages = $messages->get();

                $no_charset = true;
                if (count($client->getErrors()) > $errors_count) {
                    $last_error = $client->getLastError();
                } else {
                    $last_error = null;
                }
            }

            if ($last_error && !\Str::startsWith($last_error, 'Mailbox is empty')) {
                // Throw exception for INBOX only
                if ($folder->name == 'INBOX' && !$messages) {
                    throw new \Exception($last_error, 1);
                } else {
                    $this->error('['.date('Y-m-d H:i:s').'] '.$last_error);
                }
            }

            $this->line('['.date('Y-m-d H:i:s').'] Fetched: '.count($messages));

            $message_index = 1;

            // We have to sort messages manually, as they can be in non-chronological order
            $messages = $this->sortMessage($messages);
            foreach ($messages as $message_id => $message) {
                $this->line('['.date('Y-m-d H:i:s').'] '.$message_index.') '.$message->getSubject());
                $message_index++;

                $this->processMessage($message, $message_id, $mailbox, $this->mailboxes);
            }
        }

        $client->disconnect();
    }

    public function processMessage($message, $message_id, $mailbox, $mailboxes, $extra = false)
    {
        try {

            // From - $from is the plain text email.
            $from = $message->getReplyTo();
            if (!$from) {
                $from = $message->getFrom();
            }

            if (!$from) {
                $this->logError('From is empty');
                $message->setFlag(['Seen']);
                return;
            } else {
                $from = $this->formatEmailList($from);
                $from = $from[0];
            }

            // Message-ID can be empty.
            // https://stackoverflow.com/questions/8513165/php-imap-do-emails-have-to-have-a-messageid
            if (!$message_id) {
                // Generate artificial Message-ID.
                $message_id = \MailHelper::generateMessageId($from, $message->getRawBody());
                $this->line('['.date('Y-m-d H:i:s').'] Message-ID is empty, generated artificial Message-ID: '.$message_id);
            }

            // Gnerate artificial Message-ID if importing same email into several mailboxes.
            if ($extra) {
                // Generate artificial Message-ID.
                $message_id = \MailHelper::generateMessageId($from);
                $this->line('['.date('Y-m-d H:i:s').'] Generated artificial Message-ID: '.$message_id);
            }

            // Check if message already fetched.
            if (Thread::where('message_id', $message_id)->first()) {
                $this->line('['.date('Y-m-d H:i:s').'] Message with such Message-ID has been fetched before: '.$message_id);
                $message->setFlag(['Seen']);
                return;
            }

            // Detect prev thread
            $is_reply = false;
            $prev_thread = null;
            $user_id = null;
            $user = null; // for user reply only
            $message_from_customer = true;
            $in_reply_to = $message->getInReplyTo();
            $references = $message->getReferences();
            $attachments = $message->getAttachments();
            $html_body = '';

            // Is it a bounce message
            $is_bounce = false;

            // Determine previous Message-ID
            $prev_message_id = '';
            if ($in_reply_to) {
                $prev_message_id = $in_reply_to;
            } elseif ($references) {
                if (!is_array($references)) {
                    $references = array_filter(preg_split('/[, <>]/', $references));
                }
                // Find first non-empty reference
                if (is_array($references)) {
                    foreach ($references as $reference) {
                        if (!empty(trim($reference))) {
                            $prev_message_id = trim($reference);
                            break;
                        }
                    }
                }
            }

            // Some mail service providers change Message-ID of the outgoing email,
            // so we are passing Message-ID in marker in body.
            $reply_prefixes = [
                \MailHelper::MESSAGE_ID_PREFIX_NOTIFICATION,
                \MailHelper::MESSAGE_ID_PREFIX_REPLY_TO_CUSTOMER,
                \MailHelper::MESSAGE_ID_PREFIX_AUTO_REPLY,
            ];

            // Try to get previous message ID from marker in body.
            if (!$prev_message_id || !preg_match('/^('.implode('|', $reply_prefixes).')\-(\d+)\-/', $prev_message_id)) {
                $html_body = $message->getHTMLBody(false);
                $marker_message_id = \MailHelper::fetchMessageMarkerValue($html_body);

                if ($marker_message_id) {
                    $prev_message_id = $marker_message_id;
                }
            }

            // Bounce detection.
            $bounced_message_id = null;
            if ($message->hasAttachments()) {
                // Detect bounce by attachment.
                // Check all attachments.
                foreach ($attachments as $attachment) {
                    if (!empty(Attachment::$types[$attachment->getType()]) && Attachment::$types[$attachment->getType()] == Attachment::TYPE_MESSAGE
                    ) {
                        if (
                            // Checking the name will lead to mistakes if someone attaches a file with such name.
                            // Dashes are converted to space.
                            //in_array(strtoupper($attachment->getName()), ['RFC822', 'DELIVERY STATUS', 'DELIVERY STATUS NOTIFICATION', 'UNDELIVERED MESSAGE'])
                            preg_match('/delivery-status/', strtolower($attachment->content_type))
                            // 7.3.1 The Message/rfc822 (primary) subtype. A Content-Type of "message/rfc822" indicates that the body contains an encapsulated message, with the syntax of an RFC 822 message
                            //|| $attachment->content_type == 'message/rfc822'
                        ) {
                            $is_bounce = true;

                            $this->line('['.date('Y-m-d H:i:s').'] Bounce detected by attachment content-type: '.$attachment->content_type);

                            // Try to get Message-ID of the original email.
                            if (!$bounced_message_id) {
                                //print_r(\MailHelper::parseHeaders($attachment->getContent()));
                                $bounced_message_id = \MailHelper::getHeader($attachment->getContent(), 'message_id');
                            }
                        }
                    }
                }
            }
            $message_header = $this->headerToStr($message->getHeader());

            // Check Content-Type header.
            if (!$is_bounce && $message_header) {
                if (\MailHelper::detectBounceByHeaders($message_header)) {
                    $is_bounce = true;
                }
            }
            // Check message's From field.
            if (!$is_bounce) {
                if ($message->getFrom()) {
                    $original_from = $this->formatEmailList($message->getFrom());
                    $original_from = $original_from[0];
                    $is_bounce = preg_match('/^mailer\-daemon@/i', $original_from);
                    if ($is_bounce) {
                        $this->line('['.date('Y-m-d H:i:s').'] Bounce detected by From header: '.$original_from);
                    }
                }
            }
            // Check Return-Path header
            if (!$is_bounce && preg_match("/^Return\-Path: <>/i", $message_header)) {
                $this->line('['.date('Y-m-d H:i:s').'] Bounce detected by Return-Path header.');
                $is_bounce = true;
            }


            if ($is_bounce && !$bounced_message_id) {
                foreach ($attachments as $attachment_msg) {
                    // 7.3.1 The Message/rfc822 (primary) subtype. A Content-Type of "message/rfc822" indicates that the body contains an encapsulated message, with the syntax of an RFC 822 message
                    if ($attachment_msg->content_type == 'message/rfc822') {
                        $bounced_message_id = \MailHelper::getHeader($attachment_msg->getContent(), 'message_id');
                        if ($bounced_message_id) {
                            break;
                        }
                    }
                }
            }

            // Is it a message from Customer or User replied to the notification
            preg_match('/^'.\MailHelper::MESSAGE_ID_PREFIX_NOTIFICATION."\-(\d+)\-(\d+)\-/", $prev_message_id, $m);

            if (!$is_bounce && !empty($m[1]) && !empty($m[2])) {
                // Reply from User to the notification
                $prev_thread = Thread::find($m[1]);
                $user_id = $m[2];
                $user = User::find($user_id);
                $message_from_customer = false;
                $is_reply = true;

                if (!$user) {
                    $this->logError('User not found: '.$user_id);
                    $message->setFlag(['Seen']);
                    return;
                }
                $this->line('['.date('Y-m-d H:i:s').'] Message from: User');
            } else {
                // Message from Customer or User replied to his reply to notification
                $this->line('['.date('Y-m-d H:i:s').'] Message from: Customer');

                if (!$is_bounce) {
                    if ($prev_message_id) {
                        $prev_thread_id = '';

                        // Customer replied to the email from user
                        preg_match('/^'.\MailHelper::MESSAGE_ID_PREFIX_REPLY_TO_CUSTOMER."\-(\d+)\-/", $prev_message_id, $m);
                        if (!empty($m[1])) {
                            $prev_thread_id = $m[1];
                        }

                        // Customer replied to the auto reply
                        if (!$prev_thread_id) {
                            preg_match('/^'.\MailHelper::MESSAGE_ID_PREFIX_AUTO_REPLY."\-(\d+)\-/", $prev_message_id, $m);
                            if (!empty($m[1])) {
                                $prev_thread_id = $m[1];
                            }
                        }

                        if ($prev_thread_id) {
                            $prev_thread = Thread::find($prev_thread_id);
                        } else {
                            // Customer replied to his own message
                            $prev_thread = Thread::where('message_id', $prev_message_id)->first();
                        }

                        // Reply from user to his reply to the notification
                        if (!$prev_thread
                            && ($prev_thread = Thread::where('message_id', $prev_message_id)->first())
                            && $prev_thread->created_by_user_id
                            && $prev_thread->created_by_user->hasEmail($from)
                        ) {
                            $user_id = $user->id;
                            $message_from_customer = false;
                            $is_reply = true;
                        }
                    }
                    if (!empty($prev_thread)) {
                        $is_reply = true;
                    }
                }
            }

            // Make sure that prev_thread belongs to the current mailbox.
            // It may happen when forwarding conversation for example.
            if ($prev_thread) {
                if ($prev_thread->conversation->mailbox_id != $mailbox->id) {
                    $prev_thread = null;
                    $is_reply = false;
                }
            }

            // Get body
            if (!$html_body) {
                // Get body and do not replace :cid with images base64
                $html_body = $message->getHTMLBody(false);
            }
            if ($html_body) {
                $body = $this->separateReply($html_body, true, $is_reply);
            } else {
                $body = $message->getTextBody();
                $body = $this->separateReply($body, false, $is_reply);
            }
            // We have to fetch absolutely all emails, even with empty body.
            // if (!$body) {
            //     $this->logError('Message body is empty');
            //     $message->setFlag(['Seen']);
            //     continue;
            // }

            $subject = $message->getSubject();

            // Convert subject encoding
            if (preg_match('/=\?[a-z\d-]+\?[BQ]\?.*\?=/i', $subject)) {
                $subject = iconv_mime_decode($subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            }

            $to = $this->formatEmailList($message->getTo());
            //$to = $mailbox->removeMailboxEmailsFromList($to);

            $cc = $this->formatEmailList($message->getCc());
            //$cc = $mailbox->removeMailboxEmailsFromList($cc);

            $bcc = $this->formatEmailList($message->getBcc());
            //$bcc = $mailbox->removeMailboxEmailsFromList($bcc);

            // Create customers
            $emails = array_merge(
                $this->attrToArray($message->getFrom()), 
                $this->attrToArray($message->getReplyTo()),
                $this->attrToArray($message->getTo()),
                $this->attrToArray($message->getCc()),
                $this->attrToArray($message->getBcc())
            );
            $this->createCustomers($emails, $mailbox->getEmails());

            $data = \Eventy::filter('fetch_emails.data_to_save', [
                'mailbox'     => $mailbox,
                'message_id'  => $message_id,
                'prev_thread' => $prev_thread,
                'from'        => $from,
                'to'          => $to,
                'cc'          => $cc,
                'bcc'         => $bcc,
                'subject'     => $subject,
                'body'        => $body,
                'attachments' => $attachments,
                'message'     => $message,
                'is_bounce'   => $is_bounce,
                'message_from_customer' => $message_from_customer,
                'user'        => $user,
            ]);

            $new_thread = null;
            if ($message_from_customer) {

                if (!$data['prev_thread']) {
                    // Maybe this email need to be imported also into other mailbox.

                    $recipient_emails = array_unique($this->formatEmailList(array_merge(
                        $this->attrToArray($message->getTo()), 
                        $this->attrToArray($message->getCc()), 
                        $this->attrToArray($message->getBcc())
                    )));
                    
                    if (count($mailboxes) && count($recipient_emails) > 1) {
                        foreach ($mailboxes as $check_mailbox) {
                            if ($check_mailbox->id == $mailbox->id) {
                                continue;
                            }
                            if (!$check_mailbox->isInActive()) {
                                continue;
                            }
                            foreach ($recipient_emails as $recipient_email) {
                                // No need to check mailbox aliases.
                                if (\App\Email::sanitizeEmail($check_mailbox->email) == $recipient_email) {
                                    $this->extra_import[] = [
                                        'mailbox'    => $check_mailbox,
                                        'message'    => $message,
                                        'message_id' => $message_id,
                                    ];
                                    break;
                                }
                            }
                        }
                    }
                }

                if (\Eventy::filter('fetch_emails.should_save_thread', true, $data) !== false) {
                    // SendAutoReply listener will check bounce flag and will not send an auto reply if this is an auto responder.
                    $new_thread = $this->saveCustomerThread($mailbox, $data['message_id'], $data['prev_thread'], $data['from'], $data['to'], $data['cc'], $data['bcc'], $data['subject'], $data['body'], $data['attachments'], $data['message']->getHeader());
                } else {
                    $this->line('['.date('Y-m-d H:i:s').'] Hook fetch_emails.should_save_thread returned false. Skipping message.');
                    $message->setFlag(['Seen']);
                    return;
                }
            } else {
                // Check if From is the same as user's email.
                // If not we send an email with information to the sender.
                if (!$user->hasEmail($from)) {
                    $this->logError("From address {$from} is not the same as user {$user->id} email: ".$user->email);
                    $message->setFlag(['Seen']);

                    // Send "Unable to process your update email" to user
                    \App\Jobs\SendEmailReplyError::dispatch($from, $user, $mailbox)->onQueue('emails');

                    return;
                }

                if (\Eventy::filter('fetch_emails.should_save_thread', true, $data) !== false) {
                    $new_thread = $this->saveUserThread($data['mailbox'], $data['message_id'], $data['prev_thread'], $data['user'], $data['from'], $data['to'], $data['cc'], $data['bcc'], $data['body'], $data['attachments'], $data['message']->getHeader());
                } else {
                    $this->line('['.date('Y-m-d H:i:s').'] Hook fetch_emails.should_save_thread returned false. Skipping message.');
                    $message->setFlag(['Seen']);
                    return;
                }
            }

            if ($new_thread) {
                $message->setFlag(['Seen']);
                $this->line('['.date('Y-m-d H:i:s').'] Thread successfully created: '.$new_thread->id);

                // If it was a bounce message, save bounce data.
                if ($message_from_customer && $is_bounce) {
                    $this->saveBounceData($new_thread, $bounced_message_id, $from);
                }
            } else {
                $this->logError('Error occured processing message');
            }
        } catch (\Exception $e) {
            $message->setFlag(['Seen']);
            $this->logError(\Helper::formatException($e));
        }
    }

    public function saveBounceData($new_thread, $bounced_message_id, $from)
    {
        // Try to find bounced thread by Message-ID.
        $bounced_thread = null;
        if ($bounced_message_id) {
            $prefixes = [
                \MailHelper::MESSAGE_ID_PREFIX_REPLY_TO_CUSTOMER,
                \MailHelper::MESSAGE_ID_PREFIX_AUTO_REPLY,
            ];
            preg_match('/^('.implode('|', $prefixes).')\-(\d+)\-/', $bounced_message_id, $matches);
            if (!empty($matches[2])) {
                $bounced_thread = Thread::find($matches[2]);
            }
        }

        $status_data = [
            'is_bounce' => true,
        ];
        if ($bounced_thread) {
            $status_data['bounce_for_thread'] = $bounced_thread->id;
            $status_data['bounce_for_conversation'] = $bounced_thread->conversation_id;
        }

        $new_thread->updateSendStatusData($status_data);
        $new_thread->save();

        // Update status of the original message and create log record.
        if ($bounced_thread) {
            $bounced_thread->send_status = SendLog::STATUS_DELIVERY_ERROR;

            $status_data = [
                'bounced_by_thread'       => $new_thread->id,
                'bounced_by_conversation' => $new_thread->conversation_id,
                // todo.
                // 'bounce_info' => [
                // ]
            ];

            $bounced_thread->updateSendStatusData($status_data);
            $bounced_thread->save();

            // Bounces can be soft and hard, for now log both as STATUS_DELIVERY_ERROR.
            SendLog::log($bounced_thread->id, null, $from, SendLog::MAIL_TYPE_EMAIL_TO_CUSTOMER, SendLog::STATUS_DELIVERY_ERROR, $bounced_thread->created_by_customer_id, null, 'Message bounced');
        }
    }

    public function logError($message)
    {
        $this->error('['.date('Y-m-d H:i:s').'] '.$message);

        $mailbox_name = '';
        if ($this->mailbox) {
            $mailbox_name = $this->mailbox->name;
        }

        try {
            activity()
                ->withProperties([
                    'error'   => $message,
                    'mailbox' => $mailbox_name,
                ])
                ->useLog(\App\ActivityLog::NAME_EMAILS_FETCHING)
                ->log(\App\ActivityLog::DESCRIPTION_EMAILS_FETCHING_ERROR);
        } catch (\Exception $e) {
            // Do nothing
        }
    }

    /**
     * Save email from customer as thread.
     */
    public function saveCustomerThread($mailbox, $message_id, $prev_thread, $from, $to, $cc, $bcc, $subject, $body, $attachments, $headers)
    {
        // Find conversation
        $new = false;
        $conversation = null;
        $prev_customer_id = null;
        $now = date('Y-m-d H:i:s');
        $conv_cc = $cc;

        // Customers are created before with email and name
        $customer = Customer::create($from);
        if ($prev_thread) {
            $conversation = $prev_thread->conversation;

            // If reply came from another customer: change customer, add original as CC.
            // If FreeScout will not change the customer, the reply will be shown 
            // as coming from the original customer (not the real sender) and cause confusion.
            if ($conversation->customer_id != $customer->id) {
                $prev_customer_id = $conversation->customer_id;
                $prev_customer_email = $conversation->customer_email;

                // Do not add to CC emails from the original's BCC
                if (!in_array($conversation->customer_email, $conversation->getBccArray())) {
                    $conv_cc[] = $conversation->customer_email;
                }
                $conversation->customer_id = $customer->id;
            }
        } else {
            // Create conversation
            $new = true;

            $conversation = new Conversation();
            $conversation->type = Conversation::TYPE_EMAIL;
            $conversation->state = Conversation::STATE_PUBLISHED;
            $conversation->subject = $subject;
            $conversation->setPreview($body);
            $conversation->mailbox_id = $mailbox->id;
            $conversation->customer_id = $customer->id;
            $conversation->created_by_customer_id = $customer->id;
            $conversation->source_via = Conversation::PERSON_CUSTOMER;
            $conversation->source_type = Conversation::SOURCE_TYPE_EMAIL;
        }

        // Update has_attachments only if email has attachments AND conversation hasn't has_attachments already set
        // Prevent to set has_attachments value back to 0 if the new reply doesn't have any attachment
        if (!$conversation->has_attachments && count($attachments)) {
            $conversation->has_attachments = true;
        }

        // Save extra recipients to CC, but do not add the mailbox itself as a CC.
        $conversation->setCc(array_merge($conv_cc, array_diff($to, $mailbox->getEmails())));
        // BCC should keep BCC of the first email,
        // so we change BCC only if it contains emails.
        if ($bcc) {
            $conversation->setBcc($bcc);
        }
        $conversation->customer_email = $from;
        // Reply from customer makes conversation active
        $conversation->status = Conversation::STATUS_ACTIVE;
        $conversation->last_reply_at = $now;
        $conversation->last_reply_from = Conversation::PERSON_CUSTOMER;
        // Reply from customer to deleted conversation should undelete it.
        if ($conversation->state == Conversation::STATE_DELETED) {
            $conversation->state = Conversation::STATE_PUBLISHED;
        }
        // Set folder id
        $conversation->updateFolder();
        $conversation->save();

        // Thread
        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->user_id = $conversation->user_id;
        $thread->type = Thread::TYPE_CUSTOMER;
        $thread->status = $conversation->status;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->message_id = $message_id;
        $thread->headers = $this->headerToStr($headers);
        $thread->body = $body;
        $thread->from = $from;
        $thread->setTo($to);
        $thread->setCc($cc);
        $thread->setBcc($bcc);
        $thread->source_via = Thread::PERSON_CUSTOMER;
        $thread->source_type = Thread::SOURCE_TYPE_EMAIL;
        $thread->customer_id = $customer->id;
        $thread->created_by_customer_id = $customer->id;
        if ($new) {
            $thread->first = true;
        }
        $thread->save();

        $saved_attachments = $this->saveAttachments($attachments, $thread->id);
        if ($saved_attachments) {
            $thread->has_attachments = true;

            // After attachments saved to the disk we can replace cids in body (for PLAIN and HTML body)
            $thread->body = $this->replaceCidsWithAttachmentUrls($thread->body, $saved_attachments);

            $thread->save();
        }

        // Update conversation here if needed.
        if ($new) {
            $conversation = \Eventy::filter('conversation.created_by_customer', $conversation, $thread, $customer);
        } else {
            $conversation = \Eventy::filter('conversation.customer_replied', $conversation, $thread, $customer);
        }
        // save() will check if something in the model has changed. If it hasn't it won't run a db query.
        $conversation->save();

        // Update folders counters
        $conversation->mailbox->updateFoldersCounters();

        if ($new) {
            event(new CustomerCreatedConversation($conversation, $thread));
            \Eventy::action('conversation.created_by_customer', $conversation, $thread, $customer);
        } else {
            event(new CustomerReplied($conversation, $thread));
            \Eventy::action('conversation.customer_replied', $conversation, $thread, $customer);
        }

        // Conversation customer changed
        if ($prev_customer_id) {
            event(new ConversationCustomerChanged($conversation, $prev_customer_id, $prev_customer_email, null, $customer));
        }

        return $thread;
    }

    /**
     * Save email reply from user as thread.
     */
    public function saveUserThread($mailbox, $message_id, $prev_thread, $user, $from, $to, $cc, $bcc, $body, $attachments, $headers)
    {
        $conversation = null;
        $now = date('Y-m-d H:i:s');
        $user_id = $user->id;

        $conversation = $prev_thread->conversation;
        // Determine assignee
        // maybe we need to check mailbox->ticket_assignee here, maybe not
        if (!$conversation->user_id) {
            $conversation->user_id = $user_id;
        }

        // Save extra recipients to CC
        $conversation->setCc(array_merge($cc, $to));
        $conversation->setBcc($bcc);

        // Respect mailbox settings for "Status After Replying
        $prev_status = $conversation->status;
        $conversation->status = $mailbox->ticket_status;
        if ($conversation->status != $mailbox->ticket_status) {
            \Eventy::action('conversation.status_changed', $conversation, $user, true, $prev_status);
        }
        $conversation->last_reply_at = $now;
        $conversation->last_reply_from = Conversation::PERSON_USER;
        $conversation->user_updated_at = $now;
        // Set folder id
        $conversation->updateFolder();
        $conversation->save();

        // Update folders counters
        $conversation->mailbox->updateFoldersCounters();

        // Thread
        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->user_id = $conversation->user_id;
        $thread->type = Thread::TYPE_MESSAGE;
        $thread->status = $conversation->status;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->message_id = $message_id;
        $thread->headers = $headers;
        $thread->body = $body;
        $thread->from = $from;
        // To must be customer's email
        $thread->setTo([$conversation->customer_email]);
        $thread->setCc($cc);
        $thread->setBcc($bcc);
        $thread->source_via = Thread::PERSON_USER;
        $thread->source_type = Thread::SOURCE_TYPE_EMAIL;
        $thread->customer_id = $conversation->customer_id;
        $thread->created_by_user_id = $user_id;
        $thread->save();

        $saved_attachments = $this->saveAttachments($attachments, $thread->id);
        if ($saved_attachments) {
            $thread->has_attachments = true;

            // After attachments saved to the disk we can replace cids in body (for PLAIN and HTML body)
            $thread->body = $this->replaceCidsWithAttachmentUrls($thread->body, $saved_attachments);

            $thread->save();
        }

        event(new UserReplied($conversation, $thread));
        \Eventy::action('conversation.user_replied', $conversation, $thread);

        return $thread;
    }

    /**
     * Save attachments from email.
     *
     * @param array $attachments
     * @param int   $thread_id
     *
     * @return bool
     */
    public function saveAttachments($email_attachments, $thread_id)
    {
        $created_attachments = [];
        foreach ($email_attachments as $email_attachment) {
            $created_attachment = Attachment::create(
                $email_attachment->getName(),
                $email_attachment->getMimeType(),
                Attachment::typeNameToInt($email_attachment->getType()),
                $email_attachment->getContent(),
                '',
                false,
                $thread_id
            );
            if ($created_attachment) {
                $created_attachments[] = [
                    'imap_attachment' => $email_attachment,
                    'attachment'      => $created_attachment,
                ];
            }
        }

        return $created_attachments;
    }

    /**
     * Separate reply in the body.
     *
     * @param string $body
     *
     * @return string
     */
    public function separateReply($body, $is_html, $is_reply)
    {
        $cmp_reply_length_desc = function ($a, $b) {
            if (mb_strlen($a) == mb_strlen($b)) {
                return 0;
            }

            return (mb_strlen($a) < mb_strlen($b)) ? -1 : 1;
        };

        $result = '';

        if ($is_html) {
            // Extract body content from HTML
            // Split by <html>
            $htmls = [];
            preg_match_all("/<html[^>]*>(.*?)<\/html>/is", $body, $htmls);

            if (empty($htmls[0])) {
                $htmls[0] = [$body];
            }
            foreach ($htmls[0] as $html) {
                // One body.
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                libxml_use_internal_errors(false);
                $bodies = $dom->getElementsByTagName('body');
                if ($bodies->length == 1) {
                    $body_el = $bodies->item(0);
                    $html = $dom->saveHTML($body_el);
                }
                preg_match("/<body[^>]*>(.*?)<\/body>/is", $html, $matches);
                if (count($matches)) {
                    $result .= $matches[1];
                }
            }
            if (!$result) {
                $result = $body;
            }
        } else {
            $result = nl2br($body);
        }

        // This is reply, we need to separate reply text from old text
        if ($is_reply) {
            // Check all separators and choose the shortest reply
            $reply_bodies = [];
            $reply_separators = Mail::$alternative_reply_separators;

            if (!empty($this->mailbox->before_reply)) {
                $reply_separators[] = $this->mailbox->before_reply;
            }

            foreach ($reply_separators as $reply_separator) {
                if (\Str::startsWith($reply_separator, 'regex:')) {
                    $regex = preg_replace("/^regex:/", '', $reply_separator);
                    $parts = preg_split($regex, $result);
                } else {
                    $parts = explode($reply_separator, $result);
                }
                if (count($parts) > 1) {
                    // Check if past contains any real text.
                    $text = \Helper::htmlToText($parts[0]);
                    $text = trim($text);
                    $text = preg_replace('/^\s+/mu', '', $text);

                    if ($text) {
                        $reply_bodies[] = $parts[0];
                    }
                }
            }
            if (count($reply_bodies)) {
                usort($reply_bodies, $cmp_reply_length_desc);

                return $reply_bodies[0];
            }
        }

        return $result;
    }

    public function replaceCidsWithAttachmentUrls($body, $attachments)
    {
        foreach ($attachments as $attachment) {
            if ($attachment['imap_attachment']->id && isset($attachment['imap_attachment']->img_src)) {
                $body = str_replace('cid:'.$attachment['imap_attachment']->id, $attachment['attachment']->url(), $body);
            }
        }

        return $body;
    }

    /**
     * Conver email object to plain emails.
     *
     * @param array $obj_list
     *
     * @return array
     */
    public function formatEmailList($obj_list)
    {
        $plain_list = [];

        if (!$obj_list) {
            return $plain_list;
        }

        $obj_list = $this->attrToArray($obj_list);

        foreach ($obj_list as $item) {
            $item->mail = Email::sanitizeEmail($item->mail);
            if ($item->mail) {
                $plain_list[] = $item->mail;
            }
        }

        return $plain_list;
    }

    public function attrToArray($attr)
    {
        if (!$attr) {
            return [];
        }

        if (is_object($attr) && get_class($attr) == 'Webklex\PHPIMAP\Attribute') {
            $attr = $attr->get();
        }

        return $attr;
    }

    public function headerToStr($header)
    {
        if (!is_string($header)) {
            $header = $header->raw;
        }
        return $header;
    }

    /**
     * We have to sort messages manually, as they can be in non-chronological order.
     *
     * @param Collection $messages
     *
     * @return Collection
     */
    public function sortMessage($messages)
    {
        $messages = $messages->sortBy(function ($message, $key) {
            $date = $message->getDate();
            if ($date) {
                if (isset($message->getDate()->timestamp)) {
                    return $message->getDate()->timestamp;
                } else {
                    return (string)$message->getDate();
                }
            } else {
                return 0;
            }
        });

        return $messages;
    }

    /**
     * Create customers from emails.
     *
     * @param array $emails_data
     */
    public function createCustomers($emails, $exclude_emails)
    {
        foreach ($emails as $item) {
            // Email belongs to mailbox
            // if (in_array(Email::sanitizeEmail($item->mail), $exclude_emails)) {
            //     continue;
            // }
            $data = [];
            if (!empty($item->personal)) {
                $name_parts = explode(' ', $item->personal, 2);
                $data['first_name'] = $name_parts[0];
                if (!empty($name_parts[1])) {
                    $data['last_name'] = $name_parts[1];
                }
            }
            Customer::create($item->mail, $data);
        }
    }
}
