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
     * Subject.
     */
    public $subject;

    /**
     * Number of threads.
     */
    public $threads_count;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($conversation, $threads, $headers, $mailbox, $subject, $threads_count = 1)
    {
        $this->conversation = $conversation;
        $this->threads = $threads;
        $this->headers = $headers;
        $this->mailbox = $mailbox;
        $this->subject = $subject;
        $this->threads_count = $threads_count;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        \MailHelper::prepareMailable($this);

        $thread = $this->threads->first();
        $from_alias = trim($thread->from ?? '');

        // Set Message-ID
        // Settings via $this->addCustomHeaders does not work
        $new_headers = $this->headers;
        if (!empty($new_headers) || $from_alias) {
            $mailbox = $this->mailbox;
            $this->withSwiftMessage(function ($swiftmessage) use ($new_headers, $from_alias, $mailbox, $thread) {
                \MailHelper::$smtp_mime_message = '';

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
                    $from_address = $from_alias;
                    $aliases = $mailbox->getAliases();

                    // Make sure that the From contains a mailbox alias,
                    // as user thread may have From specified when a user
                    // replies to an email notification.
                    if (array_key_exists($from_alias, $aliases)) {
                        $from_name = $aliases[$from_alias] ?? '';

                        // Take into account mailbox From Name setting.
                        $mailbox_mail_from = $mailbox->getMailFrom($thread->created_by_user, $thread->conversation);
                        if ($mailbox_mail_from['name'] == $mailbox->name && $from_name) {
                            // Use name from alias.
                        } else {
                            // User name or custom.
                            $from_name = $mailbox_mail_from['name'];
                        }
                    } else {
                        $mailbox_mail_from = $mailbox->getMailFrom($thread->created_by_user, $thread->conversation);
                        $from_name = $mailbox_mail_from['name'];
                    }
                } else {
                    // No alias: use mailbox main email 
                    $from_address = $mailbox->email;
                    $mailbox_mail_from = $mailbox->getMailFrom($thread->created_by_user, $thread->conversation);
                    $from_name = $mailbox_mail_from['name'];
                }

                // Allow modules to modify the display name (From Name) only
                $from_name = \Eventy::filter('email.reply_to_customer.from_name', $from_name, $thread, $mailbox, $from_address);

                $swift_from = $headers->get('From');
                if ($from_name) {
                    $swift_from->setNameAddresses([
                        $from_address => $from_name
                    ]);
                } else {
                    $swift_from->setAddresses([
                        $from_address
                    ]);
                }
                if ($mailbox->imap_sent_folder) {
                    \MailHelper::$smtp_mime_message = $swiftmessage->toString();
                }

                return $swiftmessage;
            });
        }

        $template_html = \Eventy::filter('email.reply_to_customer.template_name_html', 'emails/customer/reply_fancy');
        $template_text = \Eventy::filter('email.reply_to_customer.template_name_text', 'emails/customer/reply_fancy_text');

        // from($this->from) Sets only email, name stays empty.
        // So we set from in Mail::setMailDriver
        $message = $this->subject($this->subject)
                    ->view($template_html)
                    ->text($template_text);

        if ($thread->has_attachments) {
            foreach ($thread->attachments as $attachment) {
                if ($attachment->fileExists()) {
                    $message->attach($attachment->getLocalFilePath());
                } else {
                    \Log::error('[ReplyToCustomer] Thread: ' . $thread->id . '. Attachment file not find on disk: ' . $attachment->getLocalFilePath());
                }
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
