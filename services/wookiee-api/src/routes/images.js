/**
 * Faithful port of the provider-calling half of inc/background-removal.php
 * (wookiee_bg_removal_cloudinary/wookiee_bg_removal_rembg) - returns the
 * raw transparent-background PNG bytes (base64-encoded, since this is a
 * JSON API) for whichever provider succeeds first. Compositing that PNG
 * onto solid white stays a WordPress-side step (GD, bundled with
 * WordPress, needs no credentials) rather than being ported here - only
 * the parts that need centralized credentials move to the backend.
 */

const express = require('express');
const crypto = require('crypto');
const store = require('../secretsStore');

const router = express.Router();

function cloudinaryConfigured() {
  return Boolean(
    store.get('cloudinary_cloud_name').trim()
    && store.get('cloudinary_api_key').trim()
    && store.get('cloudinary_api_secret').trim(),
  );
}

function rembgConfigured() {
  return Boolean(store.get('rembg_endpoint_url').trim());
}

async function removeBackgroundCloudinary(imageUrl) {
  const cloudName = store.get('cloudinary_cloud_name');
  const apiKey = store.get('cloudinary_api_key');
  const apiSecret = store.get('cloudinary_api_secret');

  const timestamp = Math.floor(Date.now() / 1000);
  const paramsToSign = { background_removal: 'cloudinary_ai', timestamp };
  const toSign = Object.keys(paramsToSign).sort()
    .map((k) => `${k}=${paramsToSign[k]}`)
    .join('&');
  const signature = crypto.createHash('sha1').update(toSign + apiSecret).digest('hex');

  const body = new URLSearchParams({
    file: imageUrl,
    api_key: apiKey,
    timestamp: String(timestamp),
    background_removal: 'cloudinary_ai',
    signature,
  });

  const response = await fetch(`https://api.cloudinary.com/v1_1/${cloudName}/image/upload`, {
    method: 'POST',
    body,
  });
  const data = await response.json().catch(() => null);

  if (!response.ok) {
    const msg = data && data.error && data.error.message ? data.error.message : `HTTP ${response.status}`;
    throw new Error(`Cloudinary error: ${msg}`);
  }

  const pngUrl = data && data.secure_url;
  if (!pngUrl) {
    throw new Error('Cloudinary did not return a processed image URL.');
  }

  const imageResponse = await fetch(pngUrl);
  if (!imageResponse.ok) {
    throw new Error('Could not download the processed image from Cloudinary.');
  }
  return Buffer.from(await imageResponse.arrayBuffer());
}

/**
 * Same GET-with-URL-bug workaround as the PHP version: rembg's own
 * "fetch this URL" route was unreliable for real CJ CDN images, so this
 * downloads the source image itself and uploads the bytes directly to
 * rembg's multipart "Remove from Stream" route instead.
 */
async function removeBackgroundRembg(imageUrl) {
  const endpoint = store.get('rembg_endpoint_url').replace(/\/+$/, '');

  const imageResponse = await fetch(imageUrl);
  if (!imageResponse.ok) {
    throw new Error(`Could not download the source image (HTTP ${imageResponse.status}).`);
  }
  const imageBuffer = Buffer.from(await imageResponse.arrayBuffer());
  if (!imageBuffer.length) {
    throw new Error('Downloaded source image was empty.');
  }

  const form = new FormData();
  form.append('file', new Blob([imageBuffer], { type: 'image/jpeg' }), 'source.jpg');

  const response = await fetch(`${endpoint}/api/remove`, { method: 'POST', body: form });
  if (!response.ok) {
    const data = await response.json().catch(() => null);
    const msg = data && data.detail ? JSON.stringify(data.detail) : `HTTP ${response.status}`;
    throw new Error(`Self-hosted rembg service error: ${msg}`);
  }

  const resultBuffer = Buffer.from(await response.arrayBuffer());
  if (!resultBuffer.length) {
    throw new Error('Self-hosted rembg service returned an empty response.');
  }
  return resultBuffer;
}

router.post('/remove-background', async (req, res) => {
  const imageUrl = req.body && req.body.image_url;
  if (!imageUrl) {
    return res.status(400).json({ error: 'No image_url given.' });
  }

  const primary = store.get('bg_removal_provider');
  if (!primary || primary === 'none') {
    return res.status(400).json({ error: 'Background removal is not enabled on the backend.' });
  }

  const order = primary === 'cloudinary' ? ['cloudinary', 'rembg'] : ['rembg', 'cloudinary'];

  let lastError = null;
  for (const provider of order) {
    if (provider === 'cloudinary' && !cloudinaryConfigured()) continue;
    if (provider === 'rembg' && !rembgConfigured()) continue;

    try {
      const png = provider === 'cloudinary'
        ? await removeBackgroundCloudinary(imageUrl)
        : await removeBackgroundRembg(imageUrl);
      return res.json({ image_base64: png.toString('base64') });
    } catch (err) {
      lastError = err;
    }
  }

  res.status(502).json({ error: lastError ? lastError.message : 'No background-removal provider is configured with valid credentials.' });
});

/* -------------------------------------------------------------------------
 * Image generation
 * ---------------------------------------------------------------------- */

/**
 * Landscape by default: every slot this fills (homepage hero, About hero,
 * About story) sits beside a column of text, so a square image forces the
 * text column narrow and a portrait one pushes the copy off the screen.
 */
const IMAGE_SIZES = ['1536x1024', '1024x1024', '1024x1536'];
const IMAGE_MODEL_DEFAULT = 'gpt-image-1';

/**
 * The operator's OpenAI key, falling back to the legacy single-endpoint key.
 * Most installs configured that one first and it is an OpenAI key in
 * practice, so requiring the newer per-provider setting would report "not
 * configured" on sites that are in fact perfectly able to generate.
 */
function imageApiKey() {
  return store.get('llm_openai_api_key').trim() || store.get('llm_api_key').trim();
}

router.post('/generate', async (req, res) => {
  const prompt = ((req.body && req.body.prompt) || '').trim();
  if (!prompt) {
    return res.status(400).json({ error: 'No prompt given.' });
  }

  const requestedSize = (req.body && req.body.size) || IMAGE_SIZES[0];
  const size = IMAGE_SIZES.includes(requestedSize) ? requestedSize : IMAGE_SIZES[0];

  const apiKey = imageApiKey();
  if (!apiKey) {
    return res.status(400).json({ error: 'No image-capable API key is configured on the backend.' });
  }

  const model = store.get('image_model').trim() || IMAGE_MODEL_DEFAULT;

  // Image generation regularly runs past 60s; the default fetch timeout
  // would abort a request that was going to succeed.
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 180000);

  try {
    const response = await fetch('https://api.openai.com/v1/images/generations', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ model, prompt, size, n: 1 }),
      signal: controller.signal,
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
      const msg = data && data.error && data.error.message ? data.error.message : `HTTP ${response.status}`;
      return res.status(502).json({ error: `Image generation failed: ${msg}` });
    }

    const first = data && Array.isArray(data.data) ? data.data[0] : null;
    if (!first) {
      return res.status(502).json({ error: 'The image provider returned no image.' });
    }

    // gpt-image-1 always returns base64; the DALL-E models return a URL
    // unless asked otherwise. Handling both means changing image_model on
    // the backend does not need a matching change here.
    if (first.b64_json) {
      return res.json({ image_base64: first.b64_json });
    }

    if (first.url) {
      const download = await fetch(first.url);
      if (!download.ok) {
        return res.status(502).json({ error: 'Could not download the generated image.' });
      }
      const buffer = Buffer.from(await download.arrayBuffer());
      return res.json({ image_base64: buffer.toString('base64') });
    }

    return res.status(502).json({ error: 'The image provider returned no usable image data.' });
  } catch (err) {
    const msg = err.name === 'AbortError' ? 'The image provider timed out.' : err.message;
    return res.status(502).json({ error: `Image generation failed: ${msg}` });
  } finally {
    clearTimeout(timeout);
  }
});

module.exports = router;
