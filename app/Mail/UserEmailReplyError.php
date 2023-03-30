<?php
/**
 * User replied from wrong email address to the email notification.
 */

namespace App\Mail;

use Illuminate\Mail\Mailable;

class UserEmailReplyError extends Mailable
{
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        \MailHelper::prepareMailable($this);
        
        return $this->subject(__('Unable to process your update'))
            ->view('emails/user/email_reply_error');
    }
}
