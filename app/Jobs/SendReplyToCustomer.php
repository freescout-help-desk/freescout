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

    // Number of retries + 1
    public $tries = 168; // one per hour

    public $conversation;

    public $threads;

    public $customer;

    private $failures = [];
    private $recipients = [];
    private $last_thread = null;
    private $message_id = '';
    private $customer_email = '';

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

        // When forwarding conversation is undone, new conversation is deleted.
        if (!$this->conversation) {
            return;
        }

        $mailbox = $this->conversation->mailbox;

        // Add forwarded conversation replies.
        if ($this->conversation->threads_count == 1 && count($this->threads) == 1) {
            $forward_child_thread = $this->threads[0];
            if ($forward_child_thread->isForwarded() && $forward_child_thread->getForwardParentConversation()) {

                // Add replies from original conversation.
                $forwarded_replies = $forward_child_thread->getForwardParentConversation()->getReplies();
                $forwarded_replies = $forwarded_replies->sortByDesc(function ($item, $key) {
                    return $item->created_at;
                });
                $forward_parent_thread = Thread::find($forward_child_thread->getMeta('forward_parent_thread_id'));

                if ($forward_parent_thread) {
                    // Remove threads created after forwarding.
                    foreach ($forwarded_replies as $i => $thread) {
                        if ($thread->created_at > $forward_parent_thread->created_at) {
                            $forwarded_replies->forget($i);
                        }
                    }
                    $this->threads = $this->threads->merge($forwarded_replies);
                    $send_previous_messages = true;
                }
            }
        }

        // Threads has to be sorted here, if sorted before, they come here in wrong order
        $this->threads = $this->threads->sortByDesc(function ($item, $key) {
            return $item->id;
        });

        $new = false;
        $headers = [];
        $this->last_thread = $this->threads->first();
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

        $email_conv_history = $this->conversation->getEmailHistoryCode();
        if (!$email_conv_history || $email_conv_history === 'global') {
            $email_conv_history = config('app.email_conv_history');
        }

        if ($email_conv_history == 'full') {
            $send_previous_messages = true;
        }

        if ($email_conv_history == 'last') {
            $send_previous_messages = true;
            $this->threads = $this->threads->slice(0, 2);
        }

        if (config('app.email_conv_history') == 'last') {
            $send_previous_messages = true;
            $this->threads = $this->threads->slice(0, 2);
        }

        $send_previous_messages = \Eventy::filter('jobs.send_reply_to_customer.send_previous_messages', $send_previous_messages, $this->last_thread, $this->threads, $this->conversation, $this->customer);

        // Remove previous messages.
        if (!$send_previous_messages) {
            $this->threads = $this->threads->slice(0, 1);
        }

        // Configure mail driver according to Mailbox settings
        \App\Misc\Mail::setMailDriver($mailbox, $this->last_thread->created_by_user);

        // Get penultimate email Message-Id if reply
        if (!$new && !empty($last_customer_thread) && $last_customer_thread->message_id) {

            $headers['In-Reply-To'] = '<'.$last_customer_thread->message_id.'>';
            $headers['References'] = '<'.$last_customer_thread->message_id.'>';
        }

        $this->message_id = \App\Misc\Mail::MESSAGE_ID_PREFIX_REPLY_TO_CUSTOMER.'-'.$this->last_thread->id.'-'.md5($this->last_thread->id).'@'.$mailbox->getEmailDomain();
        $headers['Message-ID'] = $this->message_id;

        $this->customer_email = $this->conversation->customer_email;
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

        $reply_mail = new ReplyToCustomer($this->conversation, $this->threads, $headers, $mailbox);

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

            // Retry job with delay.
            // https://stackoverflow.com/questions/35258175/how-can-i-create-delays-between-failed-queued-job-attempts-in-laravel
            if ($this->attempts() < $this->tries) {
                if ($this->attempts() == 1) {
                    // Second attempt after 5 min.
                    $this->release(300);
                } else {
                    // Others - after 1 hour.
                    $this->release(3600);
                }

                throw $e;
            } else {
                $this->last_thread->send_status = SendLog::STATUS_SEND_ERROR;
                $this->last_thread->updateSendStatusData(['msg' => $e->getMessage()]);
                $this->last_thread->save();

                // This executes $this->failed().
                $this->fail($e);

                return;
            }
        }

        $imap_sent_folder = $mailbox->imap_sent_folder;
        if ($imap_sent_folder) {
            $client = \MailHelper::getMailboxClient($mailbox);
            $client->connect();

            $envelope['from'] = $mailbox->getMailFrom()['address'];
            $envelope['to'] = $this->customer_email;
            $envelope['subject'] = 'Re: ' . $this->conversation->subject;

            // Get penultimate email Message-Id if reply
            if (!$new && !empty($last_customer_thread) && $last_customer_thread->message_id) {
                $envelope['custom_headers'] = [
                    'In-Reply-To: <'.$last_customer_thread->message_id.'>',
                    'References: <'.$last_customer_thread->message_id.'>'
                ];
            }

            $part1['type'] = TYPETEXT;
            $part1['subtype'] = 'html';
            $part1['contents.data'] = $reply_mail->render();

            try {
                $folder = $client->getFolder($imap_sent_folder);
                $folder->appendMessage(imap_mail_compose($envelope, [$part1]), '\Seen', now()->format('d-M-Y H:i:s O'));
            } catch (\Exception $e) {
                // Just log error and continue.
                $this->saveToSendLog('['.date('Y-m-d H:i:s').'] Could not get mailbox IMAP folder: '.$imap_sent_folder);
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
