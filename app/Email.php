<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    /**
     * Email types.
     */
    const TYPE_WORK = 'work';
    const TYPE_HOME = 'home';
    const TYPE_OTHER = 'other';

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
     * Sanatize email address.
     *
     * @param string $email
     *
     * @return string
     */
    public static function sanatizeEmail($email)
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $email = strtolower($email);

        return $email;
    }
}
