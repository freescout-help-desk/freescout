<?php

namespace Tests\Feature;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\User;
use Tests\TestCase;

class CustomerConversationsMailboxTest extends TestCase
{
    private $customer;
    private $mailbox1;
    private $mailbox2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailbox1 = factory(Mailbox::class)->create(['name' => 'Sales '.uniqid()]);
        $this->mailbox2 = factory(Mailbox::class)->create(['name' => 'Support '.uniqid()]);

        $this->customer = factory(Customer::class)->create();
        $author = factory(User::class)->create();

        foreach ([$this->mailbox1, $this->mailbox2] as $mailbox) {
            factory(Conversation::class)->create([
                'mailbox_id'         => $mailbox->id,
                'customer_id'        => $this->customer->id,
                'subject'            => 'Question for '.$mailbox->name,
                'created_by_user_id' => $author->id,
                'status'             => Conversation::STATUS_ACTIVE,
                'state'              => Conversation::STATE_PUBLISHED,
            ]);
        }
    }

    private function profileHtml($mailboxes)
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]);
        foreach ($mailboxes as $mailbox) {
            $mailbox->users()->attach($user->id);
        }

        $response = $this->actingAs($user)->get(route('customers.conversations', ['id' => $this->customer->id]));
        $response->assertStatus(200);

        return $response->getContent();
    }

    public function testMailboxIsShownWhenUserCanViewSeveralMailboxes()
    {
        $html = $this->profileHtml([$this->mailbox1, $this->mailbox2]);

        $this->assertStringContainsString('['.$this->mailbox1->name.']', $html);
        $this->assertStringContainsString('['.$this->mailbox2->name.']', $html);
    }

    public function testMailboxIsNotShownWhenUserCanViewOneMailbox()
    {
        $html = $this->profileHtml([$this->mailbox1]);

        $this->assertStringContainsString('Question for '.$this->mailbox1->name, $html);
        $this->assertStringNotContainsString('['.$this->mailbox1->name.']', $html);
    }
}
