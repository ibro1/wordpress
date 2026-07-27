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
const modelCatalog = require('./modelCatalog');
const imageCatalog = require('./imageCatalog');

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

/**
 * Normalizes an allowed-models value to an array of well-formed catalogue
 * ids (`<known provider>:<model>`), de-duplicated.
 *
 * Validates shape only, deliberately NOT membership of the live model list:
 * that list is fetched from the providers and changes as they add/retire
 * models, so checking against it here would mean a licence silently losing
 * a saved model the day its provider renamed it. A stale id instead fails
 * loudly at call time with the provider's own error message.
 */
function sanitizeWith(parse, models) {
  if (!Array.isArray(models)) {
    return [];
  }
  return models
    .map((m) => String(m || '').trim())
    .filter((m) => m && parse(m))
    .filter((m, i, arr) => arr.indexOf(m) === i);
}

function sanitizeModels(models) {
  return sanitizeWith(modelCatalog.parseId, models);
}

/**
 * The image equivalent, validated against the image catalogue.
 *
 * This rejects ids whose PROVIDER is not an image provider - "deepseek:chat"
 * and "anthropic:claude-haiku-4-5" cannot reach an image list, and
 * "together:FLUX.1-schnell" cannot reach a text one. It does NOT reject a
 * wrong-modality model from a provider that does both: "openai:gpt-4o" is
 * well-formed here and will be stored.
 *
 * That is the same deliberate format-only rule as sanitizeModels() above,
 * and the alternative is worse - checking against the live list would mean a
 * licence silently losing a saved model the day its provider renamed it. A
 * wrong-modality id instead fails at call time with OpenAI's own error, and
 * the admin picker only ever offers image-capable models, so getting one in
 * here takes a hand-crafted request rather than a mis-click.
 */
function sanitizeImageModels(models) {
  return sanitizeWith(imageCatalog.parseId, models);
}

function create({
  maxActivations = 1, label = '', allowedModels = [], defaultModel = '',
  allowedImageModels = [], defaultImageModel = '',
} = {}) {
  const codes = readAll();
  let code;
  do {
    code = generateCode();
  } while (codes[code]); // astronomically unlikely, but don't silently collide

  const allowed = sanitizeModels(allowedModels);
  const allowedImage = sanitizeImageModels(allowedImageModels);

  codes[code] = {
    max_activations: Math.max(1, parseInt(maxActivations, 10) || 1),
    label: String(label || ''),
    active: true,
    created_at: new Date().toISOString(),
    activations: [],
    // Empty array means "no restriction" - see isModelAllowed(). New codes
    // default to unrestricted so generating one without touching the model
    // picker behaves exactly like it did before this feature existed.
    allowed_models: allowed,
    default_model: allowed.includes(defaultModel) ? defaultModel : (allowed[0] || ''),
    // Same "empty means unrestricted" rule as the text models above, for the
    // same reason: a code created before image generation existed must keep
    // working without the operator revisiting it.
    allowed_image_models: allowedImage,
    default_image_model: allowedImage.includes(defaultImageModel) ? defaultImageModel : (allowedImage[0] || ''),
  };
  writeAll(codes);
  return { code, ...codes[code] };
}

/**
 * Partial update - only the fields actually present in $patch are touched,
 * so the admin UI can save just the model list without having to round-trip
 * (and risk clobbering) the label/max_activations/activations it didn't edit.
 */
function update(code, patch = {}) {
  const codes = readAll();
  const entry = codes[code];
  if (!entry) {
    return { ok: false, error: 'Unknown activation code.' };
  }

  if (Object.prototype.hasOwnProperty.call(patch, 'label')) {
    entry.label = String(patch.label || '');
  }

  if (Object.prototype.hasOwnProperty.call(patch, 'maxActivations')) {
    const next = Math.max(1, parseInt(patch.maxActivations, 10) || 1);
    // Refuse to set a cap below the number of sites already using this code -
    // silently doing so would leave those sites activated but over quota,
    // an inconsistent state nothing else in the store expects.
    if (next < entry.activations.length) {
      return {
        ok: false,
        error: `This code is already activated on ${entry.activations.length} site(s); the maximum cannot be set lower than that.`,
      };
    }
    entry.max_activations = next;
  }

  if (Object.prototype.hasOwnProperty.call(patch, 'allowedModels')) {
    entry.allowed_models = sanitizeModels(patch.allowedModels);
  }

  if (Object.prototype.hasOwnProperty.call(patch, 'defaultModel')) {
    const wanted = String(patch.defaultModel || '').trim();
    const allowed = entry.allowed_models || [];
    entry.default_model = (!allowed.length || allowed.includes(wanted)) ? wanted : '';
  }

  if (Object.prototype.hasOwnProperty.call(patch, 'allowedImageModels')) {
    entry.allowed_image_models = sanitizeImageModels(patch.allowedImageModels);
  }

  if (Object.prototype.hasOwnProperty.call(patch, 'defaultImageModel')) {
    const wanted = String(patch.defaultImageModel || '').trim();
    const allowed = entry.allowed_image_models || [];
    entry.default_image_model = (!allowed.length || allowed.includes(wanted)) ? wanted : '';
  }

  // A default that's no longer in the allowed list would route requests to a
  // model the license can't use - re-point it at the first allowed model.
  if (entry.allowed_models && entry.allowed_models.length && !entry.allowed_models.includes(entry.default_model)) {
    entry.default_model = entry.allowed_models[0];
  }
  if (entry.allowed_image_models && entry.allowed_image_models.length
      && !entry.allowed_image_models.includes(entry.default_image_model)) {
    entry.default_image_model = entry.allowed_image_models[0];
  }

  if (Object.prototype.hasOwnProperty.call(patch, 'active')) {
    entry.active = Boolean(patch.active);
  }

  writeAll(codes);
  return { ok: true, entry: { code, ...entry } };
}

function get(code) {
  const codes = readAll();
  return codes[code] || null;
}

/**
 * An empty/absent allowed_models list means unrestricted - that's what every
 * license created before this feature has, and treating it as "allow all"
 * (rather than "allow none") is what keeps those sites working untouched.
 */
function isModelAllowed(entry, modelId) {
  if (!entry) {
    return false;
  }
  const allowed = entry.allowed_models || [];
  if (!allowed.length) {
    return true;
  }
  return allowed.includes(modelId);
}

/** The image equivalent, with the same allow-all-when-empty rule. */
function isImageModelAllowed(entry, modelId) {
  if (!entry) {
    return false;
  }
  const allowed = entry.allowed_image_models || [];
  if (!allowed.length) {
    return true;
  }
  return allowed.includes(modelId);
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

module.exports = {
  list,
  create,
  update,
  get,
  revoke,
  activate,
  isActivatedForDomain,
  isModelAllowed,
  isImageModelAllowed,
};
