/**
 * Admin-only license management (generate/list/revoke) - mounted behind
 * the normal requireAuth middleware in index.js, same as every other
 * route in this file. The public activation endpoint lives in
 * activatePublic() below instead, since it has to be reachable with NO
 * prior credential (that's the entire point of an activation code) and
 * is wired into index.js separately, before requireAuth runs.
 */

const express = require('express');
const licenseStore = require('../licenseStore');
const modelCatalog = require('../modelCatalog');
const store = require('../secretsStore');

const router = express.Router();

router.get('/', (req, res) => {
  res.json({ codes: licenseStore.list() });
});

/**
 * The full model catalog for the admin UI's per-license picker. Each entry
 * carries `configured` so the UI can show (and grey out) models whose
 * provider has no key saved yet, rather than hiding them and leaving the
 * operator wondering where a model went.
 */
router.get('/models', (req, res) => {
  const models = modelCatalog.listModels().map((m) => {
    const parsed = modelCatalog.parseId(m.id);
    const provider = parsed ? modelCatalog.PROVIDERS[parsed.provider] : null;
    return {
      ...m,
      configured: provider ? Boolean(store.get(provider.key_setting).trim()) : false,
    };
  });
  res.json({ models, providers: modelCatalog.listProviders() });
});

router.post('/', (req, res) => {
  const { max_activations, label, allowed_models, default_model } = req.body || {};
  const result = licenseStore.create({
    maxActivations: max_activations,
    label,
    allowedModels: allowed_models,
    defaultModel: default_model,
  });
  res.json(result);
});

/**
 * Partial update. Only keys actually present in the body are applied, so the
 * UI can PATCH just the model list without resending everything else.
 */
router.patch('/:code', (req, res) => {
  const body = req.body || {};
  const patch = {};

  if (Object.prototype.hasOwnProperty.call(body, 'label')) { patch.label = body.label; }
  if (Object.prototype.hasOwnProperty.call(body, 'max_activations')) { patch.maxActivations = body.max_activations; }
  if (Object.prototype.hasOwnProperty.call(body, 'allowed_models')) { patch.allowedModels = body.allowed_models; }
  if (Object.prototype.hasOwnProperty.call(body, 'default_model')) { patch.defaultModel = body.default_model; }
  if (Object.prototype.hasOwnProperty.call(body, 'active')) { patch.active = body.active; }

  const result = licenseStore.update(req.params.code, patch);
  if (!result.ok) {
    // "Unknown code" is a 404; a rejected value (e.g. max below current
    // activations) is a 400 - different problems, different fixes.
    const status = result.error === 'Unknown activation code.' ? 404 : 400;
    return res.status(status).json({ error: result.error });
  }
  res.json(result.entry);
});

router.post('/:code/revoke', (req, res) => {
  const ok = licenseStore.revoke(req.params.code);
  if (!ok) {
    return res.status(404).json({ error: 'Unknown activation code.' });
  }
  res.json({ success: true });
});

// Exported separately - registered in index.js BEFORE the requireAuth
// middleware, so it's reachable with no X-Api-Key/Basic Auth at all.
function activatePublic(req, res) {
  const code = String((req.body && req.body.code) || '').trim().toUpperCase();
  const domain = String((req.body && req.body.domain) || '').trim().toLowerCase();

  if (!code || !domain) {
    return res.status(400).json({ error: 'Both an activation code and a domain are required.' });
  }

  const result = licenseStore.activate(code, domain);
  if (!result.ok) {
    return res.status(403).json({ error: result.error });
  }
  res.json({ activated: true });
}

module.exports = { router, activatePublic };
