<?php

namespace Modules\Workflows\Services;

use App\Conversation;
use App\Mailbox;
use App\Thread;

class ActionRunner
{
    /**
     * Status slugs change_status accepts. Any other value is left unchanged.
     *
     * @var array
     */
    private static $statusSlugs = [
        'active' => Conversation::STATUS_ACTIVE,
        'pending' => Conversation::STATUS_PENDING,
        'closed' => Conversation::STATUS_CLOSED,
        'spam' => Conversation::STATUS_SPAM,
    ];

    /**
     * Perform one action. Returns stop only for the stop action.
     * Unknown types are offered to workflow.perform_action and still return done.
     *
     * @param string $type
     * @param mixed  $value
     * @param object $context
     * @return string
     */
    public static function perform($type, $value, $context): string
    {
        if ($type === 'stop') {
            return 'stop';
        }

        $conversation = self::read($context, 'conversation');
        $workflowUser = self::read($context, 'workflowUser');

        if ($type === 'change_status') {
            self::changeStatus($conversation, $value, $workflowUser);

            return 'done';
        }

        if ($type === 'assign') {
            self::assign($conversation, $value, $workflowUser);

            return 'done';
        }

        if ($type === 'add_note') {
            if (is_object($conversation)) {
                $conversation->createUserThread($workflowUser, $value, ['type' => Thread::TYPE_NOTE]);
            }

            return 'done';
        }

        if ($type === 'move_deleted') {
            if (is_object($conversation)) {
                $conversation->deleteToFolder($workflowUser);
            }

            return 'done';
        }

        if ($type === 'delete_forever') {
            if (is_object($conversation)) {
                // Not deleteForever(): this action's contract is delete().
                $conversation->delete();
            }

            return 'done';
        }

        if ($type === 'move_mailbox') {
            // Conversation::moveToMailbox reads $mailbox->id. A raw id is a TypeError.
            $mailbox = self::mailboxToMove($value);
            if (is_object($conversation) && is_object($mailbox)) {
                $conversation->moveToMailbox($mailbox, $workflowUser);
            }

            return 'done';
        }

        if ($type === 'reply') {
            self::reply($conversation, $value, $workflowUser, $context);

            return 'done';
        }

        if ($type === 'email_customer') {
            self::emailCustomer($conversation, $value, $workflowUser, $context);

            return 'done';
        }

        if ($type === 'forward') {
            self::forwardConversation($conversation, $value, $workflowUser);

            return 'done';
        }

        if ($type === 'notification') {
            self::notification($conversation, $value, $context);

            return 'done';
        }

        if ($type === 'disable_auto_reply') {
            if (is_object($conversation)) {
                $conversation->setMeta('ar_off', true, true);
            }

            return 'done';
        }

        if ($type === 'trigger_webhook') {
            \Eventy::action(
                'workflow.webhook',
                $value,
                $conversation,
                self::read($context, 'workflow')
            );

            return 'done';
        }

        \Eventy::filter(
            'workflow.perform_action',
            false,
            $type,
            null,
            $value,
            $conversation,
            self::read($context, 'workflow')
        );

        return 'done';
    }

    /**
     * @param object|null $conversation
     * @param mixed       $value
     * @param mixed       $workflowUser
     * @return void
     */
    private static function changeStatus($conversation, $value, $workflowUser)
    {
        if (!is_object($conversation) || !is_string($value) || !isset(self::$statusSlugs[$value])) {
            return;
        }

        $conversation->changeStatus(self::$statusSlugs[$value], $workflowUser, true);
    }

    /**
     * only_if_available limits the assign to true, 1, and "1".
     * Other values, including a missing key, assign without asking the filter.
     *
     * @param object|null $conversation
     * @param mixed       $value
     * @param mixed       $workflowUser
     * @return void
     */
    private static function assign($conversation, $value, $workflowUser)
    {
        if (!is_object($conversation) || !is_array($value) || !array_key_exists('user_id', $value)) {
            return;
        }

        $userId = $value['user_id'];

        if (self::onlyIfAvailable($value)) {
            $available = \Eventy::filter('user.is_user_available', true, $userId);
            if (!$available) {
                return;
            }
        }

        $conversation->changeUser($userId, $workflowUser, true);
    }

    /**
     * The application createUserThread returns void. Its return value is still passed on.
     *
     * @param object|null $conversation
     * @param mixed       $value
     * @param mixed       $workflowUser
     * @param object      $context
     * @return void
     */
    private static function reply($conversation, $value, $workflowUser, $context)
    {
        if (!is_object($conversation)) {
            return;
        }

        $body = self::replaceBody($conversation, $value, $workflowUser);
        $thread = $conversation->createUserThread($workflowUser, $body, ['type' => Thread::TYPE_MESSAGE]);
        $context->send_reply = true;
        $context->body = $body;
        self::gateway($context)->sendReply($conversation, $thread);
    }

    /**
     * Chat conversations are not emailed. No thread is created.
     *
     * @param object|null $conversation
     * @param mixed       $value
     * @param mixed       $workflowUser
     * @param object      $context
     * @return void
     */
    private static function emailCustomer($conversation, $value, $workflowUser, $context)
    {
        if (!is_object($conversation) || $conversation->type == Conversation::TYPE_CHAT) {
            return;
        }

        $body = self::replaceBody($conversation, $value, $workflowUser);
        $context->send_plain = true;
        $context->send_reply = false;
        $context->recorded_subject = $conversation->subject;
        self::gateway($context)->sendPlain($conversation, $body);
        $context->body = $body;
    }

    /**
     * Forward does not set the reply or plain-email flags.
     *
     * @param object|null $conversation
     * @param mixed       $value
     * @param mixed       $workflowUser
     * @return void
     */
    private static function forwardConversation($conversation, $value, $workflowUser)
    {
        if (!is_object($conversation) || !is_array($value) || !array_key_exists('body', $value) || !array_key_exists('to', $value)) {
            return;
        }

        $body = self::replaceBody($conversation, $value['body'], $workflowUser);
        $conversation->forward($workflowUser, $body, $value['to']);
    }

    /**
     * Record who would be notified. This action does not send mail.
     *
     * @param object|null $conversation
     * @param mixed       $value
     * @param object      $context
     * @return void
     */
    private static function notification($conversation, $value, $context)
    {
        if ($value === 'assignee') {
            if (is_object($conversation)) {
                $context->notification_user_id = $conversation->user_id;
            }

            return;
        }

        if ($value === 'last_user') {
            $context->notification_user_id = self::read($context, 'last_user_id');

            return;
        }

        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            $context->notification_user_id = (int) $value;
        }
    }

    /**
     * {%user.*%} resolves to the workflow user, not an assignee.
     *
     * @param object $conversation
     * @param mixed  $body
     * @param mixed  $workflowUser
     * @return string
     */
    private static function replaceBody($conversation, $body, $workflowUser)
    {
        return \App\Misc\Mail::replaceMailVars($body, [
            'conversation' => $conversation,
            'mailbox' => $conversation->mailbox,
            'customer' => $conversation->customer,
            'user' => $workflowUser,
        ], false, false);
    }

    /**
     * A recording gateway on the context wins. Otherwise use MailGateway.
     *
     * @param object $context
     * @return object
     */
    private static function gateway($context)
    {
        $gateway = self::read($context, 'mailGateway');

        if (is_object($gateway)) {
            return $gateway;
        }

        return new MailGateway();
    }

    /**
     * @param array $value
     * @return bool
     */
    private static function onlyIfAvailable(array $value): bool
    {
        if (!array_key_exists('only_if_available', $value)) {
            return false;
        }

        $flag = $value['only_if_available'];

        return $flag === true || $flag === 1 || $flag === '1';
    }

    /**
     * An object is the mailbox. An id is loaded with Mailbox::find.
     * A missing row or a query error does not move.
     *
     * @param mixed $value
     * @return object|null
     */
    private static function mailboxToMove($value)
    {
        if (is_object($value)) {
            return $value;
        }

        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            return null;
        }

        try {
            $mailbox = Mailbox::find($value);
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_object($mailbox)) {
            return null;
        }

        return $mailbox;
    }

    /**
     * Missing properties are null. Undeclared properties are not read.
     *
     * @param mixed  $context
     * @param string $name
     * @return mixed
     */
    private static function read($context, $name)
    {
        if (is_object($context) && property_exists($context, $name)) {
            return $context->{$name};
        }

        return null;
    }
}
