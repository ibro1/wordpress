<?php
/**
 * Header — N13 inline ⌘K search pill nav.
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>

<header class="nav" id="nav">
	<div class="nav__inner">
		<a class="nav__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">Dave Bukar<span class="nav__brand-mono">.tech</span></a>

		<button class="searchpill" id="searchpill" aria-label="Jump to a page (⌘K)">
			<span class="searchpill__ico" aria-hidden="true"></span>
			<span class="searchpill__text">Jump to…</span>
			<span class="searchpill__kbd"><kbd>⌘</kbd><kbd>K</kbd></span>
		</button>

		<nav class="nav__right" aria-label="Primary">
			<div class="nav__links">
				<a class="nav__link" href="<?php echo esc_url( home_url( '/#services' ) ); ?>">Services</a>
				<a class="nav__link" href="<?php echo esc_url( home_url( '/#how-we-work' ) ); ?>">How we work</a>
			</div>
			<button type="button" class="btn btn--primary btn--sm js-book-call nav__cta-desktop">Book a call</button>
			<button type="button" class="nav__toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="navDrawer">
				<span class="nav__toggle-bars" aria-hidden="true"></span>
			</button>
		</nav>
	</div>
</header>

<!-- Hallmark · component: nav-drawer (mobile off-canvas) · genre: modern-minimal · theme: cobalt
     states: default · hover · focus · active (drawer links + CTA button) -->
<div class="drawer" id="navDrawer" aria-hidden="true">
	<div class="drawer__backdrop" data-drawer-close></div>
	<div class="drawer__panel" role="dialog" aria-modal="true" aria-label="Menu">
		<div class="drawer__head">
			<a class="drawer__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">Dave Bukar<span class="nav__brand-mono">.tech</span></a>
			<button type="button" class="drawer__close" data-drawer-close aria-label="Close menu">×</button>
		</div>

		<div class="drawer__section">
			<a class="drawer__link" href="<?php echo esc_url( home_url( '/#services' ) ); ?>"><span>Services</span><span class="drawer__link-arrow" aria-hidden="true">→</span></a>
			<a class="drawer__link" href="<?php echo esc_url( home_url( '/#how-we-work' ) ); ?>"><span>How we work</span><span class="drawer__link-arrow" aria-hidden="true">→</span></a>
		</div>

		<p class="drawer__label">Explore</p>
		<div class="drawer__section drawer__section--compact">
			<?php foreach ( dbt_services() as $slug => $service ) : $page = get_page_by_path( $slug, OBJECT, 'page' ); ?>
				<a class="drawer__sublink" href="<?php echo esc_url( $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' ) ); ?>"><?php echo esc_html( $service['title'] ); ?></a>
			<?php endforeach; ?>
		</div>

		<div class="drawer__foot">
			<button type="button" class="btn btn--primary drawer__cta js-book-call">Book a call</button>
			<div class="drawer__legal">
				<?php $privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' ); ?>
				<a href="<?php echo esc_url( $privacy ? get_permalink( $privacy ) : home_url( '/privacy-policy/' ) ); ?>">Privacy</a>
				<?php $terms = get_page_by_path( 'terms-of-service', OBJECT, 'page' ); ?>
				<a href="<?php echo esc_url( $terms ? get_permalink( $terms ) : home_url( '/terms-of-service/' ) ); ?>">Terms</a>
			</div>
		</div>
	</div>
</div>

<div class="cmdk" id="cmdk" aria-hidden="true">
	<div class="cmdk__backdrop" data-close></div>
	<div class="cmdk__panel" role="dialog" aria-modal="true" aria-label="Jump to a page">
		<div class="cmdk__field">
			<span class="cmdk__field-ico" aria-hidden="true"></span>
			<input id="cmdk-input" placeholder="Jump to…" autocomplete="off">
			<kbd>esc</kbd>
		</div>
		<div class="cmdk__results" id="cmdk-results"></div>
		<div class="cmdk__foot">
			<span><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
			<span><kbd>↵</kbd> open</span>
			<span><kbd>esc</kbd> close</span>
		</div>
	</div>
</div>

<!-- frontend-design · component: modal-form (book a call) · system: Hallmark/Cobalt (unchanged tokens)
     signature: real HTTP-style status chip (reuses the homepage code-card's "200 OK" device) +
     split context/form panel (reuses the homepage's one-dark-band move) instead of a generic centered dialog
     states: default · hover · focus · active · disabled · loading · error · success -->
<div class="leadform" id="leadform" aria-hidden="true">
	<div class="leadform__backdrop" data-leadform-close></div>
	<div class="leadform__panel" role="dialog" aria-modal="true" aria-labelledby="leadform-title">
		<button type="button" class="leadform__close" data-leadform-close aria-label="Close">×</button>

		<aside class="leadform__context" aria-hidden="true">
			<p class="mono-label mono-label--dark">After you send</p>
			<p class="leadform__context-title">A person reads this. Not a bot.</p>
			<ol class="leadform__context-steps">
				<li><span class="leadform__context-no">01</span>Received — logged instantly, no auto-reply spam</li>
				<li><span class="leadform__context-no">02</span>Reviewed — a founder reads it, same day</li>
				<li><span class="leadform__context-no">03</span>Reply — you hear back within one business day</li>
			</ol>
			<p class="leadform__context-compact">A person reads this — reply within one business day.</p>
		</aside>

		<div class="leadform__form-pane">
			<div class="leadform__body" data-leadform-step="form">
				<div class="leadform__head-row">
					<h2 id="leadform-title" class="leadform__title">Book a call</h2>
					<span class="status status--chip" id="leadform-status">DRAFT</span>
				</div>
				<p class="leadform__lede">Tell us what you’re building — a founder replies personally.</p>

				<form id="leadform-form" novalidate>
					<div class="field">
						<label for="lf-name">Name</label>
						<input type="text" id="lf-name" name="name" autocomplete="name" placeholder="Ada Lovelace" required>
					</div>
					<div class="field">
						<label for="lf-email">Email</label>
						<input type="email" id="lf-email" name="email" autocomplete="email" placeholder="you@company.com" required>
					</div>
					<div class="field">
						<label for="lf-company">Company <span class="field__optional">(optional)</span></label>
						<input type="text" id="lf-company" name="company" autocomplete="organization" placeholder="Acme Inc.">
					</div>
					<div class="field">
						<label for="lf-service">What do you need?</label>
						<select id="lf-service" name="service">
							<option>Software Development</option>
							<option>DevOps &amp; Infrastructure</option>
							<option>Online Advertising</option>
							<option>AI Agents &amp; Bots</option>
							<option selected>Not sure yet</option>
						</select>
					</div>
					<div class="field">
						<label for="lf-message">What are you building?</label>
						<textarea id="lf-message" name="message" rows="4" placeholder="A quick note on what you’re building or what’s broken." required></textarea>
					</div>
					<div class="field field--honeypot" aria-hidden="true">
						<label for="lf-website">Website</label>
						<input type="text" id="lf-website" name="website" tabindex="-1" autocomplete="off">
					</div>

					<p class="leadform__error" id="leadform-error" role="alert" hidden></p>

					<button type="submit" class="btn btn--primary leadform__submit" id="leadform-submit">
						<span class="leadform__submit-label">Book the call</span>
					</button>
				</form>
			</div>

			<div class="leadform__body leadform__success" data-leadform-step="success" hidden>
				<div class="leadform__head-row">
					<h2 class="leadform__title">Request received</h2>
					<span class="status status--ok">200 OK</span>
				</div>
				<p class="leadform__lede" id="leadform-success-message">We read every one ourselves — expect a reply within one business day.</p>
				<button type="button" class="btn btn--outline" data-leadform-close>Close</button>
			</div>
		</div>
	</div>
</div>

<script>
	window.dbtDestinations = <?php echo wp_json_encode( dbt_cmdk_destinations() ); ?>;
</script>

<main id="main">
