<?php

namespace App\Observers;

use App\Customer;
use App\CustomerChannel;

class CustomerObserver
{
    public function updating(Customer $customer)
    {
        $phones = $customer->getPhones();
        // Set numeric phones.
        $customer->setPhones($phones);
    }

    public function deleting(Customer $customer)
    {
        \Eventy::action('customer.deleting', $customer);
    }

    public function created(Customer $customer)
    {
        if ($customer->channel && $customer->channel_id) {
            CustomerChannel::create($customer->id, $customer->channel, $customer->channel_id);
        }
    }

    public function deleted(Customer $customer)
    {
        CustomerChannel::where('customer_id', $customer->id)->delete();
    }
}
