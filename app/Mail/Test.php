<?php

namespace App\Mail;

use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailable;

class Test extends Mailable
{
    public $mailbox;

    /**
     * Create a new message instance.
     *
     */
    public function __construct($mailbox)
    {
        $this->mailbox = $mailbox;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $message = $this->subject(__(':app_name Test Email', ['app_name' => \Config::get('app.name')]))
                    ->view('emails/user/test', ['mailbox' => $this->mailbox]);

        return $message;
    }
}
