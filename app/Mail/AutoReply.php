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
     * Mailbox alias to send From (empty to use mailbox default email).
     */
    public $from_alias = '';

    /**
     * Create a new message instance.
     */
    public function __construct($conversation, $mailbox, $customer, $headers, $from_alias = '')
    {
        $this->conversation = $conversation;
        $this->mailbox = $mailbox;
        $this->customer = $customer;
        $this->headers = $headers;
        $this->from_alias = $from_alias;
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
        $from_alias = $this->from_alias;
        if (!empty($new_headers) || $from_alias) {
            $mailbox = $this->mailbox;
            $conversation = $this->conversation;
            $this->withSwiftMessage(function ($swiftmessage) use ($new_headers, $from_alias, $mailbox, $conversation) {
                $headers = null;

                if (!empty($new_headers)) {
                    if (!empty($new_headers['Message-ID'])) {
                        $swiftmessage->setId($new_headers['Message-ID']);
                    }
                    $headers = $swiftmessage->getHeaders();
                    foreach ($new_headers as $header => $value) {
                        if ($header != 'Message-ID') {
                            $headers->addTextHeader($header, $value);
                        }
                    }
                }

                if (!empty($from_alias)) {
                    $aliases = $mailbox->getAliases();

                    if (array_key_exists($from_alias, $aliases)) {
                        $from_alias_name = $aliases[$from_alias] ?? '';

                        // Take into account mailbox From Name setting.
                        $mailbox_mail_from = $mailbox->getMailFrom(null, $conversation);
                        if ($mailbox_mail_from['name'] == $mailbox->name && $from_alias_name) {
                            // Use name from alias.
                        } else {
                            $from_alias_name = $mailbox_mail_from['name'];
                        }

                        if (!$headers) {
                            $headers = $swiftmessage->getHeaders();
                        }

                        $swift_from = $headers->get('From');

                        if ($from_alias_name) {
                            $swift_from->setNameAddresses([
                                $from_alias => $from_alias_name,
                            ]);
                        } else {
                            $swift_from->setAddresses([
                                $from_alias,
                            ]);
                        }
                    }
                }

                return $swiftmessage;
            });
        }
    }
}
