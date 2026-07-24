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
			<a class="nav__link" href="#services">Services</a>
			<a class="nav__link" href="#how-we-work">How we work</a>
			<a class="btn btn--primary btn--sm" href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>">Book a call</a>
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

<script>
	window.dbtDestinations = <?php echo wp_json_encode( dbt_cmdk_destinations() ); ?>;
</script>

<main id="main">
