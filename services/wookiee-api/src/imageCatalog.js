/**
 * Image provider registry + LIVE model discovery.
 *
 * The sibling of modelCatalog.js, and deliberately shaped like it, but the
 * two cannot share an implementation. Text generation has a de-facto
 * standard - every provider in modelCatalog.js speaks OpenAI Chat
 * Completions, so one request shape covers all of them. Image generation
 * has no such standard:
 *
 *   - OpenAI takes `size: "1536x1024"`; Together takes `width`/`height`.
 *   - gpt-image-1 REJECTS `response_format` and always returns base64;
 *     dall-e-3, Together and xAI need it asked for explicitly or they
 *     return a URL that has to be downloaded separately.
 *   - Google is not OpenAI-compatible for images at all, and is itself two
 *     different APIs: `imagen-*` uses :predict, `gemini-*-image` uses
 *     :generateContent with responseModalities.
 *
 * So each provider carries its own `buildBody` and `readImage`, and the
 * shared code only handles auth, caching, listing and error reporting.
 *
 * Catalogue ids are `<provider>:<wire model name>`, matching the text side.
 */

const CACHE_TTL_MS = 6 * 60 * 60 * 1000;

const cache = new Map(); // `${provider}|${baseUrl}` -> { fetchedAt, models }

/**
 * Reads an image out of an OpenAI-style `{ data: [ { b64_json | url } ] }`
 * response. Returns base64 or null; downloading a URL is the caller's job
 * since it needs another fetch.
 */
function readOpenAIStyle(body) {
  const first = body && Array.isArray(body.data) ? body.data[0] : null;
  if (!first) {
    return null;
  }
  if (first.b64_json) {
    return { base64: first.b64_json };
  }
  if (first.url) {
    return { url: first.url };
  }
  return null;
}

/**
 * Which of a provider's models can actually generate an image. Listing
 * endpoints return every model the key can reach - chat models included -
 * and offering a chat model in an image picker produces a confusing failure
 * at generate time rather than an honest empty list.
 */
function looksLikeImageModel(id) {
  return /(^|[-/_])(image|imagen|dall-e|dalle|flux|sdxl|stable-diffusion|playground|kandinsky|recraft|ideogram)/i.test(String(id || ''));
}

const PROVIDERS = {
  openai: {
    label: 'OpenAI',
    base_url: 'https://api.openai.com/v1',
    key_setting: 'llm_openai_api_key',
    /*
     * Falls back to the legacy single-endpoint key. Most installs configured
     * that field first and it holds an OpenAI key in practice, so requiring
     * the newer per-provider field would report "no key saved" on a backend
     * that is perfectly able to generate - which is exactly the "0 models"
     * confusion the text catalogue already had to fix.
     *
     * Guarded on the fallback base URL: that key belongs to whatever endpoint
     * llm_base_url points at, and shipping a Groq or vLLM key to
     * api.openai.com would just produce a baffling 401.
     */
    resolveKey: (get) => {
      const direct = String(get('llm_openai_api_key') || '').trim();
      if (direct) {
        return direct;
      }
      const base = String(get('llm_base_url') || '').trim();
      if (!base || /\/\/(www\.)?api\.openai\.com/i.test(base)) {
        return String(get('llm_api_key') || '').trim();
      }
      return '';
    },
    generatePath: '/images/generations',
    listPath: '/models',
    authStyle: 'bearer',
    sizes: ['1536x1024', '1024x1024', '1024x1536'],
    buildBody: (model, prompt, size) => {
      const body = { model, prompt, size, n: 1 };
      // gpt-image-1 returns base64 unconditionally and 400s if asked for a
      // response_format; the dall-e models default to a URL unless told.
      if (!/^gpt-image/i.test(model)) {
        body.response_format = 'b64_json';
      }
      return body;
    },
    readImage: readOpenAIStyle,
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : [])
      .filter((m) => looksLikeImageModel(m.id))
      .map((m) => ({ model: m.id, label: m.id })),
  },

  together: {
    label: 'Together AI',
    base_url: 'https://api.together.xyz/v1',
    key_setting: 'image_together_api_key',
    generatePath: '/images/generations',
    listPath: '/models',
    authStyle: 'bearer',
    sizes: ['1536x1024', '1024x1024', '1024x1536'],
    // Together takes explicit dimensions rather than a size string.
    buildBody: (model, prompt, size) => {
      const [width, height] = String(size).split('x').map((n) => parseInt(n, 10));
      return { model, prompt, width, height, n: 1, response_format: 'b64_json' };
    },
    readImage: readOpenAIStyle,
    // Together's /models is a bare array (not { data: [] }) and tags each
    // entry with a type, which is more reliable than matching on the name.
    normalize: (body) => (Array.isArray(body) ? body : (body && body.data) || [])
      .filter((m) => m.type === 'image' || looksLikeImageModel(m.id))
      .map((m) => ({ model: m.id, label: m.display_name || m.id })),
  },

  xai: {
    label: 'xAI (Grok)',
    base_url: 'https://api.x.ai/v1',
    key_setting: 'image_xai_api_key',
    generatePath: '/images/generations',
    listPath: '/models',
    authStyle: 'bearer',
    // xAI's image endpoint has no size parameter at all - it returns its own
    // aspect ratio. Declaring one size keeps the UI honest about that.
    sizes: ['1024x1024'],
    buildBody: (model, prompt) => ({ model, prompt, n: 1, response_format: 'b64_json' }),
    readImage: readOpenAIStyle,
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : [])
      .filter((m) => looksLikeImageModel(m.id))
      .map((m) => ({ model: m.id, label: m.id })),
  },

  google: {
    label: 'Google (Imagen / Gemini)',
    base_url: 'https://generativelanguage.googleapis.com/v1beta',
    key_setting: 'llm_google_api_key',
    listPath: '/models',
    authStyle: 'google',
    native: true,
    sizes: ['1536x1024', '1024x1024', '1024x1536'],

    // Two different APIs behind one provider, chosen by model family.
    generateUrl: (base, model, apiKey) => {
      const verb = /^imagen/i.test(model) ? 'predict' : 'generateContent';
      return `${base}/models/${model}:${verb}?key=${encodeURIComponent(apiKey)}`;
    },
    buildBody: (model, prompt, size) => {
      if (/^imagen/i.test(model)) {
        const [width, height] = String(size).split('x').map((n) => parseInt(n, 10));
        return {
          instances: [{ prompt }],
          parameters: { sampleCount: 1, aspectRatio: width > height ? '16:9' : (width < height ? '9:16' : '1:1') },
        };
      }
      return {
        contents: [{ parts: [{ text: prompt }] }],
        generationConfig: { responseModalities: ['IMAGE'] },
      };
    },
    readImage: (body) => {
      // Imagen :predict
      const prediction = body && Array.isArray(body.predictions) ? body.predictions[0] : null;
      if (prediction && prediction.bytesBase64Encoded) {
        return { base64: prediction.bytesBase64Encoded };
      }
      // Gemini :generateContent - the image is an inlineData part sitting
      // alongside any text parts the model decided to emit.
      const candidate = body && Array.isArray(body.candidates) ? body.candidates[0] : null;
      const parts = candidate && candidate.content && Array.isArray(candidate.content.parts)
        ? candidate.content.parts
        : [];
      const imagePart = parts.find((p) => p.inlineData && p.inlineData.data);
      if (imagePart) {
        return { base64: imagePart.inlineData.data };
      }
      return null;
    },
    normalize: (body) => (body && Array.isArray(body.models) ? body.models : [])
      .filter((m) => {
        const methods = m.supportedGenerationMethods || [];
        const name = String(m.name || '').replace(/^models\//, '');
        return methods.includes('predict') || looksLikeImageModel(name);
      })
      .map((m) => {
        const name = String(m.name || '').replace(/^models\//, '');
        return { model: name, label: m.displayName || name };
      }),
  },

  /*
   * The self-hosted service in services/wookiee-sd, reachable on the compose
   * network. No API key: it is not exposed publicly and has no credential to
   * present, which is why `no_auth` exists rather than making the operator
   * invent a fake key to satisfy the configured check.
   *
   * Long timeout because this is CPU inference - see the note on timeoutMs
   * below. Only the sizes SD1.5 renders coherently are declared.
   */
  local: {
    label: 'Self-hosted (this server)',
    base_url_setting: 'image_local_base_url',
    no_auth: true,
    generatePath: '/images/generations',
    listPath: '/models',
    authStyle: 'none',
    sizes: ['1536x1024', '1024x1024', '1024x1536'],
    // CPU generation runs into minutes, not seconds. A cloud provider's
    // 3-minute ceiling would cut off a run that was going to succeed.
    timeoutMs: 900000,
    buildBody: (model, prompt, size) => ({ model, prompt, size, n: 1, response_format: 'b64_json' }),
    readImage: readOpenAIStyle,
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : [])
      .map((m) => ({ model: m.id, label: m.display_name || m.id })),
  },

  // Any other OpenAI-compatible images endpoint the operator wants to point
  // at - a self-hosted ComfyUI shim, Fireworks, DeepInfra, and so on. Same
  // reasoning as the text catalogue's `custom`: without it, a perfectly good
  // endpoint has no way into the picker.
  custom: {
    label: 'Custom image endpoint',
    base_url_setting: 'image_base_url',
    key_setting: 'image_api_key',
    generatePath: '/images/generations',
    listPath: '/models',
    authStyle: 'bearer',
    sizes: ['1536x1024', '1024x1024', '1024x1536'],
    buildBody: (model, prompt, size) => ({ model, prompt, size, n: 1, response_format: 'b64_json' }),
    readImage: readOpenAIStyle,
    normalize: (body) => (body && Array.isArray(body.data) ? body.data : [])
      .map((m) => ({ model: m.id, label: m.id })),
  },
};

/**
 * A provider's API key. Most read one setting; a provider may declare
 * resolveKey() to inherit from elsewhere when its own field is blank.
 */
function providerKey_key(providerKey, getSetting) {
  const provider = PROVIDERS[providerKey];
  if (!provider) {
    return '';
  }
  if (provider.no_auth) {
    return '';
  }
  if (provider.resolveKey) {
    return String(provider.resolveKey(getSetting) || '').trim();
  }
  return String(getSetting(provider.key_setting) || '').trim();
}

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
 * Format-only validation, matching modelCatalog.parseId - a provider
 * retiring a model must not silently erase the id stored on a licence.
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

function buildHeaders(provider, apiKey) {
  if (provider.no_auth) {
    return {};
  }
  if (provider.authStyle === 'google') {
    // Google takes the key on the query string, not a header.
    return {};
  }
  return { Authorization: `Bearer ${apiKey}` };
}

function listUrl(provider, baseUrl, apiKey) {
  const base = baseUrl.replace(/\/+$/, '');
  if (provider.authStyle === 'google') {
    return `${base}${provider.listPath}?key=${encodeURIComponent(apiKey)}&pageSize=200`;
  }
  return base + provider.listPath;
}

/**
 * Fetches (and caches) one provider's live image-model list. Never throws:
 * one provider being down must not empty the whole picker.
 */
async function fetchProviderModels(providerKey, apiKey, { force = false, baseUrl = '' } = {}) {
  const provider = PROVIDERS[providerKey];
  if (!provider) {
    return { error: 'Unknown provider.' };
  }
  if (!apiKey && !provider.no_auth) {
    return { models: [], unconfigured: true };
  }

  const effectiveBase = String(baseUrl || provider.base_url || '').trim();
  if (!effectiveBase) {
    return { error: `${provider.label}: no base URL configured.` };
  }

  const cacheKey = `${providerKey}|${effectiveBase}`;
  const cached = cache.get(cacheKey);
  if (!force && cached && (Date.now() - cached.fetchedAt) < CACHE_TTL_MS) {
    return { models: cached.models, cached: true, fetchedAt: cached.fetchedAt };
  }

  let response;
  try {
    response = await fetch(listUrl(provider, effectiveBase, apiKey), {
      headers: buildHeaders(provider, apiKey),
      signal: AbortSignal.timeout(15000),
    });
  } catch (err) {
    return { error: `Could not reach ${provider.label}: ${err.message}` };
  }

  const body = await response.json().catch(() => null);

  if (!response.ok) {
    const msg = body && body.error && body.error.message ? body.error.message : `HTTP ${response.status}`;
    return { error: `${provider.label}: ${msg}` };
  }

  let models;
  try {
    models = provider.normalize(body)
      .filter((m) => m.model)
      .map((m) => ({
        ...m,
        id: `${providerKey}:${m.model}`,
        provider: providerKey,
        provider_label: provider.label,
        sizes: provider.sizes,
      }))
      .sort((a, b) => a.label.localeCompare(b.label));
  } catch (err) {
    return { error: `${provider.label}: unexpected response shape (${err.message})` };
  }

  cache.set(cacheKey, { fetchedAt: Date.now(), models });
  return { models, fetchedAt: Date.now() };
}

/**
 * Live image-model list across every provider that has a key saved.
 * Mirrors modelCatalog.listConfiguredModels, including naming the custom
 * endpoint by the host it points at.
 */
async function listConfiguredModels(getSetting, { force = false } = {}) {
  const providerKeys = Object.keys(PROVIDERS);

  const results = await Promise.all(providerKeys.map(async (providerKey) => {
    const apiKey = providerKey_key(providerKey, getSetting);
    const baseUrl = resolveBaseUrl(providerKey, getSetting);
    const result = await fetchProviderModels(providerKey, apiKey, { force, baseUrl });
    return { providerKey, apiKey: Boolean(apiKey), baseUrl, result };
  }));

  const models = [];
  const errors = {};
  const providers = [];

  results.forEach(({ providerKey, apiKey, baseUrl, result }) => {
    let label = PROVIDERS[providerKey].label;
    if (!PROVIDERS[providerKey].base_url && baseUrl) {
      try {
        label += ` (${new URL(baseUrl).host})`;
      } catch (err) {
        // Malformed base URL is the operator's to fix; the fetch error says so.
      }
    }

    providers.push({
      key: providerKey,
      label,
      // A keyless provider counts as configured once it has somewhere to
      // point; asking for a key it does not use would be nonsense.
      configured: PROVIDERS[providerKey].no_auth ? Boolean(baseUrl) : apiKey,
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

/**
 * Generates one image. Returns { base64 } or throws with a provider-worded
 * message. `getSetting` is injected for the same reason as above.
 */
async function generate({ id, prompt, size, getSetting, timeoutMs = 0 }) {
  const parsed = parseId(id);
  if (!parsed) {
    throw new Error(`"${id}" is not a valid image model id.`);
  }

  const provider = PROVIDERS[parsed.provider];
  const apiKey = providerKey_key(parsed.provider, getSetting);
  if (!apiKey && !provider.no_auth) {
    throw new Error(`No API key saved for ${provider.label}.`);
  }

  const baseUrl = resolveBaseUrl(parsed.provider, getSetting).replace(/\/+$/, '');
  if (!baseUrl) {
    throw new Error(`No base URL configured for ${provider.label}.`);
  }

  // Fall back to the provider's first declared size rather than failing:
  // xAI supports no sizes at all, and the slot asking for 1536x1024 should
  // still get an image rather than an error about a parameter xAI ignores.
  const effectiveSize = provider.sizes.includes(size) ? size : provider.sizes[0];

  const url = provider.generateUrl
    ? provider.generateUrl(baseUrl, parsed.model, apiKey)
    : baseUrl + provider.generatePath;

  const headers = { 'Content-Type': 'application/json', ...buildHeaders(provider, apiKey) };

  // A provider may declare its own ceiling - the self-hosted CPU service
  // needs far longer than a cloud API would.
  const effectiveTimeout = timeoutMs || provider.timeoutMs || 180000;

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), effectiveTimeout);

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers,
      body: JSON.stringify(provider.buildBody(parsed.model, prompt, effectiveSize)),
      signal: controller.signal,
    });

    const body = await response.json().catch(() => null);

    if (!response.ok) {
      const msg = body && body.error && body.error.message ? body.error.message : `HTTP ${response.status}`;
      throw new Error(`${provider.label}: ${msg}`);
    }

    const image = provider.readImage(body);
    if (!image) {
      throw new Error(`${provider.label} returned no usable image data.`);
    }

    if (image.base64) {
      return { base64: image.base64, model: id };
    }

    const download = await fetch(image.url, { signal: controller.signal });
    if (!download.ok) {
      throw new Error(`Could not download the generated image from ${provider.label}.`);
    }
    const buffer = Buffer.from(await download.arrayBuffer());
    return { base64: buffer.toString('base64'), model: id };
  } catch (err) {
    if (err.name === 'AbortError') {
      throw new Error(`${provider.label} timed out.`);
    }
    throw err;
  } finally {
    clearTimeout(timeout);
  }
}

function clearCache() {
  cache.clear();
}

module.exports = {
  PROVIDERS,
  parseId,
  fetchProviderModels,
  listConfiguredModels,
  generate,
  clearCache,
};
