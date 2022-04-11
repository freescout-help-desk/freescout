<?php

namespace BotMan\Drivers\Facebook\Extensions;

use BotMan\BotMan\Interfaces\WebAccess;
use JsonSerializable;

class OpenGraphTemplate implements JsonSerializable, WebAccess
{
    /**
     * @var string
     */
    protected $mediaType;

    /**
     * @var array
     */
    protected $elements = [];

    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @param OpenGraphElement $element
     *
     * @return $this
     */
    public function addElement(OpenGraphElement $element): self
    {
        $this->elements[] = $element->toArray();

        return $this;
    }

    /**
     * @param OpenGraphElement[] $elements
     *
     * @return $this
     */
    public function addElements(array $elements): self
    {
        foreach ($elements as $element) {
            if ($element instanceof OpenGraphElement) {
                $this->elements[] = $element->toArray();
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'open_graph',
                    'elements' => $this->elements,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver(): array
    {
        return [
            'type' => $this->mediaType,
            'elements' => $this->elements,
        ];
    }
}
