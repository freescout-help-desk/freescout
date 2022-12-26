<?php

use App\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetNumericPhonesInCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        do {
            $customers = Customer::where('phones', 'like', '%"value":%')
                ->where('phones', 'not like', '%"n":%')
                ->limit(100)
                ->get();
            foreach ($customers as $customer) {
                $phones = $customer->getPhones();
                $customer->setPhones($phones);
                $customer->save();
            }
        } while(count($customers));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
