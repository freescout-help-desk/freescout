# CustomerFieldSearch

Plain-text customer search (Search > Customers, Search > Conversations, and
the shared ticket-sidebar lookup used by Change Customer, Merge, Cc/Bcc, New
Ticket, and advanced search's Customer filter) didn't match customer custom
field values — only name, email, phone, company, address, etc. The (paid)
Crm module already lets an agent filter by an exact custom field value via
`#field:value` syntax, but typing e.g. an Account Number or ID Card number
into the regular search box found nothing.

This module ORs a "does this customer have a custom field value starting
with the search term" condition into all three search surfaces, without
needing to know the field names in advance — any Crm custom field (Account
Number, ID Card, or any added later) becomes searchable automatically.

## Requirements

* The (paid) [Crm](https://freescout.net/module/crm/) module, installed and
  active — specifically its `customer_customer_field` table. This module
  no-ops cleanly (via a `Schema::hasTable()` guard) if that table isn't
  present, so it's harmless to leave active without Crm installed.
* Confirmed compatible with this fork's core at `1.8.229` — depends on three
  Eventy hook points: `search.customers.text_match` and
  `search.customers.ajax_text_match` (both new fork patches, see below) and
  `search.conversations.or_where` (already present upstream).

## Why prefix match, not substring

Per ARMS-22's explicit requirement, matching is `value LIKE 'q%'`
(prefix-anchored), never `LIKE '%q%'`. A leading wildcard can't use a
B-tree index and forces a full table scan; a search hitting every
`customer_customer_field` row on every keystroke doesn't hold up at the
10,000 → 100,000 customer scale ARMS-22 targets. This module's own migration
adds an index on `customer_customer_field.value` (a MySQL prefix-length
index, or a Postgres `text_pattern_ops` index) specifically to make the
prefix match sargable.

The user's own search term is also escaped for LIKE metacharacters (`%`,
`_`, `\`) before being used as a pattern, so typing e.g. an account number
containing a literal `%` or `_` can't turn part of the search into an
unintended wildcard.

## New fork patches

Two new hooks were added to core, both fired from *inside* the existing
native-match closure, before mailbox-visibility scoping is applied outside
it:

* `search.customers.text_match` in
  `app/Http/Controllers/ConversationsController.php` (`searchCustomers()`,
  the Search > Customers tab) — registered `20, 3` (`$query, $q, $like_op`).
* `search.customers.ajax_text_match` in
  `app/Http/Controllers/CustomersController.php` (`ajaxSearch()`, the
  endpoint shared by the ticket sidebar, Change Customer, Merge, Cc/Bcc, New
  Ticket, and advanced search) — registered `20, 2` (`$query, $q`), and only
  fired when `search_by == 'all'` so the intentionally-narrower
  `name`/`email`/`phone` modes aren't broadened.

Adding these as `orWhere` conditions from a *later* hook (e.g. the existing
`search.customers.apply_filters`) would have been unsafe: Laravel flattens
sequential `where()`/`orWhere()` calls at the top level, so an `orWhere`
added after mailbox scoping's `AND` condition ORs against the *entire*
preceding expression, not just the intended match group — silently
bypassing mailbox visibility restrictions. Firing from inside the original
closure avoids this.

Search > Conversations needed no new core patch: `app/Conversation.php`'s
`search()` already fires `search.conversations.or_where` from exactly the
right position, so this module just listens on it.

Every listener uses a correlated `whereExists()` subquery against
`customer_customer_field`, never a `join`. `ConversationsController`'s query
already unconditionally groups by `customers.id`, but `CustomersController::ajaxSearch()`
doesn't (its select list can carry an unaggregated `emails.email` column,
which a blanket `groupBy` would break under strict SQL mode) — `whereExists`
sidesteps the row-multiplication problem entirely rather than requiring a
matching `groupBy` at every call site.

## Tests

`tests/Feature/CustomerFieldSearchTest.php` covers: mailbox-scoping is
preserved for all three search surfaces, matching is prefix-only (a value
containing but not starting with the term is not matched), `ajaxSearch()`
doesn't duplicate rows for a customer with multiple matching field values,
`search_by` modes other than `all` aren't broadened, and a search term
containing `%`/`_` doesn't behave as a wildcard.

## Activation

Manage → Modules → Customer Field Search → Activate, then run
`php artisan migrate` to add the `customer_customer_field.value` index
(picked up automatically via `loadMigrationsFrom()`). No effect without the
Crm module also installed and active.
