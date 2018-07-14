<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use App\Mailbox;

class User extends Authenticatable
{
    use Notifiable;

    // const CREATED_AT = 'created_at';
    // const UPDATED_AT = 'modified_at';

    /**
     * Roles
     */
    const ROLE_USER = 1;
    const ROLE_ADMIN = 2;

    public static $roles = array(
        self::ROLE_ADMIN => 'admin',
        self::ROLE_USER => 'user'
    );

    /**
     * Types
     */
    const TYPE_USER = 1;
    const TYPE_TEAM = 2;
    
    /**
     * Invite states
     */
    const INVITE_STATE_ACTIVATED = 0;
    const INVITE_STATE_NOT_INVITED = 1;
    const INVITE_STATE_SENT = 2;

    /**
     * Time formats
     */
    const TIME_FORMAT_12 = 1;
    const TIME_FORMAT_24 = 2;
    
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded  = ['role'];

    /**
     * The attributes that should be hidden for arrays, excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Attributes fillable using fill() method
     * @var [type]
     */
    protected $fillable  = ['role', 'first_name', 'last_name', 'email', 'password', 'role', 'timezone', 'photo_url', 'type', 'emails', 'job_title', 'phone', 'time_format', 'enable_kb_shortcuts'];

    /**
     * Get mailboxes to which usre has access
     */
    public function mailboxes()
    {
        return $this->belongsToMany('App\Mailbox');
    }

    /**
     * Get conversations assigned to user
     */
    public function conversations()
    {
        return $this->hasMany('App\Conversation');
    }

    /**
     * User's folders
     */
    public function folders()
    {
        return $this->hasMany('App\Folder');
    }

    /**
     * Get user role
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
     * Check if user is admin
     * 
     * @return boolean
     */
    public function isAdmin()
    {
        return ($this->role == self::ROLE_ADMIN);
    }

    /**
     * Get user full name
     * @return string
     */
    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get mailboxes to which user has access
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
     * Generate random password for the user
     * @param  integer $length
     * @return string
     */
    public function generatePassword($length = 8)
    {
        $this->password = Hash::make(str_random($length));
        return $this->password;
    }

    /**
     * Get URL for editing user
     * @return string
     */
    public function urlEdit()
    {
        return route('users.profile', ['id'=>$this->id]);
    }

    /**
     * Create personal folders for user mailboxes.
     * 
     * @param  integer $mailbox_id 
     * @param  mixed $users    
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
                $folder = new Folder;
                $folder->mailbox_id = $mailbox_id;
                $folder->user_id = $this->id;
                $folder->type = $type;
                $folder->save();
            }
        }
    }
}
