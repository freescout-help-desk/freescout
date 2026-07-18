# SortableCustomFields

Adds sortable columns for Custom Fields to the conversation list, and a CSS
class per row/cell keyed to each field's value (e.g. `cf_priority_high`) so
rows can be colour-coded — e.g. highlight High-priority tickets ([ARMS-33](https://threls.atlassian.net/browse/ARMS-33)).

This is a Threls fork of the upstream MIT-licensed
[karrierekick-dev/freescout-sortable-custom-fields](https://github.com/karrierekick-dev/freescout-sortable-custom-fields),
pinned to upstream commit `74e958f` (25 Mar 2025, its latest — single
maintainer, no tagged releases). Original `LICENSE` kept alongside this file
per the MIT terms.

## Requirements

* The (paid) [Custom Fields](https://freescout.net/module/custom-fields/) module, installed and active on the same mailbox(es).
* Confirmed compatible with this fork's core at `1.8.229` (module declares `requiredAppVersion: 1.8.117`; the Eventy hook points it uses — `folder.conversations_query`, `conversations_table.{col,th,td}_before_conv_number`, `conversations_table.row_class`, `conversations_table.toolbar` — are all present in `resources/views/conversations/conversations_table.blade.php` / `app/Conversation.php`; the last one is a fork patch, see below).
* Every conversation-list route in this fork is scoped to a single mailbox (`/mailbox/{id}/{folder_id}`) — the module resolves its per-mailbox custom fields from that route param, so it works cleanly across both ARMS mailboxes. This would silently stop rendering columns on a mailbox-spanning view; not a concern here since the Global Mailbox module is deliberately not used on this fork.

## Per-agent column visibility (Columns control)

With several custom fields in play (Priority, Category, Type, Topic,
Electricity/Water…), every one of them becoming a mandatory column for every
agent gets cramped fast, most are usually empty for any given ticket. A
"Columns" button in the conversation-list toolbar lets each agent choose,
per mailbox: which custom fields show as columns at all, and — separately —
which of those are sortable (a field can be worth glancing at without being
worth sorting by). Choices are per-agent and follow them across devices,
since agents aren't tied to one machine.

**Opt-in by default**: a field an agent has never touched starts hidden and
non-sortable, not shown. Starting from "everything visible" got cluttered
fast as more fields were added (the original motivation for this control in
the first place) — better for an agent to pick the handful they actually
care about than to start from all of them and hide down. "Reset to default"
in the popover clears back to this same all-hidden state, not
all-visible.

**New fork patch** (grep for `threls fork patch` across the codebase to find
this and the other core patches this fork carries): a single always-visible
`@action('conversations_table.toolbar', $folder ?? null)` hook added to
`conversations_table.blade.php`, right after the `bulk_actions` include.
Core's own bulk-actions toolbar is hidden until rows are selected
(`main.js`'s `converstationBulkActionsInit()`), so it couldn't host a
persistent control — this fork patch exists because there was nowhere else
to hang one.

**New table**: `sortablecustomfields_user_columns` (user_id, mailbox_id,
custom_field_id, visible, sortable) — owned entirely by this module via its
own migration (`Database/Migrations/`), not a core table. Absence of a row
means "hidden and not sortable" (see "Opt-in by default" above) — no
backfill needed either way, since this is purely a rendering default with
no other side effects.

**New routes**: `sortablecustomfields.columns.save` (POST) and
`sortablecustomfields.columns.reset` (POST), both gated by the `auth`
middleware and `MailboxPolicy::view` (an agent can only set preferences for
mailboxes they can actually view; `save`'s `custom_field_id` must also
belong to the given `mailbox_id` or the request is rejected). Registered
from `SortableCustomFieldsServiceProvider::boot()`, same as any package
registering its own routes — except this fork's `RouteCollection` only
refreshes its name→route index once, at the end of the app's normal
route-loading pass. A route registered from a module's `boot()` (which runs
after that pass) still works, but `route('...')` on it resolves to nothing
until `\Route::getRoutes()->refreshNameLookups()` is called again — worth
knowing for any future module that registers its own named routes, not just
this one.

The popover shows a checkbox per field (visible) and a small toggle
(sortable); toggling either saves via AJAX and re-fetches the conversation
list through the app's own existing `loadConversations()` (the same
mechanism core's sort-header clicks already use), so the table reflects the
change immediately without a full page reload. "Reset to default" calls
the `reset` route once to delete every saved preference for that
user/mailbox, rather than one `save` call per field — since the default is
already "no row", writing an explicit false/false row per field on reset
would just be redundant storage, on top of firing one request per field.

## Fixed vs. upstream

Upstream's `folder.conversations_query` filter concatenated
`$_REQUEST['sorting']['sort_by']` (the conversation list's sort parameter)
directly into a `DB::Raw()` string used as a SQL `LIKE` pattern — any
authenticated agent could break out of that string literal (SQL injection via
the sort control). It also matched via `LIKE` against the slugified field
name, where `_` is a SQL wildcard for "any one character", so a slug of the
right length could false-positive-match the wrong custom field.

Patched in `Providers/SortableCustomFieldsServiceProvider.php`: the request's
sort slug is now resolved against this mailbox's real `CustomField` names
*before* anything touches SQL — untrusted input never reaches the query, and
the join/alias are only ever built from the matched, trusted field name.

Also fixed a second, independent reflected-XSS: `conversations_table.th_before_conv_number`
echoed `sorting[order]` straight into a `data-order="..."` attribute
unescaped — normalized to a strict `asc`/`desc` value at the point it's read
from the request instead. And escaped two spots that echoed custom-field
name/value straight into the table markup without escaping
(`e($custom_field->name)`, `e($custom_field->getAsText())`) — a mailbox admin
could otherwise store an XSS payload in a custom field name or value and have
it render unescaped for every agent viewing the list.

The sort filter also now reads the mailbox off the `$folder` object
`Conversation::getQueryByFolder()` passes it, rather than guessing from the
request URL like the rest of the module does. This required registering the
filter with an explicit argument count (`addFilter(..., 20, 2)`) — Eventy's
`Filter::fire()` truncates the arguments actually passed to a listener down
to whatever count it was registered with (default 1), so `$folder` would
silently always be `null` without it, even though the caller passes it.

And fixed a typo'd published-config filename (`srotablecustomfields.php` →
`sortablecustomfields.php`); the config itself is unused elsewhere so this is
cosmetic.

Also: `conversations_table.td_before_conv_number` and `.row_class` never
checked `show_in_list` upstream, unlike `.col_before_conv_number` and
`.th_before_conv_number`, which did. If `$conversation->custom_fields`
includes fields the admin excluded from the list, that meant a stray `<td>`
per row with no matching `<th>`/`<col>`, misaligning every column after it.
Both hooks now skip those fields too.

## Tests

`tests/Unit/SortableCustomFieldsTest.php` covers the slug-safety invariant
the injection fix depends on, and the name/value/show_in_list escaping and
gating, without needing the Custom Fields module.
`tests/Feature/SortableCustomFieldsTest.php` exercises the sort filter, the
order-normalization fix, and the Columns control (preference storage, the
save endpoint's authorization, and the visible/sortable rendering gates)
against a real (fixture) `CustomField` model and ad hoc `custom_fields`/
`conversation_custom_field` tables, since the real paid module isn't
installed in this repo — see `tests/Fixtures/CustomFieldFixture.php`. The
save-endpoint tests call the controller directly rather than through a real
HTTP request — `$this->post()` trips a pre-existing, unrelated environment
issue on PHP 8.2 (see the test file's docblock for specifics; the same issue
is why `ConversationChangeCustomerTest.php` skips its own HTTP assertions
below PHP 8.4).

## Usage

Every Custom Field (with "Show in list" enabled) on a mailbox becomes a
sortable column in that mailbox's conversation list, unless an agent has
hidden it via the Columns control. Click a column header to sort; core's own
`date`-based sort still works as before for fields that aren't custom.

## Styling by field value

Each Custom Field adds a `cf_<field>_<value>` class (slugified) to the row
(`tr.conv-row`) and its cell. Style via the official Customization &
Rebranding module's custom CSS — do not edit `Public/css/style.css` here for
per-deployment styling, that stays generic. Example, given a "Priority"
field with values High/Normal/Low ([ARMS-18](https://threls.atlassian.net/browse/ARMS-18)):

```css
.conv-row.cf_priority_high td {
    background-color: #fee2e2;
    border-top: 2px solid #b91c1c;
}
.conv-row.cf_priority_high td a {
    font-weight: bold;
    color: #b91c1c;
}
```

Confirm the exact palette with ARMS before shipping any colour-coding beyond
the demo.

## Activation

Manage → Modules → Sortable Custom Fields → Activate, then run
`php artisan migrate` to create `sortablecustomfields_user_columns` (picked
up automatically via `loadMigrationsFrom()` — no manual migration path
needed). Requires the Custom Fields module to already be active on the
mailbox(es) in question — if it isn't, the sortable columns simply won't
appear (the module's own `if ($mailbox_id)` / `CustomField::where(...)`
guards no-op cleanly rather than erroring).

## Maintenance note

Small, single-maintainer upstream project with no tagged releases — we pin
the exact commit (see above) and carry our own patches on this fork rather
than tracking upstream `master`. Re-check upstream for fixes/improvements
before repointing the pin, but re-apply the SQL-injection and escaping fixes
above if so, since they aren't merged upstream.
