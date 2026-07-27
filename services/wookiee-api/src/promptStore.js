/**
 * Operator-editable prompt overrides.
 *
 * Design note on where the source of truth lives: the DEFAULT text of every
 * prompt stays in the theme, next to the code that builds and uses it. This
 * store only ever holds overrides. The theme publishes its registry here
 * (id, label, description, placeholders, default text) via POST /prompts/registry
 * so the admin UI can show the real current default beside the override -
 * duplicating the defaults in this service instead would guarantee the two
 * drift apart the first time a prompt is edited in the theme.
 *
 * Consequences worth knowing:
 *  - A slot with no override resolves to whatever the theme ships, so an
 *    untouched install behaves exactly as it did before this feature.
 *  - "Reset to default" deletes the override rather than copying the default
 *    into it, so a reset slot keeps tracking future theme improvements.
 *
 * Plain JSON, not encrypted: prompts are product configuration, not
 * credentials. secretsStore.js remains the place for anything secret.
 */

const fs = require('fs');
const path = require('path');

const DATA_DIR = process.env.DATA_DIR || path.join(__dirname, '..', 'data');
const PROMPTS_FILE = path.join(DATA_DIR, 'prompts.json');

function readFile() {
  if (!fs.existsSync(PROMPTS_FILE)) {
    return { registry: {}, overrides: {} };
  }
  try {
    const parsed = JSON.parse(fs.readFileSync(PROMPTS_FILE, 'utf8'));
    return {
      registry: parsed.registry && typeof parsed.registry === 'object' ? parsed.registry : {},
      overrides: parsed.overrides && typeof parsed.overrides === 'object' ? parsed.overrides : {},
    };
  } catch (err) {
    // A corrupt file must not take the whole service down - an empty result
    // means "no overrides", which degrades to the theme's own defaults.
    return { registry: {}, overrides: {} };
  }
}

function writeFile(data) {
  fs.mkdirSync(DATA_DIR, { recursive: true });
  fs.writeFileSync(PROMPTS_FILE, JSON.stringify(data, null, 2));
}

/**
 * Records the theme's own prompt registry. Called by the theme, which knows
 * its defaults; replaces the stored registry wholesale so slots removed from
 * a newer theme release stop being advertised in the admin UI.
 *
 * Overrides are deliberately NOT touched here: an operator's edit must
 * survive a theme update. An override for a slot that no longer exists is
 * kept (harmless, and recoverable if the slot comes back) but flagged as
 * orphaned by list().
 */
function saveRegistry(slots) {
  const data = readFile();
  const registry = {};

  (Array.isArray(slots) ? slots : []).forEach((slot) => {
    const id = String((slot && slot.id) || '').trim();
    if (!id) {
      return;
    }
    registry[id] = {
      id,
      label: String(slot.label || id),
      description: String(slot.description || ''),
      // Placeholder names the theme will substitute, e.g. ["business_details"].
      placeholders: Array.isArray(slot.placeholders) ? slot.placeholders.map(String) : [],
      // Placeholders the prompt is not meaningful without - the UI refuses to
      // save an override that drops one of these.
      required_placeholders: Array.isArray(slot.required_placeholders)
        ? slot.required_placeholders.map(String)
        : [],
      default_text: String(slot.default_text || ''),
      updated_at: new Date().toISOString(),
    };
  });

  data.registry = registry;
  writeFile(data);
  return registry;
}

/**
 * Every known slot, merged with its override (if any), for the admin UI.
 */
function list() {
  const { registry, overrides } = readFile();
  const ids = Array.from(new Set([...Object.keys(registry), ...Object.keys(overrides)]));

  return ids.map((id) => {
    const slot = registry[id] || null;
    const override = overrides[id] || null;

    return {
      id,
      label: slot ? slot.label : id,
      description: slot ? slot.description : '',
      placeholders: slot ? slot.placeholders : [],
      required_placeholders: slot ? slot.required_placeholders : [],
      default_text: slot ? slot.default_text : '',
      override_text: override ? override.text : null,
      overridden: Boolean(override),
      updated_at: override ? override.updated_at : (slot ? slot.updated_at : null),
      // True when an override exists for a slot the current theme no longer
      // publishes - surfaced so the operator can tell "this edit does nothing
      // any more" apart from "this edit is live".
      orphaned: Boolean(override) && !slot,
    };
  }).sort((a, b) => a.label.localeCompare(b.label));
}

/**
 * The overrides only, as { id: text } - what the theme actually consumes.
 */
function overridesForTheme() {
  const { overrides } = readFile();
  const out = {};
  Object.keys(overrides).forEach((id) => {
    out[id] = overrides[id].text;
  });
  return out;
}

/**
 * Saves an override. Refuses one that drops a placeholder the prompt cannot
 * function without - e.g. a policy prompt that no longer interpolates the
 * business's real details would silently start producing generic, factually
 * empty policy pages, which is worse than an obvious failure.
 */
function setOverride(id, text) {
  const data = readFile();
  const slot = data.registry[id];

  if (!slot) {
    return { ok: false, error: 'Unknown prompt. The theme has not published a prompt with this id.' };
  }

  const value = String(text == null ? '' : text);
  if (!value.trim()) {
    return { ok: false, error: 'Prompt text cannot be empty. Use reset to go back to the theme default.' };
  }

  const missing = (slot.required_placeholders || []).filter(
    (name) => !value.includes(`{{${name}}}`)
  );
  if (missing.length) {
    return {
      ok: false,
      error: `This prompt must still include: ${missing.map((n) => `{{${n}}}`).join(', ')}`,
    };
  }

  data.overrides[id] = { text: value, updated_at: new Date().toISOString() };
  writeFile(data);
  return { ok: true };
}

/**
 * Drops the override so the slot tracks the theme default again (including
 * future improvements to it).
 */
function resetOverride(id) {
  const data = readFile();
  if (!data.overrides[id]) {
    return { ok: false, error: 'That prompt is already using the theme default.' };
  }
  delete data.overrides[id];
  writeFile(data);
  return { ok: true };
}

module.exports = { saveRegistry, list, overridesForTheme, setOverride, resetOverride };
