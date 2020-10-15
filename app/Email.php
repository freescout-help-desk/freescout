<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class Email extends Model
{
    use Rememberable;
    // This is obligatory.
    public $rememberCacheDriver = 'array';
    
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
        return $this->belongsTo(\App\Customer::class);
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
        // FILTER_VALIDATE_EMAIL does not work with long emails for example
        // Email validation is not recommended:
        // http://stackoverflow.com/questions/201323/using-a-regular-expression-to-validate-an-email-address/201378#201378
        // So we just check for @
        if (!preg_match('/@/', $email)) {
            return false;
        }
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $email = mb_strtolower($email, 'UTF-8');
        // Remove trailing dots.
        $email = preg_replace("/\.+$/", '', $email);
        // Remove dot before @
        $email = preg_replace("/\.+@/", '@', $email);

        return $email;
    }

    public function getNameFromEmail()
    {
        return explode('@', $this->email)[0];
    }

    public static function create($email, $customer_id, $type = self::TYPE_WORK)
    {
        try {
            $email_obj = new Email();
            $email_obj->email = $email;
            $email_obj->type = array_key_exists($type, self::$types) ? $type : self::TYPE_WORK;
            $email_obj->customer_id = $customer_id;
            $email_obj->save();

            return $email_obj;
        } catch (\Exception $e) {
            return null;
        }
    }
}
