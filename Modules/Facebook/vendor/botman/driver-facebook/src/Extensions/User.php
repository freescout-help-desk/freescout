<?php

namespace BotMan\Drivers\Facebook\Extensions;

use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Users\User as BotManUser;

class User extends BotManUser implements UserInterface
{
    /**
     * @var array
     */
    protected $user_info;

    public function __construct(
        $id = null,
        $first_name = null,
        $last_name = null,
        $username = null,
        array $user_info = []
    ) {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->username = $username;
        $this->user_info = (array) $user_info;
    }

    /**
     * @return string
     */
    public function getProfilePic()
    {
        if (isset($this->user_info['profile_pic'])) {
            return $this->user_info['profile_pic'];
        }

        // Workplace (Facebook for companies) uses picture parameter
        if (isset($this->user_info['picture'])) {
            return $this->user_info['picture']['data']['url'];
        }
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->user_info['locale'] ?? null;
    }

    /**
     * @return int
     */
    public function getTimezone()
    {
        return isset($this->user_info['timezone']) ? $this->user_info['timezone'] : null;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return isset($this->user_info['gender']) ? $this->user_info['gender'] : null;
    }

    /**
     * @return bool
     */
    public function getIsPaymentEnabled()
    {
        return isset($this->user_info['is_payment_enabled']) ? $this->user_info['is_payment_enabled'] : null;
    }

    /**
     * @return array
     */
    public function getLastAdReferral()
    {
        return isset($this->user_info['last_ad_referral']) ? $this->user_info['last_ad_referral'] : null;
    }
}
