<?php

namespace App\Jobs;

use App\Mail\UserNotification;
use App\SendLog;
use App\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class SendNotificationToUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $users;

    public $conversation;

    public $threads;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($users, $conversation, $threads)
    {
        $this->users = $users;
        $this->conversation = $conversation;
        $this->threads = $threads;
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
        \App\Mail\Mail::setMailDriver($mailbox);

        // Threads has to be sorted here, if sorted before, they come here in wrong order
        $this->threads = $this->threads->sortByDesc(function ($item, $key) {
            return $item->created_at;
        });

        $headers = [];
        $last_thread = $this->threads->first();

        // All notification for the same conversation has same dummy Message-ID
        $prev_message_id = \App\Mail\Mail::MESSAGE_ID_PREFIX_NOTIFICATION_IN_REPLY.'-'.$this->conversation->id.'-'.md5($this->conversation->id).'@'.$mailbox->getEmailDomain();
        $headers['In-Reply-To'] = '<'.$prev_message_id.'>';
        $headers['References'] = '<'.$prev_message_id.'>';

        // We throw an exception if any of the send attempts throws an exception (connection error, etc)
        $global_exception = null;

        foreach ($this->users as $user) {
            $message_id = \App\Mail\Mail::MESSAGE_ID_PREFIX_NOTIFICATION.'-'.$last_thread->id.'-'.$user->id.'-'.time().'@'.$mailbox->getEmailDomain();
            $headers['Message-ID'] = $message_id;

            // If this is notification on message from customer set customer as sender name
            $from_name = '';
            if ($last_thread->type == Thread::TYPE_CUSTOMER) {
                $from_name = $last_thread->customer->getFullName();
                if ($from_name) {
                    $from_name = $from_name.' '.__('via').' '.\Config::get('app.name');
                }
            }
            if (!$from_name) {
                $from_name = \Config::get('app.name');
            }
            $from = ['address' => $mailbox->email, 'name' => $from_name];

            $exception = null;

            try {
                Mail::to([['name' => $user->getFullName(), 'email' => $user->email]])
                    ->send(new UserNotification($user, $this->conversation, $this->threads, $headers, $from));
            } catch (\Exception $e) {
                // We come here in case SMTP server unavailable for example
                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
                     ])
                    ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
                    ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER);

                $exception = $e;
                $global_exception = $e;
            }

            if ($exception) {
                $status = SendLog::STATUS_SEND_ERROR;
                $status_message = $exception->getMessage();
            } else {
                $failures = Mail::failures();

                // Save to send log
                if (!empty($failures) && in_array($user->email, $failures)) {
                    $status = SendLog::STATUS_SEND_ERROR;
                } else {
                    $status = SendLog::STATUS_ACCEPTED;
                }
            }

            SendLog::log($last_thread->id, $message_id, $user->email, $status, null, $user->id, $status_message);
        }

        if ($global_exception) {
            throw $global_exception;
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
    // public function failed(\Exception $e)
    // {
    //     // Write to activity log
    //     activity()
    //        //->causedBy($this->customer)
    //        ->withProperties([
    //             'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
    //             //'to'       => $this->customer->getMainEmail(),
    //         ])
    //        ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
    //        ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER);
    // }
}
