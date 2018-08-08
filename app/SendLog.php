<?php
/**
 * Outgoing emails.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class SendLog extends Model
{
    /**
     * Status of the email sent to the customer or user.
     * https://documentation.mailgun.com/en/latest/api-events.html#event-types.
     */
    const STATUS_ACCEPTED = 1; // accepted (for delivery)
    const STATUS_SEND_ERROR = 2;
    const STATUS_DELIVERY_SUCCESS = 4;
    const STATUS_DELIVERY_ERROR = 5; // rejected, failed
    const STATUS_OPENED = 6;
    const STATUS_CLICKED = 7;
    const STATUS_UNSUBSCRIBED = 8;
    const STATUS_COMPLAINED = 9;

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
        return $this->belongsTo('App\Customer');
    }

    /**
     * User.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Thread.
     */
    public function thread()
    {
        return $this->belongsTo('App\Thread');
    }

    /**
     * Save log record.
     */
    public static function log($thread_id, $message_id, $email, $status, $customer_id = null, $user_id = null, $message = null)
    {
        $send_log = new self();
        $send_log->thread_id = $thread_id;
        $send_log->message_id = $message_id;
        $send_log->email = $email;
        $send_log->status = $status;
        $send_log->customer_id = $customer_id;
        $send_log->user_id = $user_id;
        $send_log->message = $message;
        $send_log->save();

        return true;
    }
}
