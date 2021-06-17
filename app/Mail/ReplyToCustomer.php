<?php

namespace App\Mail;

use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailable;

// https://medium.com/@guysmilez/queuing-mailables-with-custom-headers-in-laravel-5-4-ab615f022f17
//abstract class AbstractMessage extends Mailable
class ReplyToCustomer extends Mailable
{
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
     * Custom headers.
     *
     * @var array
     */
    public $headers = [];

    /**
     * Mailbox.
     *
     * @var array
     */
    public $mailbox;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($conversation, $threads, $headers, $mailbox)
    {
        $this->conversation = $conversation;
        $this->threads = $threads;
        $this->headers = $headers;
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

        $subject = $this->conversation->subject;
        if (count($this->threads) > 1) {
            $subject = 'Re: '.$subject;
        }
        $subject = \Eventy::filter('email.reply_to_customer.subject', $subject, $this->conversation);

        // from($this->from) Sets only email, name stays empty.
        // So we set from in Mail::setMailDriver
        $message = $this->subject($subject)
                    ->view('emails/customer/reply_fancy')
                    ->text('emails/customer/reply_fancy_text');

        if ($this->threads->first()->has_attachments) {
            foreach ($this->threads->first()->attachments as $attachment) {
                $message->attach($attachment->getLocalFilePath());
            }
        }

        return $message;
    }

    /*
     * Send the message using the given mailer.
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    // public function send(MailerContract $mailer)
    // {
    //     Container::getInstance()->call([$this, 'build']);

    //     $mailer->send($this->buildView(), $this->buildViewData(), function ($message) {
    //         $this->buildFrom($message)
    //              ->buildRecipients($message)
    //              ->buildSubject($message)
    //              ->buildAttachments($message)
    //              ->addCustomHeaders($message) // This is new!
    //              ->runCallbacks($message);
    //     });
    // }

    /*
     * Add custom headers to the message.
     *
     * @param \Illuminate\Mail\Message $message
     * @return $this
     */
    // protected function addCustomHeaders($message)
    // {
    //     $swift = $message->getSwiftMessage();
    //     $headers = $swift->getHeaders();

    //     // By some reason $this->headers are empty here
    //     foreach ($this->headers as $header => $value) {
    //         $headers->addTextHeader($header, $value);
    //     }
    //     return $this;
    // }
}
