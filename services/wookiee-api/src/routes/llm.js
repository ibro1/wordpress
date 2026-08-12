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
// 429 (rate limited), 500/502/503/504 (upstream having a bad moment) - worth
// retrying rather than failing outright. Confirmed directly against
// AgentRouter: back-to-back attempts for the same model have been seen
// bouncing between all three of these (rate limit, "no available channel",
// gateway timeout) within minutes of each other, so ONE retry consistently
// wasn't enough - see TRANSIENT_RETRY_DELAYS_MS below. Anything else (400,
// 401, 403...) is the request being genuinely rejected and retrying it
// changes nothing.
function isTransientError(status) {
  return status === 429 || (status >= 500 && status <= 504);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/*
 * How many times (and how long to wait between) a single model gets retried
 * on a transient error before this endpoint gives up on it. Backed off
 * rather than fixed-interval, and long enough to plausibly outlast a
 * short-lived upstream hiccup (a rate-limit window resetting, a distributor
 * finding an available channel) without the caller needing to know any of
 * that happened.
 */
const TRANSIENT_RETRY_DELAYS_MS = [3000, 8000, 15000];

/*
 * A hard ceiling on how long this endpoint will spend on ONE /generate call,
 * across every attempt at the primary model, its retries, and a fallback
 * model if one is configured. Exists so a provider that is failing slowly
 * (rather than failing fast) can never silently consume the entire time
 * budget WordPress gave this request (see the set_time_limit()/timeout
 * values in inc/ai-client.php, which are sized with this constant in mind)
 * and get killed mid-response instead of returning a clean error.
 */
const OVERALL_BUDGET_MS = 500000;

// Below this much time left, trying a whole second model - itself worth
// several attempts - isn't realistic; fail with whatever the primary model
// produced instead of starting something that will just get cut off anyway.
const MIN_FALLBACK_BUDGET_MS = 20000;

/**
 * Fire-and-forget status ping back to WordPress while a generation is still
 * running, so the job's poll response (see wookiee_poll_job_handler() and
 * wookiee_update_job_progress_handler() in inc/background-jobs.php) can show
 * the browser what is actually happening server-side - which attempt, which
 * model, why it's retrying - instead of one static "Generating..." message
 * for however long a slow or flaky provider takes. Best-effort only: a
 * failed or slow progress ping must never slow down or fail the generation
 * itself, so this is never awaited by its caller and swallows its own
 * errors.
 */
async function postProgressUpdate(callback, message) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 4000);
  try {
    const params = new URLSearchParams();
    params.set('action', 'wookiee_update_job_progress');
    params.set('job_id', callback.job_id);
    params.set('secret', callback.secret);
    params.set('message', message);
    await fetch(callback.url, { method: 'POST', body: params, signal: controller.signal });
  } catch (err) {
    // Best-effort - see the function comment above.
  } finally {
    clearTimeout(timer);
  }
}

// Returns a no-op when the caller didn't opt in (no progress_callback in the
// request body - the legacy/direct-call shape), so every call site below can
// call reportProgress() unconditionally without an extra branch.
function buildProgressReporter(callback) {
  if (!callback || !callback.url || !callback.job_id || !callback.secret) {
    return () => {};
  }
  return (message) => {
    postProgressUpdate(callback, message);
  };
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
 * finish_reason "max_tokens"/"length" means the model was cut off before
 * IT chose to stop - full stop, regardless of how much text came out
 * before that happened. The earlier version of this check only retried
 * when the output was suspiciously short (under ~800 characters), on the
 * theory that this was specifically about hidden reasoning tokens eating
 * the whole budget. That undersold the problem: production logs show
 * plenty of max_tokens completions in the 4,000-14,000 character range -
 * long enough to clear that floor, still genuinely incomplete (a policy
 * document cut off mid-clause, not a short one that happened to finish).
 * Those were logged as "success" here and only caught afterward by
 * WordPress's own truncation guard - wasting a full generation round trip
 * on a result that was always going to be rejected. Retrying on ANY
 * length-ceiling finish, not just short ones, catches it at the source
 * instead. Safe to do unconditionally now: nothing here is racing a
 * browser-facing timeout (see inc/background-jobs.php on the WordPress
 * side), so the extra attempt costs time, never correctness.
 */
function looksReasoningTruncated(text, finishReason) {
  return finishReason === 'max_tokens' || finishReason === 'length';
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

/**
 * Runs one model to exhaustion: an initial attempt, plus two kinds of retry
 * layered on top of each other -
 *
 *  - Deterministic corrections, applied once each and free of the backoff
 *    budget below because they fix a known, specific problem rather than
 *    hoping the same request works better a second time: swapping
 *    max_tokens for max_completion_tokens when the provider demands it, and
 *    asking for a bigger budget when a completion came back cut off at the
 *    length ceiling (see looksReasoningTruncated).
 *  - Backed-off retries (TRANSIENT_RETRY_DELAYS_MS) for a plain 429/5xx,
 *    where the request itself is fine and the provider is just having a bad
 *    moment.
 *
 * Both are bounded by ctx.deadline so a model that keeps failing slowly
 * can't quietly burn the whole request budget - see OVERALL_BUDGET_MS.
 * Returns {text, diagnostics} on success, {error, status} once every avenue
 * for this specific model is exhausted; the caller decides whether a
 * fallback model is worth trying next.
 */
async function runModelToExhaustion(target, prompt, maxTokens, ctx) {
  const { requestId, label, reportProgress, deadline } = ctx;
  let tokenBudget = maxTokens;
  let useMaxCompletionTokens = false;
  let truncationRetried = false;
  let transientAttempt = 0;
  let attemptCount = 0;

  for (;;) {
    if (attemptCount > 0 && Date.now() >= deadline) {
      logGenerate(requestId, 'budget_exhausted', { label, attemptCount });
      return { error: 'Ran out of time waiting on a provider that kept failing.', status: 504 };
    }
    attemptCount++;

    const result = await sendCompletionRequest(target, prompt, tokenBudget, useMaxCompletionTokens);

    if (result.networkError) {
      logGenerate(requestId, 'fetch_failed', { label, attempt: attemptCount, error: result.networkError });
      if (transientAttempt < TRANSIENT_RETRY_DELAYS_MS.length) {
        const delay = TRANSIENT_RETRY_DELAYS_MS[transientAttempt++];
        reportProgress(`${label}: connection error, retrying in ${Math.round(delay / 1000)}s…`);
        await sleep(delay);
        continue;
      }
      return { error: result.networkError, status: 502 };
    }

    if (result.status) {
      // A hard rejection - not a completion that came back too short, but
      // the request itself refused.
      logGenerate(requestId, 'attempt_failed', {
        label,
        attempt: attemptCount,
        status: result.status,
        errorType: result.errBody && result.errBody.error && result.errBody.error.type,
        errorParam: result.errBody && result.errBody.error && result.errBody.error.param,
        errorMessage: truncate(result.errBody && result.errBody.error && result.errBody.error.message),
      });

      if (isUnsupportedMaxTokensError(result.errBody) && !useMaxCompletionTokens) {
        /*
         * A model that rejects max_tokens in favour of max_completion_tokens
         * is, by that same rejection, a reasoning-family model (GPT-5/
         * o-series) - and those spend part of that budget on hidden
         * internal reasoning before writing anything visible. A budget
         * sized only for the plain output can be consumed entirely by
         * reasoning and return zero visible text, so this switch asks for
         * real headroom on top of what was actually requested.
         */
        useMaxCompletionTokens = true;
        tokenBudget = Math.max(tokenBudget * 3, 16000);
        logGenerate(requestId, 'switching_to_max_completion_tokens', { label, retryBudget: tokenBudget });
        reportProgress(`${label}: adjusting request for a reasoning model…`);
        continue;
      }

      if (isTransientError(result.status) && transientAttempt < TRANSIENT_RETRY_DELAYS_MS.length) {
        const delay = TRANSIENT_RETRY_DELAYS_MS[transientAttempt++];
        const reason = result.status === 429 ? 'rate limited' : `upstream error ${result.status}`;
        logGenerate(requestId, 'retrying_transient_error', { label, attempt: attemptCount, status: result.status, delay });
        reportProgress(`${label}: ${reason}, retrying in ${Math.round(delay / 1000)}s…`);
        await sleep(delay);
        continue;
      }

      const msg = result.errBody && result.errBody.error && result.errBody.error.message ? result.errBody.error.message : `HTTP ${result.status}`;
      return { error: msg, status: 502 };
    }

    if (result.readError) {
      return { error: `Stream reading failed: ${result.readError}`, status: 502 };
    }

    const finishReason = result.diagnostics && result.diagnostics.finishReason;
    if (!truncationRetried && looksReasoningTruncated(result.text, finishReason)) {
      /*
       * The request succeeded and the provider considers this a complete
       * response - it just spent almost the whole budget on something other
       * than the visible completion. Retrying at max_tokens*4 (floor
       * 24000): a smaller headroom bump has already been seen to survive
       * once and still come back cut off.
       */
      truncationRetried = true;
      tokenBudget = Math.max(maxTokens * 4, 24000);
      logGenerate(requestId, 'retrying_reasoning_truncated', {
        label,
        firstAttemptTextLength: result.text ? result.text.length : 0,
        finishReason,
        retryBudget: tokenBudget,
      });
      reportProgress(`${label}: response was cut off, retrying with more room…`);
      continue;
    }

    return { text: result.text, diagnostics: result.diagnostics };
  }
}

router.post('/generate', async (req, res) => {
  const requestId = crypto.randomUUID();
  const startedAt = Date.now();
  const deadline = startedAt + OVERALL_BUDGET_MS;
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

  const reportProgress = buildProgressReporter(req.body && req.body.progress_callback);

  logGenerate(requestId, 'start', {
    // baseUrl's host only - never the key, and the path/query on some
    // custom endpoints could itself be operator-identifying.
    providerHost: safeHost(target.baseUrl),
    model: target.model,
    maxTokens,
    promptLength: String(prompt).length,
    license: req.license ? req.license.code : null,
  });
  reportProgress(`Generating with ${target.model}…`);

  let outcome = await runModelToExhaustion(target, prompt, maxTokens, {
    requestId, label: target.model, reportProgress, deadline,
  });
  let servedBy = target.model;

  // Falls back to a second, operator-configured model when the primary one
  // never produced usable text - whether that's a hard error after
  // exhausting its own retries, or a "successful" response that still came
  // back empty. Skipped once there isn't realistically enough time left in
  // the overall budget to run a second model through its own retry cycle.
  if (outcome.error || !outcome.text) {
    logGenerate(requestId, 'primary_model_exhausted', {
      model: target.model,
      error: outcome.error ? truncate(outcome.error) : 'empty completion',
    });

    const fallbackModel = req.body && req.body.fallback_model ? String(req.body.fallback_model).trim() : '';
    const remainingMs = deadline - Date.now();

    if (fallbackModel && remainingMs > MIN_FALLBACK_BUDGET_MS) {
      const fallbackTarget = resolveTarget(fallbackModel, licenseEntry);
      if (fallbackTarget.error) {
        logGenerate(requestId, 'fallback_resolve_failed', { error: fallbackTarget.error });
      } else {
        logGenerate(requestId, 'trying_fallback_model', { model: fallbackTarget.model });
        reportProgress(`${target.model} isn't responding, switching to ${fallbackTarget.model}…`);
        const fallbackOutcome = await runModelToExhaustion(fallbackTarget, prompt, maxTokens, {
          requestId, label: fallbackTarget.model, reportProgress, deadline,
        });
        if (!fallbackOutcome.error && fallbackOutcome.text) {
          outcome = fallbackOutcome;
          servedBy = fallbackTarget.model;
        } else {
          logGenerate(requestId, 'fallback_model_exhausted', {
            model: fallbackTarget.model,
            error: fallbackOutcome.error ? truncate(fallbackOutcome.error) : 'empty completion',
          });
          // The fallback's own error is more informative than the primary's
          // stale one, if it has one - it's the more recent thing that
          // actually happened.
          if (fallbackOutcome.error) {
            outcome = fallbackOutcome;
          }
        }
      }
    } else if (fallbackModel) {
      logGenerate(requestId, 'fallback_skipped_out_of_budget', { remainingMs });
    }
  }

  if (outcome.error) {
    return res.status(502).json({ error: `LLM API error: ${outcome.error}` });
  }

  const text = outcome.text;
  const diagnostics = outcome.diagnostics || {};

  if (!text) {
    // This is the one outcome that reaches WordPress as a flat, unhelpful
    // "empty response" - the diagnostics logged here (finish_reason above
    // all) are what actually explain it, and servedBy confirms whether this
    // is the primary model or an already-attempted fallback.
    logGenerate(requestId, 'empty_completion', { ...diagnostics, elapsedMs: Date.now() - startedAt, servedBy });
    return res.status(502).json({ error: 'The LLM returned an empty response.' });
  }

  logGenerate(requestId, 'success', { ...diagnostics, textLength: text.length, elapsedMs: Date.now() - startedAt, model: servedBy });
  reportProgress('Done.');

  // Echoed back so the caller can log/display which model actually served
  // the request - it may have come from the license default, or the
  // fallback, rather than anything the caller explicitly asked for.
  res.json({ text, model: servedBy });
});

module.exports = router;
