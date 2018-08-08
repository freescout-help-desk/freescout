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
    public function user()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * User.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    
}
