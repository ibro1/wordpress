/**
 * Two ways in, both backed by the same shared secrets, no sessions/DB:
 *  - `X-Api-Key: <API_SHARED_SECRET>` header, for WordPress's own
 *    server-to-server calls (easy to send from wp_remote_get()).
 *  - HTTP Basic Auth (any username, password = ADMIN_PASSWORD), for a human
 *    opening this in a browser - the browser's native login prompt is
 *    enough, no login page/session cookie needed for a small internal tool.
 *
 * Every route in this service is private by default; nothing is reachable
 * without one of these two credentials, including the Google OAuth start
 * link (a browser navigating there already has the Basic Auth prompt).
 */

const crypto = require('crypto');

function timingSafeEqualStr(a, b) {
  const bufA = Buffer.from(String(a));
  const bufB = Buffer.from(String(b));
  if (bufA.length !== bufB.length) {
    return false;
  }
  return crypto.timingSafeEqual(bufA, bufB);
}

function requireAuth(req, res, next) {
  const sharedSecret = process.env.API_SHARED_SECRET || '';
  const adminPassword = process.env.ADMIN_PASSWORD || '';

  const apiKeyHeader = req.get('X-Api-Key') || '';
  if (sharedSecret && apiKeyHeader && timingSafeEqualStr(apiKeyHeader, sharedSecret)) {
    return next();
  }

  const authHeader = req.get('Authorization') || '';
  if (authHeader.startsWith('Basic ')) {
    const decoded = Buffer.from(authHeader.slice(6), 'base64').toString('utf8');
    const separatorIndex = decoded.indexOf(':');
    const password = separatorIndex >= 0 ? decoded.slice(separatorIndex + 1) : '';
    if (adminPassword && timingSafeEqualStr(password, adminPassword)) {
      return next();
    }
  }

  res.set('WWW-Authenticate', 'Basic realm="Wookiee API"');
  return res.status(401).json({ error: 'Unauthorized' });
}

module.exports = { requireAuth };
