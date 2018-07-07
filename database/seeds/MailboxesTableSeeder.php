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
    	//factory(App\Mailbox::class, 3)->create();
        factory(App\Mailbox::class, 3)->create()->each(function ($m) {
	        $m->users()->save(factory(App\User::class)->create());
	        $m->users()->save(factory(App\User::class)->create());
	    });
    }
}
