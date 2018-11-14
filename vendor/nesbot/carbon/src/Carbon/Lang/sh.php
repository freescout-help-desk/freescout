<?php

/*
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

\Symfony\Component\Translation\PluralizationRules::set(function ($number) {
    return ((1 == $number % 10) && (11 != $number % 100)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
}, 'sh');

return array(
    'year' => ':count godina|:count godine|:count godina',
    'y' => ':count godina|:count godine|:count godina',
    'month' => ':count mesec|:count meseca|:count meseci',
    'm' => ':count mesec|:count meseca|:count meseci',
    'week' => ':count nedelja|:count nedelje|:count nedelja',
    'w' => ':count nedelja|:count nedelje|:count nedelja',
    'day' => ':count dan|:count dana|:count dana',
    'd' => ':count dan|:count dana|:count dana',
    'hour' => ':count čas|:count časa|:count časova',
    'h' => ':count čas|:count časa|:count časova',
    'minute' => ':count minut|:count minuta|:count minuta',
    'min' => ':count minut|:count minuta|:count minuta',
    'second' => ':count sekund|:count sekunda|:count sekundi',
    's' => ':count sekund|:count sekunda|:count sekundi',
    'ago' => 'pre :time',
    'from_now' => 'za :time',
    'after' => 'nakon :time',
    'before' => ':time raniјe',
);
