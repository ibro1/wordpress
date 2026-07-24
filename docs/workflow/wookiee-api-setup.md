# wookiee-api backend - setup & operations

Central backend for the Wookiee Decor theme. Holds every third-party
provider credential (Companies House, LLM, CJ Dropshipping, Cloudinary/
rembg, Google Ads, Spaceship) in one place, shared across every WordPress
install running this theme. Source: `services/wookiee-api` in this repo.
Compose service block: `docs/workflow/docker-compose.yml`.

## 1. Deploy

1. Add the `wookiee_api` service block from `docs/workflow/docker-compose.yml`
   to your actual Dokploy-managed compose config (that file lives outside
   this repo).
2. Set these three env vars in Dokploy for this app (generate with
   `openssl rand -hex 32` / `openssl rand -hex 16`):
   - `WOOKIEE_API_MASTER_KEY`
   - `WOOKIEE_API_SHARED_SECRET`
   - `WOOKIEE_API_ADMIN_PASSWORD`
3. Optionally also set the provider-key env vars (see `.env.example` in
   `services/wookiee-api` for the full list - every field is just its name
   uppercased, e.g. `companies_house_api_key` -> `COMPANIES_HOUSE_API_KEY`).
   Leave any you don't have blank; they can be filled in later via the
   settings UI either way, and a UI-saved value always overrides the env var.
4. Deploy. Traefik routes `api.<MAIN_DOMAIN>` to it automatically, same as
   the main WordPress domain.
5. Visit `https://api.<MAIN_DOMAIN>/` - browser prompts for Basic Auth (any
   username, password = `WOOKIEE_API_ADMIN_PASSWORD`) - to see the settings
   page and confirm everything you set actually landed.

## 2. Connect WordPress to it

In WordPress: Wookiee Settings -> AI & Integrations tab -> top of the page:

- **Central backend URL**: `https://api.<MAIN_DOMAIN>`
- **Central backend shared secret**: same value as `WOOKIEE_API_SHARED_SECRET`

Save. From then on, Companies House lookups, AI generation, domain
suggestions/registration, and Google Ads calls all go through the backend
instead of calling providers directly with local keys.

## 3. Getting rid of the old keys in WordPress

Two ways, both in the same "AI & Integrations" tab, right below the backend
URL/secret fields - only one is needed, not both:

- **Migrate existing keys to backend** button - appears once the backend
  URL/secret above are saved. Reads whatever's currently in this
  WordPress install's key fields, POSTs them to the backend, then deletes
  them locally. Needs the backend to be reachable at the moment you click it.
- **"I already copied these to the backend myself - just clear them here"**
  button - for when you read the values via the Show/Hide toggle on each
  password field and pasted them into the backend's env vars or settings UI
  by hand instead. Doesn't contact the backend at all, just deletes the
  fields from this WordPress site and hides them from the Settings page
  from then on.

Either one ends with the same result: the 17 provider-key fields disappear
from Settings, replaced by a short "already migrated" notice.

## 4. Google Ads - getting a (new) refresh token

The refresh token is normally never touched by hand - it's fetched
automatically through an OAuth "Connect to Google Ads" flow. Once the
backend is deployed and has a Client ID + Client Secret (via env var or the
settings UI):

1. **Register the redirect URI.** In Google Cloud Console -> APIs & Services
   -> Credentials -> your OAuth 2.0 Client (must be type **Web application**,
   not **Desktop** - a Desktop-type client only allows `http://localhost`
   redirect URIs and will reject this), add to "Authorized redirect URIs":

   ```
   https://api.<MAIN_DOMAIN>/google-ads/oauth/callback
   ```

   If your existing Client ID was created as a Desktop app (e.g. the one
   used by a local `get_refresh_token.py`-style script), create a separate
   Web application OAuth client in the same Google Cloud project instead,
   and use that one's Client ID/Secret in the backend from here on.

2. **Visit the connect URL** (this is "the URL" - it's not a page you
   design, it's this exact path on your backend):

   ```
   https://api.<MAIN_DOMAIN>/google-ads/oauth/start
   ```

   Open it directly, or click "Connect to Google Ads" on the backend's own
   settings page (`https://api.<MAIN_DOMAIN>/`) - same thing, that button
   just links to this URL.

3. Browser prompts for Basic Auth (`WOOKIEE_API_ADMIN_PASSWORD`) if it
   hasn't already this session, then redirects to Google's consent screen.
   Approve access.

4. Google redirects back to the callback URL above, the backend exchanges
   the code for a refresh token automatically, and saves it - overwriting
   whatever refresh token was there before (including one you'd copied over
   manually from WordPress). You'll see a plain "Connected" confirmation
   page; no token to copy by hand.

Re-running steps 2-4 at any time issues a brand new refresh token and
replaces the old one - useful if access was ever revoked, the token
stopped working, or you're rotating credentials to a different Google Ads
account/client.
