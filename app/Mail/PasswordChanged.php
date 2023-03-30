<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PasswordChanged extends Mailable
{
    /**
     * User to whom email is sent.
     */
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        \MailHelper::prepareMailable($this);
        
        $message = $this->subject(__('Password Changed'))
                    ->view('emails/user/password_changed')
                    ->text('emails/user/password_changed_text');

        return $message;
    }
}
