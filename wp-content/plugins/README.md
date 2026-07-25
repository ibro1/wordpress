# wp-content/plugins

Deployed automatically on every push to `main` — no manual steps. Dokploy already rebuilds and redeploys this project's compose stack on every push; the `wordpress` service (see `docs/workflow/docker-compose.yml` + `Dockerfile.wordpress`) bakes this directory (and `wp-content/themes/`) into its image, and its entrypoint syncs that baked-in content onto the persistent `wp_app` volume every time the container starts.

Docker's build cache is content-hash based, so the container only actually restarts (and re-syncs) on a push that changes something under this directory or `wp-content/themes/` — a no-op push leaves the running container untouched.

Plugins currently tracked here: `action-scheduler`, `akismet`, `auto-featured-image`, `auto-featured-image.de`, `hello.php`, `thecityceleb`, `woocommerce`, `ultimate-multisite`.
