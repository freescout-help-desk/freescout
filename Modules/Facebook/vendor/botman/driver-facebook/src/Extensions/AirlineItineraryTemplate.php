<?php

namespace BotMan\Drivers\Facebook\Extensions;

class AirlineItineraryTemplate extends AbstractAirlineTemplate
{
    /**
     * @var string
     */
    protected $introMessage;

    /**
     * @var string
     */
    protected $pnrNumber;

    /**
     * @var array
     */
    protected $passengerInfo;

    /**
     * @var array
     */
    protected $flightInfo;

    /**
     * @var array
     */
    protected $passengerSegmentInfo;

    /**
     * @var array
     */
    protected $priceInfo = [];

    /**
     * @var null|string
     */
    protected $basePrice;

    /**
     * @var null|string
     */
    protected $tax;

    /**
     * @var string
     */
    protected $totalPrice;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @param string $introMessage
     * @param string $locale
     * @param string $pnrNumber
     * @param array  $passengerInfo
     * @param array  $flightInfo
     * @param array  $passengerSegmentInfo
     * @param string $totalPrice
     * @param string $currency
     *
     * @return \BotMan\Drivers\Facebook\Extensions\AirlineItineraryTemplate
     */
    public static function create(
        string $introMessage,
        string $locale,
        string $pnrNumber,
        array $passengerInfo,
        array $flightInfo,
        array $passengerSegmentInfo,
        string $totalPrice,
        string $currency
    ): self {
        return new static(
            $introMessage,
            $locale,
            $pnrNumber,
            $passengerInfo,
            $flightInfo,
            $passengerSegmentInfo,
            $totalPrice,
            $currency
        );
    }

    /**
     * AirlineItineraryTemplate constructor.
     *
     * @param string $introMessage
     * @param string $locale
     * @param string $pnrNumber
     * @param array  $passengerInfo
     * @param array  $flightInfo
     * @param array  $passengerSegmentInfo
     * @param string $totalPrice
     * @param string $currency
     */
    public function __construct(
        string $introMessage,
        string $locale,
        string $pnrNumber,
        array $passengerInfo,
        array $flightInfo,
        array $passengerSegmentInfo,
        string $totalPrice,
        string $currency
    ) {
        parent::__construct($locale);

        $this->introMessage = $introMessage;
        $this->pnrNumber = $pnrNumber;
        $this->passengerInfo = $passengerInfo;
        $this->flightInfo = $flightInfo;
        $this->passengerSegmentInfo = $passengerSegmentInfo;
        $this->totalPrice = $totalPrice;
        $this->currency = $currency;
    }

    /**
     * @param string      $title
     * @param string      $amount
     * @param string|null $currency
     *
     * @return \BotMan\Drivers\Facebook\Extensions\AirlineItineraryTemplate
     */
    public function addPriceInfo(string $title, string $amount, string $currency = null): self
    {
        $priceInfo = [
            'title' => $title,
            'amount' => $amount,
            'currency' => $currency,
        ];

        $this->priceInfo[] = array_filter($priceInfo);

        return $this;
    }

    /**
     * @param string $basePrice
     *
     * @return \BotMan\Drivers\Facebook\Extensions\AirlineItineraryTemplate
     */
    public function basePrice(string $basePrice): self
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    /**
     * @param string $tax
     *
     * @return \BotMan\Drivers\Facebook\Extensions\AirlineItineraryTemplate
     */
    public function tax(string $tax): self
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        return array_merge_recursive($array, [
            'attachment' => [
                'payload' => [
                    'template_type' => 'airline_itinerary',
                    'intro_message' => $this->introMessage,
                    'pnr_number' => $this->pnrNumber,
                    'passenger_info' => $this->passengerInfo,
                    'flight_info' => $this->flightInfo,
                    'passenger_segment_info' => $this->passengerSegmentInfo,
                    'price_info' => $this->priceInfo,
                    'base_price' => $this->basePrice,
                    'tax' => $this->tax,
                    'total_price' => $this->totalPrice,
                    'currency' => $this->currency,
                ],
            ],
        ]);
    }

    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver(): array
    {
        $webDriver = parent::toWebDriver();
        $webDriver += [
            'template_type' => 'airline_itinerary',
            'intro_message' => $this->introMessage,
            'pnr_number' => $this->pnrNumber,
            'passenger_info' => $this->passengerInfo,
            'flight_info' => $this->flightInfo,
            'passenger_segment_info' => $this->passengerSegmentInfo,
            'price_info' => $this->priceInfo,
            'base_price' => $this->basePrice,
            'tax' => $this->tax,
            'total_price' => $this->totalPrice,
            'currency' => $this->currency,
        ];

        return $webDriver;
    }
}
