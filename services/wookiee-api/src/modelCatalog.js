/**
 * Provider registry + model catalog.
 *
 * Every provider here speaks the OpenAI Chat Completions wire format
 * (`POST {base_url}/chat/completions`, `Authorization: Bearer <key>`), so
 * routes/llm.js needs exactly one request shape no matter which model is
 * picked - including Anthropic and Google, both of which publish an
 * OpenAI-compatible endpoint alongside their native APIs:
 *   - Anthropic:  https://api.anthropic.com/v1  (docs.anthropic.com/en/api/openai-sdk)
 *   - Google:     https://generativelanguage.googleapis.com/v1beta/openai
 * Known gaps on those compatibility layers (prompt caching, strict tool
 * schemas, audio input) don't apply to this service - it only ever sends a
 * single-turn text prompt and reads back choices[0].message.content.
 *
 * A model's catalog id is `<provider>:<wire model name>`, e.g.
 *   openrouter:anthropic/claude-haiku-4.5
 *   anthropic:claude-haiku-4-5
 * The same underlying model therefore appears once per route it can be
 * reached through, which is the point: the operator may hold an OpenRouter
 * key, a direct Anthropic key, or both, and a license can be limited to
 * whichever of those the operator wants that customer billing through.
 */

const PROVIDERS = {
  openrouter: {
    label: 'OpenRouter',
    base_url: 'https://openrouter.ai/api/v1',
    key_setting: 'llm_openrouter_api_key',
  },
  openai: {
    label: 'OpenAI',
    base_url: 'https://api.openai.com/v1',
    key_setting: 'llm_openai_api_key',
  },
  anthropic: {
    label: 'Anthropic',
    base_url: 'https://api.anthropic.com/v1',
    key_setting: 'llm_anthropic_api_key',
  },
  google: {
    label: 'Google Gemini',
    base_url: 'https://generativelanguage.googleapis.com/v1beta/openai',
    key_setting: 'llm_google_api_key',
  },
  deepseek: {
    label: 'DeepSeek',
    base_url: 'https://api.deepseek.com/v1',
    key_setting: 'llm_deepseek_api_key',
  },
};

/**
 * Indicative list prices in USD per 1M tokens, recorded 2026-07. Shown in
 * the admin UI purely to help the operator choose - they are NOT used for
 * billing or enforcement anywhere, and vendors change them without notice,
 * so treat them as a stale-able hint and re-check the provider's own
 * pricing page before making a commercial decision on them.
 */
const MODELS = [
  // --- via OpenRouter (one key reaches all of these) ---
  { id: 'openrouter:deepseek/deepseek-v4-flash', label: 'DeepSeek V4 Flash', in: 0.14, out: 0.28 },
  { id: 'openrouter:google/gemini-2.5-flash-lite', label: 'Gemini 2.5 Flash-Lite', in: 0.10, out: 0.40 },
  { id: 'openrouter:google/gemini-2.5-flash', label: 'Gemini 2.5 Flash', in: 0.30, out: 2.50 },
  { id: 'openrouter:openai/gpt-5.4-nano', label: 'GPT-5.4 nano', in: 0.20, out: 1.25 },
  { id: 'openrouter:openai/gpt-5.4-mini', label: 'GPT-5.4 mini', in: 0.75, out: 4.50 },
  { id: 'openrouter:anthropic/claude-haiku-4.5', label: 'Claude Haiku 4.5', in: 1.00, out: 5.00 },
  { id: 'openrouter:anthropic/claude-sonnet-5', label: 'Claude Sonnet 5', in: 2.00, out: 10.00 },

  // --- direct provider keys (no aggregator fee, separate billing) ---
  { id: 'deepseek:deepseek-chat', label: 'DeepSeek V4 Flash (direct)', in: 0.14, out: 0.28 },
  { id: 'google:gemini-2.5-flash-lite', label: 'Gemini 2.5 Flash-Lite (direct)', in: 0.10, out: 0.40 },
  { id: 'google:gemini-2.5-flash', label: 'Gemini 2.5 Flash (direct)', in: 0.30, out: 2.50 },
  { id: 'openai:gpt-5.4-nano', label: 'GPT-5.4 nano (direct)', in: 0.20, out: 1.25 },
  { id: 'openai:gpt-5.4-mini', label: 'GPT-5.4 mini (direct)', in: 0.75, out: 4.50 },
  { id: 'anthropic:claude-haiku-4-5', label: 'Claude Haiku 4.5 (direct)', in: 1.00, out: 5.00 },
  { id: 'anthropic:claude-sonnet-5', label: 'Claude Sonnet 5 (direct)', in: 2.00, out: 10.00 },
];

/**
 * Splits a catalog id into its provider + wire model name. Returns null for
 * anything that doesn't name a provider this service knows about, so
 * callers can reject unknown ids rather than blindly forwarding them.
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
  return Object.keys(PROVIDERS).map((key) => ({ key, ...PROVIDERS[key] }));
}

/**
 * The catalog, annotated with each model's provider/label so the admin UI
 * can group and describe them without duplicating the registry client-side.
 */
function listModels() {
  return MODELS.map((m) => {
    const parsed = parseId(m.id);
    return {
      ...m,
      provider: parsed ? parsed.provider : null,
      provider_label: parsed ? PROVIDERS[parsed.provider].label : null,
    };
  });
}

function isKnownModel(id) {
  return MODELS.some((m) => m.id === id);
}

module.exports = { PROVIDERS, listProviders, listModels, parseId, isKnownModel };
