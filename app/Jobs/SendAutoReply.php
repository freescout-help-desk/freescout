<?php

namespace App\Jobs;

use App\Mail\AutoReply;
use App\SendLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class SendAutoReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conversation;

    public $thread;

    public $mailbox;

    public $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($conversation, $thread, $mailbox, $customer)
    {
        $this->conversation = $conversation;
        $this->thread = $thread;
        $this->mailbox = $mailbox;
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Configure mail driver according to Mailbox settings
        \App\Misc\Mail::setMailDriver($this->mailbox);

        // Auto reply appears as reply in customer's mailbox
        $headers['In-Reply-To'] = '<'.$this->thread->message_id.'>';
        $headers['References'] = '<'.$this->thread->message_id.'>';

        // Create Message-ID for the auto reply
        $message_id = \App\Misc\Mail::MESSAGE_ID_PREFIX_AUTO_REPLY.'-'.$this->thread->id.'-'.md5($this->thread->id).'@'.$this->mailbox->getEmailDomain();
        $headers['Message-ID'] = $message_id;

        $customer_email = $this->conversation->customer_email;
        $recipients = [$customer_email];
        $failures = [];
        $exception = null;

        try {
            Mail::to([['name' => $this->customer->getFullName(), 'email' => $customer_email]])
                ->send(new AutoReply($this->conversation, $this->mailbox, $this->customer, $headers));
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
            $failures = $recipients;

            $exception = $e;
        }

        foreach ($recipients as $recipient) {
            $status_message = '';
            if ($exception) {
                $status = SendLog::STATUS_SEND_ERROR;
                $status_message = $exception->getMessage();
            } else {
                $failures = Mail::failures();

                // Status for send log
                if (!empty($failures) && in_array($recipient, $failures)) {
                    $status = SendLog::STATUS_SEND_ERROR;
                } else {
                    $status = SendLog::STATUS_ACCEPTED;
                }
            }
            if ($customer_email == $recipient) {
                $customer_id = $this->customer->id;
            } else {
                $customer_id = null;
            }

            SendLog::log($this->thread->id, $message_id, $recipient, SendLog::MAIL_TYPE_AUTO_REPLY, $status, $customer_id, null, $status_message);
        }

        if ($exception) {
            throw $exception;
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
        // Write to activity log
        activity()
           ->causedBy($this->customer)
           ->withProperties([
                'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
            ])
           ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
           ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER);
    }
}
