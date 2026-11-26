// Scheduled check: Linear issue state changes -> FreeScout (signed event).
// Reads the index of issues created from FreeScout tickets (written by the
// "FreeScout ticket to Linear issue" flow) and compares each issue's state type
// with the last one seen. Only real changes are sent.

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
  const tenant = tenantKey(ctx);
  const index = (await fastn.state.get(`freescout-linear:${tenant}:index`)) || [];
  const limit = (ctx.input && Number(ctx.input.limit)) || 50;
  const result = { checked: 0, sent: 0, baselined: 0, errors: 0, errorDetails: [], changes: [] };

  for (const issueId of index.slice(-limit)) {
    const revKey = `freescout-linear:${tenant}:issue:${issueId}`;
    const link = await fastn.state.get(revKey);
    if (!link || !link.ticketId) continue;
    try {
      const res = await fastn.connector.linear.getIssue({ id: issueId });
      const issue = res.output && res.output.data && res.output.data.issue;
      if (!issue) continue;
      result.checked += 1;
      const state = issue.state || {};
      if (!link.stateType || link.stateType === 'unknown') {
        link.stateType = state.type;
        await fastn.state.set(revKey, link);
        result.baselined += 1;
        continue;
      }
      if (state.type && state.type !== link.stateType) {
        const r = await emitStateChanged({
          ticketId: String(link.ticketId),
          ticketNumber: link.number ? String(link.number) : undefined,
          issue: { id: String(issue.id), identifier: String(issue.identifier || ''), url: String(issue.url || ''), title: String(issue.title || '') },
          state: { name: String(state.name || ''), type: String(state.type) },
        }, `${issue.id}:${state.id || state.type}:${issue.updatedAt || ''}`);
        link.stateType = state.type;
        await fastn.state.set(revKey, link);
        result.sent += 1;
        result.changes.push({ issue: issue.identifier, ticketId: link.ticketId, state: state.name, queued: r && r.queued });
      }
    } catch (e) {
      result.errors += 1;
      result.errorDetails.push({ issueId, errorMessage: String((e && e.message) || e) });
    }
  }
  if (result.sent || result.errors) fastn.loki.info('linear check done', { checked: result.checked, sent: result.sent, errors: result.errors });
  return result;
}
