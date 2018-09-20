<?php

namespace App\Mail;

use App\Option;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordChanged extends Mailable
{
    /**
     * User to whom email is sent.
     */
    public $user;

    /**
     * Create a new message instance.
     *
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
        $message = $this->subject(__('Password Changed'))
                    ->view('emails/user/password_changed')
                    ->text('emails/user/password_changed_text');

        return $message;
    }
}
