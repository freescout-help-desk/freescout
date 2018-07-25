<?php

use App\Thread;
use Faker\Generator as Faker;

$factory->define(Thread::class, function (Faker $faker, $params) {
    if (!empty($params['customer_id'])) {
        $customer_id = $params['customer_id'];
    } else {
        // Pick random customer
        //$customer_id =  $faker->randomElement(App\Customer::pluck('id')->toArray());
        $customer = User::inRandomOrder()->first();
        if (!$customer) {
            $customer = factory(App\Customer::class)->create();
        }
        $customer_id = $customer->id;
    }
    if (!empty($params['to'])) {
        $to = $params['to'];
    } elseif ($customer) {
        $to = $customer->getMainEmail();
    } else {
        $to = json_encode([$faker->unique()->safeEmail]);
    }

    return [
        'type' => Thread::TYPE_CUSTOMER,
        //'conversation_id' => ,
        'customer_id'             => $customer_id,
        'state'                   => Thread::STATE_PUBLISHED,
        'body'                    => $faker->text(500),
        'to'                      => $to,
        'cc'                      => json_encode([$faker->unique()->safeEmail]),
        'bcc'                     => json_encode([$faker->unique()->safeEmail]),
        'source_via'              => Thread::PERSON_CUSTOMER,
        'source_type'             => Thread::SOURCE_TYPE_EMAIL,
        'created_by_customer_id'  => $customer_id,
    ];
});
