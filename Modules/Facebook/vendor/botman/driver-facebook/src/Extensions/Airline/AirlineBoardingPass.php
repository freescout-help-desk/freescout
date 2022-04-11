<?php

namespace BotMan\Drivers\Facebook\Extensions\Airline;

use BotMan\Drivers\Facebook\Exceptions\FacebookException;
use BotMan\Drivers\Facebook\Interfaces\Airline;
use JsonSerializable;

class AirlineBoardingPass implements JsonSerializable, Airline
{
    /**
     * @var string
     */
    protected $passengerName;

    /**
     * @var string
     */
    protected $pnrNumber;

    /**
     * @var null|string
     */
    protected $travelClass;

    /**
     * @var null|string
     */
    protected $seat;

    /**
     * @var array
     */
    protected $auxiliaryFields = [];

    /**
     * @var array
     */
    protected $secondaryFields = [];

    /**
     * @var string
     */
    protected $logoImageUrl;

    /**
     * @var null|string
     */
    protected $headerImageUrl;

    /**
     * @var null|string
     */
    protected $headerTextField;

    /**
     * @var null|string
     */
    protected $qrCode;

    /**
     * @var null|string
     */
    protected $barcodeImageUrl;

    /**
     * @var string
     */
    protected $aboveBarcodeImageUrl;

    /**
     * @var AirlineFlightInfo;
     */
    protected $flightInfo;

    /**
     * @param string                                                        $passengerName
     * @param string                                                        $pnrNumber
     * @param string                                                        $logoImageUrl
     * @param string                                                        $code
     * @param string                                                        $aboveBarcodeImageUrl
     * @param \BotMan\Drivers\Facebook\Extensions\Airline\AirlineFlightInfo $flightInfo
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass
     */
    public static function create(
        string $passengerName,
        string $pnrNumber,
        string $logoImageUrl,
        string $code,
        string $aboveBarcodeImageUrl,
        AirlineFlightInfo $flightInfo
    ): self {
        return new static($passengerName, $pnrNumber, $logoImageUrl, $code, $aboveBarcodeImageUrl, $flightInfo);
    }

    /**
     * AirlineBoardingPass constructor.
     *
     * @param string                                                        $passengerName
     * @param string                                                        $pnrNumber
     * @param string                                                        $logoImageUrl
     * @param string                                                        $code
     * @param string                                                        $aboveBarcodeImageUrl
     * @param \BotMan\Drivers\Facebook\Extensions\Airline\AirlineFlightInfo $flightInfo
     */
    public function __construct(
        string $passengerName,
        string $pnrNumber,
        string $logoImageUrl,
        string $code,
        string $aboveBarcodeImageUrl,
        AirlineFlightInfo $flightInfo
    ) {
        $this->passengerName = $passengerName;
        $this->pnrNumber = $pnrNumber;
        $this->logoImageUrl = $logoImageUrl;
        $this->aboveBarcodeImageUrl = $aboveBarcodeImageUrl;
        $this->flightInfo = $flightInfo;

        $this->setCode($code);
    }

    /**
     * @param string $travelClass
     *
     * @throws \BotMan\Drivers\Facebook\Exceptions\FacebookException
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass
     */
    public function travelClass(string $travelClass): self
    {
        if (! \in_array($travelClass, self::TRAVEL_TYPES, true)) {
            throw new FacebookException(
                sprintf('travel_class must be either "%s"', implode(', ', self::TRAVEL_TYPES))
            );
        }

        $this->travelClass = $travelClass;

        return $this;
    }

    /**
     * @param string $seat
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass
     */
    public function seat(string $seat): self
    {
        $this->seat = $seat;

        return $this;
    }

    /**
     * @param string $label
     * @param string $value
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass
     */
    public function addAuxiliaryField(string $label, string $value): self
    {
        $this->auxiliaryFields[] = $this->setLabelValue($label, $value);

        return $this;
    }

    /**
     * @param array $auxiliaryFields
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass
     */
    public function addAuxiliaryFields(array $auxiliaryFields): self
    {
        foreach ($auxiliaryFields as $label => $value) {
            $this->auxiliaryFields[] = $this->setLabelValue($label, $value);
        }

        return $this;
    }

    /**
     * @param string $label
     * @param string $value
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass
     */
    public function addSecondaryField(string $label, string $value): self
    {
        $this->secondaryFields[] = $this->setLabelValue($label, $value);

        return $this;
    }

    /**
     * @param array $secondaryFields
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass
     */
    public function addSecondaryFields(array $secondaryFields): self
    {
        foreach ($secondaryFields as $label => $value) {
            $this->secondaryFields[] = $this->setLabelValue($label, $value);
        }

        return $this;
    }

    /**
     * @param string $headerImageUrl
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass
     */
    public function headerImageUrl(string $headerImageUrl): self
    {
        $this->headerImageUrl = $headerImageUrl;

        return $this;
    }

    /**
     * @param string $headerTextField
     *
     * @return \BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass
     */
    public function headerTextField(string $headerTextField): self
    {
        $this->headerTextField = $headerTextField;

        return $this;
    }

    /**
     * @param string $code
     */
    private function setCode(string $code)
    {
        if (filter_var($code, FILTER_VALIDATE_URL)) {
            $this->barcodeImageUrl = $code;

            return;
        }
        $this->qrCode = $code;
    }

    /**
     * @param string $label
     * @param string $value
     *
     * @return array
     */
    private function setLabelValue(string $label, string $value): array
    {
        return [
            'label' => $label,
            'value' => $value,
        ];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'passenger_name' => $this->passengerName,
            'pnr_number' => $this->pnrNumber,
            'travel_class' => $this->travelClass,
            'seat' => $this->seat,
            'auxiliary_fields' => $this->auxiliaryFields,
            'secondary_fields' => $this->secondaryFields,
            'logo_image_url' => $this->logoImageUrl,
            'header_image_url' => $this->headerImageUrl,
            'header_text_field' => $this->headerTextField,
            'qr_code' => $this->qrCode,
            'barcode_image_url' => $this->barcodeImageUrl,
            'above_bar_code_image_url' => $this->aboveBarcodeImageUrl,
            'flight_info' => $this->flightInfo,
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
