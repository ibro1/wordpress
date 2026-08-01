<?php
/**
 * Real, functioning cookie-consent mechanism - not just the informational
 * "Cookie Preferences" page that existed before, which only linked out to
 * browser settings and third-party opt-out tools. That's a genuine PECR
 * gap (a compliance audit correctly flagged it as "Serious"): visitors
 * need an actual accept/reject/manage choice, not just a description of
 * how to change browser settings.
 *
 * This shows a banner on first visit, lets a visitor Accept All, Reject
 * Non-Essential, or manage Analytics/Marketing individually, remembers
 * the choice in a cookie, and exposes window.wookieeConsent so any
 * future analytics/marketing script can check consent before firing.
 * There are currently no such scripts wired into this theme, so there is
 * nothing to actually gate yet - this is the real infrastructure ahead
 * of that, not a cosmetic addition.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The plain-language facts about how consent actually works on this
 * site, reused by both the Cookie Policy generation prompt and the
 * audit-fix prompt in inc/content-generator.php, so the AI describes
 * the real mechanism rather than inventing or omitting one.
 */
function wookiee_cookie_consent_mechanism_description() {
	return 'This site shows a cookie consent banner to first-time visitors, offering three choices: Accept All, Reject Non-Essential, or Manage Preferences. Manage Preferences opens a dialog listing the three categories with a checkbox each, and that dialog itself offers Decline All, Accept All and Save My Choices - so a visitor can refuse everything in one click from the dialog exactly as easily as accepting everything. Non-essential categories are off until the visitor allows them. Strictly Necessary cookies - the ones required to run the shopping cart, checkout, and basic site security - cannot be disabled since the site cannot function without them; this is the "essential" category. Analytics and Marketing cookies are the "non-essential" categories a visitor can freely accept or reject. The visitor\'s choice is remembered for 6 months, after which the banner appears again and consent is asked for afresh. It can be changed at any time from the "Cookie preferences" link in the site footer, which opens the same dialog directly. This site does not currently have any third-party analytics or advertising scripts (e.g. Google Analytics, ad networks) actually installed - state this plainly rather than describing hypothetical third-party cookies.';
}

add_action( 'wp_footer', 'wookiee_render_cookie_consent_banner' );
function wookiee_render_cookie_consent_banner() {
	if ( is_admin() ) {
		return;
	}
	?>
	<div id="wookiee-cookie-banner" class="wookiee-cookie-banner" hidden>
		<div class="wookiee-cookie-banner-inner">
			<p>We use cookies to run this site (necessary), understand how it's used (analytics), and show relevant ads (marketing). Accept all, reject non-essential cookies, or choose exactly which to allow. See our <a href="<?php echo esc_url( home_url( '/cookie/' ) ); ?>">Cookie Policy</a>.</p>
			<div class="wookiee-cookie-banner-actions">
				<button type="button" class="wookiee-cookie-btn" id="wookiee-cookie-manage">Manage preferences</button>
				<button type="button" class="wookiee-cookie-btn" id="wookiee-cookie-reject">Reject non-essential</button>
				<button type="button" class="wookiee-cookie-btn wookiee-cookie-btn--primary" id="wookiee-cookie-accept">Accept all</button>
			</div>
		</div>
	</div>

	<?php
	/*
	 * The panel carries Accept all and Decline all as well as Save, so a
	 * visitor who opens it to change one thing is not forced to tick boxes to
	 * get to a blanket answer. That is not only convenience: PECR expects
	 * refusing to be as easy as accepting, and a panel offering only "save
	 * what you have ticked" makes rejecting the longer path of the two.
	 *
	 * Only the three categories this site genuinely has. Adding a
	 * "Personalization" row because other consent tools show one would be a
	 * disclosure about cookies that are never set - the same false-precision
	 * problem the Cookie Policy prompt already forbids.
	 */
	?>
	<div id="wookiee-cookie-panel" class="wookiee-cookie-panel" hidden>
		<div class="wookiee-cookie-panel-inner" role="dialog" aria-modal="true" aria-labelledby="wookiee-cookie-panel-title">
			<div class="wookiee-cookie-panel-head">
				<h2 id="wookiee-cookie-panel-title">Cookie preferences</h2>
				<button type="button" class="wookiee-cookie-close" id="wookiee-cookie-panel-close" aria-label="Close cookie preferences">&times;</button>
			</div>
			<p class="wookiee-cookie-panel-intro">You choose which cookies this site may use. Strictly necessary cookies are always on because the site cannot run without them; everything else is off until you allow it. Your choice is remembered for six months and you can change it any time from the footer. See our <a href="<?php echo esc_url( home_url( '/cookie/' ) ); ?>">Cookie Policy</a>.</p>

			<div class="wookiee-cookie-rows">
				<label class="wookiee-cookie-row">
					<input type="checkbox" checked disabled>
					<span class="wookiee-cookie-row-text">
						<strong>Strictly necessary</strong>
						<span>Required for the site to work — your basket, checkout and basic security. These cannot be turned off.</span>
					</span>
				</label>
				<label class="wookiee-cookie-row">
					<input type="checkbox" id="wookiee-cookie-analytics">
					<span class="wookiee-cookie-row-text">
						<strong>Analytics</strong>
						<span>Helps us understand how visitors use the site so we can improve it.</span>
					</span>
				</label>
				<label class="wookiee-cookie-row">
					<input type="checkbox" id="wookiee-cookie-marketing">
					<span class="wookiee-cookie-row-text">
						<strong>Marketing</strong>
						<span>Used to show relevant ads and measure how campaigns perform.</span>
					</span>
				</label>
			</div>

			<div class="wookiee-cookie-panel-actions">
				<button type="button" class="wookiee-cookie-btn" id="wookiee-cookie-panel-reject">Decline all</button>
				<button type="button" class="wookiee-cookie-btn" id="wookiee-cookie-panel-accept">Accept all</button>
				<button type="button" class="wookiee-cookie-btn wookiee-cookie-btn--primary" id="wookiee-cookie-panel-save">Save my choices</button>
			</div>
		</div>
	</div>
	<?php
}

add_action( 'wp_enqueue_scripts', 'wookiee_enqueue_cookie_consent_assets' );
function wookiee_enqueue_cookie_consent_assets() {
	wp_enqueue_script( 'wookiee-cookie-consent', WOOKIEE_URI . 'assets/js/cookie-consent.js', array(), WOOKIEE_VERSION, true );
}

/**
 * Reopens the preference panel from anywhere - used on the Cookie
 * Preferences page (see the repair function below) and available for
 * a footer link too: [wookiee_cookie_preferences_button]
 */
add_shortcode( 'wookiee_cookie_preferences_button', 'wookiee_cookie_preferences_button_shortcode' );
function wookiee_cookie_preferences_button_shortcode() {
	return '<button type="button" class="wookiee-cookie-btn wookiee-cookie-btn--primary" onclick="window.wookieeConsent && window.wookieeConsent.openPanel();">Manage cookie preferences</button>';
}

/**
 * The existing "Cookie preferences" page (inc/static-content.php) only
 * ever contained informational text and links to browser settings, with
 * no real way to actually change consent on this site - this repairs
 * that page in place, once, self-healing on init like the theme's other
 * starter-content setup, so the site that's already live gets the real
 * fix too, not just future installs.
 */
add_action( 'init', 'wookiee_ensure_cookie_preferences_button', 26 );
function wookiee_ensure_cookie_preferences_button() {
	$page = get_page_by_title( 'Cookie preferences', OBJECT, 'page' );
	if ( ! $page || false !== strpos( $page->post_content, 'wookiee_cookie_preferences_button' ) ) {
		return;
	}

	$button_block    = '<div style="max-width:860px;margin:0 auto;padding:32px 20px 0;text-align:center;">[wookiee_cookie_preferences_button]</div>';
	$updated_content = $button_block . $page->post_content;

	wp_update_post( array(
		'ID'           => $page->ID,
		'post_content' => $updated_content,
	) );
}
