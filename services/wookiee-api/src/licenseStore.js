/**
 * License/activation codes - lets one backend serve many WordPress
 * installs of the Wookiee Decor theme without sharing a single static
 * secret across all of them. Same pattern as most commercial WP plugin
 * licensing (Easy Digital Downloads Software Licensing, WooCommerce's
 * Software Add-on, etc.): a code has a max activation count, gets bound
 * to specific site domains as it's activated, and every non-activation
 * request has to present a code that's already activated for the
 * domain making the request.
 *
 * Plain JSON, not encrypted (unlike secretsStore.js) - these codes are
 * bearer credentials same as API keys, but the sensitivity profile is
 * different: a license system's security boundary is "was this code
 * validated and domain-bound", not "is the file unreadable at rest",
 * and every real license-key system (including the ones cited above)
 * stores this in a plain DB table for the same reason.
 */

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const DATA_DIR = process.env.DATA_DIR || path.join(__dirname, '..', 'data');
const LICENSES_FILE = path.join(DATA_DIR, 'licenses.json');

// Excludes visually-ambiguous characters (0/O, 1/I/L) - these get read
// back and typed in by a human.
const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

function generateCode() {
  const groups = [];
  for (let g = 0; g < 4; g++) {
    let group = '';
    const bytes = crypto.randomBytes(4);
    for (let i = 0; i < 4; i++) {
      group += CODE_ALPHABET[bytes[i] % CODE_ALPHABET.length];
    }
    groups.push(group);
  }
  return `WOOK-${groups.join('-')}`;
}

function readAll() {
  if (!fs.existsSync(LICENSES_FILE)) {
    return {};
  }
  return JSON.parse(fs.readFileSync(LICENSES_FILE, 'utf8'));
}

function writeAll(codes) {
  fs.mkdirSync(DATA_DIR, { recursive: true });
  fs.writeFileSync(LICENSES_FILE, JSON.stringify(codes, null, 2));
}

function list() {
  return readAll();
}

function create({ maxActivations = 1, label = '' } = {}) {
  const codes = readAll();
  let code;
  do {
    code = generateCode();
  } while (codes[code]); // astronomically unlikely, but don't silently collide

  codes[code] = {
    max_activations: Math.max(1, parseInt(maxActivations, 10) || 1),
    label: String(label || ''),
    active: true,
    created_at: new Date().toISOString(),
    activations: [],
  };
  writeAll(codes);
  return { code, ...codes[code] };
}

function revoke(code) {
  const codes = readAll();
  if (!codes[code]) {
    return false;
  }
  codes[code].active = false;
  writeAll(codes);
  return true;
}

/**
 * The only place activations are consumed. Idempotent for a domain
 * that's already activated this exact code (re-saving the same
 * activation code in WordPress shouldn't count as a second site).
 */
function activate(code, domain) {
  const codes = readAll();
  const entry = codes[code];

  if (!entry || !entry.active) {
    return { ok: false, error: 'Invalid activation code.' };
  }

  const already = entry.activations.find((a) => a.domain === domain);
  if (already) {
    return { ok: true };
  }

  if (entry.activations.length >= entry.max_activations) {
    return { ok: false, error: 'This activation code has already been used on its maximum number of sites.' };
  }

  entry.activations.push({ domain, activated_at: new Date().toISOString() });
  writeAll(codes);
  return { ok: true };
}

/**
 * Used by the auth middleware for every non-activation request: is this
 * code active AND already bound to this specific domain.
 */
function isActivatedForDomain(code, domain) {
  const codes = readAll();
  const entry = codes[code];
  if (!entry || !entry.active) {
    return false;
  }
  return entry.activations.some((a) => a.domain === domain);
}

module.exports = { list, create, revoke, activate, isActivatedForDomain };
