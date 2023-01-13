<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AutoReply extends Mailable
{
    /**
     * Conversation created by customer.
     */
    public $conversation;

    /**
     * Mailbox.
     */
    public $mailbox;

    /**
     * Customer.
     */
    public $customer;

    /**
     * Custom headers.
     */
    public $headers = [];

    /**
     * Create a new message instance.
     */
    public function __construct($conversation, $mailbox, $customer, $headers)
    {
        $this->conversation = $conversation;
        $this->mailbox = $mailbox;
        $this->customer = $customer;
        $this->headers = $headers;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        \MailHelper::prepareMailable($this);
        
        $view_params = [];

        // Set headers
        $this->setHeaders();

        $data = [
            'mailbox'      => $this->mailbox,
            'conversation' => $this->conversation,
            'customer'     => $this->customer,
        ];

        // Set variables
        $subject = \MailHelper::replaceMailVars($this->mailbox->auto_reply_subject, $data);
        $view_params['auto_reply_message'] = \MailHelper::replaceMailVars($this->mailbox->auto_reply_message, $data);

        $subject = \Eventy::filter('email.auto_reply.subject', $subject, $this->conversation);

        $message = $this->subject($subject)
                    ->view('emails/customer/auto_reply', $view_params)
                    ->text('emails/customer/auto_reply_text', $view_params);

        return $message;
    }

    /**
     * Set headers.
     * Settings via $this->addCustomHeaders does not work.
     */
    public function setHeaders()
    {
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
    }
}
