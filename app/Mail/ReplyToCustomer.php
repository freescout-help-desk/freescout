<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Queue\ShouldQueue;

// https://medium.com/@guysmilez/queuing-mailables-with-custom-headers-in-laravel-5-4-ab615f022f17
//abstract class AbstractMessage extends Mailable
class ReplyToCustomer extends Mailable
{
    use Queueable, SerializesModels;

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
     * Custome haders.
     * 
     * @var array
     */
    public $headers = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($conversation, $threads, $headers)
    {
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
        if (!empty($this->headers['Message-ID'])) {
            //$message_id = $this->headers['Message-ID'];
            $new_headers = $this->headers;
            $this->withSwiftMessage(function ($swiftmessage) use ($new_headers) {
                $swiftmessage->setId($new_headers['Message-ID']);
                $headers = $swiftmessage->getHeaders();
                foreach ($new_headers as $header => $value) {
                    if ($header != 'Message-ID') {
                        $headers->addTextHeader($header, $value);
                    }
                }
                return $swiftmessage;
            });
            //unset($this->headers['Message-ID']);
        }

        $subject = $this->conversation->subject;
        if (count($this->threads) > 1) {
            $subject = 'Re: '.$subject;
        }

        // from($this->from) Sets only email, name stays empty.
        return $this->subject($subject)
                    ->view('emails/customer/reply_fancy')
                    ->text('emails/customer/reply_fancy_text');
        // ->attach('/path/to/file');
    }

    /**
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

    /**
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
