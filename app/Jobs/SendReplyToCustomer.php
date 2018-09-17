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
        $mailbox = $this->conversation->mailbox;

        // Threads has to be sorted here, if sorted before, they come here in wrong order
        $this->threads = $this->threads->sortByDesc(function ($item, $key) {
            return $item->created_at;
        });

        $new = false;
        $headers = [];
        $this->last_thread = $this->threads->first();
        $prev_thread = null;

        // If thread is draft, it means it has been undone
        if ($this->last_thread->isDraft()) {
            return;
        }

        // Configure mail driver according to Mailbox settings
        \App\Misc\Mail::setMailDriver($mailbox, $this->last_thread->created_by_user);

        if (count($this->threads) == 1) {
            $new = true;
        }
        $i = 0;
        foreach ($this->threads as $thread) {
            if ($i == 1) {
                $prev_thread = $thread;
                break;
            }
            $i++;
        }

        // Get penultimate email Message-Id if reply
        if (!$new && !empty($prev_thread) && $prev_thread->message_id) {
            $headers['In-Reply-To'] = '<'.$prev_thread->message_id.'>';
            $headers['References'] = '<'.$prev_thread->message_id.'>';
        }
        $this->message_id = \App\Misc\Mail::MESSAGE_ID_PREFIX_REPLY_TO_CUSTOMER.'-'.$this->last_thread->id.'-'.md5($this->last_thread->id).'@'.$mailbox->getEmailDomain();
        $headers['Message-ID'] = $this->message_id;

        $this->customer_email = $this->conversation->customer_email;
        $cc_array = $mailbox->removeMailboxEmailsFromList($this->last_thread->getCcArray());
        $bcc_array = $mailbox->removeMailboxEmailsFromList($this->last_thread->getBccArray());

        // Remove customer email from CC and BCC
        $cc_array = \App\Misc\Mail::removeEmailFromArray($cc_array, $this->customer_email);
        $bcc_array = \App\Misc\Mail::removeEmailFromArray($bcc_array, $this->customer_email);

        // Remove from BCC emails which are present in CC
        foreach ($cc_array as $cc_email) {
            $bcc_array = \App\Misc\Mail::removeEmailFromArray($bcc_array, $cc_email);
        }

        $this->recipients = array_merge([$this->customer_email], $cc_array, $bcc_array);

        try {
            Mail::to([['name' => $this->customer->getFullName(), 'email' => $this->customer_email]])
                ->cc($cc_array)
                ->bcc($bcc_array)
                ->send(new ReplyToCustomer($this->conversation, $this->threads, $headers));
        } catch (\Exception $e) {
            // We come here in case SMTP server unavailable for example
            activity()
                ->causedBy($this->customer)
                ->withProperties([
                    'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
                 ])
                ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
                ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER);

            // Failures will be saved to send log when retry attempts will finish
            $this->failures = $this->recipients;

            // Save to send log
            $this->saveToSendLog($e->getMessage());

            throw $e;
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
