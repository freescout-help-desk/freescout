<?php

namespace BotMan\Drivers\Facebook\Extensions;

use JsonSerializable;

class MediaAttachmentElement implements JsonSerializable
{
    /** @var string */
    protected $media_type;

    /** @var string */
    protected $attachment_id;

    /** @var array */
    protected $buttons;

    /**
     * @param $mediaType
     * @return static
     */
    public static function create($mediaType)
    {
        return new static($mediaType);
    }

    /**
     * @param $mediaType
     */
    public function __construct($mediaType)
    {
        $this->media_type = $mediaType;
    }

    /**
     * @param $attachmentId
     * @return $this
     */
    public function attachmentId($attachmentId)
    {
        $this->attachment_id = $attachmentId;

        return $this;
    }

    /**
     * @param ElementButton $button
     * @return $this
     */
    public function addButton(ElementButton $button)
    {
        $this->buttons[] = $button->toArray();

        return $this;
    }

    /**
     * @param array $buttons
     * @return $this
     */
    public function addButtons(array $buttons)
    {
        foreach ($buttons as $button) {
            if ($button instanceof ElementButton) {
                $this->buttons[] = $button->toArray();
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'media_type' => $this->media_type,
            'attachment_id' => $this->attachment_id,
            'buttons' => $this->buttons,
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
