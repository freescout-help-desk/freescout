<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class Test extends Mailable
{
    public $mailbox;

    /**
     * Create a new message instance.
     */
    public function __construct($mailbox = null)
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
        $this->withSwiftMessage(function ($swiftmessage) {
            $headers = $swiftmessage->getHeaders();
            $headers->addTextHeader('X-FreeScout-Mail-Type', 'test.mailbox');

            return $swiftmessage;
        });

        $message = $this->subject(__(':app_name Test Email', ['app_name' => \Config::get('app.name')]));
        if ($this->mailbox) {
            $message->view('emails/user/test', ['mailbox' => $this->mailbox]);
        } else {
            $message->view('emails/user/test_system');
        }

        return $message;
    }
}
