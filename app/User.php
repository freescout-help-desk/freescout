<?php
/**
 * User model class.
 * Class also responsible for dates conversion and representation.
 */

namespace App;

use App\Mail\PasswordChanged;
use App\Mail\UserInvite;
use App\Notifications\WebsiteNotification;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Watson\Rememberable\Rememberable;

class User extends Authenticatable
{
    use Notifiable;
    use Rememberable;

    public $rememberCacheDriver = 'array';

    const PHOTO_DIRECTORY = 'users';
    const PHOTO_SIZE = 50; // px
    const PHOTO_QUALITY = 77;

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
    const PERM_DELETE_CONVERSATIONS = 1;
    const PERM_EDIT_CONVERSATIONS = 2;
    const PERM_EDIT_SAVED_REPLIES = 3;
    const PERM_EDIT_TAGS = 4;

    public static $user_permissions = [
        self::PERM_DELETE_CONVERSATIONS,
        self::PERM_EDIT_CONVERSATIONS,
        self::PERM_EDIT_SAVED_REPLIES,
        self::PERM_EDIT_TAGS,
    ];

    const WEBSITE_NOTIFICATIONS_PAGE_SIZE = 25;
    const WEBSITE_NOTIFICATIONS_PAGE_PARAM = 'wp_page';

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
    protected $fillable = ['role', 'first_name', 'last_name', 'email', 'password', 'role', 'timezone', 'photo_url', 'type', 'emails', 'job_title', 'phone', 'time_format', 'enable_kb_shortcuts', 'locale'];

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
    public static function generateRandomPassword($length = 8)
    {
        return str_random($length);
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

    /**
     * Convert date into human readable format with minutes and hours.
     *
     * @param Carbon $date
     *
     * @return string
     */
    public static function dateDiffForHumansWithHours($date)
    {
        $dateForHuman = self::dateDiffForHumans($date);

        if (!$dateForHuman) {
            return '';
        }

        if (stripos($dateForHuman, 'just') === false) {
            return $dateForHuman.' @ '.$date->format('H:i');
        } else {
            return $dateForHuman;
        }
    }

    public static function getUserPermissionName($user_permission)
    {
        $user_permission_names = [
            self::PERM_DELETE_CONVERSATIONS => __('Users are allowed to delete notes/conversations'),
            self::PERM_EDIT_CONVERSATIONS   => __('Users are allowed to edit notes/threads'),
            self::PERM_EDIT_SAVED_REPLIES   => __('Users are allowed to edit/delete saved replies'),
            self::PERM_EDIT_TAGS            => __('Users are allowed to manage tags'),
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
            self::INVITE_STATE_ACTIVATED   => __('Active'),
            self::INVITE_STATE_SENT        => __('Invited'),
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
    public function sendInvite($throw_exceptions = false)
    {
        function saveToSendLog($user, $status)
        {
            SendLog::log(null, null, $user->email, SendLog::MAIL_TYPE_INVITE, $status, null, $user->id);
        }

        if ($this->invite_state == self::INVITE_STATE_ACTIVATED) {
            return false;
        }
        // We are using remember_token as a hash for invite
        if (!$this->invite_hash) {
            $this->invite_hash = Str::random(60);
            $this->save();
        }

        try {
            \App\Misc\Mail::setSystemMailDriver();

            \Mail::to([['name' => $this->getFullName(), 'email' => $this->email]])
                ->send(new UserInvite($this));
        } catch (\Exception $e) {
            // We come here in case SMTP server unavailable for example
            // But Mail does not through an exception if you specify incorrect SMTP details for example
            activity()
                ->causedBy($this)
                ->withProperties([
                    'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
                 ])
                ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
                ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_INVITE);

            saveToSendLog($this, SendLog::STATUS_SEND_ERROR);

            if ($throw_exceptions) {
                throw $e;
            } else {
                return false;
            }
        }

        if (\Mail::failures()) {
            saveToSendLog($this, SendLog::STATUS_SEND_ERROR);

            if ($throw_exceptions) {
                throw new \Exception(__('Error occured sending email to :email. Please check logs for more details.', ['email' => $this->email]), 1);
            } else {
                return false;
            }
        }

        if ($this->invite_state != self::INVITE_STATE_SENT) {
            $this->invite_state = self::INVITE_STATE_SENT;
            $this->save();
        }

        saveToSendLog($this, SendLog::STATUS_ACCEPTED);

        return true;
    }

    /**
     * Send password changed noitfication.
     */
    public function sendPasswordChanged()
    {
        function saveToSendLog($user, $status)
        {
            SendLog::log(null, null, $user->email, SendLog::MAIL_TYPE_PASSWORD_CHANGED, $status, null, $user->id);
        }

        try {
            \App\Misc\Mail::setSystemMailDriver();

            \Mail::to([['name' => $this->getFullName(), 'email' => $this->email]])
                ->send(new PasswordChanged($this));
        } catch (\Exception $e) {
            // We come here in case SMTP server unavailable for example
            // But Mail does not through an exception if you specify incorrect SMTP details for example
            activity()
                ->causedBy($this)
                ->withProperties([
                    'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
                 ])
                ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
                ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_PASSWORD_CHANGED);

            saveToSendLog($this, SendLog::STATUS_SEND_ERROR);

            return false;
        }

        if (\Mail::failures()) {
            saveToSendLog($this, SendLog::STATUS_SEND_ERROR);

            return false;
        }

        saveToSendLog($this, SendLog::STATUS_ACCEPTED);

        return true;
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     *
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        \App\Misc\Mail::setSystemMailDriver();

        $this->notify(new ResetPasswordNotification($token));
    }

    public function getWebsiteNotifications()
    {
        return $this->notifications()->paginate(self::WEBSITE_NOTIFICATIONS_PAGE_SIZE, ['*'], self::WEBSITE_NOTIFICATIONS_PAGE_PARAM, request()->wn_page);
    }

    public function getWebsiteNotificationsInfo($cache = true)
    {
        if ($cache) {
            // Get from cache
            $user = $this;

            return \Cache::rememberForever('user_web_notifications_'.$user->id, function () use ($user) {
                $notifications = $user->getWebsiteNotifications();

                $info = [
                    'data'          => WebsiteNotification::fetchNotificationsData($notifications),
                    'notifications' => $notifications,
                    'unread_count'  => $user->unreadNotifications()->count(),
                ];

                $info['html'] = view('users/partials/web_notifications', ['web_notifications_info_data' => $info['data']])->render();

                return $info;
            });
        } else {
            $notifications = $this->getWebsiteNotifications();

            $info = [
                'data'          => WebsiteNotification::fetchNotificationsData($notifications),
                'notifications' => $notifications,
                'unread_count'  => $this->unreadNotifications()->count(),
            ];

            return $info;
        }
    }

    public function clearWebsiteNotificationsCache()
    {
        \Cache::forget('user_web_notifications_'.$this->id);
    }

    public function getPhotoUrl()
    {
        if (!empty($this->photo_url)) {
            return Storage::url(self::PHOTO_DIRECTORY.DIRECTORY_SEPARATOR.$this->photo_url);
        } else {
            return '/img/default-avatar.png';
        }
    }

    /**
     * Resize and save user photo.
     */
    public function savePhoto($uploaded_file)
    {
        $resized_image = \App\Misc\Helper::resizeImage($uploaded_file->getRealPath(), $uploaded_file->getMimeType(), self::PHOTO_SIZE, self::PHOTO_SIZE);

        if (!$resized_image) {
            return false;
        }

        $file_name = md5(Hash::make($this->id)).'.jpg';
        $dest_path = Storage::path(self::PHOTO_DIRECTORY.DIRECTORY_SEPARATOR.$file_name);

        $dest_dir = pathinfo($dest_path, PATHINFO_DIRNAME);
        if (!file_exists($dest_dir)) {
            \File::makeDirectory($dest_dir, 0755);
        }

        // Remove current photo
        if ($this->photo_url) {
            Storage::delete(self::PHOTO_DIRECTORY.DIRECTORY_SEPARATOR.$this->photo_url);
        }

        imagejpeg($resized_image, $dest_path, self::PHOTO_QUALITY);
        // $photo_url = $request->file('photo_url')->storeAs(
        //     User::PHOTO_DIRECTORY, !Hash::make($user->id).'.jpg'
        // );

        return $file_name;
    }

    /**
     * Remove user photo.
     */
    public function removePhoto()
    {
        if ($this->photo_url) {
            Storage::delete(self::PHOTO_DIRECTORY.DIRECTORY_SEPARATOR.$this->photo_url);
        }
        $this->photo_url = '';
    }

    public function hasPermission($permission)
    {
        $permissions = Option::get('user_permissions');
        if (!empty($permissions) && is_array($permissions) && in_array($permission, $permissions)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Todo: implement super admin role.
     * For now we return just first admin.
     *
     * @return [type] [description]
     */
    public static function getSuperAdmin()
    {
        return self::where('role', self::ROLE_ADMIN)->first();
    }

    /**
     * Create user.
     */
    public static function create($data)
    {
        $user = new self();

        if (empty($data['email']) || empty($data['password'])) {
            return false;
        }

        $user->fill($data);

        $user->password = \Hash::make($data['password']);
        $user->email = Email::sanitizeEmail($data['email']);

        try {
            $user->save();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Check if current user's role is higher than passed.
     */
    public static function checkRole($role)
    {
        $user = auth()->user();
        if ($user) {
            return $user->role >= $role;
        } else {
            return false;
        }
    }

    /**
     * Get dummy user, for example, when real user has been deleted.
     */
    public static function getDeletedUser()
    {
        $user = new self();
        $user->first_name = 'DELETED';
        $user->last_name = 'DELETED';
        $user->email = 'deleted@example.org';

        return $user;
    }

    /**
     * Get user locale.
     * 
     * @return [type] [description]
     */
    public function getLocale()
    {
        if ($this->locale) {
            return $this->locale;
        } else {
            return config('app.locale');
        }
    }
}
