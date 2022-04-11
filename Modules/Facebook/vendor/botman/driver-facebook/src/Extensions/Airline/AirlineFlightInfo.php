<?php

namespace BotMan\Drivers\Facebook\Extensions\Airline;

class AirlineFlightInfo extends AbstractAirlineFlightInfo
{
    /**
     * @param string                                                            $flightNumber
     * @param \BotMan\Drivers\Facebook\Extensions\Airline\AirlineAirport        $departureAirport
     * @param \BotMan\Drivers\Facebook\Extensions\Airline\AirlineAirport        $arrivalAirport
     * @param \BotMan\Drivers\Facebook\Extensions\Airline\AirlineFlightSchedule $flightSchedule
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineFlightInfo
     */
    public static function create(
        string $flightNumber,
        AirlineAirport $departureAirport,
        AirlineAirport $arrivalAirport,
        AirlineFlightSchedule $flightSchedule
    ): self {
        return new self($flightNumber, $departureAirport, $arrivalAirport, $flightSchedule);
    }
}
