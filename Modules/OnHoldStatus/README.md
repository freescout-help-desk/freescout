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

## On-Hold in Workflows (ARMS-26)

On-Hold doesn't show up as a Workflow condition ("Status is equal to...") or
action ("Change Status to...") by default — the paid Workflows module
hardcodes the four core statuses as PHP array literals in
`Modules/Workflows/Entities/Workflow.php` and exposes no Eventy hook to
extend them, unlike core (which this module already answers via the two
filters above).

Workflows is a paid, runtime-installed module and — unlike this one — is
**not tracked by this repo's git** (`.gitignore`'s blanket `/Modules/*` rule
has no allowlist entry for it). A hand-edit to its file would be invisible
to git and silently wiped by the next Workflows update or reinstall. So
instead of editing it directly, `php artisan onholdstatus:patch-workflows`
(registered by this module, `Console/PatchWorkflowsStatuses.php`) patches it
programmatically:

- **Idempotent** — a no-op if the On-Hold entry is already present, safe to
  run on every deploy
- **Guarded** — before writing anything, it checks the target text appears
  exactly twice (once for the condition, once for the action); if Workflows
  has changed shape since this was written, it refuses to modify the file
  and exits non-zero rather than guessing
- **Backed up** — writes `Workflow.php.bak` before any change
- **Reversible** — `php artisan onholdstatus:patch-workflows --revert`
  removes the entry again
- **No-ops cleanly** if Workflows isn't installed at all

Add `$FORGE_PHP artisan onholdstatus:patch-workflows` to the deploy script
(after `artisan migrate --force` is a natural spot) so it self-heals after
every Workflows module update, the same way migrations already do.

The patch's exact expected text was transcribed from a live-server
terminal paste, not verified byte-for-byte against the file — the
occurrence-count guard above is what protects the real file if that
transcription turns out to be slightly off (it'll just refuse to patch and
report a count other than 2, rather than corrupting anything). **Run it
once manually on the demo server and check the output before wiring it into
the automatic deploy step.**

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
