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

    const EMAIL_MAX_LENGTH = 100;

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
     * Statuses.
     */
    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 2;
    const STATUS_DELETED = 3;

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
    const PERM_EDIT_CONVERSATIONS   = 2;
    const PERM_EDIT_SAVED_REPLIES   = 3;
    const PERM_EDIT_TAGS            = 4;
    const PERM_EDIT_CUSTOM_FOLDERS  = 5;
    const PERM_EDIT_USERS           = 10;

    public static $user_permissions = [
        self::PERM_DELETE_CONVERSATIONS,
        self::PERM_EDIT_CONVERSATIONS,
        self::PERM_EDIT_SAVED_REPLIES,
        self::PERM_EDIT_TAGS,
        self::PERM_EDIT_CUSTOM_FOLDERS,
        self::PERM_EDIT_USERS,
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
    protected $fillable = ['role', 'status', 'first_name', 'last_name', 'email', 'password', 'role', 'timezone', 'photo_url', 'type', 'emails', 'job_title', 'phone', 'time_format', 'enable_kb_shortcuts', 'locale'];

    protected $casts = [
        'permissions' => 'array',
    ];
    
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
        return $this->belongsToMany('App\Mailbox');
    }

    /**
     * Cached mailboxes.
     */
    public function mailboxes_cached()
    {
        return $this->mailboxes()->rememberForever();
    }

    public function mailboxesWithSettings()
    {
        return $this->belongsToMany('App\Mailbox')->as('settings')
            ->withPivot('after_send')
            ->withPivot('hide')
            ->withPivot('mute')
            ->withPivot('access');
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
    public function mailboxesCanView($cache = false)
    {
        if ($this->isAdmin()) {
            if ($cache) {
                $mailboxes = Mailbox::rememberForever()->get();
            } else {
                $mailboxes = Mailbox::all();
            }
        } else {
            if ($cache) {
                $mailboxes = $this->mailboxes_cached;
            } else {
                $mailboxes = $this->mailboxes;
            }
        }

        return $mailboxes->sortBy('name');
    }

    /**
     * Get mailboxes to which user has access.
     */
    public function mailboxesCanViewWithSettings($cache = false)
    {
        $user = $this;

        if ($this->isAdmin()) {
            $query = Mailbox::select(['mailboxes.*', 'mailbox_user.hide', 'mailbox_user.mute', 'mailbox_user.access'])
                        ->leftJoin('mailbox_user', function ($join) use ($user) {
                            $join->on('mailbox_user.mailbox_id', '=', 'mailboxes.id');
                            $join->where('mailbox_user.user_id', $user->id);
                        });
        } else {
            $query = Mailbox::select(['mailboxes.*', 'mailbox_user.hide', 'mailbox_user.mute', 'mailbox_user.access'])
                        ->join('mailbox_user', function ($join) use ($user) {
                            $join->on('mailbox_user.mailbox_id', '=', 'mailboxes.id');
                            $join->where('mailbox_user.user_id', $user->id);
                        });
        }
        if ($cache) {
            return $query->rememberForever()->get();
        } else {
            return $query->get();
        }
    }

    public function mailboxesSettings($cache = true)
    {
        $user = $this;

        $query = MailboxUser::where('user_id', $user->id);

        if ($cache) {
            return $query->rememberForever()->get();
        } else {
            return $query->get();
        }
    }

    public function mailboxSettings($mailbox_id)
    {
        $settings = $this->mailboxesSettings()->where('mailbox_id', $mailbox_id)->first();

        if (!$settings) {
            return Mailbox::getDummySettings();
        }

        return $settings;
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

    public function hasAccessToMailbox($mailbox_id)
    {
        $ids = $this->mailboxesIdsCanView();
        return in_array($mailbox_id, $ids);
    }

    /**
     * Check to see if the user can manage any mailboxes
     */
    public function hasManageMailboxAccess() {
        if ($this->isAdmin()) {
            return true;
        } else {
            //$mailboxes = $this->mailboxesCanViewWithSettings(true);
            $mailboxes = $this->mailboxesSettings();
            foreach ($mailboxes as $mailbox) {
                if (!empty(json_decode($mailbox->access))) {
                    return true;
                }
            };
        }
    }

    /**
     * Check to see if the user can manage a specific mailbox
     */
    public function canManageMailbox($mailbox_id)
    {
        if ($this->isAdmin()) {
            return true;
        } else {
            //$mailbox = $this->mailboxesCanViewWithSettings(true)->where('id', $mailbox_id)->first();
            $mailbox = $this->mailboxesSettings()->where('mailbox_id', $mailbox_id)->first();
            if ($mailbox && !empty(json_decode($mailbox->access))) {
                return true;
            }
        }
    }

    public function hasManageMailboxPermission($mailbox_id, $perm) {
        if ($this->isAdmin()) {
            return true;
        } else {
            //$mailbox = $this->mailboxesCanViewWithSettings(true)->where('id', $mailbox_id)->first();
            $mailbox = $this->mailboxesSettings()->where('mailbox_id', $mailbox_id)->first();
            if ($mailbox && !empty($mailbox->access) && in_array($perm, json_decode($mailbox->access))) {
                return true;
            }
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
    public static function dateFormat($date, $format = 'M j, Y H:i', $user = null, $modify_format = true)
    {
        if (!$user) {
            $user = auth()->user();
        }
        if (is_string($date)) {
            // Convert string in to Carbon
            try {
                $date = Carbon::parse($date);
            } catch (\Exception $e) {
                $date = null;
            }
        }

        if (!$date) {
            return '';
        }

        if (!$format) {
            $format = 'M j, Y H:i';
        }

        if ($user && $user !== false) {
            if ($modify_format) {
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

        if (is_string($date)) {
            // Convert string in to Carbon
            $date = Carbon::parse($date);
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
            $diff_text = preg_replace('/minute[sn]?/', 'min', $diff_text);

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
            return __(':date @ :time', ['date' => $dateForHuman, 'time' => $date->format('H:i')]);
        } else {
            return $dateForHuman;
        }
    }

    public static function getUserPermissionName($user_permission)
    {
        $user_permission_names = [
            self::PERM_DELETE_CONVERSATIONS => __('Users are allowed to delete notes/conversations'),
            self::PERM_EDIT_CONVERSATIONS   => __('Users are allowed to edit notes/replies'),
            self::PERM_EDIT_SAVED_REPLIES   => __('Users are allowed to edit/delete saved replies'),
            self::PERM_EDIT_TAGS            => __('Users are allowed to manage tags'),
            self::PERM_EDIT_CUSTOM_FOLDERS  => __('Users are allowed to manage custom folders'),
            self::PERM_EDIT_USERS           => __('Users are allowed to manage users'),
        ];

        if (!empty($user_permission_names[$user_permission])) {
            return $user_permission_names[$user_permission];
        } else {
            return \Eventy::filter('user_permissions.name', '', $user_permission);
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
            $this->setInviteHash();
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
     * Generate and set password.
     */
    public function setPassword()
    {
        $this->password = Hash::make($this->generateRandomPassword());
    }

    /**
     * Generate and set invite_hash.
     */
    public function setInviteHash()
    {
        $this->invite_hash = Str::random(60);
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

    public function getPhotoUrl($default_if_empty = true)
    {
        if (!empty($this->photo_url) || !$default_if_empty) {
            if (!empty($this->photo_url)) {
                return Storage::url(self::PHOTO_DIRECTORY.DIRECTORY_SEPARATOR.$this->photo_url);
            } else {
                return '';
            }
        } else {
            return asset('/img/default-avatar.png');
        }
    }

    /**
     * Resize and save user photo.
     *
     * $uploaded_file can be \File or string.
     */
    public function savePhoto($uploaded_file, $mime_type = '')
    {
        $real_path = $uploaded_file;
        if (!is_string($uploaded_file)) {
            $real_path = $uploaded_file->getRealPath();
            $mime_type = $uploaded_file->getMimeType();
        }
        $resized_image = \App\Misc\Helper::resizeImage($real_path, $mime_type, self::PHOTO_SIZE, self::PHOTO_SIZE);

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

    public function hasPermission($permission, $check_own_permissions = true)
    {
        $has_permission = false;

        $global_permissions = self::getGlobalUserPermissions();

        if (!empty($global_permissions) && is_array($global_permissions) && in_array($permission, $global_permissions)) {
            $has_permission = true;
        }

        if ($check_own_permissions && !empty($this->permissions)) {
            if (isset($this->permissions[$permission])) {
                $has_permission = (bool)$this->permissions[$permission];
            }
        }

        return $has_permission;
    }

    public static function getGlobalUserPermissions()
    {
        $permissions = [];
        $permissions_json = config('app.user_permissions');

        if ($permissions_json) {
            $permissions_json = base64_decode($permissions_json);
            try {
                $permissions = json_decode($permissions_json, true);
            } catch (\Exception $e) {
                // Do nothing.
            }
        }

        if (!is_array($permissions)) {
            $permissions = [];
        }

        return $permissions;
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
            return null;
        }

        $user->fill($data);

        $user->password = \Hash::make($data['password']);
        $user->email = Email::sanitizeEmail($data['email']);

        try {
            $user->save();
        } catch (\Exception $e) {
            return null;
        }

        return $user;
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
            return \Helper::getRealAppLocale();
        }
    }

    /**
     * Get query to fetch non-deleted users.
     *
     * @return [type] [description]
     */
    public static function nonDeleted()
    {
        return self::where('status', '!=', self::STATUS_DELETED);
    }

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function isDisabled()
    {
        return $this->status == self::STATUS_DISABLED;
    }

    public function isDeleted()
    {
        return $this->status == self::STATUS_DELETED;
    }

    /**
     * Get users which current user can see.
     */
    public function whichUsersCanView($mailboxes = null, $sort = true)
    {
        if ($this->isAdmin()) {
            $users = User::nonDeleted()->get();
        } else {
            // Get user mailboxes.
            if ($mailboxes == null) {
                $mailbox_ids = $this->mailboxesIdsCanView();
            } else {
                $mailbox_ids = $mailboxes->pluck('id')->toArray();
            }

            // Get users
            $users = User::nonDeleted()->select('users.*')
                ->join('mailbox_user', function ($join) {
                    $join->on('mailbox_user.user_id', '=', 'users.id');
                })
                ->whereIn('mailbox_user.mailbox_id', $mailbox_ids)
                ->get();
        }

        if ($sort) {
            $users = User::sortUsers($users);
        }

        return $users;
    }

    /**
     * Get user initials: FL.
     */
    public function getInitials($length = 2)
    {
        if ($length == 2) {
            return strtoupper(mb_substr($this->first_name, 0, 1)).strtoupper(mb_substr($this->last_name, 0, 1));
        } else {
            return strtoupper(mb_substr($this->first_name, 0, 1));
        }
    }

    public function getAuthToken()
    {
        return md5($this->id.config('app.key'));
    }

    public static function findNonDeleted($id)
    {
        return User::nonDeleted()->where('id', $id)->first();
    }

    /**
     * Sorting users alphabetically.
     * It has to be done in PHP.
     */
    public static function sortUsers($users)
    {
        $users = $users->sortBy(function ($user, $i) {
            return $user->getFullName();
        }, SORT_STRING | SORT_FLAG_CASE);

        return $users;
    }

    public static function getUserPermissionsList()
    {
        return \Eventy::filter('user_permissions.list', self::$user_permissions);
    }
}
