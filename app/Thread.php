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

    protected $dates = [
        'opened_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The user assigned to this thread (assignedTo).
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * The user assigned to this thread (cached).
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
     * Get the thread customer (cached).
     */
    public function customer_cached()
    {
        return $this->customer()->rememberForever();
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
     * All kinds of attachments including embedded.
     */
    public function all_attachments()
    {
        return $this->hasMany('App\Attachment');
    }

    /**
     * Get user who created the thread.
     */
    public function created_by_user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get user who created the thread (cached).
     */
    public function created_by_user_cached()
    {
        return $this->created_by_user()->rememberForever();
    }

    /**
     * Get customer who created the thread.
     */
    public function created_by_customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Get user who edited draft.
     */
    public function edited_by_user()
    {
        return $this->belongsTo('App\User');
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
        return \App\Misc\Helper::jsonToArray($this->to, $exclude_array);
    }

    public function getToString($exclude_array = [])
    {
        return implode(', ', $this->getToArray($exclude_array));
    }

    /**
     * Get first address from the To list.
     */
    public function getToFirst()
    {
        $to = $this->getToArray();

        return array_shift($to);
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
        return \App\Misc\Helper::jsonToArray($this->cc, $exclude_array);
    }

    public function getCcString($exclude_array = [])
    {
        return implode(', ', $this->getCcArray($exclude_array));
    }

    /**
     * Get thread BCC recipients.
     *
     * @return array
     */
    public function getBccArray($exclude_array = [])
    {
        return \App\Misc\Helper::jsonToArray($this->bcc, $exclude_array);
    }

    public function getBccString($exclude_array = [])
    {
        return implode(', ', $this->getBccArray($exclude_array));
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
     * Get text for the assignee in line item.
     *
     * @return string
     */
    public function getAssigneeName($ucfirst = false, $by_user = null)
    {
        if (!$by_user) {
            $by_user = auth()->user();
        }
        if (!$this->user_id) {
            if ($ucfirst) {
                return __('Anyone');
            } else {
                return __('anyone');
            }
        } elseif ($by_user && $this->user_id == $by_user->id) {
            if ($this->created_by_user_id && $this->created_by_user_id == $this->user_id) {
                $name = __('yourself');
            } else {
                $name = __('you');
            }
            if ($ucfirst) {
                $name = ucfirst($name);
            }

            return $name;
        } else {
            // User may be deleted
            if ($this->user) {
                return $this->user->getFullName();
            } else {
                return '';
            }
        }
    }

    /**
     * Get user or customer who created the thead.
     */
    public function getCreatedBy()
    {
        if (!empty($this->created_by_user_id)) {
            // User can be deleted
            if ($this->created_by_user) {
                return $this->created_by_user;
            } else {
                return \App\User::getDeletedUser();
            }
        } else {
            return $this->created_by_customer;
        }
    }

    /**
     * Get creator of the thread.
     */
    public function getPerson($cached = false)
    {
        if ($this->type == self::TYPE_CUSTOMER) {
            if ($cached) {
                return $this->customer_cached;
            } else {
                return $this->customer;
            }
        } else {
            if ($cached) {
                return $this->created_by_user_cached;
            } else {
                return $this->created_by_user;
            }
        }
    }

    /**
     * Description of what happened.
     */
    public function getActionDescription($conversation_number, $escape = true)
    {
        $person = '';
        $did_this = '';

        // Person
        $person = '';
        if ($this->type == self::TYPE_CUSTOMER) {
            $person = $this->customer_cached->getFullName(true);
        } elseif ($this->state == self::STATE_DRAFT && !empty($this->edited_by_user_id)) {
            // Draft
            if (auth()->user() && $this->edited_by_user_id == auth()->user()->id) {
                $person = __('you');
            } else {
                $person = $this->edited_by_user->getFullName();
            }
        } elseif ($this->created_by_user_cached) {
            if ($this->created_by_user_id && auth()->user() && $this->created_by_user_cached->id == auth()->user()->id) {
                $person = __('you');
            } else {
                $person = $this->created_by_user_cached->getFullName();
            }
        }

        // Did this
        if ($this->type == self::TYPE_LINEITEM) {
            if ($this->action_type == self::ACTION_TYPE_STATUS_CHANGED) {
                $did_this = __('marked as :status_name conversation #:conversation_number', ['status_name' => $this->getStatusName(), 'conversation_number' => $conversation_number]);
            } elseif ($this->action_type == self::ACTION_TYPE_USER_CHANGED) {
                $did_this = __('assigned :assignee convsersation #:conversation_number', ['assignee' => $this->getAssigneeName(false, null), 'conversation_number' => $conversation_number]);
            } elseif ($this->action_type == self::ACTION_TYPE_CUSTOMER_CHANGED) {
                $did_this = __('changed the customer to :customer in conversation #:conversation_number', ['customer' => $this->customer->getFullName(true), 'conversation_number' => $conversation_number]);
            }
        } elseif ($this->state == self::STATE_DRAFT) {
            if (empty($this->edited_by_user_id)) {
                $did_this = __('created a draft');
            } else {
                $did_this = __("edited :creator's draft", ['creator' => $this->created_by_user_cached->getFirstName()]);
            }
        } else {
            if ($this->first) {
                $did_this = __('started a new conversation #:conversation_number', ['conversation_number' => $conversation_number]);
            } elseif ($this->type == self::TYPE_NOTE) {
                $did_this = __('added a note to conversation #:conversation_number', ['conversation_number' => $conversation_number]);
            } else {
                $did_this = __('replied to conversation #:conversation_number', ['conversation_number' => $conversation_number]);
            }
        }

        $description = ':person_tag_start:person:person_tag_end :did_this';
        if ($escape) {
            $description = htmlspecialchars($description);
        }

        return __($description, [
            'person'           => $person,
            'person_tag_start' => '<strong>',
            'person_tag_end'   => '</strong>',
            'did_this'         => $did_this,
        ]);
    }

    /**
     * Get thread state name.
     */
    public function getStateName()
    {
        return self::$states[$this->state];
    }

    public function deleteThread()
    {
        $this->deteleAttachments();
        $this->delete();
    }

    /**
     * Delete thread attachments.
     */
    public function deteleAttachments()
    {
        Attachment::deleteByIds($this->all_attachments()->pluck('id')->toArray());
    }

    public function isDraft()
    {
        return $this->state == self::STATE_DRAFT;
    }

    /**
     * Get original body or body.
     */
    public function getBodyOriginal()
    {
        if (!empty($this->body_original)) {
            return $this->body_original;
        } else {
            return $this->body;
        }
    }

    /**
     * Get name for the reply to customer.
     *
     * @param [type] $mailbox [description]
     *
     * @return [type] [description]
     */
    public function getFromName($mailbox = null)
    {
        // Created by customer
        if ($this->source_via == self::PERSON_CUSTOMER) {
            return $this->getCreatedBy()->getFirstName(true);
        }

        // Created by user
        if (empty($mailbox)) {
            $mailbox = $this->conversation->mailbox;
        }
        // Mailbox name by default
        $name = $mailbox->name;

        if ($mailbox->from_name == Mailbox::FROM_NAME_CUSTOM && $mailbox->from_name_custom) {
            $name = $mailbox->from_name_custom;
        } elseif ($mailbox->from_name == Mailbox::FROM_NAME_USER && $this->getCreatedBy()) {
            $name = $this->getCreatedBy()->getFirstName(true);
        }

        return $name;
    }

    /**
     * Check if thread is a reply from customer or user.
     *
     * @return bool [description]
     */
    public function isReply()
    {
        return in_array($this->type, [\App\Thread::TYPE_MESSAGE, \App\Thread::TYPE_CUSTOMER]);
    }

    /**
     * Is this thread created from auto responder email.
     *
     * @return bool [description]
     */
    public function isAutoResponder()
    {
        return \MailHelper::isAutoResponder($this->headers);
    }

    /**
     * Is thread created from incoming bounce email.
     *
     * @return bool [description]
     */
    public function isBounce()
    {
        if (!empty($this->getSendStatusData()['is_bounce'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Send status data mayb contain the following information:
     * - bounce info (status_code, action, diagnostic_code, is_bounce, bounce_for_thread, bounce_for_conversation, bounced_by_thread, bounced_by_conversation)
     * - send error message
     * - click date
     * - unsubscribe date
     * - complain date.
     *
     * @return [type] [description]
     */
    public function getSendStatusData()
    {
        return \Helper::jsonToArray($this->send_status_data);
    }

    public function updateSendStatusData($new_data)
    {
        if ($new_data) {
            $send_status_data = $this->getSendStatusData();
            if ($send_status_data) {
                $send_status_data = array_merge($send_status_data, $new_data);
            } else {
                $send_status_data = $new_data;
            }
            $this->send_status_data = json_encode($send_status_data);
        } else {
            $this->send_status_data = null;
        }
    }

    public function isSendStatusError()
    {
        return in_array($this->send_status, \App\SendLog::$status_errors);
    }

    /**
     * Create thread.
     * 
     * @param  [type] $conversation_id [description]
     * @param  [type] $text            [description]
     * @param  array  $data            [description]
     * @return [type]                  [description]
     */
    public static function create($conversation, $type, $body, $data = [], $save = true)
    {
        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->type = $type;
        $thread->body = $body;
        $thread->status = $conversation->status;
        $thread->state = Thread::STATE_PUBLISHED;

        // Assigned to.
        if (!empty($data['user_id'])) {
            $thread->user_id = $data['user_id'];
        }
        if (!empty($data['message_id'])) {
            $thread->message_id = $data['message_id'];
        }
        if (!empty($data['headers'])) {
            $thread->headers = $data['headers'];
        }
        if (!empty($data['from'])) {
            $thread->from = $data['from'];
        }
        if (!empty($data['to'])) {
            $thread->setTo($data['to']);
        }
        if (!empty($data['cc'])) {
            $thread->setCc($data['cc']);
        }
        if (!empty($data['bcc'])) {
            $thread->setBcc($data['bcc']);
        }
        if (isset($data['first'])) {
            $thread->from = $data['first'];
        }
        if (isset($data['source_via'])) {
            $thread->source_via = $data['source_via'];
        }
        if (isset($data['source_type'])) {
            $thread->source_type = $data['source_type'];
        }
        if (!empty($data['customer_id'])) {
            $thread->customer_id = $data['customer_id'];
        }
        if (!empty($data['created_by_customer_id'])) {
            $thread->created_by_customer_id = $data['created_by_customer_id'];
        }
        if (!empty($data['created_by_user_id'])) {
            $thread->created_by_user_id = $data['created_by_user_id'];
        }

        if ($save) {
            $thread->save();
        }

        return $thread;
    }
}
