# wp-content/plugins

Deployed automatically by `.github/workflows/deploy-theme.yml` on every push to `main` that touches this directory (or `wp-content/themes/`). The workflow SSHes into the production server and `docker cp`s both directories straight into the running `wordpress_multisite_app` container — no manual steps, no Dokploy rebuild involved.

Plugins currently tracked here: `action-scheduler`, `akismet`, `auto-featured-image`, `auto-featured-image.de`, `hello.php`, `thecityceleb`, `woocommerce`, `ultimate-multisite`.
