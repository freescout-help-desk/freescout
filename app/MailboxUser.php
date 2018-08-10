<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MailboxUser extends Model
{
    // Action after sending a message
    const AFTER_SEND_STAY = 1;
    const AFTER_SEND_NEXT = 2;
    const AFTER_SEND_FOLDER = 3;

    protected $table = 'mailbox_user';
    public $timestamps = false;
}
