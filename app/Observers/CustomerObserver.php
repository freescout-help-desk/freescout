<?php

namespace App\Observers;

use App\Customer;

class CustomerObserver
{
    public function updating(Customer $customer)
    {
        $phones = $customer->getPhones();
        // Set numeric phones.
        $customer->setPhones($phones);
    }
}
