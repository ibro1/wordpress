/**
 * Provider registry + LIVE model discovery.
 *
 * Nothing about which models exist, what they're called, or what they cost
 * is hardcoded here - it is all fetched from each provider's own models
 * endpoint using the key saved for that provider. A hardcoded table goes
 * stale silently and dangerously: vendors rename models, retire them, and
 * change prices without notice, and the operator would have no way to tell
 * from this UI that what it's showing is fiction.
 *
 * Every provider below speaks the OpenAI Chat Completions wire format for
 * generation (`POST {base_url}/chat/completions`, `Authorization: Bearer`),
 * including Anthropic and Google, which publish OpenAI-compatible endpoints
 * alongside their native APIs. Model *listing* is less standardised, so each
 * provider carries its own `listPath`/`authStyle`/`normalize`.
 *
 * Catalogue ids stay `<provider>:<wire model name>`, e.g.
 *   openrouter:anthropic/claude-haiku-4.5
 *   anthropic:claude-haiku-4-5
 * so the same model can be offered through whichever billing route the
 * operator wants a given licence to use.
 */

// How long a provider's model list is reused before re-fetching. Model
// catalogues change on the order of weeks; the admin UI also exposes a
// force-refresh, so this only bounds how stale an untouched page can be.
const CACHE_TTL_MS = 6 * 60 * 60 * 1000;

const cache = new Map(); // provider key -> { fetchedAt, models }

/**
 * Per-token price strings (OpenRouter's format) -> price per 1M tokens.
 * Returns null for absent/unparseable values so the UI can omit the figure
 * entirely rather than display a misleading 0.00.
 */
function perMillion(value) {
  const n = parseFloat(value);
  if (!isFinite(n) || n <= 0) {
    return null;
  }
  return Math.round(n * 1e6 * 1e6) / 1e6;
}

const PROVIDERS = {
  openrouter: {
    label: 'OpenRouter',
    base_url: 'https://openrouter.ai/api/v1',
    key_setting: 'llm_openrouter_api_key',
    listPath: '/models',
    authStyle: 'bearer',
    // The richest source: real display names, live per-token pricing and
    // context length for every model it proxies.
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : []).map((m) => ({
      model: m.id,
      label: m.name || m.id,
      in: m.pricing ? perMillion(m.pricing.prompt) : null,
      out: m.pricing ? perMillion(m.pricing.completion) : null,
      context: m.context_length || null,
    })),
  },
  openai: {
    label: 'OpenAI',
    base_url: 'https://api.openai.com/v1',
    key_setting: 'llm_openai_api_key',
    listPath: '/models',
    authStyle: 'bearer',
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : []).map((m) => ({
      model: m.id,
      label: m.id,
      in: null,
      out: null,
      context: null,
    })),
  },
  anthropic: {
    label: 'Anthropic',
    base_url: 'https://api.anthropic.com/v1',
    key_setting: 'llm_anthropic_api_key',
    listPath: '/models',
    // Anthropic's OpenAI-compatibility layer covers /chat/completions, not
    // /models - that one still wants native x-api-key + anthropic-version.
    authStyle: 'anthropic',
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : []).map((m) => ({
      model: m.id,
      label: m.display_name || m.id,
      in: null,
      out: null,
      context: m.max_input_tokens || null,
    })),
  },
  google: {
    label: 'Google Gemini',
    base_url: 'https://generativelanguage.googleapis.com/v1beta/openai',
    key_setting: 'llm_google_api_key',
    listPath: '/models',
    authStyle: 'bearer',
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : []).map((m) => ({
      // The OpenAI-compat layer returns ids like "models/gemini-2.5-flash";
      // the chat endpoint accepts them either way, but the bare name is what
      // the operator recognises.
      model: String(m.id || '').replace(/^models\//, ''),
      label: String(m.id || '').replace(/^models\//, ''),
      in: null,
      out: null,
      context: null,
    })),
  },
  deepseek: {
    label: 'DeepSeek',
    base_url: 'https://api.deepseek.com/v1',
    key_setting: 'llm_deepseek_api_key',
    listPath: '/models',
    authStyle: 'bearer',
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : []).map((m) => ({
      model: m.id,
      label: m.id,
      in: null,
      out: null,
      context: null,
    })),
  },
};

/**
 * Splits a catalogue id into provider + wire model name.
 *
 * Deliberately validates FORMAT only (known provider, non-empty model) and
 * not membership of any live list: a provider retiring a model must not
 * cause the id already stored on a licence to be silently discarded. An id
 * that no longer exists upstream fails at call time with the provider's own
 * error, which is far easier to diagnose than config vanishing on save.
 */
function parseId(id) {
  const raw = String(id || '').trim();
  const sep = raw.indexOf(':');
  if (sep < 1) {
    return null;
  }
  const provider = raw.slice(0, sep);
  const model = raw.slice(sep + 1);
  if (!PROVIDERS[provider] || !model) {
    return null;
  }
  return { provider, model };
}

function listProviders() {
  return Object.keys(PROVIDERS).map((key) => ({
    key,
    label: PROVIDERS[key].label,
    base_url: PROVIDERS[key].base_url,
    key_setting: PROVIDERS[key].key_setting,
  }));
}

function buildHeaders(provider, apiKey) {
  if (provider.authStyle === 'anthropic') {
    return { 'x-api-key': apiKey, 'anthropic-version': '2023-06-01' };
  }
  return { Authorization: `Bearer ${apiKey}` };
}

/**
 * Fetches (and caches) one provider's live model list. Never throws - a
 * provider being down, rate-limiting, or holding a bad key must not take
 * down the whole picker, so failures come back as { error } and the caller
 * surfaces them per provider.
 */
async function fetchProviderModels(providerKey, apiKey, { force = false } = {}) {
  const provider = PROVIDERS[providerKey];
  if (!provider) {
    return { error: 'Unknown provider.' };
  }
  if (!apiKey) {
    return { models: [], unconfigured: true };
  }

  const cached = cache.get(providerKey);
  if (!force && cached && (Date.now() - cached.fetchedAt) < CACHE_TTL_MS) {
    return { models: cached.models, cached: true, fetchedAt: cached.fetchedAt };
  }

  const url = provider.base_url.replace(/\/+$/, '') + provider.listPath;

  let response;
  try {
    response = await fetch(url, {
      headers: buildHeaders(provider, apiKey),
      signal: AbortSignal.timeout(15000),
    });
  } catch (err) {
    return { error: `Could not reach ${provider.label}: ${err.message}` };
  }

  const body = await response.json().catch(() => null);

  if (!response.ok) {
    const msg = body && body.error && body.error.message
      ? body.error.message
      : `HTTP ${response.status}`;
    return { error: `${provider.label}: ${msg}` };
  }

  let models;
  try {
    models = provider.normalize(body)
      .filter((m) => m.model)
      .map((m) => ({ ...m, id: `${providerKey}:${m.model}`, provider: providerKey, provider_label: provider.label }))
      .sort((a, b) => a.label.localeCompare(b.label));
  } catch (err) {
    return { error: `${provider.label}: unexpected response shape (${err.message})` };
  }

  cache.set(providerKey, { fetchedAt: Date.now(), models });
  return { models, fetchedAt: Date.now() };
}

/**
 * Live model list across every provider that has a key saved.
 *
 * `getKey` is injected rather than requiring secretsStore here, so this
 * module stays a pure registry and is trivially testable with fake keys.
 * Returns per-provider errors alongside the models so the UI can say
 * "Anthropic failed" without pretending Anthropic simply has no models.
 */
async function listConfiguredModels(getKey, { force = false } = {}) {
  const providerKeys = Object.keys(PROVIDERS);

  const results = await Promise.all(providerKeys.map(async (providerKey) => {
    const apiKey = String(getKey(PROVIDERS[providerKey].key_setting) || '').trim();
    const result = await fetchProviderModels(providerKey, apiKey, { force });
    return { providerKey, apiKey: Boolean(apiKey), result };
  }));

  const models = [];
  const errors = {};
  const providers = [];

  results.forEach(({ providerKey, apiKey, result }) => {
    providers.push({
      key: providerKey,
      label: PROVIDERS[providerKey].label,
      configured: apiKey,
      count: result.models ? result.models.length : 0,
      error: result.error || null,
    });
    if (result.error) {
      errors[providerKey] = result.error;
    }
    if (result.models) {
      models.push(...result.models);
    }
  });

  return { models, errors, providers };
}

function clearCache() {
  cache.clear();
}

module.exports = {
  PROVIDERS,
  listProviders,
  parseId,
  fetchProviderModels,
  listConfiguredModels,
  clearCache,
};
