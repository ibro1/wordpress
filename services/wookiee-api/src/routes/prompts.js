/**
 * Prompt management.
 *
 * Split across two auth tiers, mounted separately in index.js:
 *  - adminRouter: the operator's editing surface (view/edit/reset). Behind
 *    requireAdminAuth - a customer site must never be able to read or
 *    rewrite the prompts that drive every other customer's content.
 *  - siteRouter: what an activated site fetches (overrides only) and the
 *    registry publish it performs. Behind requireApiAuth.
 */

const express = require('express');
const promptStore = require('../promptStore');

const adminRouter = express.Router();
const siteRouter = express.Router();

// --- Operator ---------------------------------------------------------

adminRouter.get('/', (req, res) => {
  res.json({ prompts: promptStore.list() });
});

adminRouter.put('/:id', (req, res) => {
  const result = promptStore.setOverride(req.params.id, req.body && req.body.text);
  if (!result.ok) {
    // "Unknown prompt" is a missing resource; a rejected edit (empty, or
    // dropping a required placeholder) is a bad request.
    const status = result.error.startsWith('Unknown prompt') ? 404 : 400;
    return res.status(status).json({ error: result.error });
  }
  res.json({ success: true, prompts: promptStore.list() });
});

adminRouter.post('/:id/reset', (req, res) => {
  const result = promptStore.resetOverride(req.params.id);
  if (!result.ok) {
    return res.status(400).json({ error: result.error });
  }
  res.json({ success: true, prompts: promptStore.list() });
});

// --- Activated sites --------------------------------------------------

/**
 * The theme publishes its own prompt registry here so the admin UI can show
 * real defaults. Idempotent, and safe to call on every settings load - it
 * replaces the registry but never touches operator overrides.
 */
siteRouter.post('/registry', (req, res) => {
  const slots = req.body && req.body.prompts;
  if (!Array.isArray(slots)) {
    return res.status(400).json({ error: 'Expected a prompts array.' });
  }
  const registry = promptStore.saveRegistry(slots);
  res.json({ success: true, count: Object.keys(registry).length });
});

/**
 * Overrides only - the theme already has its own defaults, so sending them
 * back would be redundant payload on every generation.
 */
siteRouter.get('/', (req, res) => {
  res.json({ prompts: promptStore.overridesForTheme() });
});

module.exports = { adminRouter, siteRouter };
