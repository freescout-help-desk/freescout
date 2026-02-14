<?php

namespace App\Listeners;

use App\Conversation;
use App\Customer;

class SendReplyToCustomer
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event)
    {
        $conversation = $event->conversation;

        $main_customer_email = $conversation->customer->getMainEmail();

        // Do not send email if this is a Phone conversation and customer has no email.
        if ($conversation->isPhone()) {
            if (!$main_customer_email) {
                return;
            }
        }

        $replies = $conversation->getReplies();

        // Ignore imported messages.
        if ($replies && $replies->first() && $replies->first()->imported) {
            return;
        }

        // Remove threads added after this event had fired.
        $thread = $event->last_thread ?? $event->thread ?? null;
        if ($thread) {
            foreach ($replies as $i => $reply) {
                if ($reply->id == $thread->id) {
                    break;
                } else {
                    $replies->forget($i);
                }
            }
        }

        // Allow to cancel mail sending if needed.
        $skip_send = \Eventy::filter('conversation.skip_send_reply_to_customer', false, $conversation, $replies);
        if ($skip_send) {
             return;
        }

        // Chat conversation.
        if ($conversation->isChat()) {
            \Helper::backgroundAction('chat_conversation.send_reply', [$conversation, $replies, $conversation->customer], now()->addSeconds(Conversation::UNDO_TIMOUT));
            return;
        }

        // We can not check imported here, as after conversation has been imported via API
        // notifications has to be sent.
        //if (!$conversation->imported) {
        $delay = \Eventy::filter('conversation.send_reply_to_customer_delay', now()->addSeconds(Conversation::UNDO_TIMOUT), $conversation, $replies);

        $recipient_customer = $conversation->customer;

        // The reply may be sent to some other customer from previous threads.
        // https://github.com/freescout-help-desk/freescout/pull/5199
        if ($thread && ($customer_email = $thread->getToArray()[0]) && $customer_email != $main_customer_email) {
            $other_customer = Customer::getByEmail($customer_email);
            if ($other_customer) {
                $recipient_customer = $other_customer;
            }
        }

        \App\Jobs\SendReplyToCustomer::dispatch($conversation, $replies, $recipient_customer)
            ->delay($delay)
            ->onQueue('emails');
    }
}
