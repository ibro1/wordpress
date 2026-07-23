/**
 * Encrypted key/value store for every credential this service centralizes -
 * one AES-256-GCM encrypted JSON file on a Docker volume, not a database,
 * since there are only ~18 scalar values to hold and a real DB engine would
 * just be extra weight for no benefit here.
 *
 * MASTER_KEY (an env var, set once in the compose file/Dokploy secrets, never
 * committed) is the only thing standing between this file and the plaintext
 * values - back it up somewhere safe, because losing it means losing every
 * stored key with no recovery path.
 */

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const DATA_DIR = process.env.DATA_DIR || path.join(__dirname, '..', 'data');
const SECRETS_FILE = path.join(DATA_DIR, 'secrets.enc');

// Mirrors wookiee_settings_fields() in inc/theme-settings.php - the exact
// set of keys this service is meant to centralize away from wp_options.
const SECRET_KEYS = [
  'companies_house_api_key',
  'llm_api_key',
  'llm_base_url',
  'llm_default_model',
  'cj_email',
  'cj_api_key',
  'cloudinary_cloud_name',
  'cloudinary_api_key',
  'cloudinary_api_secret',
  'rembg_endpoint_url',
  'google_ads_developer_token',
  'google_ads_client_id',
  'google_ads_client_secret',
  'google_ads_refresh_token',
  'google_ads_customer_id',
  'google_ads_login_customer_id',
  'spaceship_api_key',
  'spaceship_api_secret',
];

const DEFAULTS = {
  llm_base_url: 'https://api.openai.com/v1',
  llm_default_model: 'gpt-4o-mini',
  rembg_endpoint_url: 'http://rembg:7000',
};

// Every provider key can also be set as a plain environment variable
// (its name uppercased, e.g. companies_house_api_key -> COMPANIES_HOUSE_API_KEY)
// - convenient for setting them all at deploy time in Dokploy instead of
// logging into the settings UI afterward. Precedence: encrypted store (if a
// value has been saved there) > env var > hardcoded default above. Saving a
// value through the settings UI always wins from then on, even if the env
// var is still set, since the store is what setMany() actually writes to.
function envVarName(key) {
  return key.toUpperCase();
}

function deriveKey() {
  const passphrase = process.env.MASTER_KEY;
  if (!passphrase) {
    throw new Error('MASTER_KEY environment variable is required to encrypt/decrypt stored secrets.');
  }
  // The salt doesn't need to be secret or random - MASTER_KEY is the actual
  // secret here, this just turns an arbitrary-length passphrase into a
  // fixed 32-byte AES key.
  return crypto.scryptSync(passphrase, 'wookiee-api-secrets-salt-v1', 32);
}

function readAll() {
  if (!fs.existsSync(SECRETS_FILE)) {
    return {};
  }
  const raw = fs.readFileSync(SECRETS_FILE);
  const iv = raw.subarray(0, 12);
  const authTag = raw.subarray(12, 28);
  const ciphertext = raw.subarray(28);
  const key = deriveKey();
  const decipher = crypto.createDecipheriv('aes-256-gcm', key, iv);
  decipher.setAuthTag(authTag);
  const decrypted = Buffer.concat([decipher.update(ciphertext), decipher.final()]);
  return JSON.parse(decrypted.toString('utf8'));
}

function writeAll(values) {
  fs.mkdirSync(DATA_DIR, { recursive: true });
  const key = deriveKey();
  const iv = crypto.randomBytes(12);
  const cipher = crypto.createCipheriv('aes-256-gcm', key, iv);
  const plaintext = Buffer.from(JSON.stringify(values), 'utf8');
  const ciphertext = Buffer.concat([cipher.update(plaintext), cipher.final()]);
  const authTag = cipher.getAuthTag();
  fs.writeFileSync(SECRETS_FILE, Buffer.concat([iv, authTag, ciphertext]));
}

function getAll() {
  const stored = readAll();
  const result = {};
  SECRET_KEYS.forEach((k) => {
    result[k] = stored[k] || process.env[envVarName(k)] || DEFAULTS[k] || '';
  });
  return result;
}

function get(key) {
  const stored = readAll();
  return stored[key] || process.env[envVarName(key)] || DEFAULTS[key] || '';
}

function setMany(updates) {
  const stored = readAll();
  Object.keys(updates).forEach((k) => {
    if (SECRET_KEYS.includes(k)) {
      stored[k] = String(updates[k] ?? '').trim();
    }
  });
  writeAll(stored);
  return getAll();
}

module.exports = { SECRET_KEYS, getAll, get, setMany };
