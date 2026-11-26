// FreeScout ticket events -> Google Sheets (one row per ticket, kept up to date).
// ticket.created: adds the row. Any later event: rewrites that ticket's row.
// Settings (widget form, per help desk): spreadsheet (URL or id, optional), sheetTab (optional).

const HEADER = ['Ticket #', 'Subject', 'Status', 'Mailbox', 'Customer name', 'Customer email', 'Company', 'Created at', 'Link', 'Last activity', 'Updated at'];

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

function spreadsheetIdFrom(value) {
  const v = String(value || '').trim();
  if (!v) return '';
  const m = v.match(/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/);
  return m ? m[1] : v;
}

function quoteTab(tab) {
  return `'${String(tab).replace(/'/g, "''")}'`;
}

function activity(event, change) {
  const by = change.by ? ` by ${change.by}` : '';
  const text = String(change.text || '').replace(/\s+/g, ' ').trim().slice(0, 140);
  switch (event) {
    case 'ticket.created': return 'Ticket created';
    case 'ticket.status_changed': return `Status changed from ${change.prevStatus || '?'}${by}`;
    case 'ticket.replied': return `${change.byCustomer ? 'Customer' : 'Agent'} reply${by}: ${text}`;
    case 'ticket.note_added': return `Internal note${by}: ${text}`;
    case 'ticket.assigned': return `Assigned to ${change.assignee || 'nobody'}${by}`;
    default: return event;
  }
}

function rowValues(ticket, customer, event, change) {
  return [
    String(ticket.number || ''),
    String(ticket.subject || ''),
    String(ticket.status || ''),
    String(ticket.mailbox || ''),
    String(customer.fullName || customer.email || ''),
    String(customer.email || ''),
    String(customer.company || ''),
    String(ticket.createdAt || ''),
    String(ticket.url || ''),
    activity(event, change),
    String(ticket.updatedAt || new Date().toISOString()),
  ];
}

async function target(ctx, tenant) {
  const settings = await readSettings(ctx);
  let spreadsheetId = spreadsheetIdFrom(settings.spreadsheet);
  let tab = String(settings.sheetTab || '').trim();
  let created = false;
  if (!spreadsheetId) {
    const sheetKey = `freescout-sheets:${tenant}:spreadsheet`;
    const saved = await fastn.state.get(sheetKey);
    if (saved && saved.id) {
      spreadsheetId = saved.id;
      tab = tab || saved.tab;
    } else {
      const c = await fastn.connector.googleSheets.createSpreadsheet({ title: 'FreeScout Tickets' });
      spreadsheetId = c.output.spreadsheetId;
      tab = tab || (c.output.sheets && c.output.sheets[0] && c.output.sheets[0].properties.title) || 'Sheet1';
      await fastn.state.set(sheetKey, { id: spreadsheetId, tab, url: c.output.spreadsheetUrl });
      created = true;
    }
  }
  return { spreadsheetId, tab, created };
}

// Make sure row 1 has the header. Empty sheet: write it all. Older 9-column header: add the 2 new columns.
async function ensureHeader(spreadsheetId, tab) {
  const prefix = tab ? `${quoteTab(tab)}!` : '';
  const first = await fastn.connector.googleSheets.getValues({ spreadsheetId, range: `${prefix}A1:K1` });
  const row = (first.output && first.output.values && first.output.values[0]) || [];
  const hasAny = row.some(c => String(c || '').trim() !== '');
  if (!hasAny) return true; // caller writes the header with the first row
  if (!String(row[9] || '').trim() && String(row[0] || '').trim() === 'Ticket #') {
    await fastn.connector.googleSheets.updateValues({ spreadsheetId, range: `${prefix}J1:K1`, valueInputOption: 'RAW', values: [HEADER.slice(9)] });
  }
  return false;
}

async function appendRow(spreadsheetId, tab, values, needsHeader) {
  const range = tab ? `${quoteTab(tab)}!A1` : 'A1';
  const rows = needsHeader ? [HEADER, values] : [values];
  const ap = await fastn.connector.googleSheets.appendValues({ spreadsheetId, range, valueInputOption: 'RAW', insertDataOption: 'INSERT_ROWS', values: rows });
  if (!(ap.output && ap.output.updates && ap.output.updates.updatedRows >= 1)) throw new Error('Google Sheets did not add the row');
  return ap.output.updates.updatedRange;
}

// "Sheet1!A3:I3" or "'My Tab'!A1:K2" -> { sheet: "Sheet1", row: 3 }
function rowFromRange(range) {
  const m = String(range || '').match(/^(.*)!([A-Z]+)(\d+)(?::([A-Z]+)(\d+))?$/);
  if (!m) return null;
  return { sheet: m[1], row: Number(m[5] || m[3]) };
}

export default async function (ctx) {
  const input = ctx.input || {};
  const ticket = input.ticket;
  if (!ticket || typeof ticket !== 'object') throw new Error('ticket is required');
  const customer = input.customer || {};
  const change = input.change || {};
  const event = input.event || 'ticket.created';
  const result = { created: 0, updated: 0, skipped: 0, errors: 0, errorDetails: [], spreadsheetId: null };

  const tenant = tenantKey(ctx);
  const sentKey = `freescout-sheets:${tenant}:sent:${ticket.id}`;
  const sent = await fastn.state.get(sentKey);

  if (event === 'ticket.created' && sent) {
    result.skipped = 1;
    result.reason = 'already sent';
    return result;
  }

  try {
    const values = rowValues(ticket, customer, event, change);

    // Update the existing row.
    if (sent && sent.spreadsheetId) {
      const pos = rowFromRange(sent.range);
      if (pos && pos.row > 1) {
        await ensureHeader(sent.spreadsheetId, pos.sheet.replace(/^'|'$/g, '').replace(/''/g, "'"));
        const range = `${pos.sheet}!A${pos.row}:K${pos.row}`;
        const up = await fastn.connector.googleSheets.updateValues({ spreadsheetId: sent.spreadsheetId, range, valueInputOption: 'RAW', values: [values] });
        if (!(up.output && up.output.updatedRows >= 1)) throw new Error('Google Sheets did not update the row');
        result.updated = 1;
        result.spreadsheetId = sent.spreadsheetId;
        result.updatedRange = up.output.updatedRange || range;
        return result;
      }
    }

    // New ticket, or an update for a ticket that has no row yet: add the row.
    const t = await target(ctx, tenant);
    const needsHeader = t.created ? true : await ensureHeader(t.spreadsheetId, t.tab);
    const updatedRange = await appendRow(t.spreadsheetId, t.tab, values, needsHeader);
    await fastn.state.set(sentKey, { spreadsheetId: t.spreadsheetId, range: updatedRange });
    result.created = 1;
    result.spreadsheetId = t.spreadsheetId;
    result.updatedRange = updatedRange;
  } catch (e) {
    result.errors = 1;
    result.errorDetails.push({ ticketId: String(ticket.id), errorMessage: String((e && e.message) || e) });
  }
  return result;
}
