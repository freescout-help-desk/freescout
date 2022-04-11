<?php

namespace BotMan\Drivers\Facebook\Extensions\Airline;

use JsonSerializable;

class AirlineAirport implements JsonSerializable
{
    /**
     * @var string
     */
    protected $airportCode;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $terminal;

    /**
     * @var string
     */
    protected $gate;

    /**
     * @param string $airportCode
     * @param string $city
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineAirport
     */
    public static function create(string $airportCode, string $city): self
    {
        return new static($airportCode, $city);
    }

    /**
     * AirlineAirport constructor.
     *
     * @param string $airportCode
     * @param string $city
     */
    public function __construct(string $airportCode, string $city)
    {
        $this->airportCode = $airportCode;
        $this->city = $city;
    }

    /**
     * @param string $terminal
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineAirport
     */
    public function terminal(string $terminal): self
    {
        $this->terminal = $terminal;

        return $this;
    }

    /**
     * @param string $gate
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineAirport
     */
    public function gate(string $gate): self
    {
        $this->gate = $gate;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'airport_code' => $this->airportCode,
            'city' => $this->city,
            'terminal' => $this->terminal,
            'gate' => $this->gate,
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
