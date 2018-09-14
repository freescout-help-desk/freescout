<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class ConversationFolder extends Model
{
	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conversation_folder';

    protected $fillable = ['folder_id', 'conversation_id'];
}