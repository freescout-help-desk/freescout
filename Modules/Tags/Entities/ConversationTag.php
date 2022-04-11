<?php
/**
 * Outgoing emails.
 */

namespace Modules\Tags\Entities;

use Illuminate\Database\Eloquent\Model;

class ConversationTag extends Model
{
    protected $table = 'conversation_tag';
    public $timestamps = false;

    public function tag()
    {
        return $this->belongsTo('Modules\Tags\Entities\Tag');
    }
}
