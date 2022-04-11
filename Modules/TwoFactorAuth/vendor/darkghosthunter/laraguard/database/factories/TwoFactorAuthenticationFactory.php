<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Illuminate\Support\Collection;
use DarkGhostHunter\Laraguard\Eloquent\TwoFactorAuthentication;

$factory->define(TwoFactorAuthentication::class, function (Faker $faker) {

    $config = config('laraguard');

    $array = array_merge([
        'shared_secret' => TwoFactorAuthentication::generateRandomSecret(),
        'enabled_at'    => $faker->dateTimeBetween('-1 year'),
        'label'         => $faker->freeEmail,
    ], $config['totp']);

    [$enabled, $amount, $length] = array_values($config['recovery']);

    if ($enabled) {
        $array['recovery_codes'] = TwoFactorAuthentication::generateRecoveryCodes($amount, $length);
        $array['recovery_codes_generated_at'] = $faker->dateTimeBetween('-1 years');
    }

    return $array;
});

$factory->state(TwoFactorAuthentication::class, 'with recovery', function (Faker $faker) {
    [$enabled, $amount, $length] = array_values(config('laraguard.recovery'));
    return [
        'recovery_codes'              => TwoFactorAuthentication::generateRecoveryCodes($amount, $length),
        'recovery_codes_generated_at' => $faker->dateTimeBetween('-1 years'),
    ];
});

// This state will create a full set of safe devices, but it will leave the last as purposefully expired.
$factory->state(TwoFactorAuthentication::class, 'with safe devices', function (Faker $faker) {

    $max = config('laraguard.safe_devices.max_devices');

    return [
        'safe_devices' => Collection::times($max, function ($step) use ($faker, $max) {

            $expiration_days = config('laraguard.safe_devices.expiration_days');

            $added_at = $max !== $step
                ? now()
                : $faker->dateTimeBetween(now()->subDays($expiration_days * 2), now()->subDays($expiration_days));

            return [
                '2fa_remember' => TwoFactorAuthentication::generateDefaultTwoFactorRemember(),
                'ip'           => $faker->ipv4,
                'added_at'     => $added_at,
            ];
        }),
    ];
});

$factory->state(TwoFactorAuthentication::class, 'disabled', [
    'enabled_at' => null,
]);
