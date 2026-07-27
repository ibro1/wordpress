/**
 * Talks to any OpenAI-compatible Chat Completions endpoint.
 *
 * Two ways a request gets routed:
 *  1. It names a catalog model (`model` in the body, e.g.
 *     "openrouter:google/gemini-2.5-flash-lite"). The provider is derived
 *     from that id, its base URL comes from the registry in
 *     ../modelCatalog.js, and its key from the matching per-provider
 *     setting. The calling license must be allowed to use that model.
 *  2. It names nothing. Falls back to the legacy single-endpoint settings
 *     (llm_base_url / llm_api_key / llm_default_model) - which is what
 *     every site activated before the model catalog existed sends, and
 *     also the escape hatch for an OpenAI-compatible provider that isn't
 *     in the catalog at all.
 *
 * A license with an empty allowed_models list is unrestricted (see
 * licenseStore.isModelAllowed) - so adding this feature did not silently
 * cut off any existing site.
 */

const express = require('express');
const store = require('../secretsStore');
const licenseStore = require('../licenseStore');
const modelCatalog = require('../modelCatalog');

const router = express.Router();

/**
 * Which catalog models the caller may use, and which one they get by
 * default. The WordPress side calls this to build its model dropdown, so it
 * only ever offers models that will actually be accepted.
 */
router.get('/models', async (req, res) => {
  const entry = req.license ? req.license.entry : null;

  // Live from each configured provider - so a site only ever sees models
  // that genuinely exist right now under a key the operator has saved.
  const { models } = await modelCatalog.listConfiguredModels((k) => store.get(k));

  const allowed = models.filter((m) => licenseStore.isModelAllowed(entry, m.id));

  res.json({
    models: allowed,
    default_model: (entry && entry.default_model) || '',
    // True when this license is pinned to a subset; lets the WP UI explain
    // why the list is short rather than looking arbitrarily incomplete.
    restricted: Boolean(entry && (entry.allowed_models || []).length),
    legacy_fallback: Boolean(store.get('llm_api_key').trim()),
  });
});

/**
 * Resolves the request's model into a concrete {baseUrl, apiKey, model}, or
 * an {error, status} describing exactly why it couldn't be.
 */
function resolveTarget(requestedModel, licenseEntry) {
  const wanted = String(requestedModel || '').trim()
    || (licenseEntry && licenseEntry.default_model)
    || '';

  // Nothing named anywhere - legacy single-endpoint path.
  if (!wanted) {
    const apiKey = store.get('llm_api_key').trim();
    if (!apiKey) {
      return { error: 'No model was requested and no fallback LLM API key is configured.', status: 400 };
    }
    return {
      baseUrl: store.get('llm_base_url').replace(/\/+$/, ''),
      apiKey,
      model: store.get('llm_default_model'),
    };
  }

  const parsed = modelCatalog.parseId(wanted);
  if (!parsed) {
    return { error: `Malformed model id "${wanted}" - expected "<provider>:<model>".`, status: 400 };
  }

  if (!licenseStore.isModelAllowed(licenseEntry, wanted)) {
    return { error: `This activation code is not permitted to use the model "${wanted}".`, status: 403 };
  }

  const provider = modelCatalog.PROVIDERS[parsed.provider];
  const apiKey = store.get(provider.key_setting).trim();
  if (!apiKey) {
    return { error: `No API key is configured for ${provider.label}.`, status: 400 };
  }

  return {
    baseUrl: provider.base_url.replace(/\/+$/, ''),
    apiKey,
    model: parsed.model,
  };
}

router.post('/generate', async (req, res) => {
  const prompt = req.body && req.body.prompt;
  const maxTokens = req.body && req.body.max_tokens ? parseInt(req.body.max_tokens, 10) : 2048;

  if (!prompt || !String(prompt).trim()) {
    return res.status(400).json({ error: 'Missing prompt.' });
  }

  const licenseEntry = req.license ? req.license.entry : null;
  const target = resolveTarget(req.body && req.body.model, licenseEntry);
  if (target.error) {
    return res.status(target.status).json({ error: target.error });
  }

  let response;
  try {
    response = await fetch(`${target.baseUrl}/chat/completions`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${target.apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        model: target.model,
        max_tokens: maxTokens,
        messages: [{ role: 'user', content: prompt }],
      }),
    });
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }

  const data = await response.json().catch(() => null);
  if (!response.ok) {
    const msg = data && data.error && data.error.message ? data.error.message : `HTTP ${response.status}`;
    return res.status(502).json({ error: `LLM API error: ${msg}` });
  }

  const text = data && data.choices && data.choices[0] && data.choices[0].message
    ? String(data.choices[0].message.content || '').trim()
    : '';

  if (!text) {
    return res.status(502).json({ error: 'The LLM returned an empty response.' });
  }

  // Echoed back so the caller can log/display which model actually served
  // the request - it may have come from the license default rather than
  // anything the caller explicitly asked for.
  res.json({ text, model: target.model });
});

module.exports = router;
