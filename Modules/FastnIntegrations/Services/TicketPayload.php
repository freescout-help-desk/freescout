<?php

namespace Modules\FastnIntegrations\Services;

use App\Conversation;

/**
 * The JSON a ticket is sent to fastn as. This shape is the input contract of
 * the Linear, HubSpot and Google Sheets workflows, so change both together.
 */
class TicketPayload
{
    public static function build(Conversation $conversation, $event = 'ticket.created', array $change = [])
    {
        $customer = $conversation->customer;
        $assignee = $conversation->user;
        $firstThread = $conversation->threads()->orderBy('created_at', 'asc')->first();

        return [
            'source' => 'freescout',
            'event'  => $event,
            'ticket' => [
                'id'        => (string) $conversation->id,
                'number'    => (string) $conversation->number,
                'subject'   => (string) $conversation->subject,
                'status'    => (string) $conversation->getStatusName(),
                'type'      => (string) $conversation->getTypeName(),
                'mailbox'   => $conversation->mailbox ? (string) $conversation->mailbox->name : '',
                'preview'   => (string) $conversation->preview,
                'body'      => $firstThread ? trim(html_entity_decode(strip_tags((string) $firstThread->body))) : '',
                'url'       => rtrim((string) config('app.url'), '/').'/conversation/'.$conversation->id,
                'createdAt' => $conversation->created_at ? $conversation->created_at->toIso8601String() : '',
                'updatedAt' => $conversation->updated_at ? $conversation->updated_at->toIso8601String() : '',
                'assignee'  => $assignee ? (string) $assignee->getFullName() : '',
            ],
            'change'   => (object) $change,
            'customer' => [
                'email'     => $customer ? (string) $customer->getMainEmail() : (string) $conversation->customer_email,
                'firstName' => $customer ? (string) $customer->first_name : '',
                'lastName'  => $customer ? (string) $customer->last_name : '',
                'fullName'  => $customer ? (string) $customer->getFullName() : '',
                'company'   => $customer ? (string) $customer->company : '',
                'jobTitle'  => $customer ? (string) $customer->job_title : '',
                'phone'     => $customer ? (string) $customer->getMainPhoneValue() : '',
            ],
        ];
    }

    /**
     * A fixed sample ticket for the "Send test ticket" button.
     */
    public static function sample()
    {
        $now = date('c');

        return [
            'source' => 'freescout',
            'event'  => 'ticket.created',
            'ticket' => [
                'id'        => 'test-'.time(),
                'number'    => 'TEST',
                'subject'   => 'fastn test ticket from FreeScout',
                'status'    => 'Active',
                'type'      => 'Email',
                'mailbox'   => 'Support',
                'preview'   => 'This is a test ticket sent from the FreeScout Integrations page.',
                'body'      => 'This is a test ticket sent from the FreeScout Integrations page.',
                'url'       => url('/'),
                'createdAt' => $now,
            ],
            'customer' => [
                'email'     => 'fastn-test-customer@example.com',
                'firstName' => 'Test',
                'lastName'  => 'Customer',
                'fullName'  => 'Test Customer',
                'company'   => 'Example Inc',
                'jobTitle'  => '',
                'phone'     => '',
            ],
        ];
    }
}
