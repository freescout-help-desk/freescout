<?php
/**
 * Outgoing emails.
 */

namespace Modules\CustomFields\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CustomFields\Entities\CustomField;

class ConversationCustomField extends Model
{
    protected $table = 'conversation_custom_field';
    
    public $timestamps = false;

    protected $fillable = [
    	'conversation_id', 'custom_field_id', 'value'
    ];

    /**
     * Get custom field.
     */
    public function custom_field()
    {
        return $this->belongsTo('Modules\CustomFields\Entities\CustomField');
    }

    /**
     * Get conversation.
     */
    public function conversation()
    {
        return $this->belongsTo('App\Conversation');
    }
}
