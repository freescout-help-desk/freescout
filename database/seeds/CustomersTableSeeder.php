<?php

use Illuminate\Database\Seeder;

class CustomersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Customer::class, 5)->create()->each(function ($m) {
	        $m->emails()->save(factory(App\Email::class)->make());
	        $m->emails()->save(factory(App\Email::class)->make());
	    });
    }
}
