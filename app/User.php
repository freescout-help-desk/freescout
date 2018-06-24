<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'modified_at';

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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded  = ['role'];

    /**
     * The attributes that should be hidden for arrays.
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
}
