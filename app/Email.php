<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    /**
     * Email types.
     */
    const TYPE_WORK = 1;
    const TYPE_HOME = 2;
    const TYPE_OTHER = 3;

    public static $types = [
        self::TYPE_WORK  => 'work',
        self::TYPE_HOME  => 'home',
        self::TYPE_OTHER => 'other',
    ];

    public $timestamps = false;

    /**
     * Attributes which are not fillable using fill() method.
     */
    protected $guarded = ['id', 'customer_id'];

    /**
     * Get email's customer.
     */
    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Sanitize email address.
     *
     * @param string $email
     *
     * @return string
     */
    public static function sanitizeEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $email = strtolower($email);

        return $email;
    }
}
