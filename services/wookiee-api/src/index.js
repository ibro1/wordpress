const express = require('express');
const path = require('path');
const { requireAdminAuth, requireApiAuth } = require('./auth');
const { router: licensesRouter, activatePublic } = require('./routes/licenses');

const app = express();

// Traefik sits in front of this container - trust its X-Forwarded-Proto/Host
// so req.protocol and the Google OAuth redirect URI come out as https and
// the real public hostname, not http/the internal container name.
app.set('trust proxy', true);

app.use(express.json({ limit: '2mb' }));
app.use(express.urlencoded({ extended: true }));

app.get('/health', (req, res) => res.json({ ok: true }));

// The one deliberately unauthenticated endpoint - presenting zero prior
// credentials is the whole point of activating a new code. Every other
// route below requires either requireAdminAuth (operator only:
// settings, license management, the static UI) or requireApiAuth (a
// WordPress site with an already-activated code, or the operator).
app.post('/licenses/activate', activatePublic);

// A dedicated exact-path route rather than app.use('/', ..., express.static(...))
// on purpose - express.static mounted at '/' matches every path as a prefix,
// which would run requireAdminAuth (and therefore reject) in front of every
// route below too, since Express treats '/' as a prefix match, not an exact one.
app.get('/', requireAdminAuth, (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'public', 'index.html'));
});
app.use('/settings', requireAdminAuth, require('./routes/settings'));
app.use('/licenses', requireAdminAuth, licensesRouter);

app.use('/llm', requireApiAuth, require('./routes/llm'));
app.use('/companies-house', requireApiAuth, require('./routes/companiesHouse'));
app.use('/domains', requireApiAuth, require('./routes/spaceship'));
app.use('/google-ads', requireApiAuth, require('./routes/googleAds'));

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
