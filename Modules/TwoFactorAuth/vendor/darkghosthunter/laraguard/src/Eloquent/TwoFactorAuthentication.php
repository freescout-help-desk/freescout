<?php

namespace DarkGhostHunter\Laraguard\Eloquent;

use ParagonIE\ConstantTime\Base32;
use Illuminate\Database\Eloquent\Model;
use DarkGhostHunter\Laraguard\Contracts\TwoFactorTotp;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property-read int $id
 *
 * @property-read null|\DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable $authenticatable
 *
 * @property string $shared_secret
 *
 * @property string $label
 * @property int $digits
 * @property int $seconds
 * @property int $window
 * @property string $algorithm
 * @property array $totp_config
 * @property null|\Illuminate\Support\Collection $recovery_codes
 * @property null|\Illuminate\Support\Collection $safe_devices
 * @property null|\Illuminate\Support\Carbon|\DateTime $enabled_at
 * @property null|\Illuminate\Support\Carbon|\DateTime $recovery_codes_generated_at
 *
 * @property null|\Illuminate\Support\Carbon|\DateTime $updated_at
 * @property null|\Illuminate\Support\Carbon|\DateTime $created_at
 */
class TwoFactorAuthentication extends Model implements TwoFactorTotp
{
    use HandlesCodes;
    use HandlesRecoveryCodes;
    use HandlesSafeDevices;
    use SerializesSharedSecret;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'authenticatable_id' => 'int',
        'digits'             => 'int',
        'seconds'            => 'int',
        'window'             => 'int',
        'recovery_codes'     => 'collection',
        'safe_devices'       => 'collection',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'enabled_at',
        'recovery_codes_generated_at',
    ];

    /**
     * The model that uses Two Factor Authentication.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function authenticatable()
    {
        return $this->morphTo('authenticatable');
    }

    /**
     * Gets the Shared Secret attribute from its binary form.
     *
     * @param $value
     * @return null|string
     */
    protected function getSharedSecretAttribute($value)
    {
        return $value === null ? $value : Base32::encodeUpper($value);
    }

    /**
     * Sets the Shared Secret attribute to its binary form.
     *
     * @param $value
     */
    protected function setSharedSecretAttribute($value)
    {
        $this->attributes['shared_secret'] = Base32::decodeUpper($value);
    }

    /**
     * Sets the Algorithm to lowercase.
     *
     * @param $value
     */
    protected function setAlgorithmAttribute($value)
    {
        $this->attributes['algorithm'] = strtolower($value);
    }

    /**
     * Returns if the Two Factor Authentication has been enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled_at !== null;
    }

    /**
     * Returns if the Two Factor Authentication is not been enabled.
     *
     * @return bool
     */
    public function isDisabled()
    {
        return ! $this->isEnabled();
    }

    /**
     * Flushes all authentication data and cycles the Shared Secret.
     *
     * @return $this
     */
    public function flushAuth()
    {
        $this->attributes['recovery_codes'] = null;
        $this->attributes['recovery_codes_generated_at'] = null;
        $this->attributes['safe_devices'] = null;
        $this->attributes['enabled_at'] = null;

        $this->attributes = array_merge($this->attributes, config('laraguard.totp'));

        $this->setSharedSecretAttribute(static::generateRandomSecret());

        return $this;
    }

    /**
     * Creates a new Random Secret.
     *
     * @return string
     */
    public static function generateRandomSecret()
    {
        return Base32::encodeUpper(
            random_bytes(config('laraguard.secret_length'))
        );
    }
}
