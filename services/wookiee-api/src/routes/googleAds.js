/**
 * Faithful port of inc/keyword-research.php's Google Ads integration - the
 * OAuth "Connect to Google Ads" flow (previously admin-post.php-based,
 * tied to a WP nonce/session) and the keyword-ideas call. The nonce-based
 * CSRF state is replaced with a signed, time-limited state token (HMAC over
 * a timestamp) since this service has no user sessions to hang a nonce off.
 *
 * IMPORTANT: the redirect URI this computes (https://<host>/google-ads/
 * oauth/callback) must be registered on a Google OAuth Client of type "Web
 * application" in Google Cloud Console. A "Desktop" app client (the type
 * used by one-off local scripts, which only allows http://localhost
 * redirect URIs) will NOT work here - if the existing Client ID was created
 * as a Desktop app, create a new Web application OAuth client instead and
 * point these settings at that one's Client ID/Secret.
 */

const express = require('express');
const crypto = require('crypto');
const store = require('../secretsStore');

const router = express.Router();

const GOOGLE_ADS_API_VERSION = 'v17';
const STATE_MAX_AGE_MS = 10 * 60 * 1000;

let accessTokenCache = null; // { token, expiresAt }

function stateSecret() {
  return process.env.ADMIN_PASSWORD || process.env.API_SHARED_SECRET || 'wookiee-api-fallback-state-secret';
}

function signState() {
  const timestamp = Date.now().toString();
  const hmac = crypto.createHmac('sha256', stateSecret()).update(timestamp).digest('hex');
  return `${timestamp}.${hmac}`;
}

function verifyState(state) {
  const [timestamp, hmac] = String(state || '').split('.');
  if (!timestamp || !hmac) {
    return false;
  }
  const expected = crypto.createHmac('sha256', stateSecret()).update(timestamp).digest('hex');
  const expectedBuf = Buffer.from(expected);
  const givenBuf = Buffer.from(hmac);
  if (expectedBuf.length !== givenBuf.length || !crypto.timingSafeEqual(expectedBuf, givenBuf)) {
    return false;
  }
  const age = Date.now() - parseInt(timestamp, 10);
  return age >= 0 && age < STATE_MAX_AGE_MS;
}

function redirectUri(req) {
  return `${req.protocol}://${req.get('host')}/google-ads/oauth/callback`;
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function resultPage(title, message) {
  return `<!doctype html><html><body style="font-family:sans-serif;max-width:520px;margin:60px auto;">
  <h2>${escapeHtml(title)}</h2><p>${escapeHtml(message)}</p></body></html>`;
}

function isConfigured() {
  return Boolean(
    store.get('google_ads_developer_token').trim()
    && store.get('google_ads_client_id').trim()
    && store.get('google_ads_client_secret').trim()
    && store.get('google_ads_refresh_token').trim()
    && store.get('google_ads_customer_id').trim(),
  );
}

router.get('/status', (req, res) => {
  res.json({
    configured: isConfigured(),
    has_client_credentials: Boolean(store.get('google_ads_client_id').trim() && store.get('google_ads_client_secret').trim()),
    has_refresh_token: Boolean(store.get('google_ads_refresh_token').trim()),
  });
});

router.get('/oauth/start', (req, res) => {
  const clientId = store.get('google_ads_client_id');
  if (!clientId.trim()) {
    return res.status(400).send(resultPage('Cannot connect yet', 'Add your Google Ads Client ID and Client Secret in Settings first, then try again.'));
  }
  const params = new URLSearchParams({
    client_id: clientId,
    redirect_uri: redirectUri(req),
    response_type: 'code',
    scope: 'https://www.googleapis.com/auth/adwords',
    access_type: 'offline',
    prompt: 'consent',
    state: signState(),
  });
  res.redirect(`https://accounts.google.com/o/oauth2/v2/auth?${params.toString()}`);
});

router.get('/oauth/callback', async (req, res) => {
  if (req.query.error) {
    return res.status(400).send(resultPage('Google Ads connection failed', String(req.query.error)));
  }
  if (!verifyState(req.query.state)) {
    return res.status(400).send(resultPage('Google Ads connection failed', 'Invalid or expired request - start the connection again.'));
  }
  const code = String(req.query.code || '');
  if (!code) {
    return res.status(400).send(resultPage('Google Ads connection failed', 'Google did not send back an authorization code.'));
  }

  let response;
  try {
    response = await fetch('https://oauth2.googleapis.com/token', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        code,
        client_id: store.get('google_ads_client_id'),
        client_secret: store.get('google_ads_client_secret'),
        redirect_uri: redirectUri(req),
        grant_type: 'authorization_code',
      }),
    });
  } catch (err) {
    return res.status(502).send(resultPage('Google Ads connection failed', err.message));
  }

  const data = await response.json().catch(() => null);
  if (!data || !data.refresh_token) {
    const msg = data && data.error_description ? data.error_description : 'Google did not return a refresh token.';
    return res.status(502).send(resultPage('Google Ads connection failed', msg));
  }

  store.setMany({ google_ads_refresh_token: data.refresh_token });
  accessTokenCache = null;

  res.send(resultPage('Connected', 'Your Google Ads refresh token was saved. You can close this tab.'));
});

async function getAccessToken() {
  if (accessTokenCache && accessTokenCache.expiresAt > Date.now()) {
    return accessTokenCache.token;
  }
  const response = await fetch('https://oauth2.googleapis.com/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      client_id: store.get('google_ads_client_id'),
      client_secret: store.get('google_ads_client_secret'),
      refresh_token: store.get('google_ads_refresh_token'),
      grant_type: 'refresh_token',
    }),
  });
  const data = await response.json().catch(() => null);
  if (!response.ok || !data || !data.access_token) {
    const msg = data && data.error_description ? data.error_description : `HTTP ${response.status}`;
    throw new Error(`Google Ads authentication failed: ${msg}`);
  }
  const expiresIn = data.expires_in ? parseInt(data.expires_in, 10) : 3600;
  accessTokenCache = { token: data.access_token, expiresAt: Date.now() + Math.max(60, expiresIn - 300) * 1000 };
  return data.access_token;
}

router.post('/keyword-ideas', async (req, res) => {
  if (!isConfigured()) {
    return res.status(400).json({ error: 'Add your Google Ads API credentials first.' });
  }

  const seedKeywords = Array.isArray(req.body && req.body.seed_keywords)
    ? req.body.seed_keywords.filter(Boolean)
    : [];

  let accessToken;
  try {
    accessToken = await getAccessToken();
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }

  const customerId = store.get('google_ads_customer_id').replace(/[^0-9]/g, '');
  const loginId = store.get('google_ads_login_customer_id').replace(/[^0-9]/g, '');

  const headers = {
    Authorization: `Bearer ${accessToken}`,
    'developer-token': store.get('google_ads_developer_token'),
    'Content-Type': 'application/json',
  };
  if (loginId) {
    headers['login-customer-id'] = loginId;
  }

  let response;
  try {
    response = await fetch(`https://googleads.googleapis.com/${GOOGLE_ADS_API_VERSION}/customers/${customerId}:generateKeywordIdeas`, {
      method: 'POST',
      headers,
      body: JSON.stringify({
        keywordSeed: { keywords: seedKeywords },
        geoTargetConstants: ['geoTargetConstants/2826'], // United Kingdom
        language: 'languageConstants/1000', // English
        keywordPlanNetwork: 'GOOGLE_SEARCH',
      }),
    });
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }

  const data = await response.json().catch(() => null);
  if (!response.ok) {
    const msg = data && data.error && data.error.message ? data.error.message : `HTTP ${response.status}`;
    return res.status(502).json({ error: `Google Ads API error: ${msg}` });
  }

  const results = Array.isArray(data.results) ? data.results : [];
  const ideas = results.map((result) => {
    const metrics = result.keywordIdeaMetrics || {};
    return {
      keyword: result.text || '',
      avg_monthly_searches: parseInt(metrics.avgMonthlySearches, 10) || 0,
      competition: metrics.competition || 'UNKNOWN',
      low_cpc_gbp: metrics.lowTopOfPageBidMicros ? Math.round((metrics.lowTopOfPageBidMicros / 1000000) * 100) / 100 : null,
      high_cpc_gbp: metrics.highTopOfPageBidMicros ? Math.round((metrics.highTopOfPageBidMicros / 1000000) * 100) / 100 : null,
    };
  });
  ideas.sort((a, b) => b.avg_monthly_searches - a.avg_monthly_searches);

  res.json({ ideas });
});

module.exports = router;
