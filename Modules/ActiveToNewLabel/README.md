# ActiveToNewLabel

Relabels the conversation status **Active** to **New**, matching the term ARMS's agents already use. Only the label changes — the underlying status value and all status logic are untouched.

## How it works

`resources/lang/en.json` (a plain Laravel translation override) carries `"Active": "New"`. Every place in this codebase that renders the label already calls `__('Active')` through `Conversation::statusCodeToName()` / `Thread::statusCodeToName()` (the same resolver ARMS-37's Closed → Solved rename relies on), so this one entry reaches the status dropdown, the bulk "change status" menu, thread history log lines, and notification emails with no further changes.

## Why this needed more than a one-line lang file

Unlike "Closed," the word "Active" is reused for several things that have nothing to do with conversation status. A blanket translation override would have relabeled all of them too, so each was given its own distinct translation key instead of sharing `'Active'`:

- `App\User::getInviteStateName()` — an already-accepted invite now reads "Activated," not "New."
- The mailbox connection-health indicator (`resources/views/mailboxes/connection_incoming.blade.php`) — now reads "Working."
- The Modules admin page's enabled badge (`resources/views/modules/partials/module_card.blade.php`) — now reads "Enabled."
- **Workflows' own "is this rule enabled" checkbox** — a paid, non-git-tracked module that uses the identical `__('Active')` call for something unrelated to conversation status. Can't be fixed with a translation key change (that file isn't ours to edit and wouldn't survive a Workflows update anyway), so this module runs `php artisan activetonewlabel:patch-workflows` to rewrite that one label directly. Idempotent and guarded the same way as the existing Workflows Status list patch (`Modules/OnHoldStatus`) — refuses to touch the file if it doesn't look exactly as expected, backs it up before writing, and reversible via `--revert`.

Two labels that share the same underlying concept were deliberately left alone: the mailbox "default status for new tickets" dropdown options (`Mailbox::TICKET_STATUS_ACTIVE`) — same concept as the conversation status, correct for them to read "New" too.

## Known, accepted limitation

ARMS's own status model splits "Active" conversations into "New" (unassigned) and "Open" (assigned) — a distinction based on assignment, not on anything the status label itself carries. This rename doesn't add that distinction anywhere it doesn't already exist:

- Folder navigation (Unassigned / Mine / Assigned) already splits New from Open correctly via assignment — untouched either way.
- Advanced Search's Status filter and the ArmsReports "Average time in status" report have no assignment dimension, so filtering/reporting on "New" after this change includes both unassigned and assigned conversations. Accepted as-is rather than expanding scope to add an assignment-aware breakdown to either surface.

## Activation

Manage → Modules → ActiveToNewLabel → Activate (activation state is DB-driven; `module.json`'s `active` flag is ignored). Add `$FORGE_PHP artisan activetonewlabel:patch-workflows` to the deploy script (alongside the existing `onholdstatus:patch-workflows` line) so the Workflows patch self-heals after every Workflows update, the same way the Status list patch already does.

## Tests

`tests/Unit/ActiveToNewLabelTest.php` — the translation override itself, the four collision fixes reading their own distinct labels (not "New"), and that `Conversation::STATUS_ACTIVE`/`Thread::STATUS_ACTIVE` (the actual numeric values) are untouched. `tests/Feature/PatchWorkflowsActiveLabelTest.php` — the patch command against a fixture file: patches once, is idempotent on a second run, refuses to touch a file that doesn't match the expected shape, and reverts cleanly.
