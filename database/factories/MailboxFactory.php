<?php

use Faker\Generator as Faker;

$factory->define(App\Mailbox::class, function (Faker $faker) {
    $name = $faker->company;
    $email = $faker->unique()->companyEmail;
    $domain = explode('@', $email)[1];

    return [
        'name'      => $name,
        'email'     => $email,
        'aliases'   => 'support@'.$domain.',help@'.$domain.', contact@'.$domain,
        'signature' => '--<br/>'.$name,
    ];
});
