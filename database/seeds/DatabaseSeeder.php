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
        $this->call(MailboxesTableSeeder::class);
        $this->call(CustomersTableSeeder::class);
    }
}
