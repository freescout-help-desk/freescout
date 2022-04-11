<?php

namespace BotMan\Drivers\Facebook\Extensions;

class AirlineBoardingPassTemplate extends AbstractAirlineTemplate
{
    /**
     * @var string
     */
    protected $introMessage;

    /**
     * @var array
     */
    protected $boardingPass;

    /**
     * @param string $introMessage
     * @param string $locale
     * @param array  $boardingPass
     *
     * @return static
     */
    public static function create(string $introMessage, string $locale, array $boardingPass)
    {
        return new static($introMessage, $locale, $boardingPass);
    }

    /**
     * @param string $introMessage
     * @param string $locale
     * @param array  $boardingPass
     */
    public function __construct(string $introMessage, string $locale, array $boardingPass)
    {
        parent::__construct($locale);

        $this->introMessage = $introMessage;
        $this->boardingPass = $boardingPass;
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
                    'template_type' => 'airline_boardingpass',
                    'intro_message' => $this->introMessage,
                    'boarding_pass' => $this->boardingPass,
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
            'type' => 'airline_boardingpass',
            'intro_message' => $this->introMessage,
            'boarding_pass' => $this->boardingPass,
        ];

        return $webDriver;
    }
}
