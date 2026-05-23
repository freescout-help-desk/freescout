<?php

namespace App\Listeners;

use App\Conversation;
use App\Customer;
use App\Thread;

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

        // Get threads with line items, line items are needed
        // to show proper signature when conversation is being moved
        // between mailboxes.
        //$replies = $conversation->getReplies();
        $replies = $conversation->getThreads(null, null, [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE, Thread::TYPE_LINEITEM]);

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

        // Add mailbox_id to threads to show proper signature.
        // https://github.com/freescout-help-desk/freescout/issues/5419
        $mailbox_id = $conversation->mailbox_id;
        $mailbox_change_history = [];
        foreach ($replies as $i => $reply) {
            if ($reply->action_type == Thread::ACTION_TYPE_MOVED_FROM_MAILBOX && is_numeric($reply->action_data)) {
                $mailbox_id = (int)$reply->action_data;
            }
            $replies[$i]->mailbox_id = $mailbox_id;
            if ($reply->action_type != Thread::ACTION_TYPE_MOVED_FROM_MAILBOX) {
                if (!empty($replies[$i-2]) && $replies[$i-2]->mailbox_id != $mailbox_id) {
                    $mailbox_change_history[$reply->id] = $mailbox_id;
                }
            }
        }

        // Now remove line items.
        $replies = $replies->whereIn('type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE]);

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
        if ($thread 
            && ($to_array = $thread->getToArray())
            && !empty($to_array[0])
            && ($customer_email = $to_array[0])
            && $customer_email != $main_customer_email
        ) {
            $other_customer = Customer::getByEmail($customer_email);
            if ($other_customer) {
                $recipient_customer = $other_customer;
            }
        }

        \App\Jobs\SendReplyToCustomer::dispatch($conversation, $replies, $recipient_customer, $mailbox_change_history)
            ->delay($delay)
            ->onQueue('emails');
    }
}
