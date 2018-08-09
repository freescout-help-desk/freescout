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
        $last_thread = $this->threads->first();
        $prev_thread = null;

        // Configure mail driver according to Mailbox settings
        \App\Mail\Mail::setMailDriver($mailbox, $last_thread->created_by_user);

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
        $message_id = \App\Mail\Mail::MESSAGE_ID_PREFIX_REPLY_TO_CUSTOMER.'-'.$last_thread->id.'-'.md5($last_thread->id).'@'.$mailbox->getEmailDomain();
        $headers['Message-ID'] = $message_id;

        $customer_email = $this->customer->getMainEmail();
        Mail::to([['name' => $this->customer->getFullName(), 'email' => $customer_email]])
            ->cc($last_thread->getCcArray())
            ->bcc($last_thread->getBccArray())
            ->send(new ReplyToCustomer($this->conversation, $this->threads, $headers));

        // In message_id we are storing Message-ID of the incoming email which created the thread
        // Outcoming message_id can be generated for each thread by thread->id
        // $last_thread->message_id = $message_id;
        // $last_thread->save();

        // Laravel tells us exactly what email addresses failed
        $failures = Mail::failures();

        // Save to send log
        $recipients = array_merge([$customer_email], $last_thread->getCcArray(), $last_thread->getBccArray());
        foreach ($recipients as $recipient) {
            if (in_array($recipient, $failures)) {
                $status = SendLog::STATUS_SEND_ERROR;
            } else {
                $status = SendLog::STATUS_ACCEPTED;
            }
            if ($customer_email == $recipient) {
                $customer_id = $this->customer->id;
            } else {
                $customer_id = null;
            }
            SendLog::log($last_thread->id, $message_id, $recipient, $status, $customer_id);
        }

        if (!empty($failures)) {
            throw new \Exception('Could not send email to: '.implode(', ', $failures));
        }
    }

    /**
     * The job failed to process.
     *
     * @param Exception $exception
     *
     * @return void
     */
    public function failed(\Exception $exception)
    {
        // No need
        // $this->threads = $this->threads->sortByDesc(function ($item, $key) {
        //     return $item->created_at;
        // });
        // $this->threads[0]->send_status = Thread::SEND_STATUS_SEND_ERROR;
        // $this->threads[0]->save();

        activity()
           ->causedBy($this->customer)
           ->withProperties([
                'error'    => $exception->getMessage(),
                'to'       => $this->customer->getMainEmail(),
            ])
           ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
           ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR);
    }
}
