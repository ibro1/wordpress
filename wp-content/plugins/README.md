# wp-content/plugins

Deployed automatically on every push to `main` — no manual steps. Dokploy already rebuilds and redeploys this project's compose stack on every push; the `wordpress` service (see `docs/workflow/docker-compose.yml` + `Dockerfile.wordpress`) bakes this directory (and `wp-content/themes/`) into its image, and its entrypoint syncs that baked-in content onto the persistent `wp_app` volume every time the container starts.

Docker's build cache is content-hash based, so the container only actually restarts (and re-syncs) on a push that changes something under this directory or `wp-content/themes/` — a no-op push leaves the running container untouched.

**This is a merge, not a replace.** The sync uses `rsync` without `--delete`, so a plugin installed directly via wp-admin (never committed here) survives future pushes untouched. The tradeoff: removing a plugin from this directory in git does *not* remove it from the live site by itself — that still needs a manual cleanup on the server, since the sync only ever adds/updates, never deletes.

Plugins currently tracked here: `action-scheduler`, `akismet`, `auto-featured-image`, `auto-featured-image.de`, `hello.php`, `thecityceleb`, `woocommerce`, `ultimate-multisite`, `paystack-gateway`, `dual-currency`, `nowpayments-gateway`.
