<?php

namespace BotMan\Drivers\Facebook\Extensions;

class ElementButton
{
    /** @var string */
    protected $title;

    /** @var string */
    protected $type = self::TYPE_WEB_URL;

    /** @var string */
    protected $url;

    /** @var string */
    protected $fallback_url;

    /** @var string */
    protected $payload;

    /** @var string */
    protected $webview_height_ratio = self::RATIO_FULL;

    /** @var string */
    protected $webview_share_button;

    /** @var bool */
    protected $messenger_extensions = false;

    /** @var GenericTemplate */
    protected $shareContents;

    const TYPE_ACCOUNT_LINK = 'account_link';
    const TYPE_ACCOUNT_UNLINK = 'account_unlink';
    const TYPE_WEB_URL = 'web_url';
    const TYPE_PAYMENT = 'payment';
    const TYPE_POSTBACK = 'postback';
    const TYPE_SHARE = 'element_share';
    const TYPE_CALL = 'phone_number';

    const RATIO_COMPACT = 'compact';
    const RATIO_TALL = 'tall';
    const RATIO_FULL = 'full';

    /**
     * @param string $title
     * @return static
     */
    public static function create($title)
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
     * Set the button URL.
     * @param string $url
     * @return $this
     */
    public function url($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the button type.
     * @param string $type
     * @return $this
     */
    public function type($type)
    {
        $this->type = $type;

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
     * @param string $fallback_url
     * @return $this
     */
    public function fallbackUrl($fallback_url)
    {
        $this->fallback_url = $fallback_url;

        return $this;
    }

    /**
     * enable messenger extensions.
     * @return $this
     */
    public function enableExtensions()
    {
        $this->messenger_extensions = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableShare()
    {
        $this->webview_share_button = 'HIDE';

        return $this;
    }

    /**
     * @return $this
     */
    public function removeHeightRatio()
    {
        $this->webview_height_ratio = null;

        return $this;
    }

    /**
     * Set ratio to one of RATIO_COMPACT, RATIO_TALL, RATIO_FULL.
     * @param string $ratio
     * @return $this
     */
    public function heightRatio($ratio = self::RATIO_FULL)
    {
        $this->webview_height_ratio = $ratio;

        return $this;
    }

    /**
     * Optional. The message that you wish the recipient of the share to see,
     * if it is different from the one this button is attached to.
     * The format follows that used in Send API, but must be a generic template with up to one URL button.
     * @param GenericTemplate $shareContents
     * @return $this
     */
    public function shareContents($shareContents)
    {
        $this->shareContents = $shareContents;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $buttonArray = [
            'type' => $this->type,
        ];

        if ($this->type !== self::TYPE_SHARE) {
            if ($this->type !== self::TYPE_ACCOUNT_LINK && $this->type !== self::TYPE_ACCOUNT_UNLINK) {
                $buttonArray['title'] = $this->title;
            }

            if ($this->type === self::TYPE_POSTBACK || $this->type === self::TYPE_CALL) {
                $buttonArray['payload'] = $this->payload;
            } else {
                $buttonArray['url'] = $this->url;
            }

            if ($this->type === self::TYPE_WEB_URL) {
                if (! is_null($this->webview_height_ratio)) {
                    $buttonArray['webview_height_ratio'] = $this->webview_height_ratio;
                }
                if (! is_null($this->webview_share_button)) {
                    $buttonArray['webview_share_button'] = $this->webview_share_button;
                }

                if ($this->messenger_extensions) {
                    $buttonArray['messenger_extensions'] = $this->messenger_extensions;
                    $buttonArray['fallback_url'] = $this->fallback_url ?: $this->url;
                }
            }
        } elseif ($this->type == self::TYPE_SHARE && ! is_null($this->shareContents)) {
            $buttonArray['share_contents'] = $this->shareContents->toArray();
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
