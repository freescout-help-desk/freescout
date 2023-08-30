<?php

namespace App\Jobs;

use App\Mail\ReplyToCustomer;
use App\SendLog;
use App\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Webklex\IMAP\Client;

class SendReplyToCustomer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conversation;

    public $threads;

    public $customer;

    private $failures = [];
    private $recipients = [];
    private $last_thread = null;
    private $message_id = '';
    private $customer_email = '';

    // Number of retries + 1
    public $tries = 168; // one per hour
    
    /**
     * The number of seconds the job can run before timing out.
     * fwrite() function in /vendor/swiftmailer/swiftmailer/lib/classes/Swift/Transport/StreamBuffer.php
     * in some cases may stuck and continue infinitely. This blocks queue:work and no other jobs are processed.
     * So we need to set the timeout. On timeout the whole queue:work process is being killed by Laravel.
     */
    public $timeout = 120;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($conversation, $threads, $customer)
    {
        $this->conversation = $conversation;
        $this->threads = $threads;
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $send_previous_messages = false;
        $is_forward = false;

        // When forwarding conversation is undone, new conversation is deleted.
        if (!$this->conversation) {
            return;
        }

        $mailbox = $this->conversation->mailbox;

        // Mailbox may be deleted.
        if (!$mailbox) {
            return;
        }

        // Add forwarded conversation replies.
        if ($this->conversation->threads_count == 1 && count($this->threads) == 1) {
            $forward_child_thread = $this->threads[0];
            if ($forward_child_thread->isForwarded() && $forward_child_thread->getForwardParentConversation()) {

                // Add replies from original conversation.
                $forwarded_replies = $forward_child_thread->getForwardParentConversation()->getReplies();
                $forwarded_replies = Thread::sortThreads($forwarded_replies);
                $forward_parent_thread = Thread::find($forward_child_thread->getMetaFw(Thread::META_FORWARD_PARENT_THREAD_ID));

                if ($forward_parent_thread) {
                    // Remove threads created after forwarding.
                    foreach ($forwarded_replies as $i => $thread) {
                        if ($thread->created_at > $forward_parent_thread->created_at) {
                            $forwarded_replies->forget($i);
                        }
                    }
                    $this->threads = $this->threads->merge($forwarded_replies);
                    $is_forward = true;
                }
            }
        }

        // Threads has to be sorted here, if sorted before, they come here in wrong order
        $this->threads = Thread::sortThreads($this->threads);

        $new = false;
        $headers = [];

        $this->last_thread = $this->threads->first();

        if ($this->last_thread === null) {
            return;
        }
        $last_customer_thread = null;

        // If thread is draft, it means it has been undone
        if ($this->last_thread->isDraft()) {
            return;
        }

        if (count($this->threads) == 1) {
            $new = true;
        }
        if (!$new) {
            $i = 0;
            foreach ($this->threads as $thread) {
                if ($i > 0 && $thread->type == Thread::TYPE_CUSTOMER) {
                    $last_customer_thread = $thread;
                    break;
                }
                $i++;
            }
        }

        // In-Reply-To and References headers.
        $references = '';
        if (!$new && !empty($last_customer_thread) && $last_customer_thread->message_id) {

            $headers['In-Reply-To'] = '<'.$last_customer_thread->message_id.'>';
            //$headers['References'] = '<'.$last_customer_thread->message_id.'>';
            // https://github.com/freescout-helpdesk/freescout/issues/3175
            $i = 0;
            $references_array = [];
            foreach ($this->threads as $thread) {
                if ($i > 0) {
                    $reference = $thread->getMessageId();
                    if ($reference) {
                        $references_array[] = $reference;
                    }
                }
                $i++;
            }
            if ($references_array) {
                $references = '<'.implode('> <', array_reverse($references_array)).'>';
            }
            if ($references) {
                $headers['References'] = $references;
            }
        }

        // Conversation history.
        $email_conv_history = config('app.email_conv_history');

        $threads_count = count($this->threads);

        $meta_conv_history = $this->last_thread->getMeta(Thread::META_CONVERSATION_HISTORY);
        if (!empty($meta_conv_history)) {
            $email_conv_history = $meta_conv_history;
        }

        if ($is_forward && $email_conv_history == 'global') {
            $email_conv_history = 'full';
        }

        if ($is_forward && $email_conv_history == 'none') {
            $email_conv_history = 'full';
        }

        if ($email_conv_history == 'full') {
            $send_previous_messages = true;
        }

        if ($email_conv_history == 'last') {
            $send_previous_messages = true;
            $this->threads = $this->threads->slice(0, 2);
        }

        if ($email_conv_history == 'none') {
            $send_previous_messages = false;
        }

        if (!$is_forward) {
            $send_previous_messages = \Eventy::filter('jobs.send_reply_to_customer.send_previous_messages', $send_previous_messages, $this->last_thread, $this->threads, $this->conversation, $this->customer);
        }

        // Remove previous messages.
        if (!$send_previous_messages) {
            $this->threads = $this->threads->slice(0, 1);
        }

        // Configure mail driver according to Mailbox settings
        \App\Misc\Mail::setMailDriver($mailbox, $this->last_thread->created_by_user, $this->conversation);

        $this->message_id = $this->last_thread->getMessageId($mailbox);
        $headers['Message-ID'] = $this->message_id;

        $this->customer_email = $this->conversation->customer_email;

        // For phone conversations we may need to get customer email.
        // https://github.com/freescout-helpdesk/freescout/issues/3270
        if (!$this->customer_email && $this->conversation->isPhone()) {            
            $this->customer_email = $this->conversation->customer->getMainEmail();
            if (!$this->customer_email) {
                return;
            }
        }

        $to_array = $mailbox->removeMailboxEmailsFromList($this->last_thread->getToArray());
        $cc_array = $mailbox->removeMailboxEmailsFromList($this->last_thread->getCcArray());
        $bcc_array = $mailbox->removeMailboxEmailsFromList($this->last_thread->getBccArray());

        // Remove customer email from CC and BCC
        $cc_array = \App\Misc\Mail::removeEmailFromArray($cc_array, $this->customer_email);
        $bcc_array = \App\Misc\Mail::removeEmailFromArray($bcc_array, $this->customer_email);

        // Auto Bcc.
        if ($mailbox->auto_bcc) {
            $auto_bcc = \MailHelper::sanitizeEmails($mailbox->auto_bcc);
            if ($auto_bcc) {
                $bcc_array = array_merge($bcc_array, $auto_bcc);
            }
        }

        // Remove from BCC emails which are present in CC
        foreach ($cc_array as $cc_email) {
            $bcc_array = \App\Misc\Mail::removeEmailFromArray($bcc_array, $cc_email);
        }

        $this->recipients = array_merge($to_array, $cc_array, $bcc_array);

        $to = [];
        if (count($to_array) > 1) {
            $to = $to_array;
        } else {
            $to = [['name' => $this->customer->getFullName(), 'email' => $this->customer_email]];
        }

        // If sending fails, all recipiens fail.
        // if ($this->attempts() > 1) {
        //     $cc_array = [];
        //     $bcc_array = [];
        // }

        $subject = $this->conversation->subject;
        if (!$new && !$is_forward) {
            $subject = 'Re: '.$subject;
        }
        $subject = \Eventy::filter('email.reply_to_customer.subject', $subject, $this->conversation);
        $this->threads = \Eventy::filter('email.reply_to_customer.threads', $this->threads, $this->conversation, $mailbox);

        $headers['X-FreeScout-Mail-Type'] = 'customer.message';

        $reply_mail = new ReplyToCustomer($this->conversation, $this->threads, $headers, $mailbox, $subject, $threads_count);

        try {
            Mail::to($to)
                ->cc($cc_array)
                ->bcc($bcc_array)
                ->send($reply_mail);
        } catch (\Exception $e) {
            // We come here in case SMTP server unavailable for example
            if ($this->attempts() == 1) {
                activity()
                    ->causedBy($this->customer)
                    ->withProperties([
                        'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
                     ])
                    ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
                    ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER);
            }

            // Failures will be saved to send log when retry attempts will finish
            // Mail::failures() is empty in case of connection error.
            $this->failures = $this->recipients;

            // Save to send log (only first attempt).
            if ($this->attempts() == 1) {
                $this->saveToSendLog($e->getMessage());
            }

            $error_message = $e->getMessage();

            // Retry job with delay.
            // https://stackoverflow.com/questions/35258175/how-can-i-create-delays-between-failed-queued-job-attempts-in-laravel
            if ($this->attempts() < $this->tries && !preg_match("/".config("app.no_retry_mail_errors")."/i", $error_message)) {
                if ($this->attempts() == 1) {
                    // Second attempt after 5 min.
                    $this->release(300);
                } else {
                    // Others - after 1 hour.
                    $this->release(3600);
                }

                // If an email has not been sent after 1 hour - show an error message to support agent.
                if ($this->attempts() >= 3) {
                    $this->last_thread->send_status = SendLog::STATUS_SEND_ERROR;
                    $this->last_thread->updateSendStatusData(['msg' => $error_message]);
                    $this->last_thread->save();
                }

                throw $e;
            } else {
                $this->last_thread->send_status = SendLog::STATUS_SEND_ERROR;
                $this->last_thread->updateSendStatusData(['msg' => $error_message]);
                $this->last_thread->save();

                // This executes $this->failed().
                $this->fail($e);

                return;
            }
        }

        // Clean error message if email finally has been sent.
        if ($this->last_thread->send_status == SendLog::STATUS_SEND_ERROR) {
            $this->last_thread->send_status = null;
            $this->last_thread->updateSendStatusData(['msg' => '']);
            $this->last_thread->save();
        }

        $imap_sent_folder = $mailbox->imap_sent_folder;
        if ($imap_sent_folder) {
            try {
                $client = \MailHelper::getMailboxClient($mailbox);
                
                $client->connect();

                $envelope['from'] = $mailbox->getMailFrom(null, $this->conversation)['address'];
                $envelope['to'] = $this->customer_email;
                $envelope['subject'] = $subject;
                $envelope['date'] = now()->toRfc2822String();
                $envelope['message_id'] = $this->message_id;

                // CC.
                if (count($cc_array)) {
                    $envelope['cc'] = implode(',', $cc_array);
                }

                // Get penultimate email Message-Id if reply
                if (!$new && !empty($last_customer_thread) && $last_customer_thread->message_id) {
                    $envelope['custom_headers'] = [
                        'In-Reply-To: <'.$last_customer_thread->message_id.'>',
                        'References: '.$references,
                    ];
                }
                // Remove new lines to avoid "imap_mail_compose(): header injection attempt in subject".
                foreach ($envelope as $i => $envelope_value) {
                    $envelope[$i] = preg_replace("/[\r\n]/", '', $envelope_value);
                }

                $parts = [];

                // Multipart flag.
                if ($this->last_thread->has_attachments) {
                    $multipart = [];
                    $multipart["type"] = TYPEMULTIPART;
                    $multipart["subtype"] = "alternative";
                    $parts[] = $multipart;
                }

                // Body.
                $part_body['type'] = TYPETEXT;
                $part_body['subtype'] = 'html';
                $part_body['contents.data'] = $reply_mail->render();
                $part_body['charset'] = 'utf-8';

                $parts[] = $part_body;

                // Add attachments.
                if ($this->last_thread->has_attachments) {

                    foreach ($this->last_thread->attachments as $attachment) {

                        if ($attachment->embedded) {
                            continue;
                        }

                        if ($attachment->fileExists()) {
                            $part = [];
                            $part["type"] = 'APPLICATION';
                            $part["encoding"] = ENCBASE64;
                            $part["subtype"] = "octet-stream";
                            $part["description"] = $attachment->file_name;
                            $part['disposition.type'] = 'attachment';
                            $part['disposition'] = array('filename' => $attachment->file_name);
                            $part['type.parameters'] = array('name' => $attachment->file_name);
                            $part["description"] = '';
                            $part["contents.data"] = base64_encode($attachment->getFileContents());
                            
                            $parts[] = $part;
                        } else {
                            \Log::error('[IMAP Folder To Save Outgoing Replies] Thread: '.$this->last_thread->id.'. Attachment file not find on disk: '.$attachment->getLocalFilePath());
                        }
                    }
                }

                try {
                    // https://github.com/Webklex/php-imap/issues/380
                    if (method_exists($client, 'getFolderByPath')) {
                        $folder = $client->getFolderByPath($imap_sent_folder);
                    } else {
                        $folder = $client->getFolder($imap_sent_folder);
                    }
                    // Get folder method does not work if sent folder has spaces.
                    if ($folder) {
                        try {
                            $save_result = $this->saveEmailToFolder($client, $folder, $envelope, $parts, $bcc_array);
                            // Sometimes emails with attachments by some reason are not saved.
                            // https://github.com/freescout-helpdesk/freescout/issues/2749
                            if (!$save_result) {
                                // Save without attachments.
                                $this->saveEmailToFolder($client, $folder, $envelope, [$part_body], $bcc_array);
                            }
                        } catch (\Exception $e) {
                            // Just log error and continue.
                            \Helper::logException($e, 'Could not save outgoing reply to the IMAP folder: ');
                        }
                    } else {
                        \Log::error('Could not save outgoing reply to the IMAP folder (make sure IMAP folder does not have spaces - folders with spaces do not work): '.$imap_sent_folder);
                    }
                } catch (\Exception $e) {
                    // Just log error and continue.
                    \Helper::logException($e, 'Could not save outgoing reply to the IMAP folder, IMAP folder not found: '.$imap_sent_folder.' - ');
                    //$this->saveToSendLog('['.date('Y-m-d H:i:s').'] Could not save outgoing reply to the IMAP folder: '.$imap_sent_folder);
                }
            } catch (\Exception $e) {
                // Just log error and continue.
                //$this->saveToSendLog('['.date('Y-m-d H:i:s').'] Could not get mailbox IMAP folder: '.$imap_sent_folder);
                \Helper::logException($e, 'Could not save outgoing reply to the IMAP folder: '.$imap_sent_folder.' - ');
            }
        }

        // In message_id we are storing Message-ID of the incoming email which created the thread
        // Outcoming message_id can be generated for each thread by thread->id
        // $this->last_thread->message_id = $message_id;
        // $this->last_thread->save();

        // Laravel tells us exactly what email addresses failed
        $this->failures = Mail::failures();

        // Save to send log
        $this->saveToSendLog();
    }

    // Save an email to IMAP folder.
    public function saveEmailToFolder($client, $folder, $envelope, $parts, $bcc = [])
    {
        $envelope_str = imap_mail_compose($envelope, $parts);

        // Add BCC.
        // https://stackoverflow.com/questions/47353938/php-imap-append-with-bcc
        if (!empty($bcc)) {
            // There will be a "To:" parameter for sure.
            $to_pos = strpos($envelope_str , "To:");
            if ($to_pos !== false) {
                $bcc_str = "Bcc: " . implode(',', $bcc) . "\r\n";
                $envelope_str = substr_replace($envelope_str , $bcc_str, $to_pos, 0);
            }
        }

        if (get_class($client) == 'Webklex\PHPIMAP\Client') {
            return $folder->appendMessage($envelope_str, ['Seen'], now()->format('d-M-Y H:i:s O'));
        } else {
            return $folder->appendMessage($envelope_str, '\Seen', now()->format('d-M-Y H:i:s O'));
        }
    }

    /**
     * The job failed to process.
     * This method is called after attempts had finished.
     * At this stage method has access only to variables passed in constructor.
     *
     * @param Exception $exception
     *
     * @return void
     */
    public function failed(\Exception $e)
    {
        activity()
           ->causedBy($this->customer)
           ->withProperties([
                'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
                'to'       => $this->customer_email,
            ])
           ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
           ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER);

        $this->saveToSendLog();
    }

    /**
     * Save emails to send log.
     */
    public function saveToSendLog($error_message = '')
    {
        foreach ($this->recipients as $recipient) {
            if (in_array($recipient, $this->failures)) {
                $status = SendLog::STATUS_SEND_ERROR;
                $status_message = $error_message;
            } else {
                $status = SendLog::STATUS_ACCEPTED;
                $status_message = '';
            }
            if ($this->customer_email == $recipient) {
                $customer_id = $this->customer->id;
            } else {
                $customer_id = null;
            }
            SendLog::log($this->last_thread->id, $this->message_id, $recipient, SendLog::MAIL_TYPE_EMAIL_TO_CUSTOMER, $status, $customer_id, null, $status_message);
        }
    }
}
