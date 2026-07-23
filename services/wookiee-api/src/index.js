const express = require('express');
const path = require('path');
const { requireAuth } = require('./auth');

const app = express();

// Traefik sits in front of this container - trust its X-Forwarded-Proto/Host
// so req.protocol and the Google OAuth redirect URI come out as https and
// the real public hostname, not http/the internal container name.
app.set('trust proxy', true);

app.use(express.json({ limit: '2mb' }));
app.use(express.urlencoded({ extended: true }));

app.get('/health', (req, res) => res.json({ ok: true }));

// Everything below this line requires either X-Api-Key (WordPress) or
// HTTP Basic Auth (a human in a browser) - see src/auth.js.
app.use(requireAuth);

app.use(express.static(path.join(__dirname, '..', 'public')));

app.use('/settings', require('./routes/settings'));
app.use('/llm', require('./routes/llm'));
app.use('/companies-house', require('./routes/companiesHouse'));
app.use('/domains', require('./routes/spaceship'));
app.use('/google-ads', require('./routes/googleAds'));

app.use((err, req, res, next) => {
  // eslint-disable-next-line no-console
  console.error(err);
  res.status(500).json({ error: 'Internal server error.' });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  // eslint-disable-next-line no-console
  console.log(`wookiee-api listening on port ${PORT}`);
});
