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

    // Max retries + 1
    public $tries = 168; // One per hour

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
        \App\Misc\Mail::setMailDriver($mailbox);

        // Threads has to be sorted here, if sorted before, they come here in wrong order
        $this->threads = $this->threads->sortByDesc(function ($item, $key) {
            return $item->id;
        });

        $headers = [];
        $last_thread = $this->threads->first();

        if (!$last_thread) {
            return;
        }

        // If thread is draft, it means it has been undone
        if ($last_thread->isDraft()) {
            return;
        }

        // Limit conversation history
        if (config('app.email_user_history') == 'last') {
            $this->threads = $this->threads->slice(0, 2);
        }

        if (config('app.email_user_history') == 'none') {
            $this->threads = $this->threads->slice(0, 1);
        }

        // All notification for the same conversation has same dummy Message-ID
        $prev_message_id = \App\Misc\Mail::MESSAGE_ID_PREFIX_NOTIFICATION_IN_REPLY.'-'.$this->conversation->id.'-'.md5($this->conversation->id).'@'.$mailbox->getEmailDomain();
        $headers['In-Reply-To'] = '<'.$prev_message_id.'>';
        $headers['References'] = '<'.$prev_message_id.'>';

        // We throw an exception if any of the send attempts throws an exception (connection error, etc)
        $global_exception = null;

        foreach ($this->users as $user) {

            // User cam ne deleted.
            if (!isset($user->id)) {
                continue;
            }

            // If for one user sending fails the job is marked as failed and retried after some time.
            // So we have to check if notification email has already been successfully sent to this user.
            if ($this->attempts() > 1) {
                // Maybe add indexes to the table.
                $already_sent = SendLog::where('thread_id', $last_thread->id)
                    ->where('mail_type', SendLog::MAIL_TYPE_USER_NOTIFICATION)
                    ->where('user_id', $user->id)
                    ->whereIn('status', SendLog::$sent_success)
                    ->exists();
                if ($already_sent) {
                    continue;
                }
            }

            $message_id = \App\Misc\Mail::MESSAGE_ID_PREFIX_NOTIFICATION.'-'.$last_thread->id.'-'.$user->id.'-'.time().'@'.$mailbox->getEmailDomain();
            $headers['Message-ID'] = $message_id;

            // If this is notification on message from customer, set customer as sender name
            $from_name = '';
            if ($last_thread->type == Thread::TYPE_CUSTOMER) {
                $from_name = '';
                if ($last_thread->customer) {
                    $from_name = $last_thread->customer->getFullName(true, true);
                }
                if ($from_name) {
                    $from_name = $from_name.' '.__('via').' '.$mailbox->name;
                }
            }
            if (!$from_name) {
                $from_name = $mailbox->name;
            }
            $from = ['address' => $mailbox->email, 'name' => $from_name];

            // Set user language
            app()->setLocale($user->getLocale());

            $headers['X-FreeScout-Mail-Type'] = 'user.notification';

            $exception = null;

            try {
                Mail::to([['name' => $user->getFullName(), 'email' => $user->email]])
                    ->send(new UserNotification($user, $this->conversation, $this->threads, $headers, $from, $mailbox));
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

            $status_message = '';
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

            SendLog::log($last_thread->id, $message_id, $user->email, SendLog::MAIL_TYPE_USER_NOTIFICATION, $status, null, $user->id, $status_message);
        }

        if ($global_exception) {
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

                throw $global_exception;
            } else {
                $this->fail($global_exception);

                return;
            }
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
           //->causedBy($this->customer)
           ->withProperties([
                'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
            ])
           ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
           ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER);
    }
}
