<?php

namespace Modules\Workflows\Services;

use App\Jobs\SendReplyToCustomer;

class MailGateway
{
    /**
     * Dispatch the reply for this thread to the conversation customer.
     *
     * @param object $conversation
     * @param mixed  $thread
     * @return void
     */
    public function sendReply($conversation, $thread)
    {
        SendReplyToCustomer::dispatch($conversation, [$thread], $conversation->customer);
    }

    /**
     * Send only this body. The subject is the conversation subject.
     *
     * @param object $conversation
     * @param string $body
     * @return void
     */
    public function sendPlain($conversation, $body)
    {
        \App\Misc\Mail::setMailDriver($conversation->mailbox, null, $conversation);

        $subject = $conversation->subject;
        $recipient = $conversation->customer_email;

        \Mail::raw($body, function ($message) use ($subject, $recipient) {
            $message->subject($subject)->to($recipient);
        });
    }
}
