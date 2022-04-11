<?php

namespace BotMan\Drivers\Facebook\Extensions\Airline;

use JsonSerializable;

class AirlineFlightSchedule implements JsonSerializable
{
    /**
     * @var null|string
     */
    protected $boardingTime;

    /**
     * @var string
     */
    protected $departureTime;

    /**
     * @var null|string
     */
    protected $arrivalTime;

    /**
     * @param string $departureTime
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineFlightSchedule
     */
    public static function create(string $departureTime): self
    {
        return new self($departureTime);
    }

    /**
     * AirlineFlightSchedule constructor.
     *
     * @param string $departureTime
     */
    public function __construct(string $departureTime)
    {
        $this->departureTime = $departureTime;
    }

    /**
     * @param string $boardingTime
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineFlightSchedule
     */
    public function boardingTime(string $boardingTime): self
    {
        $this->boardingTime = $boardingTime;

        return $this;
    }

    /**
     * @param string $arrivalTime
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineFlightSchedule
     */
    public function arrivalTime(string $arrivalTime): self
    {
        $this->arrivalTime = $arrivalTime;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'boarding_time' => $this->boardingTime,
            'departure_time' => $this->departureTime,
            'arrival_time' => $this->arrivalTime,
        ];

        return array_filter($array);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
