<?php

namespace Modules\Workflows\Entities;

use App\Conversation;
use Illuminate\Database\Eloquent\Model;

class ConversationWorkflow extends Model
{
    public $timestamps = false;
    protected $table = 'conversation_workflow';
}