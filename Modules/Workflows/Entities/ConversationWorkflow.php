<?php

namespace Modules\Workflows\Entities;

use Illuminate\Database\Eloquent\Model;

class ConversationWorkflow extends Model
{
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conversation_workflows';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
