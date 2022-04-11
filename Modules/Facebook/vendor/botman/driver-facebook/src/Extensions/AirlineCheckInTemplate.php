<?php

namespace BotMan\Drivers\Facebook\Extensions;

class AirlineCheckInTemplate extends AbstractAirlineTemplate
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
    protected $flightInfo;

    /**
     * @var string
     */
    protected $checkinUrl;

    /**
     * @param string $introMessage
     * @param string $locale
     * @param string $pnrNumber
     * @param array  $flightInfo
     * @param string $checkinUrl
     *
     * @return static
     */
    public static function create(
        string $introMessage,
        string $locale,
        string $pnrNumber,
        array $flightInfo,
        string $checkinUrl
    ) {
        return new static($introMessage, $locale, $pnrNumber, $flightInfo, $checkinUrl);
    }

    /**
     * AirlineCheckIn constructor.
     *
     * @param string $introMessage
     * @param string $locale
     * @param string $pnrNumber
     * @param array  $flightInfo
     * @param string $checkinUrl
     */
    public function __construct(
        string $introMessage,
        string $locale,
        string $pnrNumber,
        array $flightInfo,
        string $checkinUrl
    ) {
        parent::__construct($locale);

        $this->introMessage = $introMessage;
        $this->pnrNumber = $pnrNumber;
        $this->flightInfo = $flightInfo;
        $this->checkinUrl = $checkinUrl;
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
                    'template_type' => 'airline_checkin',
                    'intro_message' => $this->introMessage,
                    'pnr_number' => $this->pnrNumber,
                    'flight_info' => $this->flightInfo,
                    'checkin_url' => $this->checkinUrl,
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
            'type' => 'airline_checkin',
            'intro_message' => $this->introMessage,
            'pnr_number' => $this->pnrNumber,
            'flight_info' => $this->flightInfo,
            'checkin_url' => $this->checkinUrl,
        ];

        return $webDriver;
    }
}
