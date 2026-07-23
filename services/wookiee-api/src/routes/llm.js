/**
 * Faithful port of wookiee_call_llm() (inc/ai-client.php) - talks to any
 * OpenAI-compatible Chat Completions endpoint, configured entirely from the
 * stored settings (key/base URL/model), no vendor lock-in.
 */

const express = require('express');
const store = require('../secretsStore');

const router = express.Router();

router.post('/generate', async (req, res) => {
  const prompt = req.body && req.body.prompt;
  const maxTokens = req.body && req.body.max_tokens ? parseInt(req.body.max_tokens, 10) : 2048;

  if (!prompt || !String(prompt).trim()) {
    return res.status(400).json({ error: 'Missing prompt.' });
  }

  const apiKey = store.get('llm_api_key');
  if (!apiKey.trim()) {
    return res.status(400).json({ error: 'Add an LLM API key first.' });
  }

  const baseUrl = store.get('llm_base_url').replace(/\/+$/, '');
  const model = store.get('llm_default_model');

  let response;
  try {
    response = await fetch(`${baseUrl}/chat/completions`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        model,
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

  res.json({ text });
});

module.exports = router;
