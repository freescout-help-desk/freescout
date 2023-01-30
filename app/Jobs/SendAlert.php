<?php
/**
 * Send alert to super admin.
 */

namespace App\Jobs;

use App\Mail\Alert;
use App\SendLog;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class SendAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $text;

    public $title;

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
    public function __construct($text, $title = '')
    {
        $this->text = $text;
        $this->title = $title;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Configure mail driver according to Mailbox settings
        \MailHelper::setSystemMailDriver();

        $recipients = [];

        $super_admin = User::getSuperAdmin();
        if ($super_admin) {
            $recipients[] = $super_admin->email;
        }

        $extra = \MailHelper::sanitizeEmails(\Option::get('alert_recipients'));
        if ($extra) {
            $recipients = array_unique(array_merge($recipients, $extra));
        }

        foreach ($recipients as $recipient) {
            $exception = null;

            try {
                Mail::to([['name' => '', 'email' => $recipient]])
                    ->send(new Alert($this->text, $this->title));
            } catch (\Exception $e) {
                // We come here in case SMTP server unavailable for example
                activity()
                    ->withProperties([
                        'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
                     ])
                    ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
                    ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_ALERT);

                $exception = $e;
            }

            $status_message = '';
            if ($exception) {
                $status = SendLog::STATUS_SEND_ERROR;
                $status_message = $exception->getMessage();
            } else {
                $failures = Mail::failures();

                if (!empty($failures)) {
                    $status = SendLog::STATUS_SEND_ERROR;
                } else {
                    $status = SendLog::STATUS_ACCEPTED;
                }
            }

            SendLog::log(null, null, $recipient, SendLog::MAIL_TYPE_ALERT, $status, null, null, $status_message);
        }

        if ($exception) {
            throw $exception;
        }
    }
}
