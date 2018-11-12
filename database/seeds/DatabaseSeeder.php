<?php

use App\Conversation;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create users
        factory(App\User::class, 3)->create();

        // Create mailboxes, conversations, etc
        factory(App\Mailbox::class, 3)->create()->each(function ($m) {
            $user = factory(App\User::class)->create();
            $m->users()->save($user);

            for ($i = 0; $i < 7; $i++) {
                $customer = factory(App\Customer::class)->create();

                $email = factory(App\Email::class)->make();
                $customer->emails()->save($email);

                $conversation = factory(App\Conversation::class)->create([
                    'created_by_user_id' => $user->id,
                    'mailbox_id'         => $m->id,
                    'customer_id'        => $customer->id,
                    'customer_email'     => $email->email,
                    'user_id'            => $user->id,
                    'status'             => array_rand([Conversation::STATUS_ACTIVE => 1, Conversation::STATUS_PENDING => 1]),
                ]);

                $thread = factory(App\Thread::class)->make([
                    'customer_id'     => $customer->id,
                    'to'              => $email->email,
                    'conversation_id' => $conversation->id,
                ]);
                $conversation->threads()->save($thread);
            }
        });
    }
}
