<?php
/**
 * Outgoing emails.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sendmail extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Customer.
     */
    public function customer()
    {
        return $this->belongsTo(\App\Customer::class);
    }

    /**
     * User.
     */
    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }
}
