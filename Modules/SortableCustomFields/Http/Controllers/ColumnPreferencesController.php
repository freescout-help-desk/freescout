<?php

namespace Modules\SortableCustomFields\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mailbox;
use Illuminate\Http\Request;
use Modules\CustomFields\Entities\CustomField;
use Modules\SortableCustomFields\Entities\UserColumnPreference;

class ColumnPreferencesController extends Controller
{
    public function save(Request $request)
    {
        $data = $request->validate([
            'mailbox_id'      => 'required|integer',
            'custom_field_id' => 'required|integer',
            'visible'         => 'required|boolean',
            'sortable'        => 'required|boolean',
        ]);

        $mailbox = Mailbox::find($data['mailbox_id']);
        if (!$mailbox) {
            abort(404);
        }
        $this->authorize('view', $mailbox);

        // The field must actually belong to this mailbox — otherwise a user
        // could write preference rows for fields (or field ids) that have
        // nothing to do with the mailbox they're pretending to be in.
        $fieldExists = CustomField::where('id', $data['custom_field_id'])
            ->where('mailbox_id', $data['mailbox_id'])
            ->exists();
        if (!$fieldExists) {
            abort(404);
        }

        UserColumnPreference::setPreference(
            $request->user()->id,
            $data['mailbox_id'],
            $data['custom_field_id'],
            ['visible' => $data['visible'], 'sortable' => $data['sortable']]
        );

        return response()->json(['status' => 'success']);
    }

    /**
     * Resets every field back to the default (hidden, not sortable) for
     * this user/mailbox in one request — a single delete rather than one
     * save() call per field, which would otherwise fire N concurrent
     * requests and write N redundant rows for a state the absence of a row
     * already represents.
     */
    public function reset(Request $request)
    {
        $data = $request->validate([
            'mailbox_id' => 'required|integer',
        ]);

        $mailbox = Mailbox::find($data['mailbox_id']);
        if (!$mailbox) {
            abort(404);
        }
        $this->authorize('view', $mailbox);

        UserColumnPreference::resetForUserMailbox($request->user()->id, $data['mailbox_id']);

        return response()->json(['status' => 'success']);
    }
}
