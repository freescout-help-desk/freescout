// FreeScout new ticket -> HubSpot contact (create or update by email).
// Settings (widget form, per help desk): createContacts (default on).

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

function isOff(v) {
  return v === false || v === 'false' || v === 0 || v === '0';
}

export default async function (ctx) {
  const input = ctx.input || {};
  const customer = input.customer;
  if (!customer || typeof customer !== 'object') throw new Error('customer is required');
  const result = { created: 0, updated: 0, skipped: 0, errors: 0, errorDetails: [], contactId: null };
  const event = input.event || 'ticket.created';
  if (event !== 'ticket.created' && event !== 'customer.updated') {
    result.skipped = 1;
    result.reason = 'event not used by HubSpot';
    return result;
  }

  const email = String(customer.email || '').trim().toLowerCase();
  if (!email) {
    result.skipped = 1;
    result.reason = 'no customer email';
    return result;
  }

  // Only send fields that have a value, so existing HubSpot values are never blanked.
  const props = { email };
  if (customer.firstName) props.firstname = customer.firstName;
  if (customer.lastName) props.lastname = customer.lastName;
  if (customer.company) props.company = customer.company;
  if (customer.jobTitle) props.jobtitle = customer.jobTitle;
  if (customer.phone) props.phone = customer.phone;

  try {
    const s = await fastn.connector.hubspot.searchContacts({
      limit: 1,
      properties: ['email'],
      filterGroups: [{ filters: [{ propertyName: 'email', operator: 'EQ', value: email }] }],
    });
    const found = s.output && s.output.results && s.output.results[0];
    if (found) {
      await fastn.connector.hubspot.updateContact({ contactId: String(found.id), properties: props });
      result.updated = 1;
      result.contactId = String(found.id);
      return result;
    }
    if (isOff((await readSettings(ctx)).createContacts)) {
      result.skipped = 1;
      result.reason = 'contact not in HubSpot and creating is off';
      return result;
    }
    const c = await fastn.connector.hubspot.createContact({ properties: props });
    const id = c.output && c.output.id;
    if (!id) throw new Error('HubSpot did not return a contact id');
    result.created = 1;
    result.contactId = String(id);
  } catch (e) {
    result.errors = 1;
    result.errorDetails.push({ email, errorMessage: String((e && e.message) || e) });
  }
  return result;
}
