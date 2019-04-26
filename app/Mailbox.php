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
     * Outgoing method. Must be listed in getMailDriverName.
     */
    const OUT_METHOD_PHP_MAIL = 1;
    const OUT_METHOD_SENDMAIL = 2;
    const OUT_METHOD_SMTP = 3;
    //const OUT_METHOD_GMAIL = 3; // todo
    // todo: mailgun, sendgrid, mandrill, etc

    /**
     * Outgoing encryption.
     */
    const OUT_ENCRYPTION_NONE = 1;
    const OUT_ENCRYPTION_SSL = 2;
    const OUT_ENCRYPTION_TLS = 3;

    public static $out_encryptions = [
        self::OUT_ENCRYPTION_NONE => '',
        self::OUT_ENCRYPTION_SSL  => 'ssl',
        self::OUT_ENCRYPTION_TLS  => 'tls',
    ];

    /**
     * Incoming protocol.
     */
    const IN_PROTOCOL_IMAP = 1;
    const IN_PROTOCOL_POP3 = 2;

    public static $in_protocols = [
        self::IN_PROTOCOL_IMAP => 'imap',
        self::IN_PROTOCOL_POP3 => 'pop3',
    ];

    /**
     * Incoming encryption.
     */
    const IN_ENCRYPTION_NONE = 1;
    const IN_ENCRYPTION_SSL = 2;
    const IN_ENCRYPTION_TLS = 3;

    public static $in_encryptions = [
        self::IN_ENCRYPTION_NONE => '',
        self::IN_ENCRYPTION_SSL  => 'ssl',
        self::IN_ENCRYPTION_TLS  => 'tls',
    ];

    /**
     * Ratings Playcement: place ratings text above/below signature.
     */
    const RATINGS_PLACEMENT_ABOVE = 1;
    const RATINGS_PLACEMENT_BELOW = 2;

    /**
     * Default signature set when mailbox created.
     */
    const DEFAULT_SIGNATURE = '<br><span style="color:#808080;">--<br>
{%mailbox.name%}<br></span>';

    /**
     * Default values.
     */
    protected $attributes = [
        'signature' => self::DEFAULT_SIGNATURE,
    ];

    /**
     * Attributes fillable using fill() method.
     *
     * @var [type]
     */
    protected $fillable = ['name', 'slug', 'email', 'aliases', 'from_name', 'from_name_custom', 'ticket_status', 'ticket_assignee', 'template', 'signature', 'out_method', 'out_server', 'out_username', 'out_password', 'out_port', 'out_encryption', 'in_server', 'in_port', 'in_username', 'in_password', 'in_protocol', 'in_encryption', 'auto_reply_enabled', 'auto_reply_subject', 'auto_reply_message', 'office_hours_enabled', 'ratings', 'ratings_placement', 'ratings_text'];

    protected static function boot()
    {
        parent::boot();

        self::created(function (Mailbox $model) {
            $model->slug = strtolower(substr(md5(Hash::make($model->id)), 0, 16));
        });
    }

    /**
     * Automatically encrypt password on save.
     */
    public function setInPasswordAttribute($value)
    {
        $this->attributes['in_password'] = encrypt($value);
    }

    /**
     * Automatically decrypt password on read.
     */
    public function getInPasswordAttribute($value)
    {
        if (!$value) {
            return '';
        }

        try {
            return decrypt($value);
        } catch (\Exception $e) {
            // do nothing if decrypt wasn't succefull
            return false;
        }
    }

    /**
     * Get users having access to the mailbox.
     */
    public function users()
    {
        return $this->belongsToMany('App\User')->as('settings')->withPivot('after_send');
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
    public function syncPersonalFolders($users = null)
    {
        if (!empty($users) && is_array($users)) {
            $user_ids = $users;
        } else {
            $user_ids = $this->users()->pluck('users.id')->toArray();
        }

        // Add admins
        $admin_user_ids = User::where('role', User::ROLE_ADMIN)->pluck('id')->toArray();
        $user_ids = array_merge($user_ids, $admin_user_ids);

        self::createUsersFolders($user_ids, $this->id, Folder::$personal_types);
    }

    /**
     * Created folders of specific type for passed users.
     */
    public static function createUsersFolders($user_ids, $mailbox_id, $folder_types)
    {
        $cur_users = Folder::select('user_id')
            ->where('mailbox_id', $mailbox_id)
            ->whereIn('user_id', $user_ids)
            ->groupBy('user_id')
            ->pluck('user_id')
            ->toArray();

        foreach ($user_ids as $user_id) {
            if (in_array($user_id, $cur_users)) {
                continue;
            }
            foreach ($folder_types as $type) {
                $folder = new Folder();
                $folder->mailbox_id = $mailbox_id;
                $folder->user_id = $user_id;
                $folder->type = $type;
                $folder->save();
            }
        }
    }

    public function createPublicFolders()
    {
        foreach (Folder::$public_types as $type) {
            $folder = new Folder();
            $folder->mailbox_id = $this->id;
            $folder->type = $type;
            $folder->save();
        }
    }

    public function createAdminPersonalFolders()
    {
        $user_ids = User::where('role', User::ROLE_ADMIN)->pluck('id')->toArray();
        self::createUsersFolders($user_ids, $this->id, Folder::$personal_types);
    }

    public static function createAdminPersonalFoldersAllMailboxes($user_ids = null)
    {
        if (empty($user_ids)) {
            $user_ids = User::where('role', User::ROLE_ADMIN)->pluck('id')->toArray();
        }
        $mailbox_ids = self::pluck('id');
        foreach ($mailbox_ids as $mailbox_id) {
            self::createUsersFolders($user_ids, $mailbox_id, Folder::$personal_types);
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
                    })
                    ->orWhere(function ($query3) {
                        $query3->where(['type' => Folder::TYPE_STARRED]);
                        $query3->where(['user_id' => auth()->user()->id]);
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
    public function updateFoldersCounters($folder_type = null)
    {
        if (!$folder_type) {
            $folders = $this->folders;
        } else {
            $folders = $this->folders()->where('folders.type', $folder_type)->get();
        }

        foreach ($folders as $folder) {
            $folder->updateCounters();
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
        if ($this->in_protocol && $this->in_server && $this->in_port && $this->in_username && $this->in_password) {
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
    public function usersHavingAccess($cache = false, $fields = 'users.*')
    {
        $admins = User::where('role', User::ROLE_ADMIN)->select($fields)->remember(\App\Misc\Helper::cacheTime($cache))->get();

        $users = $this->users()->select($fields)->get()->merge($admins)->unique();

        // Exclude deleted users (better to do it in PHP).
        foreach ($users as $i => $user) {
            if ($user->isDeleted()) {
                $users->forget($i);
            }
        }

        return $users;
    }

    /**
     * Get users IDs who have access to the mailbox.
     */
    public function userIdsHavingAccess()
    {
        return $this->usersHavingAccess(false, ['users.id', 'users.status'])->pluck('id')->toArray();

        /*$user_ids = $this->users()->pluck('users.id');
        $admin_ids = User::where('role', User::ROLE_ADMIN)->pluck('id');

        return $user_ids->merge($admin_ids)->unique()->toArray();*/
    }

    /**
     * Check if user has access to the mailbox.
     *
     * @return bool
     */
    public function userHasAccess($user_id, $user = null)
    {
        if (!$user) {
            $user = User::find($user_id);
        }
        if ($user && $user->isAdmin()) {
            return true;
        } else {
            return (bool) $this->users()->where('users.id', $user_id)->count();
        }
    }

    /**
     * Get From array for the Mail function.
     *
     * @param App\User $from_user
     *
     * @return array
     */
    public function getMailFrom($from_user = null)
    {
        // Mailbox name by default
        $name = $this->name;

        if ($this->from_name == self::FROM_NAME_CUSTOM && $this->from_name_custom) {
            $name = $this->from_name_custom;
        } elseif ($this->from_name == self::FROM_NAME_USER && $from_user) {
            $name = $from_user->getFullName();
        }

        return ['address' => $this->email, 'name' => $name];
    }

    /**
     * Get corresponding Laravel mail driver name.
     */
    public function getMailDriverName()
    {
        switch ($this->out_method) {
            case self::OUT_METHOD_PHP_MAIL:
                return 'mail';

            case self::OUT_METHOD_SENDMAIL:
                return 'sendmail';

            case self::OUT_METHOD_SMTP:
                return 'smtp';

            default:
                return 'mail';
        }
    }

    /**
     * Get domain part of the mailbox email.
     *
     * @return string
     */
    public function getEmailDomain()
    {
        return explode('@', $this->email)[1];
    }

    /**
     * Get outgoing email encryption protocol.
     *
     * @return string
     */
    public function getOutEncryptionName()
    {
        return self::$out_encryptions[$this->out_encryption];
    }

    /**
     * Get incoming email encryption protocol.
     *
     * @return string
     */
    public function getInEncryptionName()
    {
        return self::$in_encryptions[$this->in_encryption];
    }

    /**
     * Get incoming protocol name.
     *
     * @return string
     */
    public function getInProtocolName()
    {
        return self::$in_protocols[$this->in_protocol];
    }

    /**
     * Get pivot table parameters for the user.
     */
    public function getUserSettings($user_id)
    {
        $mailbox_user = $this->users()->where('users.id', $user_id)->first();
        if ($mailbox_user) {
            return $mailbox_user->settings;
        } else {
            // Admin may have no record in mailbox_user table
            // Create dummy object with default parameters
            $settings = new \StdClass();
            $settings->after_send = MailboxUser::AFTER_SEND_NEXT;

            return $settings;
        }
    }

    /**
     * Get main email and aliases.
     *
     * @return array
     */
    public function getEmails()
    {
        $emails = [$this->email];

        if ($this->aliases) {
            $aliases = explode(',', $this->aliases);
            foreach ($aliases as $alias) {
                $alias = Email::sanitizeEmail($alias);
                if ($alias) {
                    $emails[] = $alias;
                }
            }
        }

        return $emails;
    }

    /**
     * Remove mailbox email and aliases from the list of emails.
     *
     * @param array   $list
     * @param Mailbox $mailbox
     *
     * @return array
     */
    public function removeMailboxEmailsFromList($list)
    {
        if (!is_array($list)) {
            return [];
        }
        $mailbox_emails = $this->getEmails();
        foreach ($list as $i => $email) {
            if (in_array($email, $mailbox_emails)) {
                unset($list[$i]);
            }
        }

        return $list;
    }

    /**
     * Get all active mailboxes.
     *
     * @return [type] [description]
     */
    public static function getActiveMailboxes()
    {
        $active = [];

        // It is more effective to retrive all mailboxes and filter them in PHP.
        $mailboxes = self::all();
        foreach ($mailboxes as $mailbox) {
            if ($mailbox->isActive()) {
                $active[] = $mailbox;
            }
        }

        return $active;
    }

    /**
     * Get mailbox URL.
     *
     * @return [type] [description]
     */
    public function url()
    {
        return route('mailboxes.view', ['id' => $this->id]);
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes [description]
     *
     * @return [type] [description]
     */
    public function fill(array $attributes)
    {
        $this->fillable(array_merge($this->getFillable(), \Eventy::filter('mailbox.fillable_fields', [])));

        return parent::fill($attributes);
    }
}
