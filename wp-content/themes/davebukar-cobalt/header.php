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
			<a class="nav__link" href="<?php echo esc_url( home_url( '/#services' ) ); ?>">Services</a>
			<a class="nav__link" href="<?php echo esc_url( home_url( '/#how-we-work' ) ); ?>">How we work</a>
			<button type="button" class="btn btn--primary btn--sm js-book-call">Book a call</button>
		</nav>
	</div>
</header>

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

<!-- Hallmark · component: modal-form (book a call) · genre: modern-minimal · theme: cobalt
     states: default · hover · focus · active · disabled · loading · error · success -->
<div class="leadform" id="leadform" aria-hidden="true">
	<div class="leadform__backdrop" data-leadform-close></div>
	<div class="leadform__panel" role="dialog" aria-modal="true" aria-labelledby="leadform-title">
		<button type="button" class="leadform__close" data-leadform-close aria-label="Close">×</button>

		<div class="leadform__body" data-leadform-step="form">
			<p class="mono-label">Book a call</p>
			<h2 id="leadform-title" class="leadform__title">Tell us what you’re building.</h2>
			<p class="leadform__lede">A real reply from a person, usually within one business day.</p>

			<form id="leadform-form" novalidate>
				<div class="field">
					<label for="lf-name">Name</label>
					<input type="text" id="lf-name" name="name" autocomplete="name" required>
				</div>
				<div class="field">
					<label for="lf-email">Email</label>
					<input type="email" id="lf-email" name="email" autocomplete="email" required>
				</div>
				<div class="field">
					<label for="lf-company">Company <span class="field__optional">(optional)</span></label>
					<input type="text" id="lf-company" name="company" autocomplete="organization">
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
					<textarea id="lf-message" name="message" rows="4" required></textarea>
				</div>
				<div class="field field--honeypot" aria-hidden="true">
					<label for="lf-website">Website</label>
					<input type="text" id="lf-website" name="website" tabindex="-1" autocomplete="off">
				</div>

				<p class="leadform__error" id="leadform-error" role="alert" hidden></p>

				<button type="submit" class="btn btn--primary leadform__submit" id="leadform-submit">
					<span class="leadform__submit-label">Send</span>
				</button>
			</form>
		</div>

		<div class="leadform__body leadform__success" data-leadform-step="success" hidden>
			<p class="mono-label">Sent</p>
			<h2 class="leadform__title">Thanks — message received.</h2>
			<p class="leadform__lede" id="leadform-success-message">We’ll be in touch within one business day.</p>
			<button type="button" class="btn btn--outline" data-leadform-close>Close</button>
		</div>
	</div>
</div>

<script>
	window.dbtDestinations = <?php echo wp_json_encode( dbt_cmdk_destinations() ); ?>;
</script>

<main id="main">
