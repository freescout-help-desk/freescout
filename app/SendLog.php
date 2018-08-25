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
     * Mail types.
     */
    const MAIL_TYPE_EMAIL_TO_CUSTOMER = 1;
    const MAIL_TYPE_USER_NOTIFICATION = 2;
    const MAIL_TYPE_AUTO_REPLY        = 3;
    const MAIL_TYPE_WRONG_USER_EMAIL_MESSAGE  = 4;

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
    public static function log($thread_id, $message_id, $email, $mail_type, $status, $customer_id = null, $user_id = null, $status_message = null)
    {
        $send_log = new self();
        $send_log->thread_id = $thread_id;
        $send_log->message_id = $message_id;
        $send_log->email = $email;
        $send_log->mail_type = $mail_type;
        $send_log->status = $status;
        $send_log->customer_id = $customer_id;
        $send_log->user_id = $user_id;
        $send_log->status_message = $status_message;
        $send_log->save();

        return true;
    }

    /**
     * Get name of the status.
     */
    public function getStatusName()
    {
        switch ($this->status) {
            case self::STATUS_ACCEPTED:
                return __('Accepted for delivery');
            case self::STATUS_SEND_ERROR:
                return __('Send error');
            case self::STATUS_DELIVERY_SUCCESS:
                return __('Successfully delivered');
            case self::STATUS_DELIVERY_ERROR:
                return __('Delivery error');
            case self::STATUS_OPENED:
                return __('Recipient opened the message');
            case self::STATUS_CLICKED:
                return __('Recipient clicked a link in the message');
            case self::STATUS_UNSUBSCRIBED:
                return __('Recipient unsubscribed');
            case self::STATUS_COMPLAINED:
                return __('Recipient complained');
            default:
                return __('Unknown');
        }
    }

    public function isErrorStatus()
    {
        if (in_array($this->status, [self::STATUS_SEND_ERROR, self::STATUS_DELIVERY_ERROR])) {
            return true;
        } else {
            return false;
        }
    }

    public function isSuccessStatus()
    {
        if (in_array($this->status, [self::STATUS_DELIVERY_SUCCESS])) {
            return true;
        } else {
            return false;
        }
    }

    public function getMailTypeName()
    {
        switch ($this->mail_type) {
            case self::MAIL_TYPE_EMAIL_TO_CUSTOMER:
                return __('Email to customer');
            case self::MAIL_TYPE_USER_NOTIFICATION:
                return __('User notification');
            case self::MAIL_TYPE_AUTO_REPLY:
                return __('Auto reply to customer');
            case self::MAIL_TYPE_WRONG_USER_EMAIL_MESSAGE:
                return __('User using wrong email notification');
        }
    }
}
