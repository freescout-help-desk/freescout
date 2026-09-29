<?php

namespace Modules\Workflows\Services;

class MailGateway
{
    /**
     * The runner still calls this. The reply listener is the only sender.
     *
     * @param object $conversation
     * @param mixed  $thread
     * @return void
     */
    public function sendReply($conversation, $thread)
    {
        // createUserThread(TYPE_MESSAGE) fires UserReplied. EventServiceProvider maps that
        // to Listeners\SendReplyToCustomer, which loads the customer, message, and line-item
        // threads, trims at the new thread, drops line items, and dispatches
        // Jobs\SendReplyToCustomer with a Collection, the undo delay, and onQueue('emails').
        // A second dispatch here would email the customer twice and would skip In-Reply-To,
        // References, and thread history. email_customer still uses sendPlain because it
        // does not create a thread.
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
