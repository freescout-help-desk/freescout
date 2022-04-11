<?php

namespace BotMan\Drivers\Facebook\Extensions;

use BotMan\BotMan\Interfaces\QuestionActionInterface;

class QuickReplyButton implements QuestionActionInterface
{
    /** @var string */
    protected $contentType = self::TYPE_TEXT;

    /** @var string */
    protected $title;

    /** @var string */
    protected $payload;

    /** @var string */
    protected $imageUrl;

    const TYPE_TEXT = 'text';

    /**
     * @param string $title
     * @return static
     */
    public static function create($title = '')
    {
        return new static($title);
    }

    /**
     * @param string $title
     */
    public function __construct($title)
    {
        $this->title = $title;
    }

    /**
     * Set the button type.
     *
     * @param string $type
     * @return $this
     */
    public function type($type)
    {
        $this->contentType = $type;

        return $this;
    }

    /**
     * @param $payload
     * @return $this
     */
    public function payload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Set the button URL.
     *
     * @param string $url
     * @return $this
     */
    public function imageUrl($url)
    {
        $this->imageUrl = $url;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $buttonArray = [];

        if ($this->contentType === 'text') {
            $buttonArray = [
                'content_type' => $this->contentType,
                'title' => $this->title,
                'payload' => $this->payload,
                'image_url' => $this->imageUrl,
            ];
        } else {
            $buttonArray['content_type'] = $this->contentType;
        }

        return $buttonArray;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
