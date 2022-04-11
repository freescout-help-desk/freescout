<?php
/**
 * Outgoing emails.
 */

namespace Modules\SavedReplies\Entities;

use App\Mailbox;
use App\User;
use Illuminate\Database\Eloquent\Model;

class SavedReply extends Model
{
    /**
     * For caching.
     */
    public static $parent_ids = [];

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        self::creating(function (SavedReply $model) {
            $model->sort_order = SavedReply::where('mailbox_id', $model->mailbox_id)->max('sort_order')+1;
        });
    }

    /**
     * Get mailbox.
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox');
    }

    /**
     * Threads created from saved reply.
     */
    public function threads()
    {
        return $this->hasMany('App\Thread');
    }

    public static function userCanUpdateMailboxSavedReplies(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin() || ($user->hasPermission(User::PERM_EDIT_SAVED_REPLIES) && $mailbox->userHasAccess($user->id))) {
            return true;
        } else {
            return false;
        }
    }

    public static function listToTree($list, $parent_saved_reply_id = 0)
    {
        $tree = [];

        if (!$list) {
            return [];
        }

        foreach ($list as $saved_reply) {
            if ($saved_reply->parent_saved_reply_id != (int)$parent_saved_reply_id) {
                continue;
            }
            
            $saved_reply->saved_replies = self::listToTree($list, $saved_reply->id);
            $tree[] = $saved_reply;
        }

        return $tree;
    }

    public function isChild($id, $saved_replies, $list_hash = '')
    {
        $is_child = false;

        if (!empty($list_hash)) {
            if (!isset(self::$parent_ids[$list_hash])) {
                self::$parent_ids[$list_hash] = array_column($saved_replies, 'parent_saved_reply_id');
            }
            if (!in_array($this->id, self::$parent_ids[$list_hash])) {
                return false;
            }
        }

        foreach ($saved_replies as $saved_reply) {
            if ($saved_reply->parent_saved_reply_id == $this->id) {
                if ($saved_reply->id == $id) {
                    return true;
                } else {
                    $is_child = $saved_reply->isChild($id, $saved_replies, $list_hash);
                    if ($is_child) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function savedRepliesListHash($list)
    {
        if (empty($list)) {
            return '';
        }
        return md5(json_encode(array_column($list, 'id')));
    }
}
