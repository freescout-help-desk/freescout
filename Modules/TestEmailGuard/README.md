# TestEmailGuard

Test-environment email safety net ([ARMS-16](https://threls.atlassian.net/browse/ARMS-16)). Lets the team bulk-test with realistic customer data while making it impossible for the test instance to email real people.

Two layers, belt and braces:

1. **Send-time guard** — every outbound recipient (To/Cc/Bcc, on every send path: replies, auto-replies, workflow emails, notifications, alerts) is rewritten to a safe address unless its domain is allow-listed.
2. **Stored-data anonymisation** — a console command rewrites customer addresses already in the database (run it right after importing real data, e.g. a Zendesk demo migration), so the DB carries no real addresses in the first place.

## The transform

The original domain is folded into the local part so distinct customers stay distinct:

```
tanti.omar@gmail.com  →  tanti.omar+gmail.com@example.com
```

A flat "replace the domain" would merge `john@gmail.com` and `john@yahoo.com` into one customer record. `example.com` is IANA-reserved, so anonymised addresses can never deliver. If `local+domain` would exceed the 64-character local-part limit, it falls back to `local…+<10-char-hash>` (uniqueness kept). Everything is lowercased. The transform is idempotent — re-running it is safe.

**Reversibility:** an anonymised address parses back to the (lowercased) original by splitting the local part on its last `+` — `EmailAnonymizer::reverse()` implements this and is covered by tests. The only exception is the hash fallback, which triggers exclusively for originals longer than 64 characters (rare in real data). The anonymise command counts those cases and reports them; if any exist and you need them recoverable, run it with `--map=<path>` to also write an `original → anonymised` CSV covering every rewritten address. ⚠ The map file contains real customer addresses — move it off the server and delete the server copy as soon as possible, or the anonymisation exercise defeats itself.

## Enabling

Activation **is** the switch: activate the module (Manage → Modules) in the environment that must be guarded. There is no "enable" flag that can be forgotten.

**Hard production block:** when `app.env` is `production` the guard refuses to rewrite anything, even if the module is activated there by mistake (it logs an error so the mismatch is visible). ⚠ Forge-style deploys default to `APP_ENV=production` — on a test/demo instance you **must** set e.g. `APP_ENV=demo` in `.env`, or the guard stays off and mail flows normally. Verify with:

```
php artisan test-email-guard:status
```

which prints the effective state and a sample rewrite, and exits non-zero if the guard is disabled.

## Configuration (`.env`, all optional)

| Variable | Default | Meaning |
|---|---|---|
| `TEST_EMAIL_GUARD_ALLOW_DOMAINS` | `arms.com.mt,threls.com` | Comma-separated domains whose recipients receive real mail, untouched. Exact-domain match (subdomains and lookalike suffixes are rewritten). |
| `TEST_EMAIL_GUARD_SINK` | *(empty)* | A real mailbox to plus-address rewritten mail into, e.g. `armssink@threls.onmicrosoft.com` → mail arrives as `armssink+tanti.omar+gmail.com@…` and can be inspected. When empty, rewrites target `example.com` and sends will bounce (NDR noise in the helpdesk — a sink is recommended for bulk testing). |

On the demo instance also consider allow-listing `threls.onmicrosoft.com` (the demo mailboxes' own domain) so inter-mailbox test flows deliver.

## Anonymising stored data

```
php artisan test-email-guard:anonymize-stored
```

Rewrites `emails.email`, the `conversations` customer_email/cc/bcc fields, the `threads` from/to/cc/bcc fields, `send_logs.email`, and address tokens inside the raw stored email headers (`threads.headers`). Refuses to run when `app.env` is `production`; prompts for confirmation unless `--force` is given. If a row cannot be updated (e.g. a unique-key collision on `emails.email`), the command reports it, carries on with the rest, and exits non-zero — it never aborts half-done.

Deliberately untouched: agents (`users` table); addresses inside message body text — free text cannot cause a send, the outbound guard is the protection there; and `Message-ID`/`References`/`In-Reply-To` header lines plus `threads.message_id` — threading identifiers that merely look like addresses, rewriting them would corrupt reply threading.

**Runbook for a migration test:** import → `test-email-guard:anonymize-stored` → `test-email-guard:status` → hand over for testing.

## How the guard hooks in

Core fires the `mail.process_swift_message` Eventy filter (via Laravel's `MessageSending` event → `App\Listeners\ProcessSwiftMessage`) for every outgoing message immediately before transport, regardless of which Mailable built it. The module hooks that single filter — no core patch needed, and unlike a registered Swift Mailer plugin it survives core's per-mailbox rebuilding of the mailer instance.
