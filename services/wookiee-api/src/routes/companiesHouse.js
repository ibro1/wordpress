/**
 * Faithful port of wookiee_ch_lookup_handler() and wookiee_ch_search_handler()
 * (inc/theme-settings.php) - Companies House number lookup and name search.
 */

const express = require('express');
const store = require('../secretsStore');

const router = express.Router();

function authHeader(apiKey) {
  return `Basic ${Buffer.from(`${apiKey}:`).toString('base64')}`;
}

router.get('/lookup', async (req, res) => {
  const companyNumber = String(req.query.company_number || '').trim();
  const apiKey = store.get('companies_house_api_key');

  if (!companyNumber) {
    return res.status(400).json({ error: 'Enter a company number first.' });
  }
  if (!apiKey.trim()) {
    return res.status(400).json({ error: 'Add your Companies House API key first.' });
  }

  let response;
  try {
    response = await fetch(
      `https://api.company-information.service.gov.uk/company/${encodeURIComponent(companyNumber)}`,
      { headers: { Authorization: authHeader(apiKey) } },
    );
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }

  if (response.status === 404) {
    return res.status(404).json({ error: 'No company found with that number.' });
  }
  if (response.status === 401) {
    return res.status(401).json({ error: 'Companies House rejected the API key - check it and try again.' });
  }
  if (!response.ok) {
    return res.status(502).json({ error: `Companies House returned an unexpected error (HTTP ${response.status}).` });
  }

  const data = await response.json();
  const addr = data.registered_office_address || {};
  const lines = [addr.premises, addr.address_line_1, addr.address_line_2, addr.locality, addr.region, addr.postal_code, addr.country]
    .filter(Boolean);

  res.json({
    company_name: data.company_name || '',
    company_status: data.company_status || '',
    address: lines.join('\n'),
  });
});

router.get('/search', async (req, res) => {
  const query = String(req.query.query || '').trim();
  const apiKey = store.get('companies_house_api_key');

  if (!query) {
    return res.status(400).json({ error: 'Enter a company name first.' });
  }
  if (!apiKey.trim()) {
    return res.status(400).json({ error: 'Add your Companies House API key first.' });
  }

  let response;
  try {
    response = await fetch(
      `https://api.company-information.service.gov.uk/search/companies?q=${encodeURIComponent(query)}&items_per_page=20`,
      { headers: { Authorization: authHeader(apiKey) } },
    );
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }

  if (response.status === 401) {
    return res.status(401).json({ error: 'Companies House rejected the API key - check it and try again.' });
  }
  if (!response.ok) {
    return res.status(502).json({ error: `Companies House returned an unexpected error (HTTP ${response.status}).` });
  }

  const data = await response.json();
  const items = Array.isArray(data.items) ? data.items : [];
  const active = items
    .filter((item) => item.company_status === 'active')
    .map((item) => ({
      company_number: item.company_number || '',
      title: item.title || '',
      address: item.address_snippet || '',
    }))
    .slice(0, 10);

  if (!active.length) {
    return res.status(404).json({ error: `No active companies found matching "${query}".` });
  }

  res.json({ results: active });
});

module.exports = router;
