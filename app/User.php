<?php
/**
 * User model class.
 * Class also responsible for dates conversion and representation.
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use Notifiable;

    // const CREATED_AT = 'created_at';
    // const UPDATED_AT = 'modified_at';

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
    const INVITE_STATE_ACTIVATED = 0;
    const INVITE_STATE_NOT_INVITED = 1;
    const INVITE_STATE_SENT = 2;

    /**
     * Time formats.
     */
    const TIME_FORMAT_12 = 1;
    const TIME_FORMAT_24 = 2;

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
     * Get mailboxes to which usre has access.
     */
    public function mailboxes()
    {
        return $this->belongsToMany('App\Mailbox');
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
     * Get mailboxes to which user has access.
     */
    public function mailboxesCanView()
    {
        if ($this->isAdmin()) {
            return Mailbox::all();
        } else {
            $this->mailboxes;
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
    public function urlEdit()
    {
        return route('users.profile', ['id'=>$this->id]);
    }

    /**
     * Create personal folders for user mailboxes.
     *
     * @param int   $mailbox_id
     * @param mixed $users
     */
    public function syncPersonalFolders($mailboxes)
    {
        if (is_array($mailboxes)) {
            $mailbox_ids = $mailboxes;
        } else {
            $mailbox_ids = $this->mailboxes()->pluck('id');
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
    public static function dateFormat($date, $format)
    {
        $user = auth()->user();
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
}
