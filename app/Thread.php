<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    /**
     * By whom action performed (source_via).
     */
    const PERSON_CUSTOMER = 1;
    const PERSON_USER = 2;

    public static $persons = [
        self::PERSON_CUSTOMER => 'customer',
        self::PERSON_USER     => 'user',
    ];

    /**
     * Thread types.
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
        // Thread by customer
        self::TYPE_CUSTOMER => 'customer',
        // Thread by user
        self::TYPE_MESSAGE => 'message',
        self::TYPE_NOTE    => 'note',
        // lineitem represents a change of state on the conversation. This could include, but not limited to, the conversation was assigned, the status changed, the conversation was moved from one mailbox to another, etc. A line item won’t have a body, to/cc/bcc lists, or attachments.
        self::TYPE_LINEITEM => 'lineitem',
        self::TYPE_PHONE    => 'phone',
        // When a conversation is forwarded, a new conversation is created to represent the forwarded conversation.
        // forwardparent is the type set on the thread of the original conversation that initiated the forward event.
        self::TYPE_FORWARDPARENT => 'forwardparent',
        // forwardchild is the type set on the first thread of the new forwarded conversation.
        self::TYPE_FORWARDCHILD => 'forwardchild',
        self::TYPE_CHAT         => 'chat',
    ];

    /**
     * Statuses (code must be equal to conversations statuses).
     */
    const STATUS_ACTIVE = 1;
    const STATUS_PENDING = 2;
    const STATUS_CLOSED = 3;
    const STATUS_SPAM = 4;
    const STATUS_NOCHANGE = 6;

    public static $statuses = [
        self::STATUS_ACTIVE   => 'active',
        self::STATUS_CLOSED   => 'closed',
        self::STATUS_NOCHANGE => 'nochange',
        self::STATUS_PENDING  => 'pending',
        self::STATUS_SPAM     => 'spam',
    ];

    /**
     * States.
     */
    const STATE_DRAFT = 1;
    const STATE_PUBLISHED = 2;
    const STATE_HIDDEN = 3;
    // A state of review means the thread has been stopped by Traffic Cop and is waiting
    // to be confirmed (or discarded) by the person that created the thread.
    const STATE_REVIEW = 4;

    public static $states = [
        self::STATE_DRAFT     => 'draft',
        self::STATE_PUBLISHED => 'published',
        self::STATE_HIDDEN    => 'hidden',
        self::STATE_REVIEW    => 'review',
    ];

    /**
     * Action associated with the line item.
     */
    // Conversation's status changed
    const ACTION_TYPE_STATUS_CHANGED = 1;
    // Conversation's assignee changed
    const ACTION_TYPE_USER_CHANGED = 2;
    // The conversation was moved from another mailbox
    const ACTION_TYPE_MOVED_FROM_MAILBOX = 3;
    // Another conversation was merged with this conversation
    const ACTION_TYPE_MERGED = 4;
    // The conversation was imported (no email notifications were sent)
    const ACTION_TYPE_IMPORTED = 5;
    //  A workflow was run on this conversation (either automatic or manual)
    const ACTION_TYPE_WORKFLOW_MANUAL = 6;
    const ACTION_TYPE_WORKFLOW_AUTO = 7;
    // The ticket was imported from an external Service
    const ACTION_TYPE_IMPORTED_EXTERNAL = 8;
    // Conversation customer changed
    const ACTION_TYPE_CUSTOMER_CHANGED = 9;
    // The ticket was deleted
    const ACTION_TYPE_DELETED_TICKET = 10;
    // The ticket was restored
    const ACTION_TYPE_RESTORE_TICKET = 11;

    // Describes an optional action associated with the line item
    // todo: values need to be checked via HelpScout API
    public static $action_types = [
        self::ACTION_TYPE_STATUS_CHANGED          => 'changed-ticket-status',
        self::ACTION_TYPE_USER_CHANGED            => 'changed-ticket-assignee',
        self::ACTION_TYPE_MOVED_FROM_MAILBOX      => 'moved-from-mailbox',
        self::ACTION_TYPE_MERGED                  => 'merged',
        self::ACTION_TYPE_IMPORTED                => 'imported',
        self::ACTION_TYPE_WORKFLOW_MANUAL         => 'manual-workflow',
        self::ACTION_TYPE_WORKFLOW_AUTO           => 'automatic-workflow',
        self::ACTION_TYPE_IMPORTED_EXTERNAL       => 'imported-external',
        self::ACTION_TYPE_CUSTOMER_CHANGED        => 'changed-ticket-customer',
        self::ACTION_TYPE_DELETED_TICKET          => 'deleted-ticket',
        self::ACTION_TYPE_RESTORE_TICKET          => 'restore-ticket',
    ];

    /**
     * Source types (equal to thread source types).
     */
    const SOURCE_TYPE_EMAIL = 1;
    const SOURCE_TYPE_WEB = 2;
    const SOURCE_TYPE_API = 3;

    public static $source_types = [
        self::SOURCE_TYPE_EMAIL => 'email',
        self::SOURCE_TYPE_WEB   => 'web',
        self::SOURCE_TYPE_API   => 'api',
    ];

    /**
     * The user assigned to this thread (assignedTo).
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * The user assigned to this thread (cached)
     */
    public function user_cached()
    {
        return $this->user()->rememberForever();
    }

    /**
     * Get the thread customer.
     */
    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Get conversation.
     */
    public function conversation()
    {
        return $this->belongsTo('App\Conversation');
    }

    /**
     * Get thread attachmets.
     */
    public function attachments()
    {
        return $this->hasMany('App\Attachment')->where('embedded', false);
        //return $this->hasMany('App\Attachment');
    }

    /**
     * Get thread embedded attachments.
     */
    public function embeds()
    {
        return $this->hasMany('App\Attachment')->where('embedded', true);
    }

    /**
     * Get user who created the thread.
     */
    public function created_by_user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get user who created the thread (cached)
     */
    public function created_by_user_cached()
    {
        return $this->created_by_user()->rememberForever()->first();
    }

    /**
     * Get customer who created the thread.
     */
    public function created_by_customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Get sanitized body HTML.
     *
     * @return string
     */
    public function getCleanBody()
    {
        $body = \Purifier::clean($this->body);

        // Remove all kinds of spaces after tags
        // https://stackoverflow.com/questions/3230623/filter-all-types-of-whitespace-in-php
        $body = preg_replace("/^(.*)>[\r\n]*\s+/mu", '$1>', $body);

        return $body;
    }

    /**
     * Get thread recipients.
     *
     * @return array
     */
    public function getToArray($exclude_array = [])
    {
        if ($this->to) {
            $to_array = json_decode($this->to);
            if ($to_array && $exclude_array) {
                $to_array = array_diff($to_array, $exclude_array);
            }
            return $to_array;
        } else {
            return [];
        }
    }

    /**
     * Get type name.
     */
    public function getTypeName()
    {
        return self::$types[$this->type];
    }

    /**
     * Get thread CC recipients.
     *
     * @return array
     */
    public function getCcArray($exclude_array = [])
    {
        if ($this->cc) {
            $cc_array = json_decode($this->cc);
            if ($cc_array && $exclude_array) {
                $cc_array = array_diff($cc_array, $exclude_array);
            }
            return $cc_array;
        } else {
            return [];
        }
    }

    /**
     * Get thread BCC recipients.
     *
     * @return array
     */
    public function getBccArray($exclude_array = [])
    {
        if ($this->bcc) {
            $bcc_array = json_decode($this->bcc);
            if ($bcc_array && $exclude_array) {
                $bcc_array = array_diff($bcc_array, $exclude_array);
            }
            return $bcc_array;
        } else {
            return [];
        }
    }

    /**
     * Set to as JSON.
     */
    public function setTo($emails)
    {
        $emails_array = Conversation::sanitizeEmails($emails);
        if ($emails_array) {
            $emails_array = array_unique($emails_array);
            $this->to = json_encode($emails_array);
        } else {
            $this->to = null;
        }
    }

    public function setCc($emails)
    {
        $emails_array = Conversation::sanitizeEmails($emails);
        if ($emails_array) {
            $emails_array = array_unique($emails_array);
            $this->cc = json_encode($emails_array);
        } else {
            $this->cc = null;
        }
    }

    public function setBcc($emails)
    {
        $emails_array = Conversation::sanitizeEmails($emails);
        if ($emails_array) {
            $emails_array = array_unique($emails_array);
            $this->bcc = json_encode($emails_array);
        } else {
            $this->bcc = null;
        }
    }

    /**
     * Get thread's status name.
     *
     * @return string
     */
    public function getStatusName()
    {
        return self::statusCodeToName($this->status);
    }

    /**
     * Get status name. Made as a function to allow status names translation.
     *
     * @param int $status
     *
     * @return string
     */
    public static function statusCodeToName($status)
    {
        switch ($status) {
            case self::STATUS_ACTIVE:
                return __('Active');
                break;

            case self::STATUS_PENDING:
                return __('Pending');
                break;

            case self::STATUS_CLOSED:
                return __('Closed');
                break;

            case self::STATUS_SPAM:
                return __('Spam');
                break;

            case self::STATUS_NOCHANGE:
                return __('Not changed');
                break;

            default:
                return '';
                break;
        }
    }

    /**
     * Get text for the assignee.
     *
     * @return string
     */
    public function getAssigneeName($ucfirst = false, $user = null)
    {
        if (!$this->user_id) {
            if ($ucfirst) {
                return __('Anyone');
            } else {
                return __('anyone');
            }
        } elseif (($user && $this->user_id == $user->id) || (!$user && $this->user_id == auth()->user()->id)) {
            if ($ucfirst) {
                return __('Yourself');
            } else {
                return __('yourself');
            }
        } else {
            return $this->user->getFullName();
        }
    }

    /**
     * Get user or customer who created the thead.
     */
    public function getCreatedBy()
    {
        if (!empty($this->created_by_user_id)) {
            return $this->created_by_user;
        } else {
            return $this->created_by_customer;
        }
    }
}
