<?php

namespace BotMan\Drivers\Facebook\Extensions;

use BotMan\BotMan\Interfaces\WebAccess;
use JsonSerializable;

class MediaTemplate implements JsonSerializable, WebAccess
{
    /** @var string */
    protected $mediaType;

    /** @var array */
    protected $elements = [];

    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @param $element
     * @return $this
     */
    public function element($element)
    {
        $this->elements = [$element->toArray()];

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'media',
                    'elements' => $this->elements,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver()
    {
        return [
            'type' => $this->mediaType,
            'elements' => $this->elements,
        ];
    }
}
