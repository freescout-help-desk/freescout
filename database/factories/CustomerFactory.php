<?php

use Faker\Generator as Faker;
use App\Customer;

$factory->define(App\Customer::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'job_title' => $faker->jobTitle,
        'phones' => Customer::formatPhones([['value' => $faker->phoneNumber, 'type' => Customer::PHONE_TYPE_WORK]])
    ];
});
