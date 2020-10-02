<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class MailboxUser extends Model
{
    use Rememberable;
    // This is obligatory.
    public $rememberCacheDriver = 'array';
    
    // Action after sending a message
    const AFTER_SEND_STAY = 1;
    const AFTER_SEND_NEXT = 2;
    const AFTER_SEND_FOLDER = 3;

    protected $table = 'mailbox_user';

    public $timestamps = false;

    // Does not work as we receive it via pivot
    // protected $casts = [
    //     'access' => 'array',
    // ];
}
