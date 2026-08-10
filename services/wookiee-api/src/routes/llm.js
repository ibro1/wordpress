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
const crypto = require('crypto');
const store = require('../secretsStore');
const licenseStore = require('../licenseStore');
const modelCatalog = require('../modelCatalog');

const router = express.Router();

/**
 * Structured, single-line JSON logs for the /generate path - this is the
 * one endpoint whose failures are otherwise invisible: WordPress only ever
 * sees "The LLM returned an empty response" or "could not reach the
 * server", with nothing in between to say WHICH provider, WHICH model, or
 * why a response that reached us successfully came back with nothing in
 * it. Every log line carries the same requestId so a single generation's
 * whole path - request, retry, stream stats, outcome - can be grepped out
 * of a container's log stream as one unit. Never logs the API key or the
 * full prompt/completion text (only bounded snippets of error bodies),
 * since this is a normal application log, not a debug capture of customer
 * content.
 */
function logGenerate(requestId, event, fields) {
  try {
    console.log(JSON.stringify({ at: 'llm.generate', requestId, event, ...fields }));
  } catch (err) {
    console.log('llm.generate log failed to serialise', event);
  }
}

// Host only, e.g. "agentrouter.org" - enough to tell providers apart in a
// log line without printing a full URL that might carry a query-string key
// on a custom endpoint.
function safeHost(url) {
  try {
    return new URL(url).host;
  } catch (err) {
    return null;
  }
}

// Bounds an error message before it goes into a log line - provider error
// bodies are occasionally huge (a full stack trace, an echoed prompt), and
// this is a log line, not a capture of the whole response.
function truncate(value, max = 300) {
  const str = String(value == null ? '' : value);
  return str.length > max ? `${str.slice(0, max)}…` : str;
}

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
 *
 * Also returns the stats that actually explain an empty result -
 * finish_reason ("length" means the budget ran out before any visible
 * text was written, "content_filter" means the provider refused it,
 * "stop" means the model just produced nothing to say) and how many
 * events/parse failures were seen - rather than leaving a silent, empty
 * string as the only trace of what happened on the wire.
 */
async function readSseCompletion(body) {
  const reader = body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';
  let text = '';
  let eventCount = 0;
  let parseFailures = 0;
  let finishReason = null;

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
        eventCount++;
        let parsed;
        try {
          parsed = JSON.parse(payload);
        } catch (err) {
          parseFailures++;
          continue; // A malformed one-off chunk shouldn't drop the whole completion.
        }
        const choice = parsed && parsed.choices && parsed.choices[0];
        if (choice && typeof choice.finish_reason === 'string' && choice.finish_reason) {
          finishReason = choice.finish_reason;
        }
        const delta = choice && choice.delta;
        if (delta && typeof delta.content === 'string') {
          text += delta.content;
        }
      }
    }
  }

  return { text: text.trim(), eventCount, parseFailures, finishReason };
}

/**
 * OpenAI's own newer "reasoning" models (the GPT-5/o-series family) reject
 * max_tokens outright and require max_completion_tokens instead - the two
 * aren't interchangeable across every model this registry can point at, so
 * whichever one a given model wants is discovered from its own rejection
 * rather than guessed per model name up front.
 */
// 429 (rate limited), 500/502/503/504 (upstream having a bad moment) -
// worth one retry rather than failing outright. Confirmed directly against
// AgentRouter: a plain "Upstream request failed" 500 on one attempt,
// nothing wrong with the request itself. Anything else (400, 401, 403...)
// is the request being genuinely rejected and retrying it changes nothing.
function isTransientError(status) {
  return status === 429 || (status >= 500 && status <= 504);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function isUnsupportedMaxTokensError(errBody) {
  const err = errBody && errBody.error;
  if (!err) {
    return false;
  }
  if (err.param === 'max_tokens') {
    return true;
  }
  return typeof err.message === 'string' && err.message.includes('max_completion_tokens');
}

function buildCompletionRequestBody(model, prompt, maxTokens, useMaxCompletionTokens) {
  const body = {
    model,
    messages: [{ role: 'user', content: prompt }],
    /*
     * Some gateways enforce a much shorter timeout on a fully-buffered
     * response than on a stream - confirmed directly against AgentRouter,
     * where a slow non-streaming completion for a long policy page came
     * back as a bare HTTP 504 well before this request's own timeout was
     * anywhere close. Streaming keeps the connection actively receiving
     * data instead of sitting idle waiting for one big response, which is
     * what a gateway's own timeout is actually watching for. The text is
     * still accumulated server-side and returned as one response below -
     * the caller's contract with this endpoint doesn't change.
     */
    stream: true,
  };
  body[useMaxCompletionTokens ? 'max_completion_tokens' : 'max_tokens'] = maxTokens;
  return body;
}

/*
 * A completion that stopped for hitting its length ceiling but produced
 * next to no visible text is not "a short answer" - it's the budget
 * having been spent on something other than the visible completion before
 * the model got to write it (an OpenAI reasoning model's hidden reasoning
 * tokens; Claude Opus via at least one gateway observed doing the
 * equivalent). Confirmed directly in production logs: a 8192-token budget
 * finishing with finish_reason "max_tokens" and 629 characters of visible
 * text - roughly 100 words out of a budget sized for several thousand.
 * 800 characters is a generous floor for "real content, just short" (a
 * short policy section still clears it easily); below that, at a
 * length-type finish reason, this is the same failure mode regardless of
 * which provider or which mechanism produced it.
 */
const MIN_PLAUSIBLE_COMPLETION_CHARS = 800;
function looksReasoningTruncated(text, finishReason) {
  return (finishReason === 'max_tokens' || finishReason === 'length')
    && (!text || text.length < MIN_PLAUSIBLE_COMPLETION_CHARS);
}

/**
 * One full attempt against the provider: request, ok-check, and body
 * parsing (stream or buffered), collapsed into one outcome shape so the
 * route below can retry without duplicating any of this.
 */
async function sendCompletionRequest(target, prompt, tokenBudget, useMaxCompletionTokens) {
  let response;
  try {
    response = await fetch(`${target.baseUrl}/chat/completions`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${target.apiKey}`,
        'Content-Type': 'application/json',
        ...target.extraHeaders,
      },
      body: JSON.stringify(buildCompletionRequestBody(target.model, prompt, tokenBudget, useMaxCompletionTokens)),
    });
  } catch (err) {
    return { networkError: err.message };
  }

  if (!response.ok) {
    const errBody = await response.json().catch(() => null);
    return { status: response.status, errBody };
  }

  const contentType = response.headers.get('content-type') || '';
  try {
    if (contentType.includes('text/event-stream') && response.body) {
      const sse = await readSseCompletion(response.body);
      return { text: sse.text, diagnostics: { mode: 'stream', eventCount: sse.eventCount, parseFailures: sse.parseFailures, finishReason: sse.finishReason } };
    }
    // The provider accepted the request but ignored stream:true (or this
    // endpoint just never streams) - handled exactly as a plain,
    // non-streaming response always was.
    const data = await response.json().catch(() => null);
    const choice = data && data.choices && data.choices[0];
    const text = choice && choice.message ? String(choice.message.content || '').trim() : '';
    return {
      text,
      diagnostics: {
        mode: 'buffered',
        hadChoices: Boolean(data && Array.isArray(data.choices) && data.choices.length),
        finishReason: choice && choice.finish_reason,
      },
    };
  } catch (err) {
    return { readError: err.message };
  }
}

router.post('/generate', async (req, res) => {
  const requestId = crypto.randomUUID();
  const startedAt = Date.now();
  const prompt = req.body && req.body.prompt;
  const maxTokens = req.body && req.body.max_tokens ? parseInt(req.body.max_tokens, 10) : 2048;

  if (!prompt || !String(prompt).trim()) {
    return res.status(400).json({ error: 'Missing prompt.' });
  }

  const licenseEntry = req.license ? req.license.entry : null;
  const target = resolveTarget(req.body && req.body.model, licenseEntry);
  if (target.error) {
    logGenerate(requestId, 'resolve_target_failed', { error: target.error });
    return res.status(target.status).json({ error: target.error });
  }

  logGenerate(requestId, 'start', {
    // baseUrl's host only - never the key, and the path/query on some
    // custom endpoints could itself be operator-identifying.
    providerHost: safeHost(target.baseUrl),
    model: target.model,
    maxTokens,
    promptLength: String(prompt).length,
    license: req.license ? req.license.code : null,
  });

  let result = await sendCompletionRequest(target, prompt, maxTokens, false);

  if (result.networkError) {
    logGenerate(requestId, 'fetch_failed', { error: result.networkError });
    return res.status(502).json({ error: result.networkError });
  }

  if (result.status) {
    // A hard rejection - not a completion that came back too short, but the
    // request itself refused. Two known causes retried here, at most once
    // each so a genuinely bad request can't loop: the model wants
    // max_completion_tokens instead of max_tokens, or the same request is
    // simply worth one more try (a 429/5xx can be transient).
    logGenerate(requestId, 'first_attempt_failed', {
      status: result.status,
      errorType: result.errBody && result.errBody.error && result.errBody.error.type,
      errorParam: result.errBody && result.errBody.error && result.errBody.error.param,
      errorMessage: truncate(result.errBody && result.errBody.error && result.errBody.error.message),
    });

    if (isUnsupportedMaxTokensError(result.errBody)) {
      const retryBudget = Math.max(maxTokens * 3, 16000);
      logGenerate(requestId, 'retrying_with_max_completion_tokens', { retryBudget });
      /*
       * A model that rejects max_tokens in favour of max_completion_tokens
       * is, by that same rejection, a reasoning-family model (GPT-5/
       * o-series) - and those spend part of that budget on hidden internal
       * reasoning before writing anything visible. A budget sized only for
       * the plain output can be consumed entirely by reasoning and return
       * zero visible text, so this retry asks for real headroom on top of
       * what was actually requested, not the same number under a
       * different name.
       */
      result = await sendCompletionRequest(target, prompt, retryBudget, true);
      if (result.networkError) {
        logGenerate(requestId, 'retry_fetch_failed', { error: result.networkError });
        return res.status(502).json({ error: result.networkError });
      }
      if (result.status) {
        const msg = result.errBody && result.errBody.error && result.errBody.error.message ? result.errBody.error.message : `HTTP ${result.status}`;
        logGenerate(requestId, 'retry_attempt_failed', { status: result.status, errorMessage: truncate(msg) });
        return res.status(502).json({ error: `LLM API error: ${msg}` });
      }
    } else if (isTransientError(result.status)) {
      logGenerate(requestId, 'retrying_transient_error', { status: result.status });
      await sleep(3000);
      result = await sendCompletionRequest(target, prompt, maxTokens, false);
      if (result.networkError) {
        logGenerate(requestId, 'retry_fetch_failed', { error: result.networkError });
        return res.status(502).json({ error: result.networkError });
      }
      if (result.status) {
        const msg = result.errBody && result.errBody.error && result.errBody.error.message ? result.errBody.error.message : `HTTP ${result.status}`;
        logGenerate(requestId, 'retry_attempt_failed', { status: result.status, errorMessage: truncate(msg) });
        return res.status(502).json({ error: `LLM API error: ${msg}` });
      }
    } else {
      const msg = result.errBody && result.errBody.error && result.errBody.error.message ? result.errBody.error.message : `HTTP ${result.status}`;
      return res.status(502).json({ error: `LLM API error: ${msg}` });
    }
  } else if (looksReasoningTruncated(result.text, result.diagnostics && result.diagnostics.finishReason)) {
    /*
     * The request succeeded and the provider considers this a complete
     * response - it just spent almost the whole budget on something other
     * than the visible completion. Confirmed live: an 8192-token budget
     * that finished with finish_reason "max_tokens" and 629 characters of
     * output. Retrying at max_tokens*4 (floor 24000) rather than the
     * smaller floor above - this has already been seen to survive one
     * ordinary headroom bump and still come back this short.
     */
    const retryBudget = Math.max(maxTokens * 4, 24000);
    logGenerate(requestId, 'retrying_reasoning_truncated', {
      firstAttemptTextLength: result.text ? result.text.length : 0,
      finishReason: result.diagnostics && result.diagnostics.finishReason,
      retryBudget,
    });
    const retryResult = await sendCompletionRequest(target, prompt, retryBudget, false);
    if (!retryResult.networkError && !retryResult.status) {
      result = retryResult;
    } else {
      logGenerate(requestId, 'reasoning_retry_failed', {
        networkError: retryResult.networkError,
        status: retryResult.status,
      });
      // Fall through with whatever the first attempt produced - a short
      // answer (or none) beats discarding it for a retry that also failed.
    }
  }

  if (result.readError) {
    logGenerate(requestId, 'body_read_failed', { error: result.readError });
    return res.status(502).json({ error: `Stream reading failed: ${result.readError}` });
  }

  const text = result.text;
  const diagnostics = result.diagnostics || {};

  if (!text) {
    // This is the one outcome that reaches WordPress as a flat, unhelpful
    // "empty response" - the diagnostics logged here (finish_reason above
    // all) are what actually explain it: "length"/"max_tokens" means the
    // token budget ran out before any visible text was written (see the
    // retries above), "content_filter" means the provider itself refused
    // the prompt, and neither looks like "the model returned nothing" from
    // the caller's side.
    logGenerate(requestId, 'empty_completion', { ...diagnostics, elapsedMs: Date.now() - startedAt });
    return res.status(502).json({ error: 'The LLM returned an empty response.' });
  }

  logGenerate(requestId, 'success', { ...diagnostics, textLength: text.length, elapsedMs: Date.now() - startedAt });

  // Echoed back so the caller can log/display which model actually served
  // the request - it may have come from the license default rather than
  // anything the caller explicitly asked for.
  res.json({ text, model: target.model });
});

module.exports = router;
