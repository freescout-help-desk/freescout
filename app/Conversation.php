<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
	/**
	 * By whom action performed (used in fields: source_via, last_reply_from)
	 */
    const PERSON_CUSTOMER = 1;
    const PERSON_USER = 2;
    
    public static $persons = array(
        self::PERSON_CUSTOMER => 'customer',
        self::PERSON_USER => 'user',
    );

    /**
     * Max length of the preview
     */
    const PREVIEW_MAXLENGTH = 255;

	/**
	 * Conversation types
	 */
    const TYPE_EMAIL = 1;
    const TYPE_PHONE = 2;
    const TYPE_CHAT = 3; // not used

    public static $types = array(
    	self::TYPE_EMAIL => 'email',
    	self::TYPE_PHONE => 'phone',
    	self::TYPE_CHAT => 'chat',
    );

    /**
     * Conversation statuses
     */
    const STATUS_ACTIVE = 1;
    const STATUS_PENDING = 2;
    const STATUS_CLOSED = 3;
    const STATUS_SPAM = 4;
    // Present in the API, but what does it mean?
    const STATUS_OPEN = 5;

    public static $statuses = array(
    	self::STATUS_ACTIVE => 'active',
        self::STATUS_PENDING => 'pending',
    	self::STATUS_CLOSED => 'closed',
    	self::STATUS_SPAM => 'spam',
        //self::STATUS_OPEN => 'open',
    );

    /**
     * https://glyphicons.bootstrapcheatsheets.com/
     */
    public static $status_icons = array(
        self::STATUS_ACTIVE => 'ok',
        self::STATUS_PENDING => 'hourglass',
        self::STATUS_CLOSED => 'lock',
        self::STATUS_SPAM => 'ban-circle',
        //self::STATUS_OPEN => 'folder-open',
    );

    public static $status_colors = array(
        self::STATUS_ACTIVE => 'success',
        self::STATUS_PENDING => 'warning',
        self::STATUS_CLOSED => 'grey',
        self::STATUS_SPAM => 'danger',
        //self::STATUS_OPEN => 'folder-open',
    );

    /**
     * Conversation states
     */
    const STATE_DRAFT = 1;
    const STATE_PUBLISHED = 2;
    const STATE_DELETED = 3;
  
    public static $states = array(
    	self::STATE_DRAFT => 'draft',
    	self::STATE_PUBLISHED => 'published',
    	self::STATE_DELETED => 'deleted',
    );

	/**
     * Source types (equal to thread source types)
     */
    const SOURCE_TYPE_EMAIL = 1;
    const SOURCE_TYPE_WEB = 2;
    const SOURCE_TYPE_API = 3;
  
    public static $source_types = array(
    	self::SOURCE_TYPE_EMAIL => 'email',
    	self::SOURCE_TYPE_WEB => 'web',
    	self::SOURCE_TYPE_API => 'api',
    );

    /**
     * Automatically converted into Carbon dates.
     */
    protected $dates = ['created_at', 'updated_at', 'last_reply_at'];

    /**
     * Attributes which are not fillable using fill() method
     */
    protected $guarded = ['id', 'folder_id'];

    protected static function boot()
    {
        parent::boot();

        self::creating(function (Conversation $model)
        {
            $model->number = Conversation::where('mailbox_id', $model->mailbox_id)->max('number')+1;
        });
    }

    /**
     * Who the conversation is assigned to (assignee)
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the folder to which conversation belongs
     */
    public function folder()
    {
        return $this->belongsTo('App\Folder');
    }

    /**
     * Get the mailbox to which conversation belongs
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox');
    }

    /**
     * Get the customer associated with this conversation (primaryCustomer)
     */
    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Get conversation threads
     */
    public function threads()
    {
        return $this->hasMany('App\Thread');
    }

    /**
     * Folders containing starred conversations
     */
    public function extraFolders()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Set preview text
     * 
     * @param string $text
     */
    public function setPreview($text = '')
    {
        $this->preview = '';

        if ($text) {
            $this->preview = mb_substr($text, 0, self::PREVIEW_MAXLENGTH);
        } else {
            $first_thread = $this->threads()->first();
            if ($first_thread) {
                $this->preview = mb_substr($first_thread->body, 0, self::PREVIEW_MAXLENGTH);
            }
        }
        return $this->preview;
    }

    /**
     * Get conversation timestamp title.
     * 
     * @return string
     */
    public function getDateTitle()
    {
        if ($this->threads_count == 1) {
            $title = __("Created by :person<br/>:date", ['person' => ucfirst(__(
            self::$persons[$this->source_via])), 'date' => User::dateFormat($this->created_at, 'M j, Y H:i')]);
        } else {
            $title = __("Last reply by :person<br/>:date", ['person' => ucfirst(__(self::$persons[$this->source_via])), 'date' => User::dateFormat($this->created_at, 'M j, Y H:i')]);
        }
        return $title;
    }

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * Get status name.
     * 
     * @param  integer $status
     * @return string        
     */
    public static function getStatusName($status)
    {
        switch ($status) {
            case self::STATUS_ACTIVE:
                return __("Active");
                break;

            case self::STATUS_PENDING:
                return __("Pending");
                break;

            case self::STATUS_CLOSED:
                return __("Closed");
                break;

            case self::STATUS_SPAM:
                return __("Spam");
                break;

            case self::STATUS_OPEN:
                return __("Open");
                break;

            default:
                return '';
                break;
        }
    }
}
