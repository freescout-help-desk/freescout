<?php

namespace App\Jobs;

use App\Mail\ReplyToCustomer;
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

    public $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($conversation, $threads, $customer, $user)
    {
        $this->conversation = $conversation;
        $this->threads = $threads;
        $this->customer = $customer;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mailbox = $this->conversation->mailbox;

        // Configure mail driver according to Mailbox settings
        \App\Mail\Mail::setMailDriver($mailbox, $this->user);

        // Threads has to be sorted here, if sorted before, they come here in wrong order
        $this->threads = $this->threads->sortByDesc(function ($item, $key) {
            return $item->created_at;
        });

        $new = false;
        $headers = [];
        $last_thread = $this->threads->first();
        $prev_thread = null;

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
        $message_id = 'thread-'.$last_thread->id.'-'.time().'@'.$mailbox->getEmailDomain();
        $headers['Message-ID'] = $message_id;

        Mail::to([['name' => $this->customer->getFullName(), 'email' => $this->customer->getMainEmail()]])
            ->cc($this->conversation->getCcArray())
            ->bcc($this->conversation->getBccArray())
            ->send(new ReplyToCustomer($this->conversation, $this->threads, $headers));

        $last_thread->message_id = $message_id;

        // Laravel tells us exactly what email addresses failed, let's send back the first
        $failures = Mail::failures();
        if (!empty($failures)) {
            $last_thread->send_status = Thread::SEND_STATUS_SEND_ERROR;
            $last_thread->save();

            throw new \Exception('Could not send email to: '.implode(', ', $failures));
        } else {
            $last_thread->send_status = Thread::SEND_STATUS_SEND_SUCCESS;
            $last_thread->save();
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
        $this->threads = $this->threads->sortByDesc(function ($item, $key) {
            return $item->created_at;
        });
        $this->threads[0]->send_status = Thread::SEND_STATUS_SEND_ERROR;
        $this->threads[0]->save();

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
