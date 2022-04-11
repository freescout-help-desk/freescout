<?php

namespace BotMan\Drivers\Facebook\Extensions;

use BotMan\BotMan\Interfaces\WebAccess;
use JsonSerializable;

class GenericTemplate implements JsonSerializable, WebAccess
{
    const RATIO_HORIZONTAL = 'horizontal';
    const RATIO_SQUARE = 'square';

    /** @var array */
    private static $allowedRatios = [
        self::RATIO_HORIZONTAL,
        self::RATIO_SQUARE,
    ];

    /** @var array */
    protected $elements = [];
    protected $quick_replies = [];

    /** @var string */
    protected $imageAspectRatio = self::RATIO_HORIZONTAL;

    /**
     * @return static
     */
    public static function create()
    {
        return new static;
    }

    /**
     * @param Element $element
     * @return $this
     */
    public function addElement(Element $element)
    {
        $this->elements[] = $element->toArray();

        return $this;
    }

    /**
     * @param array $elements
     * @return $this
     */
    public function addElements(array $elements)
    {
        foreach ($elements as $element) {
            if ($element instanceof Element) {
                $this->elements[] = $element->toArray();
            }
        }

        return $this;
    }

    /**
     * @param QuickReplyButton $button
     * @return $this
     */
    public function addQuickReply(QuickReplyButton $button)
    {
        $this->quick_replies[] = $button->toArray();

        return $this;
    }

    /**
     * @param array $buttons
     * @return $this
     */
    public function addQuickReplies(array $buttons)
    {
        foreach ($buttons as $button) {
            if ($button instanceof QuickReplyButton) {
                $this->quick_replies[] = $button->toArray();
            }
        }

        return $this;
    }

    /**
     * @param string $ratio
     * @return $this
     */
    public function addImageAspectRatio($ratio)
    {
        if (in_array($ratio, self::$allowedRatios)) {
            $this->imageAspectRatio = $ratio;
        }

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
                    'template_type' => 'generic',
                    'image_aspect_ratio' => $this->imageAspectRatio,
                    'elements' => $this->elements,
                ],
            ],
            'quick_replies' => $this->quick_replies,
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
            'type' => 'list',
            'elements' => $this->elements,
        ];
    }
}
