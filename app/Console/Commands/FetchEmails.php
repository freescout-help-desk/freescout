<?php

namespace App\Console\Commands;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Email;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Mail\Mail;
use App\Mailbox;
use App\Option;
use App\Subscription;
use App\Thread;
use Illuminate\Console\Command;
use Webklex\IMAP\Client;

class FetchEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:fetch-emails';

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
                Option::set('fetch_emails_last_successful_run', $now);
            } catch (\Exception $e) {
                $this->logError('Error: '.$e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')').')';
            }

            // Middleware Terminate handler is not launched for commands,
            // so we need to run processing subscription events manually
            Subscription::processEvents();
        }
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

        // Get unseen messages for a period
        $messages = $folder->query()->unseen()->since(now()->subDays(1))->leaveUnread()->get();

        if ($client->getLastError()) {
            $this->logError($client->getLastError());
        }

        $this->line('['.date('Y-m-d H:i:s').'] Fetched: '.count($messages));

        $message_index = 1;

        try {
            // We have to sort messages manually, as they can be in non-chronological order
            $messages = $this->sortMessage($messages);
            foreach ($messages as $message_id => $message) {
                $this->line('['.date('Y-m-d H:i:s').'] '.$message_index.') '.$message->getSubject());
                $message_index++;

                // Check if message already fetched
                if (Thread::where('message_id', $message_id)->first()) {
                    $this->line('['.date('Y-m-d H:i:s').'] Message with such Message-ID has been fetched before: '.$message_id);
                    $message->setFlag(['Seen']);
                    continue;
                }

                // Detect prev thread
                $is_reply = false;
                $prev_thread = null;
                $in_reply_to = $message->getInReplyTo();
                $references = $message->getReferences();
                
                if ($in_reply_to) {
                    $prev_thread = Thread::where('message_id', $in_reply_to)->first();
                } elseif ($references) {
                    if (!is_array($references)) {
                        $references = array_filter(preg_split('/[, <>]/', $references));
                    }
                    $prev_thread = Thread::whereIn('message_id', $references)->first();
                }
                if (!empty($prev_thread)) {
                    $is_reply = true;
                }

                if ($message->hasHTMLBody()) {
                    // Get body and replace :cid with images URLs
                    $body = $message->getHTMLBody(true);
                    $body = $this->separateReply($body, true, $is_reply);
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

                $to = $this->formatEmailList($message->getTo());
                $to = $this->removeMailboxEmail($to, $mailbox->email);

                $cc = $this->formatEmailList($message->getCc());
                $cc = $this->removeMailboxEmail($cc, $mailbox->email);

                $bcc = $this->formatEmailList($message->getBcc());
                $bcc = $this->removeMailboxEmail($bcc, $mailbox->email);

                $attachments = $message->getAttachments();

                $save_result = $this->saveThread($mailbox->id, $message_id, $prev_thread, $from, $to, $cc, $bcc, $subject, $body, $attachments);

                if ($save_result) {
                    $message->setFlag(['Seen']);
                    $this->line('['.date('Y-m-d H:i:s').'] Processed');
                } else {
                    $this->logError('Error occured processing message');
                }
            }
        } catch (\Exception $e) {
            $message->setFlag(['Seen']);

            throw $e;
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
                    'error'    => $message,
                    'mailbox'  => $mailbox_name,
                ])
                ->useLog(\App\ActivityLog::NAME_EMAILS_FETCHING)
                ->log(\App\ActivityLog::DESCRIPTION_EMAILS_FETCHING_ERROR);
        } catch (\Exception $e) {
            // Do nothing
        }
    }

    /**
     * Save email as thread.
     */
    public function saveThread($mailbox_id, $message_id, $prev_thread, $from, $to, $cc, $bcc, $subject, $body, $attachments)
    {
        $cc = array_merge($cc, $to);

        // Find conversation
        $new = false;
        $conversation = null;
        $now = date('Y-m-d H:i:s');

        $customer = Customer::create($from);
        if ($prev_thread) {
            $conversation = $prev_thread->conversation;

            // If reply came from another customer: change customer, add original as CC
            if ($conversation->customer_id != $customer->id) {
                $cc[] = $conversation->customer->getMainEmail();
                $conversation->customer_id = $customer->id;
            }
        } else {
            // Create conversation
            $new = true;

            $conversation = new Conversation();
            $conversation->type = Conversation::TYPE_EMAIL;
            $conversation->state = Conversation::STATE_PUBLISHED;
            $conversation->subject = $subject;
            $conversation->setCc($cc);
            $conversation->setBcc($bcc);
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
        // Reply from customer makes conversation active
        $conversation->status = Conversation::STATUS_ACTIVE;
        $conversation->last_reply_at = $now;
        $conversation->last_reply_from = Conversation::PERSON_USER;
        // Set folder id
        $conversation->updateFolder();
        $conversation->save();

        // Thread
        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->type = Thread::TYPE_CUSTOMER;
        $thread->status = $conversation->status;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->message_id = $message_id;
        $thread->body = $body;
        $thread->setTo($to);
        $thread->setCc($cc);
        $thread->setBcc($bcc);
        $thread->source_via = Thread::PERSON_CUSTOMER;
        $thread->source_type = Thread::SOURCE_TYPE_EMAIL;
        $thread->customer_id = $customer->id;
        $thread->created_by_customer_id = $customer->id;
        $thread->save();

        $has_attachments = $this->saveAttachments($attachments, $thread->id);
        if ($has_attachments) {
            $thread->has_attachments = true;
            $thread->save();
        }

        if ($new) {
            event(new CustomerCreatedConversation($conversation, $thread));
        } else {
            event(new CustomerReplied($conversation, $thread));
        }

        return true;
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
        $has_attachments = false;
        foreach ($email_attachments as $email_attachment) {
            $create_result = Attachment::create(
                $email_attachment->getName(),
                $email_attachment->getMimeType(),
                Attachment::typeNameToInt($email_attachment->getType()),
                $email_attachment->getContent(),
                $thread_id
            );
            if ($create_result) {
                $has_attachments = true;
            }
        }

        return $has_attachments;
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
            $separator = Mail::REPLY_SEPARATOR_HTML;

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
            $separator = Mail::REPLY_SEPARATOR_TEXT;
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

    /**
     * Remove mailbox email from the list of emails.
     *
     * @param array  $list
     * @param string $mailbox_email [description]
     *
     * @return array
     */
    public function removeMailboxEmail($list, $mailbox_email)
    {
        if (!is_array($list)) {
            return [];
        }
        foreach ($list as $i => $email) {
            if (Email::sanitizeEmail($email) == $mailbox_email) {
                unset($list[$i]);
                break;
            }
        }

        return $list;
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
            $plain_list[] = $item->mail;
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
            return $message->getDate()->timestamp;
        });

        return $messages;
    }
}
