<?php
/**
 * User model class.
 * Class also responsible for dates conversion and representation.
 */

namespace App;

use App\Mail\UserInvite;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * Roles.
     */
    const ROLE_USER = 1;
    const ROLE_ADMIN = 2;

    public static $roles = [
        self::ROLE_ADMIN => 'admin',
        self::ROLE_USER  => 'user',
    ];

    /**
     * Types.
     */
    const TYPE_USER = 1;
    const TYPE_TEAM = 2;

    /**
     * Invite states.
     */
    const INVITE_STATE_ACTIVATED = 1;
    const INVITE_STATE_SENT = 2;
    const INVITE_STATE_NOT_INVITED = 3;

    /**
     * Time formats.
     */
    const TIME_FORMAT_12 = 1;
    const TIME_FORMAT_24 = 2;

    /**
     * Global user permissions.
     */
    const USER_PERM_DELETE_CONVERSATIONS = 1;
    const USER_PERM_EDIT_CONVERSATIONS = 2;
    const USER_PERM_EDIT_SAVED_REPLIES = 3;
    const USER_PERM_EDIT_TAGS = 4;

    public static $user_permissions = [
        self::USER_PERM_DELETE_CONVERSATIONS,
        self::USER_PERM_EDIT_CONVERSATIONS,
        self::USER_PERM_EDIT_SAVED_REPLIES,
        self::USER_PERM_EDIT_TAGS,
    ];

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['role'];

    /**
     * The attributes that should be hidden for arrays, excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Attributes fillable using fill() method.
     *
     * @var [type]
     */
    protected $fillable = ['role', 'first_name', 'last_name', 'email', 'password', 'role', 'timezone', 'photo_url', 'type', 'emails', 'job_title', 'phone', 'time_format', 'enable_kb_shortcuts'];

    /**
     * For array_unique function.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->id.'';
    }

    /**
     * Get mailboxes to which usre has access.
     */
    public function mailboxes()
    {
        return $this->belongsToMany('App\Mailbox')->as('settings')->withPivot('after_send');
    }

    /**
     * Get conversations assigned to user.
     */
    public function conversations()
    {
        return $this->hasMany('App\Conversation');
    }

    /**
     * User's folders.
     */
    public function folders()
    {
        return $this->hasMany('App\Folder');
    }

    /**
     * User's subscriptions.
     */
    public function subscriptions()
    {
        return $this->hasMany('App\Subscription');
    }

    /**
     * Get user role.
     *
     * @return string
     */
    public function getRoleName($ucfirst = false)
    {
        $role_name = self::$roles[$this->role];
        if ($ucfirst) {
            $role_name = ucfirst($role_name);
        }

        return $role_name;
    }

    /**
     * Check if user is admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role == self::ROLE_ADMIN;
    }

    /**
     * Get user full name.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get user first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Get mailboxes to which user has access.
     */
    public function mailboxesCanView()
    {
        if ($this->isAdmin()) {
            return Mailbox::all();
        } else {
            return $this->mailboxes;
        }
    }

    /**
     * Get IDs of mailboxes to which user has access.
     */
    public function mailboxesIdsCanView()
    {
        if ($this->isAdmin()) {
            return Mailbox::pluck('id')->toArray();
        } else {
            return $this->mailboxes()->pluck('mailboxes.id')->toArray();
        }
    }

    /**
     * Generate random password for the user.
     *
     * @param int $length
     *
     * @return string
     */
    public function generatePassword($length = 8)
    {
        $this->password = Hash::make(str_random($length));

        return $this->password;
    }

    /**
     * Get URL for editing user.
     *
     * @return string
     */
    public function url()
    {
        return route('users.profile', ['id'=>$this->id]);
    }

    /**
     * Get URL for settings up an account from invitation.
     *
     * @return string
     */
    public function urlSetup()
    {
        return route('user_setup', ['hash' => $this->invite_hash]);
    }

    /**
     * Create personal folders for user mailboxes.
     *
     * @param int   $mailbox_id
     * @param mixed $users
     */
    public function syncPersonalFolders($mailboxes)
    {
        if ($this->isAdmin()) {
            // For admin we get all mailboxes
            $mailbox_ids = Mailbox::pluck('mailboxes.id');
        } else {
            if (is_array($mailboxes)) {
                $mailbox_ids = $mailboxes;
            } else {
                $mailbox_ids = $this->mailboxes()->pluck('mailboxes.id');
            }
        }

        $cur_mailboxes = Folder::select('mailbox_id')
            ->where('user_id', $this->id)
            ->whereIn('mailbox_id', $mailbox_ids)
            ->groupBy('mailbox_id')
            ->pluck('mailbox_id')
            ->toArray();

        foreach ($mailbox_ids as $mailbox_id) {
            if (in_array($mailbox_id, $cur_mailboxes)) {
                continue;
            }
            foreach (Folder::$personal_types as $type) {
                $folder = new Folder();
                $folder->mailbox_id = $mailbox_id;
                $folder->user_id = $this->id;
                $folder->type = $type;
                $folder->save();
            }
        }
    }

    /**
     * Format date according to user's timezone and time format.
     *
     * @param Carbon $date
     * @param string $format
     *
     * @return string
     */
    public static function dateFormat($date, $format = 'M j, Y H:i', $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        if ($user) {
            if ($user->time_format == self::TIME_FORMAT_12) {
                $format = strtr($format, [
                    'H'     => 'h',
                    'G'     => 'g',
                    ':i'    => ':ia',
                    ':ia:s' => ':i:sa',
                ]);
            } else {
                $format = strtr($format, [
                    'h'     => 'H',
                    'g'     => 'G',
                    ':ia'   => ':i',
                    ':i:sa' => ':i:s',
                ]);
            }
            // todo: formatLocalized has to be used here and below,
            // but it returns $format value instead of formatted date
            return $date->setTimezone($user->timezone)->format($format);
        } else {
            return $date->format($format);
        }
    }

    /**
     * Convert date into human readable format.
     *
     * @param Carbon $date
     *
     * @return string
     */
    public static function dateDiffForHumans($date)
    {
        if (!$date) {
            return '';
        }

        $user = auth()->user();
        if ($user) {
            $date->setTimezone($user->timezone);
        }

        if ($date->diffInSeconds(Carbon::now()) <= 60) {
            return __('Just now');
        } elseif ($date->diffInDays(Carbon::now()) > 7) {
            // Exact date
            if (Carbon::now()->year == $date->year) {
                return self::dateFormat($date, 'M j');
            } else {
                return self::dateFormat($date, 'M j, Y');
            }
        } else {
            $diff_text = $date->diffForHumans();
            $diff_text = preg_replace('/minutes?/', 'min', $diff_text);

            return $diff_text;
        }
    }

    public static function getUserPermissionName($user_permission)
    {
        $user_permission_names = [
            self::USER_PERM_DELETE_CONVERSATIONS => __('Users are allowed to delete notes/conversations'),
            self::USER_PERM_EDIT_CONVERSATIONS => __('Users are allowed to edit notes/threads'),
            self::USER_PERM_EDIT_SAVED_REPLIES => __('Users are allowed to edit/delete saved replies'),
            self::USER_PERM_EDIT_TAGS => __('Users are allowed to manage tags'),
        ];

        if (!empty($user_permission_names[$user_permission])) {
            return $user_permission_names[$user_permission];
        } else {
            return \Event::fire('filter.user_permission_name', [$user_permission]);
        }
    }

    public function getInviteStateName()
    {
        $names = [
            self::INVITE_STATE_ACTIVATED => __('Active'),
            self::INVITE_STATE_SENT => __('Invited'),
            self::INVITE_STATE_NOT_INVITED => __('Not Invited'),
        ];
        if (!isset($names[$this->invite_state])) {
            return $names[self::INVITE_STATE_ACTIVATED];
        } else {
            return $names[$this->invite_state];
        }
    }

    /**
     * Send invitation to this user.
     */
    public function sendInvite()
    {
        // We are using remember_token as a hash for invite
        if (!$this->invite_hash) {
            $this->invite_hash = Str::random(60);
            $this->save();
        }

        \App\Mail\Mail::setSystemMailDriver();

        \Mail::to([['name' => $this->getFullName(), 'email' => $this->email]])
            ->send(new UserInvite($this));

        if (\Mail::failures()) {
            throw new Exception(__("Error occured sending email to :email. Please check logs for more details.", ['email' => $this->email]), 1);
        }
    }
}
