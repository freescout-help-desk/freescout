<?php

namespace Tests\Feature;

use App\Conversation;
use App\Customer;
use App\Email;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationChangeCustomerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $unprivUser;
    private $mailbox;
    private $conversation;
    private $originalCustomer;
    private $attackerCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Create mailbox
        $this->mailbox = factory(Mailbox::class)->create();
        $this->mailbox->users()->sync([$this->admin->id]);

        // Create unprivileged user with NO mailbox access
        $this->unprivUser = factory(User::class)->create([
            'role' => User::ROLE_USER,
        ]);

        // Create customers
        $this->originalCustomer = factory(Customer::class)->create([
            'first_name' => 'Original',
            'last_name'  => 'Customer',
        ]);
        Email::create($this->originalCustomer, 'original@customer.com');

        $this->attackerCustomer = factory(Customer::class)->create([
            'first_name' => 'Attacker',
            'last_name'  => 'Evil',
        ]);
        Email::create($this->attackerCustomer, 'attacker@evil.com');

        // Create conversation belonging to original customer
        $this->conversation = factory(Conversation::class)->create([
            'mailbox_id'     => $this->mailbox->id,
            'customer_id'    => $this->originalCustomer->id,
            'customer_email' => 'original@customer.com',
            'status'         => Conversation::STATUS_ACTIVE,
            'state'          => Conversation::STATE_PUBLISHED,
        ]);
    }

    /**
     * Test that a user without mailbox access cannot change a conversation's customer.
     * This is the regression test for the authorization bypass where changeCustomer()
     * executed unconditionally even when permission checks set an error message.
     */
    public function testUnauthorizedUserCannotChangeCustomer()
    {
        $response = $this->actingAs($this->unprivUser)
            ->post('/conversation/ajax', [
                'action'          => 'conversation_change_customer',
                'conversation_id' => $this->conversation->id,
                'customer_email'  => 'attacker@evil.com',
                '_token'          => csrf_token(),
            ], [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();

        $json = $response->json();
        $this->assertEquals('Not enough permissions', $json['msg']);

        // The customer must NOT have changed
        $this->conversation->refresh();
        $this->assertEquals('original@customer.com', $this->conversation->customer_email);
        $this->assertEquals($this->originalCustomer->id, $this->conversation->customer_id);
    }

    /**
     * Test that an authorized admin user CAN change a conversation's customer.
     * This ensures the fix does not break normal functionality.
     */
    public function testAuthorizedUserCanChangeCustomer()
    {
        $response = $this->actingAs($this->admin)
            ->post('/conversation/ajax', [
                'action'          => 'conversation_change_customer',
                'conversation_id' => $this->conversation->id,
                'customer_email'  => 'attacker@evil.com',
                '_token'          => csrf_token(),
            ], [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();

        $json = $response->json();
        $this->assertEquals('success', $json['status']);
        $this->assertEmpty($json['msg']);

        // The customer SHOULD have changed
        $this->conversation->refresh();
        $this->assertEquals('attacker@evil.com', $this->conversation->customer_email);
    }
}
