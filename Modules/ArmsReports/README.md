# ArmsReports

ARMS reports catalogue ([ARMS-13](https://threls.atlassian.net/browse/ARMS-13)):
the §5.2/§5.3 report items from ARMS's discovery meeting that the paid
Reports module doesn't provide, computed from the `conversations`/`threads`
event tables. Full scope and catalogue mapping:
`local-specs/arms-freescout/customizations/custom-reports-layer.md`.

## Pages

Admin-only "ARMS Reports" nav dropdown (`menu.append` hook):

- **ARMS KPIs** — stat cards (created/resolved today, avg per day/week,
  one-touch %, reopened, first-response + first-resolution medians) and
  tables: created by hour, created by day-of-week, tickets by agent ×
  reply brackets, average time-in-status (incl. On-Hold), Ticket Brand
  slot (renders a pending-definition note until ARMS defines it)
- **Agent Performance** — per-assignee tickets handled, first-reply
  median, first-resolution median

Both pages export **CSV** and **PDF** of whatever the current filters show.

## Architecture

Query logic lives in `Services/` (thin Blade views on top) so the December
portal phase exposes the same numbers via API without re-implementation.
Timeline aggregation (reopened, time-in-status) happens in PHP over the
filtered range — fine at ARMS volume; revisit with a rollup table if
ranges ever span 10k+ conversations.

## Requirements & dependencies

- **MySQL required** — the volume queries use `HOUR()`/`DAYOFWEEK()`.
  (Demo and production are pinned MySQL; see infrastructure.md. If a
  non-MySQL environment ever matters, those two queries can move to
  PHP-side bucketing.)
- **dompdf** (`dompdf/dompdf` in composer.json) for PDF export — installed
  by the standard deploy's `composer install`. Note: dompdf emits a PHP
  8.5-only deprecation on construct; irrelevant on the PHP 8.3 baseline
  (see infrastructure.md for why the stack is pinned to 8.3).
- **Laravel 5.5 constraint**: CSV export uses `response()->stream()` —
  `streamDownload()` does not exist in 5.5.

## `first_reply_at` — ships at launch

The migration adds `conversations.first_reply_at`; a
`conversation.user_replied` listener stamps it on the first agent reply.
First-response medians read the column for new conversations and derive
from threads for historical rows — **no backfill needed**, but the module
must be active from go-live so the column is populated from the first
ticket (see ARMS-13 acceptance criteria).

## Activation

Manage → Modules → ArmsReports → Activate (activation state is DB-driven;
`module.json`'s `active` flag is ignored). The deploy pipeline's
`artisan migrate --force` applies the module migration once the module is
active. No licence.

## Tests

`tests/Unit/ArmsReportsStatsTest.php` covers the median/duration/bracket
helpers. The repo's pinned phpunit cannot run under PHP 8.5 (pre-existing);
all assertions plus the full pipeline (provider boot, migration, queries,
PDF output) are verified via a plain boot script.
