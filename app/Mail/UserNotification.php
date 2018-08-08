<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Recipient.
     *
     * @var [type]
     */
    public $user;

    /**
     * Conversation to send.
     *
     * @var [type]
     */
    public $conversation;

    /**
     * Threads to send.
     *
     * @var [type]
     */
    public $threads;

    /**
     * Custom haders.
     *
     * @var array
     */
    public $headers = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $conversation, $threads, $headers)
    {
        $this->user = $user;
        $this->conversation = $conversation;
        $this->threads = $threads;
        $this->headers = $headers;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Set Message-ID
        // Settings via $this->addCustomHeaders does not work
        $new_headers = $this->headers;
        if (!empty($new_headers)) {
            $this->withSwiftMessage(function ($swiftmessage) use ($new_headers) {
                if (!empty($new_headers['Message-ID'])) {
                    $swiftmessage->setId($new_headers['Message-ID']);
                }
                $headers = $swiftmessage->getHeaders();
                foreach ($new_headers as $header => $value) {
                    if ($header != 'Message-ID') {
                        $headers->addTextHeader($header, $value);
                    }
                }

                return $swiftmessage;
            });
        }

        $subject = '[#'.$this->conversation->number.'] ' .$this->conversation->subject;

        $customer = $this->conversation->customer;

        $thread = $this->threads->first();

        return $this->subject($subject)
                    ->view('emails/user/notification', ['customer' => $customer, 'thread' => $thread])
                    ->text('emails/user/notification_text', ['customer' => $customer, 'thread' => $thread]);
    }
}
