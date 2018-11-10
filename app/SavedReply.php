<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class SavedReply extends Model
{
    protected $fillable = ['mailbox_id', 'name', 'body'];

    /**
     * Get the mailbox to which saved reply belongs.
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox');
    }
}