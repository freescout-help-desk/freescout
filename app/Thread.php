<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    /**
     * By whom action performed (source_via)
     */
    const PERSON_CUSTOMER = 1;
    const PERSON_USER = 2;
    
    public static $persons = array(
        self::PERSON_CUSTOMER => 'customer',
        self::PERSON_USER => 'user',
    );

    /**
     * Thread types
     */
    // Email from customer
    const TYPE_CUSTOMER = 1;
    // Thead created by user
    const TYPE_MESSAGE = 2;
    const TYPE_NOTE = 3;
    // Thread status change
    const TYPE_LINEITEM = 4;
    const TYPE_PHONE = 5;
    // Forwarded threads
    const TYPE_FORWARDPARENT = 6;
    const TYPE_FORWARDCHILD = 7;
    const TYPE_CHAT = 8;

    public static $types = [
    	self::TYPE_CUSTOMER => 'customer',
    	self::TYPE_MESSAGE => 'message',
    	self::TYPE_NOTE => 'note',
    	self::TYPE_LINEITEM => 'lineitem',
    	self::TYPE_PHONE => 'phone',
    	self::TYPE_FORWARDPARENT => 'forwardparent',
    	self::TYPE_FORWARDCHILD => 'forwardchild',
    	self::TYPE_CHAT => 'chat',
    ];

    /**
     * Statuses
     */
    const STATUS_ACTIVE = 1;
    const STATUS_CLOSED = 2;
    const STATUS_NOCHANGE = 3;
    const STATUS_PENDING = 4;
    const STATUS_SPAM = 5;

    public static $statuses = array(
    	self::STATUS_ACTIVE => 'active',
    	self::STATUS_CLOSED => 'closed',
    	self::STATUS_NOCHANGE => 'nochange',
    	self::STATUS_PENDING => 'pending',
    	self::STATUS_SPAM => 'spam',
    );

    /**
     * States
     */
    const STATE_DRAFT = 1;
    const STATE_PUBLISHED = 2;
    const STATE_HIDDEN = 3;
    const STATE_REVIEW = 4;
  
    public static $states = array(
    	self::STATE_DRAFT => 'draft',
    	self::STATE_PUBLISHED => 'published',
    	self::STATE_HIDDEN => 'hidden',
    	self::STATE_REVIEW => 'review',
    );

    /**
     * Action associated with the line item
     */
    // The conversation was moved from another mailbox
    const ACTION_TYPE_MOVED_FROM_MAILBOX = 1;
    // Another conversation was merged with this conversation
    const ACTION_TYPE_MERGED = 2;
    // The conversation was imported (no email notifications were sent)
    const ACTION_TYPE_IMPORTED = 3;
    //  A workflow was run on this conversation (either automatic or manual)
    const ACTION_TYPE_WORKFLOW_MANUAL = 4;
    const ACTION_TYPE_WORKFLOW_AUTO = 5;
    // The ticket was imported from an external Service
    const ACTION_TYPE_IMPORTED_EXTERNAL = 6;
    // The customer associated with the ticket was changed
    const ACTION_TYPE_CHANGED_TICKET_CUSTOMER = 7;
    // The ticket was deleted
    const ACTION_TYPE_DELETED_TICKET = 8;
    // The ticket was restored
    const ACTION_TYPE_RESTORE_TICKET = 9;

    // Describes an optional action associated with the line item
    // todo: values need to be checked via HelpScout API
    public static $action_types = [
    	self::ACTION_TYPE_MOVED_FROM_MAILBOX => 'moved-from-mailbox',
    	self::ACTION_TYPE_MERGED => 'merged',
    	self::ACTION_TYPE_IMPORTED => 'imported',
    	self::ACTION_TYPE_WORKFLOW_MANUAL => 'manual-workflow',
    	self::ACTION_TYPE_WORKFLOW_AUTO => 'automatic-workflow',
    	self::ACTION_TYPE_IMPORTED_EXTERNAL => 'imported-external',
    	self::ACTION_TYPE_CHANGED_TICKET_CUSTOMER => 'changed-ticket-customer',
    	self::ACTION_TYPE_DELETED_TICKET => 'deleted-ticket',
    	self::ACTION_TYPE_RESTORE_TICKET => 'restore-ticket',
    ];

	/**
     * Source types (equal to thread source types)
     */
    const SOURCE_TYPE_EMAIL = 1;
    const SOURCE_TYPE_WEB = 2;
    const SOURCE_TYPE_API = 3;
  
    public static $source_types = [
    	self::SOURCE_TYPE_EMAIL => 'email',
    	self::SOURCE_TYPE_WEB => 'web',
    	self::SOURCE_TYPE_API => 'api',
    ];

	/**
     * Status of the email sent to the customer or user, to whom the thread is assigned
     */
    const SEND_STATUS_TOSEND = 1;
    const SEND_STATUS_SENT = 2;
    const SEND_STATUS_DELIVERY_SUCCESS = 3;
    const SEND_STATUS_DELIVERY_ERROR = 4;

    /**
     * The user assigned to this thread (assignedTo)
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the thread customer
     */
    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Get conversation
     */
    public function conversation()
    {
        return $this->belongsTo('App\Conversation');
    }
}
