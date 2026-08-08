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
  /*
   * A second, independently-billed Anthropic route.
   *
   * Same endpoint and wire format as the entry above - the only thing that
   * differs is which key pays. That is the point: it lets one licence tier
   * draw on a different account, workspace or budget from another, and gives
   * a live fallback when the first account hits a rate limit, without having
   * to swap a key that every other tenant is also using.
   *
   * Catalogue ids stay distinct ("anthropic_alt:claude-...") so a licence
   * pinned to one route cannot silently start billing the other.
   */
  anthropic_alt: {
    label: 'Anthropic (second account)',
    base_url: 'https://api.anthropic.com/v1',
    key_setting: 'llm_anthropic_alt_api_key',
    listPath: '/models',
    authStyle: 'anthropic',
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : []).map((m) => ({
      model: m.id,
      label: m.display_name || m.id,
      in: null,
      out: null,
      context: m.max_input_tokens || null,
    })),
  },
  claude_code: {
    label: 'Claude Code',
    base_url_setting: 'llm_claude_code_base_url',
    key_setting: 'llm_claude_code_api_key',
    listPath: '/models',
    authStyle: 'anthropic_bearer',
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : []).map((m) => {
      let label = m.display_name || m.id;
      if (m.id.includes('claude-3-5-sonnet')) {
        label = 'sonnet5-cc';
      } else {
        label = label + '-cc';
      }
      return {
        model: m.id,
        label: label,
        in: null,
        out: null,
        context: m.max_input_tokens || null,
      };
    }),
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
  // The legacy/fallback endpoint as a first-class model source. Its base URL
  // is whatever the operator configured (OpenAI, Groq, a self-hosted vLLM,
  // ...), so it is read from settings rather than fixed here. Without this,
  // an install that already had a working key in the fallback field - which
  // is every install that predates the per-provider fields - would show an
  // empty model picker despite being perfectly well configured.
  custom: {
    label: 'Fallback endpoint',
    base_url_setting: 'llm_base_url',
    key_setting: 'llm_api_key',
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
 * A provider's base URL: fixed for the known vendors, read from settings for
 * the operator-configured fallback endpoint.
 */
function resolveBaseUrl(providerKey, getSetting) {
  const provider = PROVIDERS[providerKey];
  if (!provider) {
    return '';
  }
  if (provider.base_url) {
    return provider.base_url;
  }
  return String(getSetting(provider.base_url_setting) || '').trim();
}

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
  if (provider.authStyle === 'anthropic_bearer') {
    return { 'Authorization': `Bearer ${apiKey}`, 'anthropic-version': '2023-06-01' };
  }
  return { Authorization: `Bearer ${apiKey}` };
}

/**
 * Fetches (and caches) one provider's live model list. Never throws - a
 * provider being down, rate-limiting, or holding a bad key must not take
 * down the whole picker, so failures come back as { error } and the caller
 * surfaces them per provider.
 */
async function fetchProviderModels(providerKey, apiKey, { force = false, baseUrl = '' } = {}) {
  const provider = PROVIDERS[providerKey];
  if (!provider) {
    return { error: 'Unknown provider.' };
  }
  if (!apiKey) {
    return { models: [], unconfigured: true };
  }

  const effectiveBase = String(baseUrl || provider.base_url || '').trim();
  if (!effectiveBase) {
    return { error: `${provider.label}: no base URL configured.` };
  }

  // Keyed by base URL too: the fallback endpoint's models change entirely if
  // the operator repoints it, and a cache hit on the old URL would be wrong.
  const cacheKey = `${providerKey}|${effectiveBase}`;
  const cached = cache.get(cacheKey);
  if (!force && cached && (Date.now() - cached.fetchedAt) < CACHE_TTL_MS) {
    return { models: cached.models, cached: true, fetchedAt: cached.fetchedAt };
  }

  const url = effectiveBase.replace(/\/+$/, '') + provider.listPath;

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

  cache.set(cacheKey, { fetchedAt: Date.now(), models });
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
async function listConfiguredModels(getSetting, { force = false } = {}) {
  const providerKeys = Object.keys(PROVIDERS);

  const results = await Promise.all(providerKeys.map(async (providerKey) => {
    const apiKey = String(getSetting(PROVIDERS[providerKey].key_setting) || '').trim();
    const baseUrl = resolveBaseUrl(providerKey, getSetting);
    const result = await fetchProviderModels(providerKey, apiKey, { force, baseUrl });
    return { providerKey, apiKey: Boolean(apiKey), baseUrl, result };
  }));

  const models = [];
  const errors = {};
  const providers = [];

  results.forEach(({ providerKey, apiKey, baseUrl, result }) => {
    // Name the fallback by the host it actually points at, so "Fallback
    // endpoint (api.openai.com)" tells the operator what they're looking at
    // instead of an opaque label.
    let label = PROVIDERS[providerKey].label;
    if (!PROVIDERS[providerKey].base_url && baseUrl) {
      try {
        label += ` (${new URL(baseUrl).host})`;
      } catch (err) {
        // A malformed base URL is the operator's to fix; the plain label and
        // the fetch error below already tell them enough.
      }
    }

    providers.push({
      key: providerKey,
      label,
      configured: apiKey,
      count: result.models ? result.models.length : 0,
      error: result.error || null,
    });
    if (result.error) {
      errors[providerKey] = result.error;
    }
    if (result.models) {
      models.push(...result.models.map((m) => ({ ...m, provider_label: label })));
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
