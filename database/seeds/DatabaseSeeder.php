<?php

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
        //$this->call(MailboxesTableSeeder::class);
        factory(App\Mailbox::class, 3)->create()->each(function ($m) {
            $user = factory(App\User::class)->create();
            $m->users()->save($user);

            $customer = factory(App\Customer::class)->create();
            
            $customer->emails()->save(factory(App\Email::class)->make());

            for ($i=0; $i < 3; $i++) { 
                $conversation = factory(App\Conversation::class)->create(['created_by' => $user->id, 'mailbox_id' => $m->id, 'customer_id' => $customer->id, 'user_id' => $user->id]);

                $thread = factory(App\Thread::class)->make(['customer_id' => $customer->id, 'to' => $customer->getMainEmail(), 'conversation_id' => $conversation->id]);
                $conversation->threads()->save($thread);
            }
        });
    }
}
