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

        // Configure mail driver according to Mailbox settings
        \App\Mail\Mail::setMailDriver($mailbox, $this->last_thread->created_by_user);

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
        $this->message_id = \App\Mail\Mail::MESSAGE_ID_PREFIX_REPLY_TO_CUSTOMER.'-'.$this->last_thread->id.'-'.md5($this->last_thread->id).'@'.$mailbox->getEmailDomain();
        $headers['Message-ID'] = $this->message_id;

        $customer_email = $this->customer->getMainEmail();
        $cc_array = $mailbox->removeMailboxEmailsFromList($this->last_thread->getCcArray());
        $bcc_array = $mailbox->removeMailboxEmailsFromList($this->last_thread->getBccArray());
        $this->recipients = array_merge([$customer_email], $cc_array, $bcc_array);

        try {
            Mail::to([['name' => $this->customer->getFullName(), 'email' => $customer_email]])
                ->cc($cc_array)
                ->bcc($bcc_array)
                ->send(new ReplyToCustomer($this->conversation, $this->threads, $headers));
        } catch (\Exception $e) {
            activity()
               //->causedBy()
               ->withProperties([
                    'error'    => $e->getMessage(),
                ])
               ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
               ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR);

            // Failures will be save to send log when retry attempts will finish
            $this->failures = $this->recipients;

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

        if (!empty($failures)) {
            throw new \Exception('Could not send email to: '.implode(', ', $failures));
        }
    }

    /**
     * The job failed to process.
     * This method is called after number of attempts had finished.
     *
     * @param Exception $exception
     *
     * @return void
     */
    public function failed(\Exception $exception)
    {
        activity()
           ->causedBy($this->customer)
           ->withProperties([
                'error'    => $exception->getMessage(),
                'to'       => $this->customer->getMainEmail(),
            ])
           ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
           ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR);

        $this->saveToSendLog();
    }

    /**
     * Save failed email to send log.
     */
    public function saveToSendLog()
    {
        foreach ($this->recipients as $recipient) {
            if (in_array($recipient, $this->failures)) {
                $status = SendLog::STATUS_SEND_ERROR;
            } else {
                $status = SendLog::STATUS_ACCEPTED;
            }
            if ($customer_email == $recipient) {
                $customer_id = $this->customer->id;
            } else {
                $customer_id = null;
            }
            SendLog::log($this->last_thread->id, $this->message_id, $recipient, $status, $customer_id);
        }
    }
}
