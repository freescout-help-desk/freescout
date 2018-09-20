<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserNotification extends Mailable
{
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
     * From.
     *
     * @var array
     */
    public $from = [];

    /**
     * Mailbox.
     * 
     * @var [type]
     */
    public $mailbox;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $conversation, $threads, $headers, $from, $mailbox)
    {
        $this->user = $user;
        $this->conversation = $conversation;
        $this->threads = $threads;
        $this->headers = $headers;
        $this->from = $from;
        $this->mailbox = $mailbox;
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

        $subject = '[#'.$this->conversation->number.'] '.$this->conversation->subject;

        $customer = $this->conversation->customer;

        $thread = $this->threads->first();

        return $this->subject($subject)
            ->from($this->from['address'], $this->from['name'])
            ->view('emails/user/notification', ['customer' => $customer, 'thread' => $thread, 'mailbox' => $this->mailbox])
            ->text('emails/user/notification_text', ['customer' => $customer, 'thread' => $thread, 'mailbox' => $this->mailbox]);
    }
}
