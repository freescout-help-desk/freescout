<?php

use Illuminate\Database\Seeder;

class MailboxesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	factory(App\Mailbox::class, 3)->create();
    }
}
