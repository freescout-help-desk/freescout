// Linear "Issue" webhook (via a fastn app-event trigger) -> event back to FreeScout.
// ctx.input IS Linear's payload: { action, type, data, updatedFrom, url, ... }.
// Only state changes on issues that were created from a FreeScout ticket are sent.

/** @typedef {{ ticketId: string, ticketNumber?: string, issue: { id: string, identifier: string, url: string, title: string }, state: { name: string, type: string }, previousStateId?: string }} LinearIssueStateChanged */
const EVENT_NAME = 'freescout.linear_issue_state_changed';
/** @param {LinearIssueStateChanged} data @param {string} key */
function emitStateChanged(data, key) {
  return fastn.events.emit(EVENT_NAME, data, { connector: 'linear', key });
}

function tenantKey(ctx) {
  return ctx.endOrgId || ctx.externalRef || 'self';
}

export default async function (ctx) {
  const ev = ctx.input || {};
  const issue = ev.data || {};
  const result = { sent: 0, skipped: 0, reason: null, ticketId: null };

  if (ev.type !== 'Issue' || ev.action !== 'update') {
    result.skipped = 1; result.reason = 'not an issue update'; return result;
  }
  const from = ev.updatedFrom || {};
  if (!Object.prototype.hasOwnProperty.call(from, 'stateId')) {
    result.skipped = 1; result.reason = 'state did not change'; return result;
  }

  // Find the ticket: saved link first, then the ticket link in the description.
  const link = await fastn.state.get(`freescout-linear:${tenantKey(ctx)}:issue:${issue.id}`);
  let ticketId = link && link.ticketId;
  if (!ticketId) {
    const m = String(issue.description || '').match(/\/conversation\/(\d+)/);
    if (m) ticketId = m[1];
  }
  if (!ticketId) {
    result.skipped = 1; result.reason = 'issue is not linked to a FreeScout ticket'; return result;
  }

  const state = issue.state || {};
  const r = await emitStateChanged({
    ticketId: String(ticketId),
    ticketNumber: link && link.number ? String(link.number) : undefined,
    issue: { id: String(issue.id), identifier: String(issue.identifier || ''), url: String(issue.url || ev.url || ''), title: String(issue.title || '') },
    state: { name: String(state.name || ''), type: String(state.type || '') },
    previousStateId: from.stateId ? String(from.stateId) : undefined,
  }, `${issue.id}:${issue.stateId || state.id || ''}:${issue.updatedAt || ''}`);

  fastn.loki.info('linear state sent to freescout', { issue: issue.identifier, ticketId, state: state.name, queued: r && r.queued });
  result.sent = 1;
  result.ticketId = String(ticketId);
  result.state = state.name;
  result.queued = r && r.queued;
  return result;
}
