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
     * Default Assignee.
     */
    const TICKET_ASSIGNEE_ANYONE = 1;
    const TICKET_ASSIGNEE_REPLYING_UNASSIGNED = 2;
    const TICKET_ASSIGNEE_REPLYING = 3;

    /**
     * Email Template.
     */
    const TEMPLATE_FANCY = 1;
    const TEMPLATE_PLAIN = 2;

    /**
     * Sending Emails.
     */
    const OUT_METHOD_PHP_MAIL = 1;
    const OUT_METHOD_SENDMAIL = 2;
    const OUT_METHOD_SMTP = 3;
    //const OUT_METHOD_GMAIL = 3; // todo
    // todo: mailgun, sendgrid, mandrill, etc

    /**
     * Secure Connection.
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
     * Attributes fillable using fill() method.
     *
     * @var [type]
     */
    protected $fillable = ['name', 'slug', 'email', 'aliases', 'from_name', 'from_name_custom', 'ticket_status', 'ticket_assignee', 'template', 'signature', 'out_method', 'out_server', 'out_username', 'out_password', 'out_port', 'out_ssl', 'in_server', 'in_port', 'in_username', 'in_password', 'auto_reply_enabled', 'auto_reply_subject', 'auto_reply_message', 'office_hours_enabled', 'ratings', 'ratings_placement', 'ratings_text'];

    protected static function boot()
    {
        parent::boot();

        self::created(function (Mailbox $model) {
            $model->slug = strtolower(substr(md5(Hash::make($model->id)), 0, 16));
        });
    }

    /**
     * Get users having access to the mailbox.
     */
    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    /**
     * Get mailbox conversations.
     */
    public function conversations()
    {
        return $this->hasMany('App\Conversation');
    }

    /**
     * Get mailbox folders.
     */
    public function folders()
    {
        return $this->hasMany('App\Folder');
    }

    /**
     * Create personal folders for users.
     *
     * @param mixed $users
     */
    public function syncPersonalFolders($users)
    {
        if (is_array($users)) {
            $user_ids = $users;
        } else {
            $user_ids = $this->users()->pluck('id')->toArray();
        }

        // Add admins
        $admin_user_ids = User::where('role', User::ROLE_ADMIN)->pluck('id')->toArray();
        $user_ids = array_merge($user_ids, $admin_user_ids);

        $cur_users = Folder::select('user_id')
            ->where('mailbox_id', $this->id)
            ->whereIn('user_id', $user_ids)
            ->groupBy('user_id')
            ->pluck('user_id')
            ->toArray();
        // $new_users = Mailbox::whereDoesntHave('folders', function ($query) {
        //     $query->where('mailbox_id', $this->id);
        //     $query->whereNotIn('user_id', $user_ids);
        // })->get();

        foreach ($user_ids as $user_id) {
            if (in_array($user_id, $cur_users)) {
                continue;
            }
            foreach (Folder::$personal_types as $type) {
                $folder = new Folder();
                $folder->mailbox_id = $this->id;
                $folder->user_id = $user_id;
                $folder->type = $type;
                $folder->save();
            }
        }
    }

    public function createAdminPersonalFolders()
    {
        $user_ids = User::where('role', User::ROLE_ADMIN)->pluck('id')->toArray();

        $cur_users = Folder::select('user_id')
            ->where('mailbox_id', $this->id)
            ->whereIn('user_id', $user_ids)
            ->groupBy('user_id')
            ->pluck('user_id')
            ->toArray();

        foreach ($user_ids as $user_id) {
            if (in_array($user_id, $cur_users)) {
                continue;
            }
            foreach (Folder::$personal_types as $type) {
                $folder = new Folder();
                $folder->mailbox_id = $this->id;
                $folder->user_id = $user_id;
                $folder->type = $type;
                $folder->save();
            }
        }
    }

    /**
     * Get folders for the dashboard.
     */
    public function getMainFolders()
    {
        return $this->folders()
            ->where(function ($query) {
                $query->whereIn('type', [Folder::TYPE_UNASSIGNED, Folder::TYPE_ASSIGNED, Folder::TYPE_DRAFTS])
                    ->orWhere(function ($query2) {
                        $query2->where(['type' => Folder::TYPE_MINE]);
                        $query2->where(['user_id' => auth()->user()->id]);
                    });
            })
            ->orderBy('type')
            ->get();
    }

    /**
     * Get folders available for the current user.
     */
    public function getAssesibleFolders()
    {
        return $this->folders()
            ->where(function ($query) {
                $query->whereIn('type', Folder::$public_types)
                    ->orWhere(function ($query2) {
                        $query2->whereIn('type', Folder::$personal_types);
                        $query2->where(['user_id' => auth()->user()->id]);
                    });
            })
            ->orderBy('type')
            ->get();
    }

    /**
     * Update total and active counters for folders.
     */
    public function updateFoldersCounters()
    {
        $folders = $this->folders;
        foreach ($folders as $folder) {
            // todo: starred conversations counting
            if ($folder->type == Folder::TYPE_MINE && $folder->user_id) {
                $folder->active_count = Conversation::where('status', Conversation::STATUS_ACTIVE)
                    ->where('user_id', $folder->user_id)
                    ->count();
                $folder->total_count = Conversation::where('user_id', $folder->user_id)
                    ->count();
            } else {
                $folder->active_count = $folder->conversations()
                    ->where('status', Conversation::STATUS_ACTIVE)
                    ->count();
                $folder->total_count = $folder->conversations()->count();
            }
            $folder->save();
        }
    }

    /**
     * Is mailbox available for using.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->isInActive() && $this->isOutActive();
    }

    /**
     * Is receiving emails configured for the mailbox.
     *
     * @return bool
     */
    public function isInActive()
    {
        if ($this->in_server && $this->in_port && $this->in_username && $this->in_password) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is sending emails configured for the mailbox.
     *
     * @return bool
     */
    public function isOutActive()
    {
        if ($this->out_method != self::OUT_METHOD_PHP_MAIL && $this->out_method != self::OUT_METHOD_SENDMAIL
            && (!$this->out_server || !$this->out_username || !$this->out_password)
        ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get users who have access to the mailbox.
     */
    public function usersHavingAccess()
    {
        $users = $this->users;
        $admins = User::where('role', User::ROLE_ADMIN)->get();

        return $users->merge($admins)->unique();
    }

    /**
     * Get users IDs who have access to the mailbox.
     */
    public function userIdsHavingAccess()
    {
        $user_ids = $this->users()->pluck('users.id');
        $admin_ids = User::where('role', User::ROLE_ADMIN)->pluck('id');

        return $user_ids->merge($admin_ids)->unique()->toArray();
    }

    /**
     * Check if user has access to the mailbox.
     *
     * @return bool
     */
    public function userHasAccess($user_id)
    {
        $user = User::find($user_id);
        if ($user && $user->isAdmin()) {
            return true;
        } else {
            return (bool) $this->users()->where('users.id', $user_id)->count();
        }
    }
}
