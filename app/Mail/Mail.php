<?php

namespace App\Mail;

class Mail
{
    /**
     * Configure mail sending parameters.
     *
     * @param App\Mailbox $mailbox
     */
    public static function setMailDriver($mailbox = null, $user = null)
    {
        if ($mailbox) {
            // Configure mail driver according to Mailbox settings
            \Config::set('mail.driver', $mailbox->getMailDriverName());
            \Config::set('mail.from', $mailbox->getMailFrom($user));
        } else {
            // Use default settings
            \Config::set('mail.driver', env('MAIL_DRIVER'));
            \Config::set('mail.from', ['address' => env('MAIL_FROM_ADDRESS'), 'name' => '']);
        }
        (new \Illuminate\Mail\MailServiceProvider(app()))->register();
    }
}
