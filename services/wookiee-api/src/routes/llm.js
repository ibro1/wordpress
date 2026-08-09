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

  /*
   * This endpoint is consumed by CUSTOMER sites, so it deliberately exposes
   * only what a customer needs to choose a model: the id and a clean name.
   *
   * Everything else the catalogue carries is operator-internal and must not
   * leak downstream - the provider/host ("Fallback endpoint
   * (api.openai.com)") reveals which upstream this business is resold from,
   * and per-token pricing reveals the operator's cost basis and therefore
   * its margin. Both are visible to the operator on /licenses/models, which
   * is admin-only; neither belongs on a customer's WordPress screen.
   */
  const publicModels = allowed.map((m) => ({ id: m.id, label: m.label }));

  res.json({
    models: publicModels,
    default_model: (entry && entry.default_model) || '',
    // True when this license is pinned to a subset; lets the WP UI explain
    // why the list is short rather than looking arbitrarily incomplete.
    restricted: Boolean(entry && (entry.allowed_models || []).length),
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
      extraHeaders: {},
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

  // Fixed for the known vendors; read from settings for the operator's own
  // fallback endpoint, which can point anywhere OpenAI-compatible.
  const baseUrl = provider.base_url || store.get(provider.base_url_setting).trim();
  if (!baseUrl) {
    return { error: `No base URL is configured for ${provider.label}.`, status: 400 };
  }

  return {
    baseUrl: baseUrl.replace(/\/+$/, ''),
    apiKey,
    model: parsed.model,
    extraHeaders: provider.extraHeaders || {},
  };
}

/**
 * Accumulates an OpenAI-compatible SSE chat-completion stream into the
 * final completion text: repeated "data: {...}\n\n" events carrying
 * choices[0].delta.content pieces, terminated by "data: [DONE]".
 */
async function readSseCompletion(body) {
  const reader = body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';
  let text = '';

  for (;;) {
    const { done, value } = await reader.read();
    if (done) {
      break;
    }
    buffer += decoder.decode(value, { stream: true });

    let sepIndex;
    while ((sepIndex = buffer.indexOf('\n\n')) !== -1) {
      const rawEvent = buffer.slice(0, sepIndex);
      buffer = buffer.slice(sepIndex + 2);

      for (const line of rawEvent.split('\n')) {
        const trimmed = line.trim();
        if (!trimmed.startsWith('data:')) {
          continue;
        }
        const payload = trimmed.slice(5).trim();
        if (!payload || payload === '[DONE]') {
          continue;
        }
        let parsed;
        try {
          parsed = JSON.parse(payload);
        } catch (err) {
          continue; // A malformed one-off chunk shouldn't drop the whole completion.
        }
        const delta = parsed && parsed.choices && parsed.choices[0] && parsed.choices[0].delta;
        if (delta && typeof delta.content === 'string') {
          text += delta.content;
        }
      }
    }
  }

  return text.trim();
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
        ...target.extraHeaders,
      },
      body: JSON.stringify({
        model: target.model,
        max_tokens: maxTokens,
        messages: [{ role: 'user', content: prompt }],
        /*
         * Some gateways enforce a much shorter timeout on a fully-buffered
         * response than on a stream - confirmed directly against
         * AgentRouter, where a slow non-streaming completion for a long
         * policy page came back as a bare HTTP 504 well before this
         * request's own timeout was anywhere close. Streaming keeps the
         * connection actively receiving data instead of sitting idle
         * waiting for one big response, which is what a gateway's own
         * timeout is actually watching for. The text is still accumulated
         * server-side and returned as one response below - the caller's
         * contract with this endpoint doesn't change.
         */
        stream: true,
      }),
    });
  } catch (err) {
    return res.status(502).json({ error: err.message });
  }

  if (!response.ok) {
    const errBody = await response.json().catch(() => null);
    const msg = errBody && errBody.error && errBody.error.message ? errBody.error.message : `HTTP ${response.status}`;
    return res.status(502).json({ error: `LLM API error: ${msg}` });
  }

  const contentType = response.headers.get('content-type') || '';
  let text;
  try {
    if (contentType.includes('text/event-stream') && response.body) {
      text = await readSseCompletion(response.body);
    } else {
      // The provider accepted the request but ignored stream:true (or this
      // endpoint just never streams) - handled exactly as a plain,
      // non-streaming response always was.
      const data = await response.json().catch(() => null);
      text = data && data.choices && data.choices[0] && data.choices[0].message
        ? String(data.choices[0].message.content || '').trim()
        : '';
    }
  } catch (err) {
    return res.status(502).json({ error: `Stream reading failed: ${err.message}` });
  }

  if (!text) {
    return res.status(502).json({ error: 'The LLM returned an empty response.' });
  }

  // Echoed back so the caller can log/display which model actually served
  // the request - it may have come from the license default rather than
  // anything the caller explicitly asked for.
  res.json({ text, model: target.model });
});

module.exports = router;
