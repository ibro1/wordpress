/**
 * Caches the CJ Dropshipping access/refresh token pair - regenerable
 * runtime state, not an operator-entered credential, so this is a plain
 * unencrypted JSON file (matching licenseStore.js's reasoning) rather
 * than going through secretsStore.js's encrypted store.
 */

const fs = require('fs');
const path = require('path');

const DATA_DIR = process.env.DATA_DIR || path.join(__dirname, '..', 'data');
const CACHE_FILE = path.join(DATA_DIR, 'cj-token-cache.json');

function read() {
  if (!fs.existsSync(CACHE_FILE)) {
    return {};
  }
  return JSON.parse(fs.readFileSync(CACHE_FILE, 'utf8'));
}

function write(data) {
  fs.mkdirSync(DATA_DIR, { recursive: true });
  fs.writeFileSync(CACHE_FILE, JSON.stringify(data, null, 2));
}

function get() {
  return read();
}

function set(partial) {
  const current = read();
  write({ ...current, ...partial });
}

function clearAccessToken() {
  const current = read();
  delete current.accessToken;
  delete current.accessTokenExpiry;
  write(current);
}

module.exports = { get, set, clearAccessToken };
