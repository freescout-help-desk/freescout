<?php
/**
 * User replied from wrong email address to the email notification.
 */

namespace App\Jobs;

use App\Mail\UserEmailReplyError;
use App\SendLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class SendEmailReplyError implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $from;

    public $user;

    public $mailbox;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($from, $user, $mailbox)
    {
        $this->from = $from;
        $this->user = $user;
        $this->mailbox = $mailbox;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Configure mail driver according to Mailbox settings
        \App\Misc\Mail::setMailDriver($this->mailbox, null, $this->conversation);

        $exception = null;

        try {
            Mail::to([['name' => '', 'email' => $this->from]])
                ->send(new UserEmailReplyError());
        } catch (\Exception $e) {
            // We come here in case SMTP server unavailable for example
            activity()
                ->withProperties([
                    'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
                 ])
                ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
                ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_WRONG_EMAIL);

            $exception = $e;
        }

        $status_message = '';
        if ($exception) {
            $status = SendLog::STATUS_SEND_ERROR;
            $status_message = $exception->getMessage();
        } else {
            $failures = Mail::failures();

            // Save to send log
            if (!empty($failures)) {
                $status = SendLog::STATUS_SEND_ERROR;
            } else {
                $status = SendLog::STATUS_ACCEPTED;
            }
        }

        SendLog::log(null, null, $this->from, SendLog::MAIL_TYPE_WRONG_USER_EMAIL_MESSAGE, $status, null, $this->user->id, $status_message);

        if ($exception) {
            throw $exception;
        }
    }
}
