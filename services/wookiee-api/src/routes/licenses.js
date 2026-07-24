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

const router = express.Router();

router.get('/', (req, res) => {
  res.json({ codes: licenseStore.list() });
});

router.post('/', (req, res) => {
  const { max_activations, label } = req.body || {};
  const result = licenseStore.create({ maxActivations: max_activations, label });
  res.json(result);
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
