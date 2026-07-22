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
	return 'This site shows a cookie consent banner to first-time visitors, offering three choices: Accept All, Reject Non-Essential, or Manage Preferences (to individually allow or disallow Analytics and Marketing cookies). Strictly Necessary cookies - the ones required to run the shopping cart, checkout, and basic site security - cannot be disabled since the site cannot function without them; this is the "essential" category. Analytics and Marketing cookies are the "non-essential" categories a visitor can freely accept or reject. The visitor\'s choice is remembered for 6 months and can be changed at any time via the "Manage cookie preferences" button on the Cookie Preferences page. This site does not currently have any third-party analytics or advertising scripts (e.g. Google Analytics, ad networks) actually installed - state this plainly rather than describing hypothetical third-party cookies.';
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

	<div id="wookiee-cookie-panel" class="wookiee-cookie-panel" hidden>
		<div class="wookiee-cookie-panel-inner">
			<h2>Manage cookie preferences</h2>
			<label class="wookiee-cookie-row">
				<span><strong>Strictly necessary</strong> — required for the site to work (cart, checkout, security). Always on.</span>
				<input type="checkbox" checked disabled>
			</label>
			<label class="wookiee-cookie-row">
				<span><strong>Analytics</strong> — helps us understand how visitors use the site.</span>
				<input type="checkbox" id="wookiee-cookie-analytics">
			</label>
			<label class="wookiee-cookie-row">
				<span><strong>Marketing</strong> — used to show relevant ads and measure campaigns.</span>
				<input type="checkbox" id="wookiee-cookie-marketing">
			</label>
			<div class="wookiee-cookie-panel-actions">
				<button type="button" class="wookiee-cookie-btn" id="wookiee-cookie-panel-cancel">Cancel</button>
				<button type="button" class="wookiee-cookie-btn wookiee-cookie-btn--primary" id="wookiee-cookie-panel-save">Save preferences</button>
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
