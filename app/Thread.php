<?php

namespace App;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\UserAddedNote;
use App\Events\CustomerCreatedConversation;
use App\Events\UserCreatedConversation;
use App\Events\UserCreatedConversationDraft;
use App\Events\UserCreatedThreadDraft;
use App\Events\UserReplied;
use App\Events\CustomerReplied;
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
    //const TYPE_PHONE = 5;
    // Forwarded threads - used in API only.
    //const TYPE_FORWARDPARENT = 6;
    //const TYPE_FORWARDCHILD = 7;
    const TYPE_CHAT = 8;

    public static $types = [
        // Thread by customer
        self::TYPE_CUSTOMER => 'customer',
        // Thread by user
        self::TYPE_MESSAGE => 'message',
        self::TYPE_NOTE    => 'note',
        // lineitem represents a change of state on the conversation. This could include, but not limited to, the conversation was assigned, the status changed, the conversation was moved from one mailbox to another, etc. A line item won’t have a body, to/cc/bcc lists, or attachments.
        self::TYPE_LINEITEM => 'lineitem',
        //self::TYPE_PHONE    => 'phone',
        // When a conversation is forwarded, a new conversation is created to represent the forwarded conversation.
        // forwardparent is the type set on the thread of the original conversation that initiated the forward event.
        //self::TYPE_FORWARDPARENT => 'forwardparent',
        // forwardchild is the type set on the first thread of the new forwarded conversation.
        //self::TYPE_FORWARDCHILD => 'forwardchild',
        // Not used.
        self::TYPE_CHAT         => 'chat',
    ];

    /**
     * Subtypes (for notes mostly)
     */
    const SUBTYPE_FORWARD = 1;
    const SUBTYPE_PHONE = 2;

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
     * It is recommended to add custom action types between 100 and 1000
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
    // const ACTION_TYPE_WORKFLOW_MANUAL = 6;
    // const ACTION_TYPE_WORKFLOW_AUTO = 7;
    // The ticket was imported from an external Service
    const ACTION_TYPE_IMPORTED_EXTERNAL = 8;
    // Conversation customer changed
    const ACTION_TYPE_CUSTOMER_CHANGED = 9;
    // The ticket was deleted
    const ACTION_TYPE_DELETED_TICKET = 10;
    // The ticket was restored
    const ACTION_TYPE_RESTORE_TICKET = 11;

    // Describes an optional action associated with the line item
    public static $action_types = [
        self::ACTION_TYPE_STATUS_CHANGED          => 'changed-ticket-status',
        self::ACTION_TYPE_USER_CHANGED            => 'changed-ticket-assignee',
        self::ACTION_TYPE_MOVED_FROM_MAILBOX      => 'moved-from-mailbox',
        self::ACTION_TYPE_MERGED                  => 'merged',
        self::ACTION_TYPE_IMPORTED                => 'imported',
        // self::ACTION_TYPE_WORKFLOW_MANUAL         => 'manual-workflow',
        // self::ACTION_TYPE_WORKFLOW_AUTO           => 'automatic-workflow',
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

    // Metas.
    const META_CONVERSATION_HISTORY = 'ch';
    const META_PREV_CONVERSATION = 'pc';
    const META_MERGED_WITH_CONV = 'mwc';
    const META_MERGED_INTO_CONV = 'mic';
    const META_FORWARD_PARENT_CONVERSATION_NUMBER = 'fw_pcn';
    const META_FORWARD_PARENT_CONVERSATION_ID = 'fw_pci';
    const META_FORWARD_PARENT_THREAD_ID = 'fw_pti';
    const META_FORWARD_CHILD_CONVERSATION_NUMBER = 'fw_ccn';
    const META_FORWARD_CHILD_CONVERSATION_ID = 'fw_cci';

    // At some stage metas have been renamed.
    public static $meta_fw_backward_compat = [
        self::META_FORWARD_PARENT_CONVERSATION_NUMBER => 'forward_parent_conversation_number',
        self::META_FORWARD_PARENT_CONVERSATION_ID => 'forward_parent_conversation_id',
        self::META_FORWARD_PARENT_THREAD_ID => 'forward_parent_thread_id',
        self::META_FORWARD_CHILD_CONVERSATION_NUMBER => 'forward_child_conversation_number',
        self::META_FORWARD_CHILD_CONVERSATION_ID => 'forward_child_conversation_id',
    ];

    protected $dates = [
        'opened_at',
        'created_at',
        'updated_at',
        'deleted_at',
        'edited_at',
    ];

    protected $casts = [
        'meta' => 'array',
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
     * Get user who edited thread.
     */
    public function edited_by_user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get user who edited thread (cached).
     */
    public function edited_by_user_cached()
    {
        return $this->edited_by_user()->rememberForever();
    }

    /**
     * Get sanitized body HTML.
     *
     * @return string
     */
    public function getCleanBody($body = '')
    {
        if (!$body) {
            $body = $this->body;
        }

        // Change "background:" to "background-color:".
        // https://github.com/freescout-helpdesk/freescout/issues/2560
        $body = preg_replace("/(<[^<>\r\n]+style=[\"'][^\"']*)background: *([^;() ]+;)/", '$1background-color:$2', $body);
        
        return \Helper::purifyHtml($body);
    }

    /**
     * Convert body to plain text.
     */
    public function getBodyAsText($options = ['width' => 0])
    {
        return \Helper::htmlToText($this->body, true, $options);
    }

    public function getBodyWithFormatedLinks(string $body = '') :string
    {
        if (!$body) {
            $body = $this->body;
        }

        return \Helper::linkify($this->getCleanBody($body));
    }

    /**
     * Get sanitized body HTML.
     *
     * @return string
     */
    public function getCleanBodyOriginal()
    {
        return $this->getCleanBody($this->body_original);
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
            $this->to = \Helper::jsonEncodeUtf8($emails_array);
        } else {
            $this->to = null;
        }
    }

    public function setCc($emails)
    {
        $emails_array = Conversation::sanitizeEmails($emails);
        if ($emails_array) {
            $emails_array = array_unique($emails_array);
            $this->cc = \Helper::jsonEncodeUtf8($emails_array);
        } else {
            $this->cc = null;
        }
    }

    public function setBcc($emails)
    {
        $emails_array = Conversation::sanitizeEmails($emails);
        if ($emails_array) {
            $emails_array = array_unique($emails_array);
            $this->bcc = \Helper::jsonEncodeUtf8($emails_array);
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
     * Get action's person.
     */
    public function getActionPerson($conversation_number = '')
    {
        $person = '';

        if ($this->type == self::TYPE_CUSTOMER) {
            if ($this->customer_cached) {
                $person = $this->customer_cached->getFullName(true);
            }
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

        // https://github.com/tormjens/eventy/issues/19
        $person = \Eventy::filter('thread.action_person', $person, $this, $conversation_number);

        return $person;
    }

    /**
     * Get action text.
     * $by_user - user who performed the action.
     * $person must be already escaped.
     */
    public function getActionText($conversation_number = '', $escape = false, $strip_tags = false, $by_user = null, $person = '', $viewed_by_user = null)
    {
        $did_this = '';

        // Did this
        if ($this->type == self::TYPE_LINEITEM) {

            if ($this->action_type == self::ACTION_TYPE_STATUS_CHANGED) {
                if ($conversation_number) {
                    $did_this = __(':person marked as :status_name conversation #:conversation_number', ['status_name' => $this->getStatusName(), 'conversation_number' => $conversation_number]);
                } else {
                    $did_this = __(":person marked as :status_name", ['status_name' => $this->getStatusName()]);
                }
            } elseif ($this->action_type == self::ACTION_TYPE_USER_CHANGED) {
                $assignee = $this->getAssigneeName(false, $by_user);
                if ($escape) {
                    $assignee = htmlspecialchars($assignee);
                }
                if ($conversation_number) {
                    $did_this = __(':person assigned :assignee conversation #:conversation_number', ['assignee' => $assignee, 'conversation_number' => $conversation_number]);
                } else {
                    $did_this = __(":person assigned to :assignee", ['assignee' => $assignee]);
                }
            } elseif ($this->action_type == self::ACTION_TYPE_CUSTOMER_CHANGED) {
                if ($conversation_number) {
                    $did_this = __(':person changed the customer to :customer in conversation #:conversation_number', ['customer' => $this->customer->getFullName(true), 'conversation_number' => $conversation_number]);
                } else {
                    $customer_name = '';
                    if ($this->customer_cached) {
                        $customer_name = $this->customer_cached->getFullName(true);
                    }
                    if ($escape) {
                        $customer_name = htmlspecialchars($customer_name);
                    }
                    $did_this = __(":person changed the customer to :customer", ['customer' => '<a href="'.($this->customer_cached ? $this->customer_cached->url() : '').'" title="'.$this->action_data.'" class="link-black">'.$customer_name.'</a>']);
                }
            } elseif ($this->action_type == self::ACTION_TYPE_DELETED_TICKET) {
                $did_this = __(":person deleted");
            } elseif ($this->action_type == self::ACTION_TYPE_RESTORE_TICKET) {
                $did_this = __(":person restored");
            } elseif ($this->action_type == self::ACTION_TYPE_MOVED_FROM_MAILBOX) {
                $did_this = __(":person moved conversation from another mailbox");
            } elseif ($this->action_type == self::ACTION_TYPE_MERGED) {
                if (!empty($this->getMeta(Thread::META_MERGED_WITH_CONV))) {
                    $did_this = __(":person merged with another conversation");
                } else {
                    $merge_conversation = Conversation::find($this->getMeta(Thread::META_MERGED_INTO_CONV));
                    $merge_conversation_number = '';
                    if ($merge_conversation) {
                        $merge_conversation_number = $merge_conversation->number;
                    }
                    if ($merge_conversation) {
                        $did_this = __(":person merged into conversation #:conversation_number", ['conversation_number' => '<a href="'.$merge_conversation->url().'" class="link-black">'.$merge_conversation_number.'</a>']);
                    } else {
                        $did_this = __(":person merged into conversation #:conversation_number", ['conversation_number' => $merge_conversation_number]);
                    }
                }
            }
        } elseif ($this->state == self::STATE_DRAFT) {
            if (empty($this->edited_by_user_id)) {
                $did_this = __(':person created a draft');
            } else {
                $did_this = __(":person edited :creator's draft", ['creator' => $this->created_by_user_cached->getFirstName()]);
            }
        } else {
            if ($this->isForwarded()) {
                $did_this = __(':person forwarded a conversation #:forward_parent_conversation_number', ['forward_parent_conversation_number' => $this->getMetaFw(self::META_FORWARD_PARENT_CONVERSATION_NUMBER)]);
            } elseif ($this->first) {
                $did_this = __(':person started a new conversation #:conversation_number', ['conversation_number' => $conversation_number]);
            } elseif ($this->type == self::TYPE_NOTE) {
                $did_this = __(':person added a note to conversation #:conversation_number', ['conversation_number' => $conversation_number]);
            } else {
                $did_this = __(':person replied to conversation #:conversation_number', ['conversation_number' => $conversation_number]);
            }
        }

        $did_this = \Eventy::filter('thread.action_text', $did_this, $this, $conversation_number, $escape, $viewed_by_user);

        if ($strip_tags) {
            $did_this = strip_tags($did_this);
        }

        if ($person) {
            // This causes double escaping.
            // if ($escape) {
            //     $person = htmlspecialchars($person);
            // }
            $did_this = str_replace(':person', $person, $did_this);
        }

        return $did_this;
    }

    /**
     * Description of what happened.
     */
    public function getActionDescription($conversation_number, $escape = true, $viewed_by_user = null)
    {
        // Person
        $person = $this->getActionPerson($conversation_number);
        $did_this = $this->getActionText($conversation_number, false, false, null, '', $viewed_by_user);

        if ($escape) {
            $person = htmlspecialchars($person);
            $did_this = htmlspecialchars($did_this);
        }

        return __($did_this, [
            'person'           => '<strong>'.$person.'</strong>',
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
            if ($this->getCreatedBy()) {
                return $this->getCreatedBy()->getFirstName(true);
            } else {
                return '';
            }
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
            $this->send_status_data = \Helper::jsonEncodeUtf8($send_status_data);
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
        if (!empty($data['action_type'])) {
            $thread->action_type = $data['action_type'];
        }
        if (!empty($data['meta'])) {
            $thread->setMetas($data['meta']);
        }

        if ($save) {
            $thread->save();
        }

        return $thread;
    }

    public static function createExtended($data = [], $conversation = null, $customer = null, $update_conv = true)
    {
        if (empty($data['type']) || empty($data['body'])) {
            return false;
        }

        $is_customer = ($data['type'] == Thread::TYPE_CUSTOMER);

        if (!$customer && !empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
        }
        if (!$customer) {
            $customer = $conversation->customer;
        }

        // User which creatd the thread should be passed in created_by_user_id.
        // user_id is check for backward compatibility.
        $user_id = $data['created_by_user_id'] ?? $data['user_id'] ?? null;

        // Check type.
        if ($data['type'] == Thread::TYPE_CUSTOMER && empty($customer)) {
            return false;
            //return $this->getErrorResponse('`customer` parameter is required', 'customer');
        }
        if (($data['type'] == Thread::TYPE_MESSAGE || $data['type'] == Thread::TYPE_NOTE) && empty($user_id)) {
            return false;
            //return $this->getErrorResponse('`user` parameter is required', 'user');
        }

        // Create thread.
        $now = date('Y-m-d H:i:s');

        // New conversation.
        $new = !$conversation->threads_count;

        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->type = $data['type'];
        if ($is_customer) {
            $thread->source_via = Thread::PERSON_CUSTOMER;
            $thread->created_by_customer_id = $customer->id;
        } else {
            $thread->source_via = Thread::PERSON_USER;
            $thread->created_by_user_id = $user_id;
            $thread->edited_by_user_id = null;
            $thread->edited_at = null;
        }
        $thread->source_type = Thread::SOURCE_TYPE_API;
        $thread->state = $data['state'] ?? Thread::STATE_PUBLISHED;
        $thread->customer_id = $customer->id ?? $conversation->customer_id ?? null;
        $thread->body = $data['body'];
        if (!$is_customer) {
            $thread->setTo([$customer->getMainEmail()]);
        }
        
        $cc = \MailHelper::sanitizeEmails($data['cc'] ?? []);
        $thread->setCc($cc);

        $bcc = \MailHelper::sanitizeEmails($data['bcc'] ?? []);
        $thread->setBcc($bcc);
        $thread->imported = (int)($data['imported'] ?? false);
        if ($thread->imported && !empty($data['created_at'])) {
            $thread->created_at = self::utcStringToServerDate($data['created_at']);
        }
        if ($new) {
            $thread->first = true;
        }

        // Assignee.
        if (empty($data['user_id'])) {
            $thread->user_id = $conversation->user_id;
        }

        // Process attachments.
        if (!empty($data['attachments'])) {
            $has_attachments = false;
            foreach ($data['attachments'] as $attachment) {

                $content = null;
                $uploaded_file = null;

                if (is_object($attachment) && get_class($attachment) == 'Illuminate\Http\UploadedFile') {

                    $uploaded_file = $attachment;
                    $attachment = [];
                    $attachment['file_name'] = $uploaded_file->getClientOriginalName();
                    $attachment['mime_type'] = $uploaded_file->getMimeType();

                } else {

                    if (empty($attachment['file_name'])
                        //|| empty($attachment['mime_type'])
                        || (empty($attachment['data']) && empty($attachment['file_url']))
                    ) {
                        continue;
                    }

                    if (!empty($attachment['data'])) {
                        // BASE64 string.
                        $content = base64_decode($attachment['data']);
                        if (!$content) {
                            continue;
                        }
                        if (empty($attachment['mime_type'])) {
                            $f = finfo_open();
                            $attachment['mime_type'] = finfo_buffer($f, $content, FILEINFO_MIME_TYPE);
                        }
                    } else {
                        // URL.
                        $uploaded_file = \Helper::downloadRemoteFileAsTmpFile($attachment['file_url']);
                        if (!$uploaded_file) {
                            continue;
                        }
                        if (empty($attachment['mime_type'])) {
                            $attachment['mime_type'] = $uploaded_file->getClientMimeType();
                        }
                    }
                }
                if (!$has_attachments) {
                    $thread->save();
                }
                $attachment = Attachment::create(
                    $attachment['file_name'],
                    $attachment['mime_type'],
                    null,
                    $content,
                    $uploaded_file = $uploaded_file,
                    $embedded = false,
                    $thread->id,
                    $user_id ?? null
                );

                if ($attachment) {
                    $has_attachments = true;
                }
            }
            if ($has_attachments) {
                $thread->has_attachments = true;
                $conversation->has_attachments = true;
            }
        } else {
            $has_attachments = $data['has_attachments'] ?? false;
            $thread->has_attachments = $has_attachments;
            $conversation->has_attachments = $has_attachments;
        }
        
        $thread->save();
        
        if ($new) {
            if ($is_customer) {
                $conversation->source_via = Conversation::PERSON_CUSTOMER;
                $conversation->created_by_customer_id = $customer->id;
            } else {
                $conversation->source_via = Conversation::PERSON_USER;
                $conversation->created_by_user_id = $user_id;
            }
        }

        $conversation->setCc($cc);
        // BCC should keep BCC of the first email,
        // so we change BCC only if it contains emails.
        if ($bcc) {
            $conversation->setBcc($bcc);
        }

        $update_folder = false;

        if ($thread->isReply()) {
            $conversation->last_reply_at = $now;
            if ($is_customer) {
                $conversation->last_reply_from = Conversation::PERSON_CUSTOMER;

                // Set specific status
                if (!empty($data['status'])) {
                    if ((int)$conversation->status != (int)$data['status']) {
                        $update_folder = true;
                    }
                    $conversation->status = $data['status'];
                } else {
                    if ((int)$conversation->status != Conversation::STATUS_ACTIVE) {
                        $update_folder = true;
                    }
                    // Reply from customer makes conversation active
                    $conversation->status = Conversation::STATUS_ACTIVE;
                }
            } else {
                $conversation->last_reply_from = Conversation::PERSON_USER;
                $conversation->user_updated_at = $now;
                
                if (!empty($data['status'])) {
                    if ((int)$conversation->status != (int)$data['status']) {
                        $update_folder = true;
                    }
                    $conversation->status = $data['status'];
                } else {
                    if ((int)$conversation->status != Conversation::STATUS_PENDING) {
                        $update_folder = true;
                    }
                    // Reply from customer makes conversation active
                    $conversation->status = Conversation::STATUS_PENDING;
                }
            }
        }
        
        // Reply from customer to deleted conversation should undelete it.
        if ($data['type'] == Thread::TYPE_CUSTOMER && $conversation->state == Conversation::STATE_DELETED) {
            $conversation->state = Conversation::STATE_PUBLISHED;
            $update_folder = true;
        }

        if ($update_conv) {
            $conversation->customer_id = $customer->id;

            if ($is_customer) {
                $conversation->customer_email = $customer->getMainEmail();
            }

            if ($update_folder) {
                $conversation->updateFolder();
            }
        }

        // Update conversation here if needed.
        if ($is_customer) {
            if ($new) {
                $conversation = \Eventy::filter('conversation.created_by_customer', $conversation, $thread, $customer);
            } else {
                $conversation = \Eventy::filter('conversation.customer_replied', $conversation, $thread, $customer);
            }
        }
        // save() will check if something in the model has changed. If it hasn't it won't run a db query.
        $conversation->save();

        // Update folders counters
        if (!$new) {
            // Update folders counters
            $conversation->mailbox->updateFoldersCounters();
        }

        // Events.

        // Conversation customer changed
        // Not used anywhere
        // if ($prev_customer_id) {
        //     event(new ConversationCustomerChanged($conversation, $prev_customer_id, $prev_customer_email, null, $customer));
        // }
    
        if ($new) {
            if ($is_customer) {
                event(new CustomerCreatedConversation($conversation, $thread));
                \Eventy::action('conversation.created_by_customer', $conversation, $thread, $customer);
            } else {
                // New conversation.
                event(new UserCreatedConversation($conversation, $thread));
                \Eventy::action('conversation.created_by_user_can_undo', $conversation, $thread);
                // After Conversation::UNDO_TIMOUT period trigger final event.
                \Helper::backgroundAction('conversation.created_by_user', [$conversation, $thread], now()->addSeconds(Conversation::UNDO_TIMOUT));
            }
        } elseif ($data['type'] == Thread::TYPE_NOTE) {
            // Note.
            event(new UserAddedNote($conversation, $thread));
            \Eventy::action('conversation.note_added', $conversation, $thread);
        } else {
            // Reply.
            if ($is_customer) {
                event(new CustomerReplied($conversation, $thread));
                \Eventy::action('conversation.customer_replied', $conversation, $thread, $customer);
            } else {
                event(new UserReplied($conversation, $thread));
                \Eventy::action('conversation.user_replied_can_undo', $conversation, $thread);
                // After Conversation::UNDO_TIMOUT period trigger final event.
                \Helper::backgroundAction('conversation.user_replied', [$conversation, $thread], now()->addSeconds(Conversation::UNDO_TIMOUT));
            }
        }

        return $thread;
    }

    /**
     * Get full name of the user who edited thread.
     */
    public function getEditedByUserName()
    {
        $name = '';

        if (!$this->edited_by_user_id) {
            return '';
        }

        if (auth()->user() && $this->edited_by_user_id == auth()->user()->id) {
            $name = __('you');
        } else {
            $name = $this->edited_by_user_cached->getFullName();
        }

        return $name;
    }

    /**
     * Get thread meta data as array.
     */
    public function getMetas()
    {
        return $this->meta;
        //return \Helper::jsonToArray($this->meta);
    }

    /**
     * Set thread meta value.
     */
    public function setMetas($data)
    {
        $this->meta = $data;
        //$this->meta = \Helper::jsonEncodeUtf8($data);
    }

    /**
     * Get thread meta value.
     */
    public function getMeta($key, $default = null)
    {
        $metas = $this->getMetas();
        if (isset($metas[$key])) {
            return $metas[$key];
        } else {
            return $default;
        }
    }

    /**
     * Set thread meta value.
     */
    public function setMeta($key, $value)
    {
        $metas = $this->getMetas();
        $metas[$key] = $value;
        $this->setMetas($metas);
    }

    public function getMetaFw($key, $default = null)
    {
        $meta = $this->getMeta($key, $default);
        if (!$meta) {
            $meta = $this->getMeta(self::$meta_fw_backward_compat[$key], $default);
        }
        return $meta;
    }

    /**
     * Unset thread meta value.
     */
    public function unsetMeta($key)
    {
        $metas = $this->getMetas();
        if (isset($metas[$key])) {
            unset($metas[$key]);
            $this->setMetas($metas);
        }
    }

    /**
     * Get full name of the user who forwarded conversation.
     */
    public function getForwardByFullName($by_user = null)
    {
        if (!$by_user) {
            $by_user = auth()->user();
        }
        if ($by_user && $this->created_by_user_id == $by_user->id) {
            $name = __('you');
        } else {
            $name = $this->created_by_user->getFullName();
        }

        return $name;
    }

    /**
     * Is this a note informing that conversation has been forwarded.
     */
    public function isForward()
    {
        return ($this->subtype == \App\Thread::SUBTYPE_FORWARD);
    }

    /**
     * Is this a forwarded conversation.
     */
    public function isForwarded()
    {
        if ($this->getMetaFw(self::META_FORWARD_PARENT_CONVERSATION_ID)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is this thread a note.
     */
    public function isNote()
    {
        return ($this->type == \App\Thread::TYPE_NOTE);
    }

    /**
     * Get forwarded conversation.
     */
    public function getForwardParentConversation()
    {
        return Conversation::where('id', $this->getMetaFw(self::META_FORWARD_PARENT_CONVERSATION_ID))
            ->rememberForever()
            ->first();
    }

    /**
     * Get forward child conversation.
     */
    public function getForwardChildConversation()
    {
        return Conversation::where('id', $this->getMetaFw(self::META_FORWARD_CHILD_CONVERSATION_ID))
            ->first();
    }

    /**
     * Fetch body via IMAP.
     */
    public function fetchBody()
    {
        $message = \MailHelper::fetchMessage($this->conversation->mailbox, $this->message_id);

        if (!$message) {
            return '';
        }

        $body = $message->getHTMLBody();

        if (!$body) {
            $body = $message->getTextBody();
        }

        return $body;
    }

    public function getActionTypeName()
    {
        if (!$this->action_type) {
            return '';
        }

        $action_types = \Eventy::filter('thread.action_types', self::$action_types);

        return self::$action_types[$this->action_type] ?? '';
    }

    public function isCustomerMessage()
    {
        return $this->type == self::TYPE_CUSTOMER;
    }

    public function isUserMessage()
    {
        return $this->type == self::TYPE_MESSAGE;
    }
}
