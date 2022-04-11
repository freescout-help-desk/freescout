<?php

namespace Modules\CustomFields\Entities;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class CustomField extends Model
{
    use Rememberable;

    // This is obligatory.
    public $rememberCacheDriver = 'array';

	const TYPE_DROPDOWN   = 1;
	const TYPE_SINGLE_LINE = 2;
	const TYPE_MULTI_LINE = 3;
	const TYPE_NUMBER     = 4;
	const TYPE_DATE       = 5;
	
	public static $types = [
		self::TYPE_DROPDOWN   => 'Dropdown',
		self::TYPE_SINGLE_LINE => 'Single Line',
		self::TYPE_MULTI_LINE => 'Multi Line',
		self::TYPE_NUMBER     => 'Number',
		self::TYPE_DATE       => 'Date',
	];

    protected $fillable = [
    	'name', 'type', 'required', 'options'
    ];

	protected $attributes = [
        'type' => self::TYPE_DROPDOWN,
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * To make types traanslatable.
     */
    public static function getTypes()
    {
    	return [
			1 => __('Dropdown'),
			2 => __('Single Line'),
			3 => __('Multi Line'),
			4 => __('Number'),
			5 => __('Date'),
    	];
    }

    public function setSortOrderLast()
    {
    	$this->sort_order = (int)CustomField::max('sort_order')+1;
    }

    public function getAsText()
    {
        if ($this->type == self::TYPE_DROPDOWN) {
            return $this->options[$this->value] ?? $this->value;
        } else {
            return $this->value;
        }
    }

    public static function getMailboxCustomFields($mailbox_id, $cache = false)
    {
    	$query = CustomField::where('mailbox_id', $mailbox_id)
            ->orderby('sort_order');
        if ($cache) {
            $query->rememberForever();
        }
        return $query->get();
    }

    public static function getCustomFieldsWithValues($mailbox_id, $conversation_id)
    {
        return CustomField::where('custom_fields.mailbox_id', $mailbox_id)
            ->select(['custom_fields.*', 'conversation_custom_field.value'])
            ->orderby('custom_fields.sort_order')
            ->leftJoin('conversation_custom_field', function ($join) use ($conversation_id) {
                $join->on('conversation_custom_field.custom_field_id', '=', 'custom_fields.id')
                    ->where('conversation_custom_field.conversation_id', '=', $conversation_id);
            })
            ->get();
    }

    public static function getValue($conversation_id, $custom_field_id)
    {
        $field = ConversationCustomField::where('conversation_id', $conversation_id)
            ->where('custom_field_id', $custom_field_id)
            ->first();

        if ($field) {
            return $field->value;
        } else {
            return '';
        }
    }

    public static function setValue($conversation_id, $custom_field_id, $value)
    {
        try {
            $field = ConversationCustomField::firstOrNew([
                'conversation_id' => $conversation_id,
                'custom_field_id' => $custom_field_id,
            ]);

            $field->conversation_id = $conversation_id;
            $field->custom_field_id = $custom_field_id;
            $field->value = $value;
            $field->save();

            \Eventy::action('custom_field.value_updated', $field, $conversation_id);
        } catch (\Exception $e) {
            
        }
    }

    public static function loadCustomFieldsForConversations($conversations)
    {
        // Fetch mailbox by mailbox.
        $mailbox_ids = $conversations->pluck('mailbox_id')->unique()->toArray();

        foreach ($mailbox_ids as $mailbox_id) {

            $ids = [];

            foreach ($conversations as $conversation) {
                if ($conversation->mailbox_id == $mailbox_id) {
                    $ids[] = $conversation->id;
                }
            }
            if (!$ids) {
                return $conversations;
            }

            $custom_fields = CustomField::select(['custom_fields.*', 'conversation_custom_field.value', 'conversation_custom_field.conversation_id'])
                ->where('custom_fields.mailbox_id', $mailbox_id)
                ->orderby('custom_fields.sort_order')
                ->leftJoin('conversation_custom_field', function ($join) use ($ids) {
                    $join->on('conversation_custom_field.custom_field_id', '=', 'custom_fields.id')
                        ->whereIn('conversation_custom_field.conversation_id', $ids);
                })
                ->get();

            foreach ($custom_fields as $custom_field) {
                foreach ($conversations as $i => $conversation) {
                    $custom_fields = [];
                    if (isset($conversation->custom_fields)) {
                        $custom_fields = $conversation->custom_fields;
                    }
                    if ($conversation->id == $custom_field->conversation_id) {
                        $custom_fields[] = $custom_field;
                    }
                    $conversation->custom_fields = $custom_fields;
                }
            }
        }

        return $conversations;
    }
}