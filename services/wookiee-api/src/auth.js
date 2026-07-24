/**
 * Two tiers:
 *  - requireAdminAuth: master key (API_SHARED_SECRET env var) or HTTP Basic
 *    Auth (ADMIN_PASSWORD) - the operator only. Guards settings, license
 *    generation/listing/revocation, and the static settings UI. A
 *    customer's activation code must NEVER pass this check (that would let
 *    any activated site list/revoke every other site's license).
 *  - requireApiAuth: same admin credentials still work (for the operator's
 *    own scripts/testing), OR a license code that's already been activated
 *    for the exact domain making the request (X-Api-Key + X-Site-Domain
 *    headers) - this is what a real WordPress install authenticates with
 *    day to day. Guards the actual feature endpoints (Companies House,
 *    LLM, domains, Google Ads).
 *
 * POST /licenses/activate is intentionally NOT behind either of these -
 * it's wired into index.js before both middlewares run, since presenting
 * zero prior credentials is the entire point of activating a new code.
 */

const crypto = require('crypto');
const licenseStore = require('./licenseStore');

function timingSafeEqualStr(a, b) {
  const bufA = Buffer.from(String(a));
  const bufB = Buffer.from(String(b));
  if (bufA.length !== bufB.length) {
    return false;
  }
  return crypto.timingSafeEqual(bufA, bufB);
}

function isMasterOrAdmin(req) {
  const sharedSecret = process.env.API_SHARED_SECRET || '';
  const adminPassword = process.env.ADMIN_PASSWORD || '';

  const apiKeyHeader = req.get('X-Api-Key') || '';
  if (sharedSecret && apiKeyHeader && timingSafeEqualStr(apiKeyHeader, sharedSecret)) {
    return true;
  }

  const authHeader = req.get('Authorization') || '';
  if (authHeader.startsWith('Basic ')) {
    const decoded = Buffer.from(authHeader.slice(6), 'base64').toString('utf8');
    const separatorIndex = decoded.indexOf(':');
    const password = separatorIndex >= 0 ? decoded.slice(separatorIndex + 1) : '';
    if (adminPassword && timingSafeEqualStr(password, adminPassword)) {
      return true;
    }
  }
  return false;
}

function requireAdminAuth(req, res, next) {
  if (isMasterOrAdmin(req)) {
    return next();
  }
  res.set('WWW-Authenticate', 'Basic realm="Wookiee API"');
  return res.status(401).json({ error: 'Unauthorized' });
}

function requireApiAuth(req, res, next) {
  if (isMasterOrAdmin(req)) {
    return next();
  }

  const code = (req.get('X-Api-Key') || '').trim().toUpperCase();
  const domain = (req.get('X-Site-Domain') || '').trim().toLowerCase();
  if (code && domain && licenseStore.isActivatedForDomain(code, domain)) {
    return next();
  }

  res.set('WWW-Authenticate', 'Basic realm="Wookiee API"');
  return res.status(401).json({ error: 'Unauthorized - this site has not activated a valid code.' });
}

module.exports = { requireAdminAuth, requireApiAuth };
