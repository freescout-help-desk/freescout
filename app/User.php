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
    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';

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
     * Allowed roles
     */
    public static $roles = array(self::ROLE_ADMIN, self::ROLE_USER);
    
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
     * Get user role
     * 
     * @return string
     */
    public function getRole()
    {
        return ucfirst($this->role);
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
    public function mailboxesHasAccess()
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
}
