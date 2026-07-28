/**
 * Web search, via Firecrawl.
 *
 * Exists so the WordPress side can find a business's own published phone
 * number instead of asking the operator to type one they may not have to
 * hand. Deliberately thin: this returns search results and nothing more, and
 * the interpretation - which of these numbers belongs to this business -
 * happens on the WordPress side, where the LLM plumbing, model selection and
 * per-licence limits already live.
 *
 * OPTIONAL BY DESIGN. With no key saved this answers 200 with an empty result
 * set and `configured: false`, not an error. Nothing that calls it should
 * fail, retry, or show a problem because web search was never set up - it is
 * an enrichment, and the field it fills can always be typed by hand.
 */

const express = require('express');
const store = require('../secretsStore');

const router = express.Router();

router.post('/search', async (req, res) => {
  const query = ((req.body && req.body.query) || '').trim();
  if (!query) {
    return res.status(400).json({ error: 'No query given.' });
  }

  const apiKey = store.get('firecrawl_api_key').trim();
  if (!apiKey) {
    // Not an error: see the note above.
    return res.json({ configured: false, results: [] });
  }

  const limit = Math.max(1, Math.min(10, parseInt(req.body.limit, 10) || 5));

  try {
    const response = await fetch('https://api.firecrawl.dev/v1/search', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
      /*
       * Scrape the results, do not just list them.
       *
       * Without this Firecrawl returns each result's meta description, which
       * is a summary of the page and almost never contains a phone number -
       * the number lives in a contact block partway down. Verified against a
       * real company: the descriptions came back with a Companies House
       * registered address and no number at all, so the lookup could only
       * ever report "not found".
       */
      body: JSON.stringify({
        query,
        limit,
        scrapeOptions: { formats: ['markdown'], onlyMainContent: true },
      }),
      // Short on purpose. A slow lookup must not hold up a setup step, and
      // the caller treats a timeout exactly like "nothing found".
      // Scraping several pages takes longer than listing them. Still bounded,
      // and the caller treats a timeout exactly like "nothing found".
      signal: AbortSignal.timeout(45000),
    });

    const body = await response.json().catch(() => null);

    if (!response.ok) {
      const msg = body && body.error ? body.error : `HTTP ${response.status}`;
      return res.json({ configured: true, results: [], error: `Firecrawl: ${msg}` });
    }

    const raw = body && Array.isArray(body.data) ? body.data : [];
    const results = raw.slice(0, limit).map((r) => ({
      title: String(r.title || '').slice(0, 300),
      url: String(r.url || '').slice(0, 500),
      // description is what Firecrawl returns without a scrape; markdown
      // appears when one was requested. Either may hold the number.
      // Markdown first now that it is requested - the description is the
      // fallback for a page that could not be scraped.
      snippet: String(r.markdown || r.description || '').slice(0, 4000),
    }));

    return res.json({ configured: true, results });
  } catch (err) {
    // Including AbortError. Reported, never thrown: the caller carries on.
    return res.json({ configured: true, results: [], error: `Firecrawl: ${err.message}` });
  }
});

module.exports = router;
