<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

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
        \MailHelper::prepareMailable($this);
        
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

        $subject = \Eventy::filter('email.user_notification.subject', '[#' . (isset($this->conversation->number) ? $this->conversation->number : '') . '] ' . $this->conversation->subject, $this->conversation);

        $customer = $this->conversation->customer;

        $thread = $this->threads->first();

        $template_html = \Eventy::filter('email.user_notification.template_name_html', 'emails/user/notification');
        $template_text = \Eventy::filter('email.user_notification.template_name_text', 'emails/user/notification_text');
        $template_fields = \Eventy::filter('email.user_notification.template_fields', ['customer' => $customer, 'thread' => $thread, 'mailbox' => $this->mailbox]);

        return $this->subject($subject)
            ->from($this->from['address'], $this->from['name'])
            ->view($template_html, $template_fields)
            ->text($template_text, $template_fields);
    }
}

