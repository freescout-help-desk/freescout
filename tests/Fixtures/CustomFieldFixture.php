<?php

namespace Modules\CustomFields\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Test-only stand-in for the paid Custom Fields module's CustomField model,
 * which isn't installed in this repo (runtime-installed on real
 * environments — see Modules/SortableCustomFields/README.md). Declared under
 * the real class's exact namespace so SortableCustomFieldsServiceProvider's
 * `use Modules\CustomFields\Entities\CustomField;` resolves during tests.
 *
 * Only implements the surface SortableCustomFieldsServiceProvider actually
 * touches: mailbox_id/name/show_in_list columns and a getAsText() accessor.
 * Never require this file if the real module is already loadable — always
 * prefer the genuine class.
 */
class CustomField extends Model
{
    protected $table = 'custom_fields';
    public $timestamps = false;
    protected $guarded = [];

    public function getAsText()
    {
        return (string) ($this->text_value ?? $this->value ?? '');
    }
}
