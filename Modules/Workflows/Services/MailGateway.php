<?php

namespace Modules\Workflows\Services;

class MailGateway
{
    /**
     * Dispatch the reply for this thread to the conversation customer.
     * sortThreads calls $threads->sort(), so the job receives a Collection.
     *
     * @param object $conversation
     * @param mixed  $thread
     * @return void
     */
    public function sendReply($conversation, $thread)
    {
        $threads = collect([$thread]);
        $delay = \Eventy::filter(
            'conversation.send_reply_to_customer_delay',
            now()->addSeconds(\App\Conversation::UNDO_TIMOUT),
            $conversation,
            $threads
        );
        \App\Jobs\SendReplyToCustomer::dispatch($conversation, $threads, $conversation->customer)
            ->delay($delay)
            ->onQueue('emails');
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
