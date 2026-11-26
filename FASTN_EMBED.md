# fastn embedded in FreeScout

FreeScout is a free, open-source help desk. This repo adds one module, `Modules/FastnIntegrations`, that embeds fastn so each help desk can connect its own apps and keep them in sync with its tickets.

## What it does

| App | New ticket | Later changes in FreeScout | Changes in the app |
|---|---|---|---|
| Linear | Creates an issue in the chosen team, with the message, the customer and a link back | Closed moves the issue to Done, reopened moves it to In Progress; replies, notes and assignments become comments | Issue moved to Done or Canceled closes the ticket; moved back reopens it; a note on the ticket says why |
| Google Sheets | Adds one row per ticket | Updates the same row (status, last activity, updated at) | - |
| HubSpot | Finds the customer by email, updates or creates the contact | - | - |
| ClickUp, Asana | Connect only | - | - |

Each FreeScout install is one fastn customer. Its admins connect their own accounts on **Manage > Settings > Integrations**; fastn keeps the credentials. The fastn API key stays on the server and never reaches the browser.

## How it works

- **Integrations page:** the server mints a short-lived embed token (`POST /api/v1/embed/token`) and the page loads fastn's embedded hub.
- **Ticket events:** FreeScout hooks (`conversation.created_by_customer`, `status_changed`, `user_replied`, `customer_replied`, `note_added`, `user_changed`) queue a background job that runs the fastn flows with `x-end-org-ref` and the help desk's `x-installation-id`.
- **Linear back to FreeScout:** the flow `linear-check-freescout-tickets` runs every minute from FreeScout's scheduler, reads the linked Linear issues and emits `freescout.linear_issue_state_changed` when a state changes. A fastn event subscription delivers it, signed (`X-Fastn-Signature`), to `POST /fastn-integrations/events`, which closes or reopens the ticket and adds a note. Loops are prevented: a change that came from Linear is not sent back.
- **Flow code:** `fastn/workflows/*.js`. Input shape: `fastn/ticket-input-schema.json`.

## Run it locally

1. `docker-local/run.sh` starts FreeScout on http://localhost:8080 (podman or docker).
2. Add to `.env`:
   ```
   FASTN_API_KEY=...            # your fastn API key
   FASTN_CUSTOMER_REF=freescout-local-dev
   FASTN_WEBHOOK_SECRET=...     # secret of the fastn event subscription
   APP_TRUSTED_HOSTS=<tunnel host>
   ```
3. Turn on the module in **Manage > Modules**, then open **Settings > Integrations** and connect apps.
4. For two-way sync fastn must reach FreeScout: run a tunnel (`cloudflared tunnel --url http://localhost:8080`) and point the event subscription at `https://<tunnel>/fastn-integrations/events`.
5. Keep a queue worker running (`php artisan queue:work --queue=emails,default`) and the scheduler (`php artisan schedule:run` every minute, or `docker-local/linear-check.php` in a loop).

## Notes

- Linear real-time webhooks need a Linear workspace admin, so changes are picked up by the every-minute check.
- Reconnecting an app creates a new fastn setup; FreeScout looks it up again and retries by itself.
