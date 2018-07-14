<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\MailboxUser;

class Folder extends Model
{
    /**
     * Folders types (ids from HelpScout interface)
     */
    const TYPE_UNASSIGNED = 1;
    // User specific
    const TYPE_MINE = 20;
    const TYPE_DRAFTS = 30;
    const TYPE_ASSIGNED = 40;
    const TYPE_CLOSED = 60;
    const TYPE_SPAM = 80;
    const TYPE_DELETED = 110;
    // User specific
    const TYPE_STARRED = 120;

    public static $types = [
        self::TYPE_UNASSIGNED => 'Unassigned',
        self::TYPE_MINE => 'Mine',
        self::TYPE_DRAFTS => 'Drafts',
        self::TYPE_ASSIGNED => 'Assigned',
        self::TYPE_CLOSED => 'Closed',
        self::TYPE_SPAM => 'Spam',
        self::TYPE_DELETED => 'Deleted',
        self::TYPE_STARRED => 'Starred',
    ];

    // Standard non-user specific mailbox types
    public static $common_types = [
        self::TYPE_UNASSIGNED,
        self::TYPE_DRAFTS,
        self::TYPE_ASSIGNED,
        self::TYPE_CLOSED,
        self::TYPE_SPAM,
        self::TYPE_DELETED,
    ];

    // Folder types which belong to specific user
    public static $personal_types = [
        self::TYPE_MINE,
        self::TYPE_STARRED,
    ];

    public $timestamps = false;

    /**
     * Get the mailbox to which folder belongs
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox');
    }

    /**
     * Get the user to which folder belongs
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get starred conversations
     */
    public function conversations()
    {
        return $this->belongsToMany('App\Conversation');
    }

    public function getTypeName()
    {
        return __(self::$types[$this->type]);
    }
}
