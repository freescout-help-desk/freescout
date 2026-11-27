<?php

namespace Tests\Feature;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\User;
use Tests\TestCase;

class CustomerConversationsBulkActionsTest extends TestCase
{
    private $user;
    private $mailbox;
    private $customer;
    private $conversations;

    protected function setUp(): void
    {
        parent::setUp();
        // Without it csrf_token() returns an empty string.
        \Session::start();

        $this->user = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = factory(Mailbox::class)->create();
        $this->mailbox->users()->sync([$this->user->id]);

        $this->customer = factory(Customer::class)->create();
        $this->customer->syncEmails(['customer@example.org']);

        $this->conversations = factory(Conversation::class, 2)->create([
            'mailbox_id'         => $this->mailbox->id,
            'customer_id'        => $this->customer->id,
            'customer_email'     => 'customer@example.org',
            'created_by_user_id' => $this->user->id,
            'status'             => Conversation::STATUS_ACTIVE,
            'state'              => Conversation::STATE_PUBLISHED,
        ]);
    }

    public function testCustomerProfileRendersCheckboxesAndBulkActions()
    {
        $response = $this->actingAs($this->user)
            ->get(route('customers.conversations', ['id' => $this->customer->id]));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('id="toggle-all"', $html);
        $this->assertStringContainsString('<th class="conv-cb">', $html);
        $this->assertStringContainsString('class="conv-checkbox magic-checkbox"', $html);

        // The assignee action needs a single mailbox context and stays hidden on the customer profile.
        $this->assertStringNotContainsString('conv-user', $html);
    }

    public function testBulkStatusChangeEndpointWorks()
    {
        $response = $this->actingAs($this->user)
            ->post('/conversation/ajax', [
                'action'          => 'bulk_conversation_change_status',
                'conversation_id' => $this->conversations->pluck('id')->toArray(),
                'status'          => Conversation::STATUS_CLOSED,
                '_token'          => csrf_token(),
            ], [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('success', $response->json()['status']);

        foreach ($this->conversations as $conversation) {
            $this->assertEquals(Conversation::STATUS_CLOSED, $conversation->fresh()->status);
        }
    }

    public function testRegularUserWithMailboxAccessCanUseBulkActions()
    {
        $user = factory(User::class)->create([
            'role' => User::ROLE_USER,
        ]);
        $this->mailbox->users()->attach($user->id);

        foreach ($this->conversations as $conversation) {
            $conversation->update(['user_id' => $user->id]);
        }

        $response = $this->actingAs($user)
            ->get(route('customers.conversations', ['id' => $this->customer->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('class="conv-checkbox magic-checkbox"', $response->getContent());

        $response = $this->actingAs($user)
            ->post('/conversation/ajax', [
                'action'          => 'bulk_conversation_change_status',
                'conversation_id' => $this->conversations->pluck('id')->toArray(),
                'status'          => Conversation::STATUS_CLOSED,
                '_token'          => csrf_token(),
            ], [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('success', $response->json()['status']);

        foreach ($this->conversations as $conversation) {
            $this->assertEquals(Conversation::STATUS_CLOSED, $conversation->fresh()->status);
        }
    }
}
