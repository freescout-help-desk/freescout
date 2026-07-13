# OnHoldStatus

Adds **On-Hold** as a first-class conversation status (code `5`) for ARMS.
Tracked as [ARMS-12](https://threls.atlassian.net/browse/ARMS-12).

ARMS's status lifecycle is New/Open/Pending/**On-Hold**/Solved. Core FreeScout
hard-codes four statuses; only On-Hold needs adding — the rest map natively
(New = Active+unassigned, Open = Active+assigned, Solved = Closed).

## How it works

The service provider appends status 5 to core's mutable static registries at
boot — `Conversation::$statuses` / `$status_icons` / `$status_classes` /
`$status_colors` and `Thread::$statuses`. Status dropdowns (conversation view,
search filters, bulk actions), validation, folder logic, and counters all read
those arrays, so the status propagates with no further wiring.

The status *name* resolves through the `conversation.status_name` Eventy
filter, which this module answers with "On Hold".

## ⚠ Depends on fork patches — will not work on stock FreeScout

Two filters were added to core on the threls fork (grep for
`threls fork patch` in `app/Conversation.php` / `app/Thread.php`):

1. **`conversation.status_name`** — the `default:` cases of
   `Conversation::statusCodeToName()` and `Thread::statusCodeToName()` are
   hardcoded switches; without this patch every On-Hold status name renders
   **blank** in the UI and audit trail.
2. **`conversation.open_statuses`** — the Mine folder and chat list are
   live queries with a hardcoded `status IN (Active, Pending)` whitelist
   (`Conversation::getQueryByFolder()` and `Conversation::getChats()`);
   without this patch, On-Hold conversations **vanish from the Mine folder**
   (found live on the demo instance, 13 Jul).

When merging upstream FreeScout releases into the fork, verify all four
patched call sites survived.

## Activation

Manage → Modules → OnHoldStatus → Activate (or `php artisan freescout:module-install onholdstatus`
equivalent flow). Activation state lives in the **database** — the `active`
flag in `module.json` is ignored (see `app/Module.php`). No migrations, no
licence.

## Deactivation caveat

Deactivating with status-5 conversations still in the database does **not**
crash anything — `Conversation::getStatus()` falls back to Active for
unregistered codes, so views render — but those conversations show an Active-
styled button with a blank status name, and stop appearing in any On-Hold
view. Before deactivating in production, remap the data first:

```sql
UPDATE conversations SET status = 2 WHERE status = 5; -- On-Hold → Pending
```

(Historical thread rows with status 5 can stay — they only affect how old
audit-trail lines render.)

## Reporting

The reason this exists instead of an `on-hold` tag: status changes are logged
as typed thread events. Entering On-Hold creates a `Thread` line item
(`ACTION_TYPE_STATUS_CHANGED`, status 5, timestamped); leaving it creates
either another line item (agent action) or a timestamped customer-reply thread
(which flips the conversation back to Active — `FetchEmails.php` treats
status 5 like Pending). Time-in-On-Hold is therefore derivable from the
`threads` table from day one. Whether the paid Reports module *displays*
an On-Hold slice needs on-instance verification (see ARMS-12's
post-activation checklist); the fallback is custom queries on `threads`.

## Tests

`tests/Unit/OnHoldStatusTest.php` — covers the filter fallback, registration
on both models, non-regression of existing statuses, and the `getStatus()`
deactivation guard. Note: the repo's pinned phpunit (9.5.28) cannot run on
PHP 8.5 (its error handler fatals on its own deprecations); the same
assertions pass via a plain boot script until the harness is upgraded.
