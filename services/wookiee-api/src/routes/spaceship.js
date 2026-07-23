/**
 * Faithful port of every Spaceship-related function in
 * inc/theme-settings.php: domain availability, site-title/domain
 * suggestions, registration (contact creation + registration + async
 * polling), nameservers, and DNS records. Endpoint shapes and behavior
 * mirror the PHP originals so the WordPress side only needs to swap which
 * URL it calls, not how it parses the response.
 */

const express = require('express');
const store = require('../secretsStore');

const router = express.Router();

function headers() {
  return {
    'X-Api-Key': store.get('spaceship_api_key'),
    'X-Api-Secret': store.get('spaceship_api_secret'),
  };
}

function hasSpaceshipKeys() {
  return Boolean(store.get('spaceship_api_key').trim() && store.get('spaceship_api_secret').trim());
}

async function checkDomainAvailability(domain) {
  const response = await fetch(`https://spaceship.dev/api/v1/domains/${encodeURIComponent(domain)}/available`, {
    headers: headers(),
  });
  if (!response.ok) {
    throw new Error(`Spaceship API returned HTTP ${response.status}.`);
  }
  const data = await response.json();
  if (!data || !data.result) {
    throw new Error('Could not read the Spaceship response.');
  }
  return data.result === 'available';
}

router.get('/availability', async (req, res) => {
  const domain = String(req.query.domain || '').trim();
  if (!domain) {
    return res.status(400).json({ error: 'No domain specified.' });
  }
  if (!hasSpaceshipKeys()) {
    return res.status(400).json({ error: 'Spaceship API key/secret not configured.' });
  }
  try {
    const available = await checkDomainAvailability(domain);
    res.json({ domain, available });
  } catch (err) {
    res.status(502).json({ error: err.message });
  }
});

// --- Site-title / domain candidate generation -------------------------
// Mirrors wookiee_generate_site_name_candidates()/wookiee_prettify_slug()/
// wookiee_expand_site_name_candidates()/wookiee_suggest_site_name_handler().

const LEGAL_SUFFIXES = new Set([
  'ltd', 'limited', 'llp', 'llc', 'plc', 'inc', 'incorporated',
  'corp', 'corporation', 'group', 'holdings', 'holding', 'co', 'company',
]);

function generateSiteNameCandidates(companyName) {
  const name = String(companyName || '').toLowerCase().trim().replace(/&/g, ' and ');
  const words = name.split(/[^a-z0-9]+/).filter(Boolean);
  let kept = words.filter((w) => !LEGAL_SUFFIXES.has(w));
  if (!kept.length) {
    kept = words.length ? words : ['mystore'];
  }

  let base = kept.join('').replace(/[^a-z0-9]/g, '');
  if (!base) {
    base = 'mystore';
  }
  const firstWordLen = kept[0].replace(/[^a-z0-9]/g, '').length;

  const candidates = [];
  [14, 10, 8].forEach((len) => {
    const slug = base.slice(0, len);
    if (slug.length >= 3 && !candidates.includes(slug)) {
      candidates.push(slug);
    }
  });

  return { candidates: candidates.length ? candidates : [base], firstWordLen };
}

function prettifySlug(slug, firstWordLen) {
  const s = String(slug || '');
  if (firstWordLen > 0 && firstWordLen < s.length) {
    const first = s.slice(0, firstWordLen);
    const rest = s.slice(firstWordLen);
    return `${first.charAt(0).toUpperCase()}${first.slice(1)} ${rest.charAt(0).toUpperCase()}${rest.slice(1)}`;
  }
  return `${s.charAt(0).toUpperCase()}${s.slice(1)}`;
}

function expandSiteNameCandidates(baseCandidates) {
  const expanded = [...baseCandidates];
  baseCandidates.forEach((base) => {
    ['hq', 'shop', 'store', 'co', 'online'].forEach((suffix) => {
      const slug = base + suffix;
      if (!expanded.includes(slug)) {
        expanded.push(slug);
      }
    });
  });
  return expanded.slice(0, 12);
}

router.post('/suggest-site-name', async (req, res) => {
  const companyName = req.body && req.body.company_name;
  if (!companyName || !String(companyName).trim()) {
    return res.status(400).json({ error: 'No company name to work from.' });
  }

  const { candidates: baseCandidates, firstWordLen } = generateSiteNameCandidates(companyName);

  if (!hasSpaceshipKeys()) {
    return res.json({ site_name: prettifySlug(baseCandidates[0], firstWordLen), checked: false, suggestions: null });
  }

  const candidates = expandSiteNameCandidates(baseCandidates);
  const found = { com: [], uk: [] };

  outer:
  for (const slug of candidates) {
    if (found.com.length >= 3 && found.uk.length >= 3) {
      break;
    }
    for (const tld of ['com', 'uk']) {
      if (found[tld].length >= 3) {
        continue;
      }
      let available;
      try {
        available = await checkDomainAvailability(`${slug}.${tld}`);
      } catch (err) {
        return res.json({
          site_name: prettifySlug(baseCandidates[0], firstWordLen),
          checked: false,
          suggestions: null,
          message: err.message,
        });
      }
      if (available) {
        found[tld].push({ domain: `${slug}.${tld}`, slug, site_name: prettifySlug(slug, firstWordLen) });
      }
    }
  }

  const siteName = found.com.length
    ? found.com[0].site_name
    : (found.uk.length ? found.uk[0].site_name : prettifySlug(baseCandidates[0], firstWordLen));

  res.json({ site_name: siteName, checked: true, suggestions: { com: found.com, uk: found.uk } });
});

// --- Registration -------------------------------------------------------

async function createContact(contact) {
  const response = await fetch('https://spaceship.dev/api/v1/contacts', {
    method: 'PUT',
    headers: { ...headers(), 'Content-Type': 'application/json' },
    body: JSON.stringify(contact),
  });
  const data = await response.json().catch(() => null);
  if (!response.ok) {
    const detail = data && data.detail ? data.detail : `HTTP ${response.status}`;
    throw new Error(`Spaceship rejected the contact details: ${detail}`);
  }
  if (!data || !data.contactId) {
    throw new Error('Spaceship did not return a contact ID.');
  }
  return data.contactId;
}

router.post('/register', async (req, res) => {
  if (!hasSpaceshipKeys()) {
    return res.status(400).json({ error: 'Add your Spaceship API key/secret first.' });
  }

  const body = req.body || {};
  const domain = String(body.domain || '').trim();
  const years = Math.max(1, Math.min(10, parseInt(body.years, 10) || 1));
  const autoRenew = Boolean(body.auto_renew);

  const contact = {
    firstName: String(body.first_name || '').trim(),
    lastName: String(body.last_name || '').trim(),
    organization: String(body.organization || '').trim(),
    email: String(body.email || '').trim(),
    address1: String(body.address1 || '').trim(),
    address2: String(body.address2 || '').trim(),
    city: String(body.city || '').trim(),
    stateProvince: String(body.state || '').trim(),
    postalCode: String(body.postal_code || '').trim(),
    country: String(body.country || '').trim().toUpperCase(),
    phone: String(body.phone || '').trim(),
  };

  if (!domain) {
    return res.status(400).json({ error: 'No domain specified.' });
  }
  const required = ['firstName', 'lastName', 'email', 'address1', 'city', 'country', 'phone'];
  if (required.some((f) => !contact[f])) {
    return res.status(400).json({ error: 'Fill in every required registrant field (first/last name, email, address, city, country, phone).' });
  }

  Object.keys(contact).forEach((k) => { if (!contact[k]) delete contact[k]; });

  let contactId;
  try {
    contactId = await createContact(contact);
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }

  let response;
  try {
    response = await fetch(`https://spaceship.dev/api/v1/domains/${encodeURIComponent(domain)}`, {
      method: 'POST',
      headers: { ...headers(), 'Content-Type': 'application/json' },
      body: JSON.stringify({
        autoRenew,
        years,
        privacyProtection: { level: 'high', userConsent: true },
        contacts: { registrant: contactId, admin: contactId, tech: contactId, billing: contactId },
      }),
    });
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }

  if (response.status !== 202) {
    const data = await response.json().catch(() => null);
    const detail = data && data.detail ? data.detail : `HTTP ${response.status}`;
    return res.status(502).json({ error: `Spaceship declined the registration: ${detail}` });
  }

  const operationId = response.headers.get('spaceship-async-operationid');
  if (!operationId) {
    return res.status(502).json({ error: 'Spaceship accepted the request but returned no operation ID to track it.' });
  }

  res.json({ operation_id: operationId });
});

router.get('/operations/:operationId', async (req, res) => {
  const { operationId } = req.params;
  let response;
  try {
    response = await fetch(`https://spaceship.dev/api/v1/async-operations/${encodeURIComponent(operationId)}`, {
      headers: headers(),
    });
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }
  const data = await response.json().catch(() => null);
  if (!data || !data.status) {
    return res.status(502).json({ error: 'Could not read the operation status.' });
  }
  res.json({ status: data.status, details: data.details || '' });
});

router.put('/:domain/nameservers', async (req, res) => {
  const { domain } = req.params;
  const hostsRaw = req.body && req.body.hosts ? String(req.body.hosts) : '';
  const hosts = hostsRaw.split(/[\r\n,]+/).map((h) => h.trim()).filter(Boolean);

  if (hosts.length < 2 || hosts.length > 12) {
    return res.status(400).json({ error: 'Provide between 2 and 12 nameservers.' });
  }

  let response;
  try {
    response = await fetch(`https://spaceship.dev/api/v1/domains/${encodeURIComponent(domain)}/nameservers`, {
      method: 'PUT',
      headers: { ...headers(), 'Content-Type': 'application/json' },
      body: JSON.stringify({ provider: 'custom', hosts }),
    });
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }
  if (![200, 202].includes(response.status)) {
    const data = await response.json().catch(() => null);
    const detail = data && data.detail ? data.detail : `HTTP ${response.status}`;
    return res.status(502).json({ error: `Nameserver update was rejected: ${detail}` });
  }
  res.json({ success: true });
});

const VALID_DNS_TYPES = new Set(['A', 'AAAA', 'ALIAS', 'CNAME', 'HTTPS', 'MX', 'NS', 'PTR', 'SRV', 'SVCB', 'TXT']);

router.put('/:domain/dns-records', async (req, res) => {
  const { domain } = req.params;
  const records = Array.isArray(req.body && req.body.records) ? req.body.records : [];

  const items = records
    .map((r) => {
      const type = String(r.type || '').toUpperCase();
      const address = String(r.address || '').trim();
      if (!VALID_DNS_TYPES.has(type) || !address) {
        return null;
      }
      const item = {
        type,
        name: String(r.name || '@').trim() || '@',
        address,
        ttl: Math.max(60, parseInt(r.ttl, 10) || 3600),
      };
      if (type === 'MX' && r.priority) {
        item.priority = parseInt(r.priority, 10);
      }
      return item;
    })
    .filter(Boolean);

  if (!items.length) {
    return res.status(400).json({ error: 'No valid DNS records to add.' });
  }

  let response;
  try {
    response = await fetch(`https://spaceship.dev/api/v1/dns/records/${encodeURIComponent(domain)}`, {
      method: 'PUT',
      headers: { ...headers(), 'Content-Type': 'application/json' },
      body: JSON.stringify({ force: false, items }),
    });
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }
  if (![200, 202].includes(response.status)) {
    const data = await response.json().catch(() => null);
    const detail = data && data.detail ? data.detail : `HTTP ${response.status}`;
    return res.status(502).json({ error: `DNS records were rejected: ${detail}` });
  }
  res.json({ success: true });
});

module.exports = router;
