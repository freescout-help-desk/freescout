<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    /**
     * Folders types (ids from HelpScout interface).
     */
    const TYPE_UNASSIGNED = 1;
    // User specific
    const TYPE_MINE = 20;
    // User specific
    const TYPE_STARRED = 25;
    const TYPE_DRAFTS = 30;
    const TYPE_ASSIGNED = 40;
    const TYPE_CLOSED = 60;
    const TYPE_SPAM = 80;
    const TYPE_DELETED = 110;

    public static $types = [
        self::TYPE_UNASSIGNED => 'Unassigned',
        self::TYPE_MINE       => 'Mine',
        self::TYPE_DRAFTS     => 'Drafts',
        self::TYPE_ASSIGNED   => 'Assigned',
        self::TYPE_CLOSED     => 'Closed',
        self::TYPE_SPAM       => 'Spam',
        self::TYPE_DELETED    => 'Deleted',
        self::TYPE_STARRED    => 'Starred',
    ];

    /**
     * https://glyphicons.bootstrapcheatsheets.com/.
     */
    public static $type_icons = [
        self::TYPE_UNASSIGNED => 'folder-open',
        self::TYPE_MINE       => 'hand-right',
        self::TYPE_DRAFTS     => 'duplicate',
        self::TYPE_ASSIGNED   => 'user',
        self::TYPE_CLOSED     => 'lock', // lock
        self::TYPE_SPAM       => 'ban-circle',
        self::TYPE_DELETED    => 'trash',
        self::TYPE_STARRED    => 'star',
    ];

    // Public non-user specific mailbox types
    public static $public_types = [
        self::TYPE_UNASSIGNED,
        self::TYPE_DRAFTS,
        self::TYPE_ASSIGNED,
        self::TYPE_CLOSED,
        self::TYPE_SPAM,
        self::TYPE_DELETED,
    ];

    // Folder types which belong to specific user.
    // These folders has user_id specified.
    public static $personal_types = [
        self::TYPE_MINE,
        self::TYPE_STARRED,
    ];

    // Folder types to which conversations are added via conversation_folder table.
    public static $indirect_types = [
        self::TYPE_DRAFTS,
        self::TYPE_STARRED,
    ];

    public $timestamps = false;

    /**
     * Get the mailbox to which folder belongs.
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox');
    }

    /**
     * Get the user to which folder belongs.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get starred conversations.
     */
    public function conversations()
    {
        return $this->hasMany('App\Conversation');
    }

    public function getTypeName()
    {
        // To make name translatable.
        switch ($this->type) {
            case self::TYPE_UNASSIGNED:
                return __('Unassigned');
            case self::TYPE_MINE:
                return __('Mine');
            default:
                return __(self::$types[$this->type]);
        }
    }

    public function getTypeIcon()
    {
        return self::$type_icons[$this->type];
    }

    /**
     * Get order by array.
     *
     * @return array
     */
    public function getOrderByArray()
    {
        $order_by = [];

        switch ($this->type) {
            case self::TYPE_UNASSIGNED:
            case self::TYPE_MINE:
            case self::TYPE_ASSIGNED:
                $order_by[] = ['status' => 'asc'];
                $order_by[] = ['last_reply_at' => 'desc'];
                break;

            case self::TYPE_STARRED:
                $order_by[] = ['status' => 'asc'];
                $order_by[] = ['last_reply_at' => 'desc'];
                //$order_by = [['conversation_folder.id' => 'desc']];
                break;

            case self::TYPE_DRAFTS:
                $order_by = [['updated_at' => 'desc']];
                break;

            case self::TYPE_CLOSED:
                $order_by = [['closed_at' => 'desc']];

            case self::TYPE_SPAM:
                $order_by = [['last_reply_at' => 'desc']];
                break;

            case self::TYPE_DELETED:
                $order_by = [['user_updated_at' => 'desc']];
                break;
        }

        return $order_by;
    }

    /**
     * Add order by to the query.
     */
    public function queryAddOrderBy($query)
    {
        $order_bys = $this->getOrderByArray();
        foreach ($order_bys as $order_by) {
            foreach ($order_by as $field => $sort_order) {
                $query->orderBy($field, $sort_order);
            }
        }

        return $query;
    }

    /**
     * Is this folder accumulates conversations via conversation_folder table.
     */
    public function isIndirect()
    {
        return in_array($this->type, self::$indirect_types);
    }

    public function updateCounters()
    {
        if ($this->type == self::TYPE_MINE && $this->user_id) {
            $this->active_count = Conversation::where('user_id', $this->user_id)
                ->where('mailbox_id', $this->mailbox_id)
                ->where('status', Conversation::STATUS_ACTIVE)
                ->where('state', Conversation::STATE_PUBLISHED)
                ->count();
            $this->total_count = Conversation::where('user_id', $this->user_id)
                ->where('mailbox_id', $this->mailbox_id)
                ->where('state', Conversation::STATE_PUBLISHED)
                ->count();
        } elseif ($this->type == self::TYPE_STARRED) {
            $this->active_count = count(Conversation::getUserStarredConversationIds($this->mailbox_id, $this->user_id));
            $this->total_count = $this->active_count;
        } elseif ($this->type == self::TYPE_DELETED) {
            $this->active_count = $this->conversations()->where('state', Conversation::STATE_DELETED)
                ->count();
            $this->total_count = $this->active_count;
        } elseif ($this->isIndirect()) {
            // Conversation are connected to folder via conversation_folder table.
            // Drafts.
            $this->active_count = ConversationFolder::where('conversation_folder.folder_id', $this->id)
                ->join('conversations', 'conversations.id', '=', 'conversation_folder.conversation_id')
                //->where('state', Conversation::STATE_PUBLISHED)
                ->count();
            $this->total_count = $this->active_count;
        } else {
            $this->active_count = $this->conversations()
                ->where('state', Conversation::STATE_PUBLISHED)
                ->where('status', Conversation::STATUS_ACTIVE)
                ->count();
            $this->total_count = $this->conversations()
                ->where('state', Conversation::STATE_PUBLISHED)
                ->count();
        }
        $this->save();
    }

    /**
     * Get count to display in folders list.
     * 
     * @param  array  $folders [description]
     * @return [type]          [description]
     */
    public function getCount($folders = [])
    {
        if ($this->type == self::TYPE_STARRED || $this->type == self::TYPE_DRAFTS) {
            return $this->total_count;
        } else {
            return $this->getActiveCount($folders);
        }
    }

    /**
     * Get calculated number of active conversations.
     */
    public function getActiveCount($folders = [])
    {
        $active_count = $this->active_count;
        if ($this->type == self::TYPE_ASSIGNED) {
            if ($folders) {
                $mine_folder = $folders->firstWhere('type', self::TYPE_MINE);
            } else {
                $mine_folder = $this->mailbox->folders()->where('type', self::TYPE_MINE)->first();
            }

            if ($mine_folder) {
                $active_count = $active_count - $mine_folder->active_count;
                if ($active_count < 0) {
                    $active_count = 0;
                }
            }
        }

        return $active_count;
    }

    public function getConversationsQuery()
    {
        if ($this->type == self::TYPE_MINE) {
            // Assigned to user.
            return Conversation::where('user_id', $this->user_id)
                ->where('mailbox_id', $this->mailbox_id);
        } elseif ($this->isIndirect()) {
            // Via intermediate table.
            return Conversation::join('conversation_folder', 'conversations.id', '=', 'conversation_folder.conversation_id')
                    ->where('conversation_folder.folder_id', $this->id);
        } else {
            // All other conversations.
            return $this->conversations();
        }
    }

    /**
     * Works for main folder only for now.
     * 
     * @return [type] [description]
     */
    public function getWaitingSince()
    {
        // Get oldest active conversation.
        $conversation = $this->getConversationsQuery()
            ->where('state', Conversation::STATE_PUBLISHED)
            ->where('status', Conversation::STATUS_ACTIVE)
            ->orderBy($this->getWaitingSinceField(), 'asc')
            ->first();
        if ($conversation) {
            return $conversation->getWaitingSince($this);
        } else {
            return '';
        }
    }

    /**
     * Get conversation field used to detect waiting since time.
     * @return [type] [description]
     */
    public function getWaitingSinceField()
    {
        if ($this->type == \App\Folder::TYPE_CLOSED) {
            return 'closed_at';
        } elseif ($this->type == \App\Folder::TYPE_DRAFTS) {
            return 'updated_at';
        } elseif ($this->type == \App\Folder::TYPE_DELETED) {
            return 'user_updated_at';
        } else {
            return'last_reply_at';
        }
    }
}
