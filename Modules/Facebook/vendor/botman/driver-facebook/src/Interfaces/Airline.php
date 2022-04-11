<?php

namespace BotMan\Drivers\Facebook\Interfaces;

interface Airline
{
    const TRAVEL_TYPE_ECONOMY = 'economy';
    const TRAVEL_TYPE_BUSINESS = 'business';
    const TRAVEL_TYPE_FIRST_CLASS = 'first_class';

    const TRAVEL_TYPES = [
        self::TRAVEL_TYPE_ECONOMY,
        self::TRAVEL_TYPE_BUSINESS,
        self::TRAVEL_TYPE_FIRST_CLASS,
    ];

    const UPDATE_TYPE_DELAY = 'delay';
    const UPDATE_TYPE_GATE_CHANGE = 'gate_change';
    const UPDATE_TYPE_CANCELLATION = 'cancellation';

    const UPDATE_TYPES = [
        self::UPDATE_TYPE_DELAY,
        self::UPDATE_TYPE_GATE_CHANGE,
        self::UPDATE_TYPE_CANCELLATION,
    ];
}
