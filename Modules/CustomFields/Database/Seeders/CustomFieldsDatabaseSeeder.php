<?php

namespace Modules\CustomFields\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class CustomFieldsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

//        $this->call(EmailsTableSeeder::class);
        // $this->call("OthersTableSeeder");
    }
}
