<?php

use Faker\Generator as Faker;

$factory->define(App\Email::class, function (Faker $faker) {
    return [
        'email' => $faker->unique()->safeEmail,
    ];
});
