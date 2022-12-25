<?php

namespace App\Observers;

use App\Customer;

class CustomerObserver
{
    public function updating(Customer $customer)
    {
        $phones = $customer->getPhones();
        // Set numeric phones.
        foreach ($phones as $i => $phone_data) {
            if (!empty($phone_data['value'])) {
                $phones[$i]['n'] = \Helper::phoneToNumeric($phone_data['value']);
            }
        }
        $customer->phones = \Helper::jsonEncodeUtf8($phones);
    }
}
