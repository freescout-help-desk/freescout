<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Mailbox extends Model
{

	/**
	 * From Name: name that will appear in the From field when a customer views your email.
	 */
    const FROM_NAME_MAILBOX = 1;
    const FROM_NAME_USER = 2;
    const FROM_NAME_CUSTOM = 3;

    /**
     * Default Status: when you reply to a message, this status will be set by default (also applies to email integration).
     */
    const TICKET_STATUS_ACTIVE = 1;
    const TICKET_STATUS_PENDING = 2;
    const TICKET_STATUS_CLOSED = 3;

    /**
     * Default Assignee
     */
    const TICKET_ASSIGNEE_ANYONE = 1;
    const TICKET_ASSIGNEE_REPLYING_UNASSIGNED = 2;
    const TICKET_ASSIGNEE_REPLYING = 3;

    /**
     * Email Template
     */
    const TEMPLATE_FANCY = 1;
    const TEMPLATE_PLAIN = 2;

    /**
     * Sending Emails
     */
    const OUT_METHOD_PHP_MAIL = 1;
    const OUT_METHOD_SENDMAIL = 2;
    const OUT_METHOD_SMTP = 3;
    //const OUT_METHOD_GMAIL = 3; // todo
    // todo: mailgun, sendgrid, mandrill, etc
    
    /**
     * Secure Connection
     */
    const OUT_SSL_NONE = 1;
    const OUT_SSL_SSL = 2;
    const OUT_SSL_TLS = 3;

    /**
     * Ratings Playcement: place ratings text above/below signature.
     */
    const RATINGS_PLACEMENT_ABOVE = 1;
    const RATINGS_PLACEMENT_BELOW = 2;

    /**
     * Attributes fillable using fill() method
     * @var [type]
     */
    protected $fillable  = ['name', 'slug', 'email', 'aliases', 'from_name', 'from_name_custom', 'ticket_status', 'ticket_assignee', 'template', 'signature', 'out_method', 'out_server', 'out_username', 'out_port', 'out_ssl', 'auto_reply_enabled', 'auto_reply_subject', 'auto_reply_message', 'office_hours_enabled', 'ratings', 'ratings_placement', 'ratings_text'];

    protected static function boot()
    {
        parent::boot();

        self::created(function (Mailbox $model)
        {
            $model->slug = strtolower(substr(md5(Hash::make($model->id)), 0, 16));
        });
    }

    /**
     * Get users having access to the mailbox
     */
    public function users()
    {
        return $this->belongsToMany('App\User');
    }
}
