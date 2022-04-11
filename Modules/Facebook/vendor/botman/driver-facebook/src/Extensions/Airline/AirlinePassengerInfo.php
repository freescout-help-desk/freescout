<?php

namespace BotMan\Drivers\Facebook\Extensions\Airline;

use JsonSerializable;

class AirlinePassengerInfo implements JsonSerializable
{
    /**
     * @var string
     */
    protected $passengerId;

    /**
     * @var null|string
     */
    protected $ticketNumber;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $passengerId
     * @param string $name
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlinePassengerInfo
     */
    public static function create(string $passengerId, string $name): self
    {
        return new static($passengerId, $name);
    }

    /**
     * AirlinePassengerInfo constructor.
     *
     * @param string $passengerId
     * @param string $name
     */
    public function __construct(string $passengerId, string $name)
    {
        $this->passengerId = $passengerId;
        $this->name = $name;
    }

    /**
     * @param string $ticketNumber
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlinePassengerInfo
     */
    public function ticketNumber(string $ticketNumber): self
    {
        $this->ticketNumber = $ticketNumber;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'passenger_id' => $this->passengerId,
            'ticket_number' => $this->ticketNumber,
            'name' => $this->name,
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
