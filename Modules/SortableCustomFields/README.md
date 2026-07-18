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
* Confirmed compatible with this fork's core at `1.8.229` (module declares `requiredAppVersion: 1.8.117`; the Eventy hook points it uses — `folder.conversations_query`, `conversations_table.{col,th,td}_before_conv_number`, `conversations_table.row_class` — are all still present in `resources/views/conversations/conversations_table.blade.php` / `app/Conversation.php`).
* Every conversation-list route in this fork is scoped to a single mailbox (`/mailbox/{id}/{folder_id}`) — the module resolves its per-mailbox custom fields from that route param, so it works cleanly across both ARMS mailboxes. This would silently stop rendering columns on a mailbox-spanning view; not a concern here since the Global Mailbox module is deliberately not used on this fork.

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

Also escaped two spots that echoed custom-field name/value straight into the
table markup without escaping (`e($custom_field->name)`, `e($custom_field->getAsText())`)
— a mailbox admin could otherwise store an XSS payload in a custom field name
or value and have it render unescaped for every agent viewing the list. And
fixed a typo'd published-config filename (`srotablecustomfields.php` →
`sortablecustomfields.php`); the config itself is unused elsewhere so this is
cosmetic.

## Usage

Every Custom Field (with "Show in list" enabled) on a mailbox becomes a
sortable column in that mailbox's conversation list. Click a column header to
sort; core's own `date`-based sort still works as before for fields that
aren't custom.

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

Manage → Modules → Sortable Custom Fields → Activate. Requires the Custom
Fields module to already be active on the mailbox(es) in question — if it
isn't, the sortable columns simply won't appear (the module's own
`if ($mailbox_id)` / `CustomField::where(...)` guards no-op cleanly rather
than erroring).

## Maintenance note

Small, single-maintainer upstream project with no tagged releases — we pin
the exact commit (see above) and carry our own patches on this fork rather
than tracking upstream `master`. Re-check upstream for fixes/improvements
before repointing the pin, but re-apply the SQL-injection and escaping fixes
above if so, since they aren't merged upstream.
