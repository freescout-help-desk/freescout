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
    protected $signature = 'freescout:fetch-emails {--days=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch emails from mailboxes addresses';

    /**
     * Current mailbox.
     *
     * @var Mailbox
     */
    public $mailbox;

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

        // Get active mailboxes
        $mailboxes = Mailbox::where('in_protocol', '<>', '')
            ->where('in_server', '<>', '')
            ->where('in_port', '<>', '')
            ->where('in_username', '<>', '')
            ->where('in_password', '<>', '')
            ->get();

        foreach ($mailboxes as $mailbox) {
            $this->info('['.date('Y-m-d H:i:s').'] Mailbox: '.$mailbox->name);

            $this->mailbox = $mailbox;

            try {
                $this->fetch($mailbox);
            } catch (\Exception $e) {
                $successfully = false;
                $this->logError('Error: '.$e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')').')';
            }
        }

        if ($successfully && count($mailboxes)) {
            Option::set('fetch_emails_last_successful_run', $now);
        }

        // Middleware Terminate handler is not launched for commands,
        // so we need to run processing subscription events manually
        Subscription::processEvents();

        $this->info('['.date('Y-m-d H:i:s').'] Fetching finished');
    }

    public function fetch($mailbox)
    {
        $client = new Client([
            'host'          => $mailbox->in_server,
            'port'          => $mailbox->in_port,
            'encryption'    => $mailbox->getInEncryptionName(),
            'validate_cert' => true,
            'username'      => $mailbox->in_username,
            'password'      => $mailbox->in_password,
            'protocol'      => $mailbox->getInProtocolName(),
        ]);

        // Connect to the Server
        $client->connect();

        // Get folder
        $folder = $client->getFolder('INBOX');

        if (!$folder) {
            throw new \Exception('Could not get mailbox folder: INBOX', 1);
        }
        $folders = [$folder];

        // It would be good to be able to fetch emails from Spam folder into Spam folder of the mailbox
        // But not all mail servers provide access to it.
        // For example DreamHost does have a Spam folder but allows IMAP access to the following folders only:
        //      ./cur
        //      ./new
        //      ./tmp

        // $folders = [];

        // if ($mailbox->in_protocol == Mailbox::IN_PROTOCOL_IMAP) {
        //     try {
        //         //$folders = $client->getFolders();
        //     } catch (\Exception $e) {
        //         // Do nothing
        //     }
        // }
        // if (!count($folders)) {
        //     $folders = [$client->getFolder('INBOX')];
        // }

        foreach ($folders as $folder) {
            $this->line('['.date('Y-m-d H:i:s').'] Folder: '.$folder->name);

            // Get unseen messages for a period
            $messages = $folder->query()->unseen()->since(now()->subDays($this->option('days')))->leaveUnread()->get();

            if ($client->getLastError()) {
                // Throw exception for INBOX only
                if ($folder->name == 'INBOX') {
                    throw new \Exception($client->getLastError(), 1);
                } else {
                    $this->error('['.date('Y-m-d H:i:s').'] '.$client->getLastError());
                }
            }

            $this->line('['.date('Y-m-d H:i:s').'] Fetched: '.count($messages));

            $message_index = 1;

            // We have to sort messages manually, as they can be in non-chronological order
            $messages = $this->sortMessage($messages);
            foreach ($messages as $message_id => $message) {
                try {
                    $this->line('['.date('Y-m-d H:i:s').'] '.$message_index.') '.$message->getSubject());
                    $message_index++;

                    // Check if message already fetched
                    if (Thread::where('message_id', $message_id)->first()) {
                        $this->line('['.date('Y-m-d H:i:s').'] Message with such Message-ID has been fetched before: '.$message_id);
                        $message->setFlag(['Seen']);
                        continue;
                    }

                    // From
                    $from = $message->getReplyTo();
                    if (!$from) {
                        $from = $message->getFrom();
                    }

                    if (!$from) {
                        $this->logError('From is empty');
                        $message->setFlag(['Seen']);
                        continue;
                    } else {
                        $from = $this->formatEmailList($from);
                        $from = $from[0];
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

                    if (!$prev_message_id || !preg_match('/^('.implode('|', $reply_prefixes).')\-(\d+)\-/', $prev_message_id)) {
                        // Try to get previous message ID from marker in body.
                        $html_body = $message->getHTMLBody(false);
                        $marker_message_id = \MailHelper::fetchMessageMarkerValue($html_body);

                        if ($marker_message_id) {
                            $prev_message_id = $marker_message_id;
                        }
                    }

                    // Bounce detection.
                    // This is a temporary solution.
                    if ($message->hasAttachments()) {
                        // Detect bounce by attachment.
                        foreach ($attachments as $attachment) {
                            if (!empty(Attachment::$types[$attachment->getType()]) && Attachment::$types[$attachment->getType()] == Attachment::TYPE_MESSAGE) {
                                if (in_array($attachment->getName(), ['RFC822', 'DELIVERY-STATUS'])) {
                                    $is_bounce = true;
                                    break;
                                }
                            }
                        }
                        // Check Content-Type header.
                        if ($message->getHeader()) {
                            if (\MailHelper::detectBounceByHeaders($message->getHeader())) {
                                $is_bounce = true;
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
                            continue;
                        }
                        $this->line('['.date('Y-m-d H:i:s').'] Message from: User');
                    } elseif (!$is_bounce && ($user = User::where('email', $from)->first()) && $prev_message_id && ($prev_thread = Thread::where('message_id', $prev_message_id)->first()) && $prev_thread->created_by_user_id == $user->id) {
                        // Reply from customer to his reply to the notification
                        $user_id = $user->id;
                        $message_from_customer = false;
                        $is_reply = true;
                    } else {
                        // Message from Customer
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
                            }
                            if (!empty($prev_thread)) {
                                $is_reply = true;
                            }
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
                    if (!$body) {
                        $this->logError('Message body is empty');
                        $message->setFlag(['Seen']);
                        continue;
                    }

                    $subject = $message->getSubject();

                    $to = $this->formatEmailList($message->getTo());
                    //$to = $mailbox->removeMailboxEmailsFromList($to);

                    $cc = $this->formatEmailList($message->getCc());
                    //$cc = $mailbox->removeMailboxEmailsFromList($cc);

                    $bcc = $this->formatEmailList($message->getBcc());
                    //$bcc = $mailbox->removeMailboxEmailsFromList($bcc);

                    // Create customers
                    $emails = array_merge($message->getFrom(), $message->getReplyTo(), $message->getTo(), $message->getCc(), $message->getBcc());
                    $this->createCustomers($emails, $mailbox->getEmails());

                    if ($message_from_customer) {
                        // SendAutoReply will check headers and will not send an auto reply if this is an auto responder.
                        /*if ($this->isAutoResponder($message->getHeader())) {
                            $this->line('['.date('Y-m-d H:i:s').'] Email detected as autoresponder and ignored.');
                            $message->setFlag(['Seen']);
                            continue;
                        }*/
                        $new_thread_id = $this->saveCustomerThread($mailbox->id, $message_id, $prev_thread, $from, $to, $cc, $bcc, $subject, $body, $attachments, $message->getHeader());
                    } else {
                        // Check if From is the same as user's email.
                        // If not we send an email with information to the sender.
                        if (Email::sanitizeEmail($user->email) != Email::sanitizeEmail($from)) {
                            $this->logError("From address {$from} is not the same as user {$user->id} email: ".$user->email);
                            $message->setFlag(['Seen']);

                            // Send "Unable to process your update email" to user
                            \App\Jobs\SendEmailReplyError::dispatch($from, $user, $mailbox)->onQueue('emails');

                            continue;
                        }

                        $new_thread_id = $this->saveUserThread($mailbox, $message_id, $prev_thread, $user, $from, $to, $cc, $bcc, $body, $attachments, $message->getHeader());
                    }

                    if ($new_thread_id) {
                        $message->setFlag(['Seen']);
                        $this->line('['.date('Y-m-d H:i:s').'] Thread successfully created: '.$new_thread_id);
                    } else {
                        $this->logError('Error occured processing message');
                    }
                } catch (\Exception $e) {
                    $message->setFlag(['Seen']);
                    $this->logError('Error: '.$e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')').')';
                }
            }
        }

        $client->disconnect();
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
    public function saveCustomerThread($mailbox_id, $message_id, $prev_thread, $from, $to, $cc, $bcc, $subject, $body, $attachments, $headers)
    {
        // Find conversation
        $new = false;
        $conversation = null;
        $prev_customer_id = null;
        $now = date('Y-m-d H:i:s');

        // Customers are created before with email and name
        $customer = Customer::create($from);
        if ($prev_thread) {
            $conversation = $prev_thread->conversation;

            // If reply came from another customer: change customer, add original as CC
            if ($conversation->customer_id != $customer->id) {
                $prev_customer_id = $conversation->customer_id;
                $prev_customer_email = $conversation->customer_email;

                $cc[] = $conversation->customer_email;
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
            if (count($attachments)) {
                $conversation->has_attachments = true;
            }
            $conversation->mailbox_id = $mailbox_id;
            $conversation->customer_id = $customer->id;
            $conversation->created_by_customer_id = $customer->id;
            $conversation->source_via = Conversation::PERSON_CUSTOMER;
            $conversation->source_type = Conversation::SOURCE_TYPE_EMAIL;
        }
        // Save extra recipients to CC
        $conversation->setCc(array_merge($cc, $to));
        $conversation->setBcc($bcc);
        $conversation->customer_email = $from;
        // Reply from customer makes conversation active
        $conversation->status = Conversation::STATUS_ACTIVE;
        $conversation->last_reply_at = $now;
        $conversation->last_reply_from = Conversation::PERSON_CUSTOMER;
        // Set folder id
        $conversation->updateFolder();
        $conversation->save();

        // Update folders counters
        $conversation->mailbox->updateFoldersCounters();

        // Thread
        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->user_id = $conversation->user_id;
        $thread->type = Thread::TYPE_CUSTOMER;
        $thread->status = $conversation->status;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->message_id = $message_id;
        $thread->headers = $headers;
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

        if ($new) {
            event(new CustomerCreatedConversation($conversation, $thread));
            \Eventy::action('conversation.created_by_customer', $conversation, $thread);
        } else {
            event(new CustomerReplied($conversation, $thread));
            \Eventy::action('conversation.customer_replied', $conversation, $thread);
        }

        // Conversation customer changed
        if ($prev_customer_id) {
            event(new ConversationCustomerChanged($conversation, $prev_customer_id, $prev_customer_email, null, $customer));
        }

        return $thread->id;
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
        if ($conversation->status != $mailbox->ticket_status) {
            \Eventy::action('conversation.status_changed_by_user', $conversation, $user, true);
        }
        $conversation->status = $mailbox->ticket_status;
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

        return $thread->id;
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

        if ($is_html) {
            // Extract body content from HTML
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
            libxml_use_internal_errors(false);
            $bodies = $dom->getElementsByTagName('body');
            if ($bodies->length == 1) {
                $body_el = $bodies->item(0);
                $body = $dom->saveHTML($body_el);
            }
            preg_match("/<body[^>]*>(.*?)<\/body>/is", $body, $matches);
            if (count($matches)) {
                $body = $matches[1];
            }
        } else {
            $body = nl2br($body);
        }

        // This is reply, we need to separate reply text from old text
        if ($is_reply) {
            // Check all separators and choose the shortest reply
            $reply_bodies = [];
            foreach (Mail::$alternative_reply_separators as $alt_separator) {
                $parts = explode($alt_separator, $body);
                if (count($parts) > 1) {
                    $reply_bodies[] = $parts[0];
                }
            }
            if (count($reply_bodies)) {
                usort($reply_bodies, $cmp_reply_length_desc);

                return $reply_bodies[0];
            }
        }

        return $body;
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
        foreach ($obj_list as $item) {
            $item->mail = Email::sanitizeEmail($item->mail);
            if ($item->mail) {
                $plain_list[] = $item->mail;
            }
        }

        return $plain_list;
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
            if ($message->getDate()) {
                return $message->getDate()->timestamp;
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
            if (in_array(Email::sanitizeEmail($item->mail), $exclude_emails)) {
                continue;
            }
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
