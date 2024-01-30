<?php

namespace App\Listeners;

use App\Conversation;
use App\Thread;
use Modules\SmsTickets\Models\MailboxPhoneNumber;

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

        // Do not send email if this is a Phone conversation and customer has no email.
        if ($conversation->isPhone()) {
            if (!$conversation->customer->getMainEmail()) {
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

        // Chat conversation.
        if ($conversation->isChat()) {

            $lastThread = Thread::getLastThreadOfConversation($conversation->id);

            if($lastThread){
                $thread->mailbox_phone_number_id = $lastThread->mailbox_phone_number_id;
                $mailboxPhoneNumber = MailboxPhoneNumber::findOrFail($thread->mailbox_phone_number_id);
                $conversation->replyTo = $mailboxPhoneNumber->number;
            }

            \Helper::backgroundAction('chat_conversation.send_reply', [$conversation, $replies, $conversation->customer], now()->addSeconds(Conversation::UNDO_TIMOUT));
            return;
        }

        // We can not check imported here, as after conversation has been imported via API
        // notifications has to be sent.
        //if (!$conversation->imported) {
        $delay = \Eventy::filter('conversation.send_reply_to_customer_delay', now()->addSeconds(Conversation::UNDO_TIMOUT), $conversation, $replies);

        \App\Jobs\SendReplyToCustomer::dispatch($conversation, $replies, $conversation->customer)
            ->delay($delay)
            ->onQueue('emails');
    }
}
