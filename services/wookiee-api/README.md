# wookiee-api

Centralized secrets + third-party API proxy for the Wookiee Decor theme. Every credential that used to live in `wp_options` (Companies House, LLM, CJ Dropshipping, Cloudinary/rembg, Google Ads, Spaceship) is meant to move here instead - WordPress calls this service, this service calls the actual providers.

## Status

Built and ported so far, all faithfully mirroring the existing (verified/working) PHP logic:

- Encrypted secrets store (`src/secretsStore.js`) + a browser-based settings UI (`public/admin.html`)
- Companies House: number lookup + name search (`src/routes/companiesHouse.js`)
- Spaceship: availability check, site-title/domain suggestion, registration (contact + register + async poll), nameservers, DNS records (`src/routes/spaceship.js`)
- Google Ads: one-click OAuth connect flow + keyword-ideas call (`src/routes/googleAds.js`)
- LLM: generic OpenAI-compatible chat-completions proxy (`src/routes/llm.js`)

**Not yet ported** (existing PHP still handles these directly, unchanged): CJ Dropshipping product sourcing (`inc/supplier-cj.php`) and Cloudinary/rembg background removal (`inc/background-removal.php`) - these involve more complex multi-step logic (CJ's own auth-token refresh, binary image handling) that needs a careful read-through before porting, same standard as everything above.

**WordPress has not been changed yet.** The theme still calls providers directly with its own `wp_options`-stored keys. Swapping the PHP over to call this service instead is a separate, deliberate next step - safer to do after this service is deployed and confirmed working standalone.

## Environment variables

See `.env.example`. Three required:

- `MASTER_KEY` - encrypts `data/secrets.enc` at rest. Losing it loses every stored key with no recovery path.
- `API_SHARED_SECRET` - WordPress sends this as `X-Api-Key` on every request.
- `ADMIN_PASSWORD` - HTTP Basic Auth password for the browser-facing admin UI at `/` (any username).

Generate strong random values for all three, e.g. `openssl rand -hex 32`.

Every provider key (Companies House, LLM, CJ Dropshipping, Cloudinary/rembg, Google Ads, Spaceship - the full list is in `.env.example`) can **also** be set this way instead of through the settings UI - each one is just that field's name uppercased, e.g. `companies_house_api_key` -> `COMPANIES_HOUSE_API_KEY`. Precedence: whatever's saved in the settings UI wins once you've saved it there; an env var only fills in a value that hasn't been saved yet. Leave any you don't have blank - that provider's features just stay unavailable until it's filled in (either way).

## Local development

```
cd services/wookiee-api
npm install
MASTER_KEY=dev-only API_SHARED_SECRET=dev-only ADMIN_PASSWORD=dev-only npm start
```

Then visit `http://localhost:3000/` (Basic Auth prompt, any username + `dev-only`) to see the settings UI, or `GET /health` (no auth) for a liveness check.

## Deployment (Dokploy / docker-compose)

A `wookiee_api` service block, Traefik labels for `api.${MAIN_DOMAIN}`, and a `wookiee_api_data` volume have been added to `docs/workflow/docker-compose.yml` in the theme repo. To actually deploy:

1. Point this Dokploy app's Git Provider at `ibro1/wordpress`, branch `main`, with Compose Path `./docs/workflow/docker-compose.yml`. The `build.context: ../../services/wookiee-api` in that file is relative to the compose file's own location (Docker Compose always resolves `build.context` that way, not relative to the repo root) - if you ever move the compose file, update this path to match.
2. Set `WOOKIEE_API_MASTER_KEY`, `WOOKIEE_API_SHARED_SECRET`, `WOOKIEE_API_ADMIN_PASSWORD` as real environment variables/Dokploy secrets (same random-value guidance as above).
3. Deploy. Traefik will route `api.<MAIN_DOMAIN>` to this service automatically, the same way it already routes the main WordPress domain - no separate DNS record needed beyond whatever wildcard/A record already points `*.<MAIN_DOMAIN>` (or the specific `api` subdomain) at your server.
4. Visit `https://api.<MAIN_DOMAIN>/` and log in with `ADMIN_PASSWORD` to fill in every key via the settings UI - this replaces filling them into Wookiee Settings in WordPress (once WordPress is switched over to calling this service).

## Google Ads OAuth - important

The "Connect to Google Ads" button redirects to `https://api.<MAIN_DOMAIN>/google-ads/oauth/callback`. That exact URL must be registered as an **authorized redirect URI** on a Google Cloud OAuth Client of type **Web application**.

If your existing Google Ads Client ID/Secret was created as a **Desktop app** (the type used by local CLI scripts - it only allows `http://localhost` redirect URIs), it will **not** work here. Create a separate Web application OAuth client in the same Google Cloud project instead, add the callback URL above as an authorized redirect URI, and use that client's ID/Secret in this service's settings instead of the Desktop one.

Once the Client ID + Secret are saved, click "Connect to Google Ads" in the admin UI - it drives the full consent flow and saves the resulting refresh token automatically. No manual token generation needed.
