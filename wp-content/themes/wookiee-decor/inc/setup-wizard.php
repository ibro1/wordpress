<?php
/**
 * Setup wizard (v2 spec §2d, phase 5 - the last item on the roadmap).
 * Doesn't duplicate any generator's logic - it's a status dashboard that
 * ties together the four pieces already built (Companies House lookup,
 * AI product generator, AI content generator, CJ supplier catalog) into
 * the guided "activate -> configure -> review -> done" flow the whole
 * v2 effort was scoped around, plus a live count of what still needs a
 * human review pass before anything goes live.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends the admin straight to the Setup dashboard right after activating
 * the theme, instead of leaving them to discover it in the menu on their
 * own - the "boom, configure it" part of the turnkey vision falls flat if
 * nobody knows where to start.
 */
add_action( 'after_switch_theme', 'wookiee_flag_setup_redirect' );
function wookiee_flag_setup_redirect() {
	set_transient( 'wookiee_setup_redirect', 1, MINUTE_IN_SECONDS );
}

add_action( 'admin_init', 'wookiee_maybe_redirect_to_setup' );
function wookiee_maybe_redirect_to_setup() {
	if ( ! get_transient( 'wookiee_setup_redirect' ) ) {
		return;
	}
	delete_transient( 'wookiee_setup_redirect' );

	if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=wookiee-setup' ) );
	exit;
}

/**
 * Draft products created by the AI or supplier tools, so the dashboard
 * can show "N items waiting for review" instead of the admin having to
 * remember to check separately. Page content generation used to have
 * an equivalent "(AI Draft) pages waiting for review" count here, but
 * that concept no longer exists - policy pages are edited live in
 * place, and Home/About/Contact copy is generated straight into
 * Settings fields for review there, so there's no draft-page backlog
 * to count anymore (see wookiee_render_setup_wizard_page()'s step 3).
 */
function wookiee_count_pending_ai_drafts() {
	$ai_products = 0;
	$cj_products = 0;
	if ( class_exists( 'WooCommerce' ) ) {
		$draft_products = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'draft',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );
		foreach ( $draft_products as $product_id ) {
			if ( get_post_meta( $product_id, '_wookiee_ai_generated', true ) ) {
				$ai_products++;
			} elseif ( get_post_meta( $product_id, '_wookiee_cj_pid', true ) ) {
				$cj_products++;
			}
		}
	}

	return array(
		'ai_products'  => $ai_products,
		'cj_products'  => $cj_products,
	);
}

function wookiee_render_setup_wizard_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$brief         = get_option( 'wookiee_niche_brief', '' );
	$has_ch_key    = '' !== trim( (string) wookiee_get_setting( 'companies_house_api_key' ) );
	$has_ai_key    = '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	$has_cj_creds  = '' !== trim( (string) wookiee_get_setting( 'cj_email' ) ) && '' !== trim( (string) wookiee_get_setting( 'cj_api_key' ) );
	$has_woo       = class_exists( 'WooCommerce' );
	$shipping_zone = $has_woo ? wookiee_find_uk_shipping_zone() : null;
	$pending       = wookiee_count_pending_ai_drafts();

	$settings_url = admin_url( 'admin.php?page=wookiee-settings' );
	$content_url  = admin_url( 'admin.php?page=wookiee-content-generator' );
	$product_url  = admin_url( 'admin.php?page=wookiee-product-generator' );
	$catalog_url  = admin_url( 'admin.php?page=wookiee-supplier-catalog' );
	?>
	<div class="wrap">
		<h1>Wookiee Setup</h1>
		<p>The guided path from a blank install to a reviewed, ready-to-launch single-niche store. Every step below writes real drafts for you to check — nothing here publishes anything automatically.</p>

		<h2>1. Business identity</h2>
		<table class="widefat" style="max-width:700px;">
			<tr>
				<td><strong>Registered name:</strong> <?php echo esc_html( wookiee_get_setting( 'business_name' ) ); ?></td>
				<td><strong>Company number:</strong> <?php echo esc_html( wookiee_get_setting( 'company_number' ) ); ?></td>
				<td><a href="<?php echo esc_url( $settings_url ); ?>" class="button">Edit / look up on Companies House</a></td>
			</tr>
			<tr>
				<td colspan="2"><strong>Site title:</strong> <?php echo esc_html( get_bloginfo( 'name' ) ); ?> <span class="description">— shown in the header, footer, and browser tab</span></td>
				<td><a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" class="button">Edit site title</a></td>
			</tr>
			<tr>
				<td colspan="2"><strong>Logo:</strong> <?php echo has_custom_logo() ? 'Custom logo set' : 'Using the default Wookiee wordmark'; ?></td>
				<td><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>" class="button">Upload logo</a></td>
			</tr>
		</table>
		<?php if ( ! $has_ch_key ) : ?>
			<p class="description">No Companies House API key yet — the lookup button on Wookiee Settings needs one to auto-fill this.</p>
		<?php endif; ?>

		<h2>2. Niche brief</h2>
		<table class="widefat" style="max-width:700px;">
			<tr>
				<td><?php echo $brief ? esc_html( $brief ) : '<em>Not set yet.</em>'; ?></td>
				<td><a href="<?php echo esc_url( $content_url ); ?>" class="button">Set / edit niche brief</a></td>
			</tr>
		</table>
		<p class="description">Shared by the Content Generator, Product Generator, and Supplier Catalog search — set it once here or on either generator page.</p>

		<h2>3. Generate page content</h2>
		<table class="widefat" style="max-width:700px;">
			<tr>
				<td>7 policy pages (Terms, Privacy, Shipping, Returns, Payment, Cookie Policy, Cookie Preferences) — generated straight onto the real, live pages.</td>
				<td><a href="<?php echo esc_url( $content_url ); ?>" class="button button-primary" <?php disabled( ! $has_ai_key ); ?>>Open Content Generator</a></td>
			</tr>
			<tr>
				<td>Homepage, About &amp; Contact copy — regenerated in place, reviewed right on the settings form before saving.</td>
				<td><a href="<?php echo esc_url( $settings_url . '#homepage' ); ?>" class="button" <?php disabled( ! $has_ai_key ); ?>>Homepage Copy</a></td>
				<td><a href="<?php echo esc_url( $settings_url . '#about_contact' ); ?>" class="button" <?php disabled( ! $has_ai_key ); ?>>About &amp; Contact Copy</a></td>
			</tr>
		</table>
		<?php if ( ! $has_ai_key ) : ?>
			<p class="description">Needs an LLM API key on <a href="<?php echo esc_url( $settings_url ); ?>">Wookiee Settings</a> first.</p>
		<?php endif; ?>

		<h2>4. Source products</h2>
		<table class="widefat" style="max-width:700px;">
			<tr>
				<td><?php echo intval( $pending['ai_products'] ); ?> AI-drafted product(s) and <?php echo intval( $pending['cj_products'] ); ?> CJ-sourced product(s) waiting for review.</td>
				<td><a href="<?php echo esc_url( $product_url ); ?>" class="button" <?php disabled( ! $has_ai_key || ! $has_woo ); ?>>AI Product Generator</a></td>
				<td><a href="<?php echo esc_url( $catalog_url ); ?>" class="button" <?php disabled( ! $has_cj_creds || ! $has_woo ); ?>>CJ Supplier Catalog</a></td>
				<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&post_status=draft' ) ); ?>" class="button" target="_blank" rel="noopener">Review draft products</a></td>
			</tr>
		</table>
		<?php if ( ! $has_woo ) : ?>
			<p class="description">WooCommerce isn't active — activate it to use either product source.</p>
		<?php endif; ?>
		<?php
		$display_cat_count = $has_woo ? count( wookiee_get_display_categories( 999 ) ) : 0;
		?>
		<p class="description">
			<?php if ( $display_cat_count > 0 ) : ?>
				<?php echo intval( $display_cat_count ); ?> product categor<?php echo 1 === $display_cat_count ? 'y currently has' : 'ies currently have'; ?> products — these are what show as the homepage's "Explore Our Categories" and "Shop by Collection" sections and the footer's Shop links, so they update automatically as you source/publish more products.
			<?php else : ?>
				No product categories with products yet, so the homepage collection sections are hidden for now — they'll appear automatically once products are published into categories, from either generator above.
			<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>">Manage categories</a>.
		</p>

		<h2>5. Shipping</h2>
		<table class="widefat" style="max-width:700px;">
			<tr>
				<td>
					<?php if ( $shipping_zone ) : ?>
						Live checkout shipping is active: £<?php echo esc_html( wookiee_get_setting( 'shipping_rate' ) ); ?> flat rate for United Kingdom, kept in sync with the rate on Wookiee Settings.
					<?php else : ?>
						<em>Not active yet<?php echo $has_woo ? ' — it self-creates the next time any page loads.' : ' — needs WooCommerce active.'; ?></em>
					<?php endif; ?>
				</td>
				<td><a href="<?php echo esc_url( $settings_url ); ?>" class="button">Edit shipping rate</a></td>
			</tr>
		</table>

		<h2>6. Review &amp; publish</h2>
		<p>Nothing above is live until you open each draft, check it against what's actually true for this business (real product photos, verified policy details, on-brand copy), and click Publish yourself. That review step is deliberate, not a placeholder to be automated away later — see <code>docs/workflow/v2/spec.md</code> §2c for why auto-publishing AI-sourced listings is a real consumer-protection risk.</p>
	</div>
	<?php
}
