// FreeScout ticket events -> Linear.
// ticket.created            -> create one issue (team from the widget setting).
// ticket.status_changed     -> closed in FreeScout moves the issue to Done; reopened moves it to In Progress.
// ticket.replied / ticket.note_added / ticket.assigned -> a comment on the linked issue.
// Input: { event, ticket, customer, change } as built by Modules/FastnIntegrations/Services/TicketPayload.php.
// Settings (widget form, per help desk): linearTeam = team key or name; empty = first team.

async function readSettings(ctx) {
  const raw = ctx.headers && ctx.headers['x-fastn-installation-config'];
  if (!raw) {
    // The help desk's widget settings, from its installation.
    try {
      const cfg = await fastn.config.getByWorkflow();
      return (cfg && (cfg.formValues || cfg)) || {};
    } catch (e) {
      return {};
    }
  }
  try {
    const cfg = typeof raw === 'string' ? JSON.parse(raw) : raw;
    return (cfg && (cfg.formValues || cfg)) || {};
  } catch (e) {
    return {};
  }
}

function tenantKey(ctx) {
  return ctx.endOrgId || ctx.externalRef || 'self';
}

async function resolveTeam(wanted) {
  const res = await fastn.connector.linear.listTeams({ first: 100 });
  const teams = (res.output && res.output.data && res.output.data.teams && res.output.data.teams.nodes) || [];
  if (teams.length === 0) throw new Error('No Linear teams found in the connected workspace');
  const w = String(wanted || '').trim().toLowerCase();
  if (!w) return teams[0];
  const team = teams.find(t => String(t.key || '').toLowerCase() === w || String(t.name || '').toLowerCase() === w);
  if (!team) throw new Error(`Linear team "${wanted}" was not found`);
  return team;
}

function description(ticket, customer) {
  const lines = [];
  if (ticket.body) lines.push(ticket.body, '');
  const who = [customer.fullName, customer.email ? `<${customer.email}>` : ''].filter(Boolean).join(' ');
  if (who) lines.push(`**Customer:** ${who}${customer.company ? ` (${customer.company})` : ''}`);
  const label = ticket.number ? `FreeScout ticket #${ticket.number}` : 'FreeScout ticket';
  lines.push(ticket.url ? `**${label}:** ${ticket.url}` : `**${label}**`);
  return lines.join('\n');
}

// Pick the team's state for a FreeScout status: closed -> completed ("Done"), open -> started ("In Progress").
async function pickState(teamKey, wantType) {
  const res = await fastn.connector.linear.listWorkflowStates({ first: 250 });
  const all = (res.output && res.output.data && res.output.data.workflowStates && res.output.data.workflowStates.nodes) || [];
  const mine = all.filter(s => s.team && s.team.key === teamKey && s.type === wantType);
  if (!mine.length) return null;
  const preferred = wantType === 'completed' ? 'done' : 'in progress';
  return mine.find(s => String(s.name).toLowerCase() === preferred) || mine.sort((a, b) => a.position - b.position)[0];
}

async function createIssue(ctx, ticket, customer, stateKey, tenant) {
  const settings = await readSettings(ctx);
  const team = await resolveTeam(settings.linearTeam);
  const title = String(ticket.subject || '').trim() || `FreeScout ticket #${ticket.number || ticket.id}`;
  const res = await fastn.connector.linear.createIssue({
    teamId: team.id,
    title: title.slice(0, 255),
    description: description(ticket, customer),
  });
  const payload = res.output && res.output.data && res.output.data.issueCreate;
  if (!payload || !payload.success || !payload.issue) {
    const msg = (res.output && res.output.errors && res.output.errors[0] && res.output.errors[0].message) || 'Linear did not create the issue';
    throw new Error(msg);
  }
  const issue = { id: payload.issue.id, identifier: payload.issue.identifier, url: payload.issue.url, team: team.key };
  await fastn.state.set(stateKey, issue);
  // Reverse link so a change in Linear can find its FreeScout ticket.
  await fastn.state.set(`freescout-linear:${tenant}:issue:${issue.id}`, { ticketId: String(ticket.id), number: ticket.number || null, stateType: 'unknown' });
  // Index of linked issues, read by the Linear-to-FreeScout checker.
  const indexKey = `freescout-linear:${tenant}:index`;
  const index = (await fastn.state.get(indexKey)) || [];
  if (!index.includes(issue.id)) index.push(issue.id);
  await fastn.state.set(indexKey, index.slice(-500));
  return issue;
}

async function comment(issueId, body) {
  const res = await fastn.connector.linear.createComment({ issueId, body });
  const ok = res.output && res.output.data && res.output.data.commentCreate && res.output.data.commentCreate.success;
  if (!ok) throw new Error((res.output && res.output.errors && res.output.errors[0] && res.output.errors[0].message) || 'Linear did not add the comment');
}

export default async function (ctx) {
  const input = ctx.input || {};
  const ticket = input.ticket;
  if (!ticket || typeof ticket !== 'object') throw new Error('ticket is required');
  const customer = input.customer || {};
  const change = input.change || {};
  const event = input.event || 'ticket.created';
  const result = { created: 0, updated: 0, skipped: 0, errors: 0, errorDetails: [], issue: null };

  const tenant = tenantKey(ctx);
  const stateKey = `freescout-linear:${tenant}:${ticket.id}`;
  const linked = await fastn.state.get(stateKey);

  try {
    if (event === 'ticket.created') {
      if (linked) {
        result.skipped = 1;
        result.reason = 'already sent';
        result.issue = linked;
        return result;
      }
      result.issue = await createIssue(ctx, ticket, customer, stateKey, tenant);
      result.created = 1;
      return result;
    }

    if (!linked) {
      result.skipped = 1;
      result.reason = 'no linked Linear issue';
      return result;
    }
    result.issue = linked;
    const by = change.by ? ` by ${change.by}` : '';
    const label = `FreeScout ticket #${ticket.number || ticket.id}`;

    if (event === 'ticket.status_changed') {
      const closed = String(ticket.status || '').toLowerCase() === 'closed';
      const wasClosed = String(change.prevStatus || '').toLowerCase() === 'closed';
      if (!closed && !wasClosed) {
        result.skipped = 1;
        result.reason = 'status change does not affect Linear';
        return result;
      }
      const wantType = closed ? 'completed' : 'started';
      const current = await fastn.connector.linear.getIssue({ id: linked.id });
      const curState = current.output && current.output.data && current.output.data.issue && current.output.data.issue.state;
      if (curState && curState.type === wantType) {
        result.skipped = 1;
        result.reason = 'issue already in that state';
        return result;
      }
      const target = await pickState(linked.team, wantType);
      if (!target) throw new Error(`Team ${linked.team} has no ${wantType} state`);
      const up = await fastn.connector.linear.updateIssue({ id: linked.id, stateId: target.id });
      const ok = up.output && up.output.data && up.output.data.issueUpdate && up.output.data.issueUpdate.success;
      if (!ok) throw new Error((up.output && up.output.errors && up.output.errors[0] && up.output.errors[0].message) || 'Linear did not update the issue');
      const revKey = `freescout-linear:${tenant}:issue:${linked.id}`;
      const rev = (await fastn.state.get(revKey)) || { ticketId: String(ticket.id), number: ticket.number || null };
      rev.stateType = wantType;
      await fastn.state.set(revKey, rev);
      await comment(linked.id, `${label} was ${closed ? 'closed' : 'reopened'} in FreeScout${by}. Moved to **${target.name}**.`);
      result.updated = 1;
      result.state = target.name;
      return result;
    }

    if (event === 'ticket.replied' || event === 'ticket.note_added') {
      const kind = event === 'ticket.note_added' ? 'Internal note' : (change.byCustomer ? 'Customer reply' : 'Agent reply');
      const text = String(change.text || '').trim().slice(0, 4000);
      if (!text) {
        result.skipped = 1;
        result.reason = 'empty message';
        return result;
      }
      await comment(linked.id, `**${kind}${by}** on ${label}:\n\n${text}`);
      result.updated = 1;
      return result;
    }

    if (event === 'ticket.assigned') {
      await comment(linked.id, `${label} was assigned to **${change.assignee || 'nobody'}** in FreeScout${by}.`);
      result.updated = 1;
      return result;
    }

    result.skipped = 1;
    result.reason = `event ${event} is not used by Linear`;
  } catch (e) {
    result.errors = 1;
    result.errorDetails.push({ ticketId: String(ticket.id), errorMessage: String((e && e.message) || e) });
  }
  return result;
}
