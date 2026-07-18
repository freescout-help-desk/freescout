<?php

namespace Modules\SortableCustomFields\Entities;

use Illuminate\Database\Eloquent\Model;

class UserColumnPreference extends Model
{
    protected $table = 'sortablecustomfields_user_columns';

    protected $fillable = ['user_id', 'mailbox_id', 'custom_field_id', 'visible', 'sortable'];

    protected $casts = [
        'visible'  => 'boolean',
        'sortable' => 'boolean',
    ];

    /**
     * Preferences for a user's mailbox, keyed by custom_field_id. Absent
     * keys mean "visible and sortable" — the caller should default to that
     * rather than treating a missing entry as hidden.
     */
    public static function forUserMailbox($userId, $mailboxId)
    {
        return static::where('user_id', $userId)
            ->where('mailbox_id', $mailboxId)
            ->get()
            ->keyBy('custom_field_id');
    }

    public static function setPreference($userId, $mailboxId, $customFieldId, array $attributes)
    {
        return static::updateOrCreate(
            ['user_id' => $userId, 'mailbox_id' => $mailboxId, 'custom_field_id' => $customFieldId],
            $attributes
        );
    }
}
