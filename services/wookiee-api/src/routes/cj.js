/**
 * Faithful port of only the credential-holding half of inc/supplier-cj.php
 * (wookiee_cj_get_access_token/wookiee_cj_auth_request/wookiee_cj_request)
 * - authenticates with CJ Dropshipping and proxies one generic
 * authenticated request per call. Everything else in that file (catalog
 * search parsing, the AI GMC-compliance cleanup, image handling,
 * WooCommerce draft creation, order fulfillment) stays in WordPress
 * untouched - none of it needs credentials, only wookiee_cj_request()
 * (the thing this replaces the innards of) does.
 *
 * NOT yet exercised against a live CJ account, same caveat as the
 * original PHP implementation this was ported from - the exact
 * response field names (accessToken, accessTokenExpiryDate, etc.) are
 * CJ's documented Open API v2 shape, unverified against a real call.
 */

const express = require('express');
const store = require('../secretsStore');
const tokenCache = require('../cjTokenCache');

const router = express.Router();

const CJ_BASE = 'https://developers.cjdropshipping.com/api2.0/v1';

async function cjAuthRequest(path, body) {
  const response = await fetch(CJ_BASE + path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = await response.json().catch(() => null);
  if (!data) {
    throw new Error('CJ Dropshipping returned an unreadable response.');
  }
  return data;
}

async function getAccessToken() {
  const cache = tokenCache.get();
  const now = Date.now();
  const fiveMinutes = 5 * 60 * 1000;

  const accessExpiryMs = cache.accessTokenExpiry ? new Date(cache.accessTokenExpiry).getTime() : NaN;
  if (cache.accessToken && !Number.isNaN(accessExpiryMs) && accessExpiryMs > now + fiveMinutes) {
    return cache.accessToken;
  }

  let result;
  const refreshExpiryMs = cache.refreshTokenExpiry ? new Date(cache.refreshTokenExpiry).getTime() : NaN;
  if (cache.refreshToken && !Number.isNaN(refreshExpiryMs) && refreshExpiryMs > now + fiveMinutes) {
    result = await cjAuthRequest('/authentication/refreshAccessToken', { refreshToken: cache.refreshToken });
  } else {
    const email = store.get('cj_email');
    const apiKey = store.get('cj_api_key');
    if (!email.trim() || !apiKey.trim()) {
      throw new Error('Add the CJ Dropshipping email and API key first.');
    }
    result = await cjAuthRequest('/authentication/getAccessToken', { email, password: apiKey });
  }

  const data = result.data || {};
  if (!data.accessToken) {
    throw new Error(result.message || 'CJ Dropshipping authentication failed - check the email/API key.');
  }

  const update = {
    accessToken: data.accessToken,
    accessTokenExpiry: data.accessTokenExpiryDate || new Date(now + 24 * 60 * 60 * 1000).toISOString(),
  };
  if (data.refreshToken) {
    update.refreshToken = data.refreshToken;
    update.refreshTokenExpiry = data.refreshTokenExpiryDate || new Date(now + 14 * 24 * 60 * 60 * 1000).toISOString();
  }
  tokenCache.set(update);

  return data.accessToken;
}

async function cjRequest(method, cjPath, body) {
  const token = await getAccessToken();

  const options = {
    method,
    headers: { 'CJ-Access-Token': token, 'Content-Type': 'application/json' },
  };
  if (body !== null && body !== undefined) {
    options.body = JSON.stringify(body);
  }

  const response = await fetch(CJ_BASE + cjPath, options);
  const data = await response.json().catch(() => null);

  if (response.status === 401) {
    tokenCache.clearAccessToken();
    throw new Error('CJ Dropshipping rejected the access token. Try again.');
  }
  if (!response.ok) {
    const msg = data && data.message ? data.message : `HTTP ${response.status}`;
    throw new Error(`CJ Dropshipping error: ${msg}`);
  }

  return data || {};
}

router.post('/request', async (req, res) => {
  const { method, path: cjPath, body } = req.body || {};
  if (!method || !cjPath) {
    return res.status(400).json({ error: 'method and path are required.' });
  }
  try {
    const data = await cjRequest(method, cjPath, body);
    res.json(data);
  } catch (err) {
    res.status(502).json({ error: err.message });
  }
});

module.exports = router;
