<?php
/**
 * Setup wizard (v2 spec §2d, phase 5 - the last item on the roadmap).
 * A real linear, 6-step onboarding flow (Business identity -> Niche
 * brief -> Page content -> Source products -> Shipping -> Review &
 * publish) rather than a dashboard of links out to other admin pages.
 * Every step reuses the exact same render functions/field rows the
 * standalone Settings/Product Generator/Content Generator pages already
 * use - nothing here duplicates that logic, so editing later from those
 * standalone pages and revisiting this wizard both work against the
 * same real, live data. That also means re-running this wizard on an
 * already-configured store is always safe: nothing is ever duplicated,
 * every step just edits the same real settings/pages/products in place.
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
 * Draft products created by the Product Generator or the Supplier
 * Catalog - both go through the same real CJ import and get
 * _wookiee_cj_pid, so there's no meaningful way (or reason) to
 * distinguish "AI-sourced" from "CJ-sourced" here; a product this theme
 * creates is always both (AI picks the concept, CJ supplies the real,
 * fulfillable item). This used to split the two out because an earlier
 * version had a genuinely separate fictional AI generator with no real
 * supplier behind it - that was scrapped since a product generator that
 * can't produce real, orderable items isn't automation, and every
 * product path has gone through CJ ever since. Page content generation
 * used to have an equivalent "(AI Draft) pages waiting for review"
 * count here too, but that no longer exists either - policy pages are
 * edited live in place, and Home/About/Contact copy is generated
 * straight into Settings fields for review there.
 */
function wookiee_get_pending_draft_products() {
	$draft_ids = array();
	if ( class_exists( 'WooCommerce' ) ) {
		$draft_ids = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'draft',
			'posts_per_page' => -1,
			'meta_key'       => '_wookiee_cj_pid',
			'fields'         => 'ids',
		) );
	}

	return array(
		'draft_ids' => $draft_ids,
	);
}

/**
 * The 6 wizard steps, in order - used both for the step nav and to
 * know what "furthest step reached" means. Kept as a plain array (not
 * hardcoded HTML) so the nav and the JS step-order stay in sync from a
 * single source.
 */
function wookiee_setup_steps() {
	return array(
		'business' => '1. Business identity',
		'niche'    => '2. Niche brief',
		'content'  => '3. Page content',
		'products' => '4. Source products',
		'shipping' => '5. Shipping',
		'review'   => '6. Review & publish',
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
	$pending       = wookiee_get_pending_draft_products();
	$tabs          = wookiee_settings_tabs();
	$policy_pieces = wookiee_content_generator_pieces();
	$steps         = wookiee_setup_steps();

	$settings_url = admin_url( 'admin.php?page=wookiee-settings' );
	$catalog_url  = admin_url( 'admin.php?page=wookiee-supplier-catalog' );

	$policy_live_count = 0;
	foreach ( $policy_pieces as $piece ) {
		if ( get_page_by_path( $piece['slug'], OBJECT, 'page' ) ) {
			$policy_live_count++;
		}
	}
	$display_cat_count = $has_woo ? count( wookiee_get_display_categories( 999 ) ) : 0;
	?>
	<div class="wrap">
		<h1>Wookiee Setup</h1>
		<p>The guided path from a blank install to a reviewed, ready-to-launch single-niche store. Every step writes real, live changes for you to review as you go — revisiting this wizard any time is safe, nothing is ever duplicated.</p>

		<div class="wookiee-setup-progress" id="wookiee-setup-steps">
			<?php $i = 0; foreach ( $steps as $step_key => $label ) : $i++; ?>
				<div class="wookiee-setup-progress-step<?php echo 1 === $i ? ' is-active' : ''; ?>" data-step="<?php echo esc_attr( $step_key ); ?>">
					<div class="wookiee-setup-progress-circle"><?php echo intval( $i ); ?></div>
					<div class="wookiee-setup-progress-label"><?php echo esc_html( preg_replace( '/^\d+\.\s*/', '', $label ) ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php // ---------------- Step 1: Business identity ---------------- ?>
		<div class="wookiee-setup-step" data-step-panel="business">
			<h2>Business identity</h2>
			<?php wookiee_render_settings_fields_table( array( 'company_number', 'business_name', 'registered_address', 'countries_served' ) ); ?>
			<?php if ( ! $has_ch_key ) : ?>
				<p class="description">Want the company name/address above auto-filled instead of typing them? Add a free Companies House API key on the <a href="<?php echo esc_url( $settings_url . '#business' ); ?>">Wookiee Settings</a> page, then come back here.</p>
			<?php endif; ?>

			<h3>Site title &amp; logo</h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="blogname">Site title</label></th>
					<td><input type="text" name="blogname" id="blogname" value="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="regular-text">
					<p class="description">Shown in the header, footer, and browser tab. Auto-suggested (with a domain check, if configured) whenever a company is looked up or picked above.</p>
					<p><span id="wookiee-site-name-status" style="color:#646970;"></span></p></td>
				</tr>
			</table>
			<table class="widefat" style="max-width:700px;">
				<tr>
					<td><strong>Logo:</strong> <?php echo has_custom_logo() ? 'Custom logo set' : 'Using the default wordmark'; ?></td>
					<td><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>" class="button" target="_blank" rel="noopener">Upload logo</a></td>
				</tr>
			</table>

			<p class="wookiee-setup-nav">
				<button type="button" class="button button-primary wookiee-setup-save-continue" data-next="niche">Save &amp; continue &rarr;</button>
				<span class="wookiee-setup-save-status" style="margin-left:8px;"></span>
			</p>
		</div>

		<?php // ---------------- Step 2: Niche brief ---------------- ?>
		<div class="wookiee-setup-step" data-step-panel="niche" hidden>
			<h2>Niche brief</h2>
			<p class="description">Shared by the Content Generator, Product Generator, and Supplier Catalog search — one niche for the whole site.</p>
			<span class="wookiee-niche-input-wrap is-textarea">
				<textarea id="wookiee-setup-niche-brief" rows="3" class="large-text" placeholder="e.g. UK home-storage and organisation products - baskets, shelving, drawer organisers, aimed at small flats"><?php echo esc_textarea( $brief ); ?></textarea>
				<?php wookiee_niche_suggest_button( 'wookiee-setup-niche-brief' ); ?>
			</span>
			<p class="wookiee-setup-nav">
				<button type="button" class="button wookiee-setup-nav-prev" data-prev="business">&larr; Back</button>
				<button type="button" class="button button-primary" id="wookiee-setup-niche-save-btn">Save &amp; continue &rarr;</button>
				<span id="wookiee-setup-niche-status" style="margin-left:8px;"></span>
			</p>
		</div>

		<?php
		$about_fields          = array_values( array_filter( $tabs['about_contact']['fields'], function ( $k ) { return 0 === strpos( $k, 'about_' ); } ) );
		$contact_fields        = array_values( array_filter( $tabs['about_contact']['fields'], function ( $k ) { return 0 === strpos( $k, 'contact_' ); } ) );
		$policy_ai_count       = wookiee_count_policy_pages_ai_generated();
		$homepage_ai_generated = (bool) get_option( 'wookiee_homepage_ai_generated', false );
		$about_ai_generated    = (bool) get_option( 'wookiee_about_contact_ai_generated', false );
		$accordion_sections    = array(
			'policy'  => array( 'label' => 'Policy Pages', 'status' => $policy_ai_count . ' of 7 generated' ),
			'home'    => array( 'label' => 'Home', 'status' => $homepage_ai_generated ? 'AI-generated' : 'Using defaults' ),
			'about'   => array( 'label' => 'About', 'status' => $about_ai_generated ? 'AI-generated' : 'Using defaults' ),
			'contact' => array( 'label' => 'Contact', 'status' => $about_ai_generated ? 'AI-generated' : 'Using defaults' ),
		);
		?>
		<?php // ---------------- Step 3: Generate page content ---------------- ?>
		<div class="wookiee-setup-step" data-step-panel="content" hidden>
			<h2>Page content</h2>

			<div class="wookiee-setup-accordion">
				<?php $first = true; foreach ( $accordion_sections as $section_key => $section ) : ?>
					<div class="wookiee-accordion-item<?php echo $first ? ' is-open' : ''; ?>" data-accordion="<?php echo esc_attr( $section_key ); ?>">
						<div class="wookiee-accordion-header">
							<span class="wookiee-accordion-title"><?php echo esc_html( $section['label'] ); ?></span>
							<span class="wookiee-accordion-status"><?php echo esc_html( $section['status'] ); ?></span>
							<span class="wookiee-accordion-chevron">&#9662;</span>
						</div>
						<div class="wookiee-accordion-body"<?php echo $first ? '' : ' hidden'; ?>>
							<?php if ( 'policy' === $section_key ) : ?>
								<?php if ( ! $has_ai_key ) : ?>
									<p class="description">Needs an LLM API key on <a href="<?php echo esc_url( $settings_url . '#integrations' ); ?>">Wookiee Settings</a> first.</p>
								<?php else : ?>
									<?php wookiee_render_content_generator_page(); ?>
								<?php endif; ?>
							<?php elseif ( 'home' === $section_key ) : ?>
								<?php if ( ! $has_ai_key ) : ?>
									<p class="description">Needs an LLM API key on <a href="<?php echo esc_url( $settings_url . '#integrations' ); ?>">Wookiee Settings</a> first.</p>
								<?php else : ?>
									<?php wookiee_render_ai_copy_generator_notice( 'homepage' ); ?>
									<?php wookiee_render_settings_fields_table( $tabs['homepage']['fields'] ); ?>
								<?php endif; ?>
							<?php elseif ( 'about' === $section_key ) : ?>
								<?php if ( ! $has_ai_key ) : ?>
									<p class="description">Needs an LLM API key on <a href="<?php echo esc_url( $settings_url . '#integrations' ); ?>">Wookiee Settings</a> first.</p>
								<?php else : ?>
									<?php wookiee_render_ai_copy_generator_notice( 'about_contact' ); ?>
									<?php wookiee_render_settings_fields_table( $about_fields ); ?>
								<?php endif; ?>
							<?php else : ?>
								<?php if ( ! $has_ai_key ) : ?>
									<p class="description">Needs an LLM API key on <a href="<?php echo esc_url( $settings_url . '#integrations' ); ?>">Wookiee Settings</a> first.</p>
								<?php else : ?>
									<p class="description">Generated together with the About section's copy (same AI call fills both) - generating here updates both too.</p>
									<?php wookiee_render_ai_copy_generator_notice( 'about_contact', '-contact' ); ?>
									<?php wookiee_render_settings_fields_table( $contact_fields ); ?>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
					<?php $first = false; ?>
				<?php endforeach; ?>
			</div>

			<p class="wookiee-setup-nav">
				<button type="button" class="button wookiee-setup-nav-prev" data-prev="niche">&larr; Back</button>
				<?php if ( $has_ai_key ) : ?>
					<button type="button" class="button button-primary wookiee-setup-save-continue" data-next="products">Save &amp; continue &rarr;</button>
					<span class="wookiee-setup-save-status" style="margin-left:8px;"></span>
				<?php else : ?>
					<button type="button" class="button button-primary wookiee-setup-nav-next" data-next="products">Continue to Source products &rarr;</button>
				<?php endif; ?>
			</p>
		</div>

		<?php // ---------------- Step 4: Source products ---------------- ?>
		<div class="wookiee-setup-step" data-step-panel="products" hidden>
			<h2>Source products</h2>
			<?php if ( ! $has_woo ) : ?>
				<p class="description">WooCommerce isn't active — activate it to use either product source.</p>
			<?php elseif ( ! $has_ai_key ) : ?>
				<p class="description">Needs an LLM API key on <a href="<?php echo esc_url( $settings_url . '#integrations' ); ?>">Wookiee Settings</a> first.</p>
			<?php else : ?>
				<?php wookiee_render_product_generator_page(); ?>
			<?php endif; ?>
			<hr>
			<p>
				Or browse and hand-pick from the full catalog:
				<a href="<?php echo esc_url( $catalog_url ); ?>" class="button" <?php disabled( ! $has_cj_creds || ! $has_woo ); ?>>Open CJ Supplier Catalog</a>
			</p>
			<p class="description">
				<?php if ( $display_cat_count > 0 ) : ?>
					<?php echo intval( $display_cat_count ); ?> product categor<?php echo 1 === $display_cat_count ? 'y currently has' : 'ies currently have'; ?> products — these drive the homepage's category sections and footer Shop links automatically.
				<?php else : ?>
					No product categories with products yet — the homepage's category sections stay hidden until at least one product is published into a category.
				<?php endif; ?>
			</p>
			<p class="wookiee-setup-nav">
				<button type="button" class="button wookiee-setup-nav-prev" data-prev="content">&larr; Back</button>
				<button type="button" class="button button-primary wookiee-setup-nav-next" data-next="shipping">Continue to Shipping &rarr;</button>
			</p>
		</div>

		<?php // ---------------- Step 5: Shipping ---------------- ?>
		<div class="wookiee-setup-step" data-step-panel="shipping" hidden>
			<h2>Shipping</h2>
			<?php wookiee_render_settings_fields_table( array( 'shipping_rate', 'shipping_dispatch', 'returns_address', 'returns_period_days' ) ); ?>
			<p>
				<?php if ( $shipping_zone ) : ?>
					Live checkout shipping is active: £<?php echo esc_html( wookiee_get_setting( 'shipping_rate' ) ); ?> flat rate for United Kingdom, kept in sync with the rate above.
				<?php else : ?>
					<em>Not active yet<?php echo $has_woo ? ' — it self-creates the next time any page loads.' : ' — needs WooCommerce active.'; ?></em>
				<?php endif; ?>
			</p>
			<p class="wookiee-setup-nav">
				<button type="button" class="button wookiee-setup-nav-prev" data-prev="products">&larr; Back</button>
				<button type="button" class="button button-primary wookiee-setup-save-continue" data-next="review">Save &amp; continue &rarr;</button>
				<span class="wookiee-setup-save-status" style="margin-left:8px;"></span>
			</p>
		</div>

		<?php // ---------------- Step 6: Review & publish ---------------- ?>
		<div class="wookiee-setup-step" data-step-panel="review" hidden>
			<h2>Review &amp; publish</h2>
			<p>Nothing below is live until you open each draft, check it against what's actually true for this business (real product photos, verified policy details, on-brand copy), and click Publish yourself. That review step is deliberate, not a placeholder to be automated away later — see <code>docs/workflow/v2/spec.md</code> §2c for why auto-publishing AI-sourced listings is a real consumer-protection risk.</p>

			<table class="widefat" style="max-width:700px;">
				<tr><td>Business identity</td><td><?php echo wookiee_get_setting( 'business_name' ) ? '&#10003; Set' : 'Not set'; ?></td></tr>
				<tr><td>Niche brief</td><td><?php echo $brief ? '&#10003; Set' : 'Not set'; ?></td></tr>
				<tr><td>Policy pages</td><td><?php echo intval( $policy_live_count ); ?> of 7 live</td></tr>
				<tr><td>Draft products awaiting review</td><td><?php echo intval( count( $pending['draft_ids'] ) ); ?></td></tr>
				<tr><td>Product categories with products</td><td><?php echo intval( $display_cat_count ); ?></td></tr>
				<tr><td>Shipping</td><td><?php echo $shipping_zone ? '&#10003; Active' : 'Not active yet'; ?></td></tr>
			</table>

			<h3>Draft products</h3>
			<?php if ( empty( $pending['draft_ids'] ) ) : ?>
				<p class="description">No draft products waiting right now.</p>
			<?php else : ?>
				<p>
					<label><input type="checkbox" id="wookiee-review-select-all"> Select all</label>
					<button type="button" class="button button-primary" id="wookiee-review-publish-btn" disabled>Publish selected</button>
					<span id="wookiee-review-publish-status" style="margin-left:8px;"></span>
				</p>
				<table class="widefat">
					<thead><tr><th style="width:24px;"></th><th>Product</th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $pending['draft_ids'] as $draft_id ) : ?>
							<tr>
								<td><input type="checkbox" class="wookiee-review-product-check" value="<?php echo esc_attr( $draft_id ); ?>"></td>
								<td><?php echo esc_html( get_the_title( $draft_id ) ); ?></td>
								<td><a href="<?php echo esc_url( get_edit_post_link( $draft_id, 'raw' ) ); ?>" class="button" target="_blank" rel="noopener">Review</a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<p class="wookiee-setup-nav">
				<button type="button" class="button wookiee-setup-nav-prev" data-prev="shipping">&larr; Back</button>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button button-primary" target="_blank" rel="noopener">View your store &rarr;</a>
			</p>
		</div>
	</div>
	<style>
		.wookiee-setup-progress {
			display: flex;
			justify-content: space-between;
			margin: 28px 0 32px;
			max-width: 900px;
		}
		.wookiee-setup-progress-step {
			display: flex;
			flex-direction: column;
			align-items: center;
			flex: 1;
			position: relative;
			cursor: pointer;
		}
		.wookiee-setup-progress-step:not(:last-child)::after {
			content: '';
			position: absolute;
			top: 17px;
			left: 50%;
			width: 100%;
			height: 2px;
			background: #dcdcde;
			z-index: 0;
		}
		.wookiee-setup-progress-step.is-complete:not(:last-child)::after {
			background: #2271b1;
		}
		.wookiee-setup-progress-circle {
			width: 34px;
			height: 34px;
			border-radius: 50%;
			background: #fff;
			border: 2px solid #dcdcde;
			color: #646970;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: 14px;
			position: relative;
			z-index: 1;
			transition: background-color .15s, border-color .15s, color .15s;
		}
		.wookiee-setup-progress-step.is-active .wookiee-setup-progress-circle {
			border-color: #2271b1;
			color: #2271b1;
			background: #fff;
			box-shadow: 0 0 0 3px rgba(34,113,177,0.15);
		}
		.wookiee-setup-progress-step.is-complete .wookiee-setup-progress-circle {
			border-color: #2271b1;
			background: #2271b1;
			color: #fff;
		}
		.wookiee-setup-progress-label {
			margin-top: 8px;
			font-size: 12px;
			color: #646970;
			text-align: center;
			max-width: 110px;
			line-height: 1.3;
		}
		.wookiee-setup-progress-step.is-active .wookiee-setup-progress-label {
			color: #1d2327;
			font-weight: 600;
		}
		.wookiee-setup-step {
			background: #fff;
			border: 1px solid #dcdcde;
			border-radius: 8px;
			padding: 28px 32px;
			margin-bottom: 24px;
			box-shadow: 0 1px 2px rgba(0,0,0,0.04);
			max-width: 900px;
		}
		.wookiee-setup-step h2 { margin-top: 0; }
		.wookiee-setup-step h3 {
			margin-top: 32px;
			padding-top: 24px;
			border-top: 1px solid #f0f0f1;
		}
		.wookiee-setup-nav { margin-top: 24px; padding-top: 16px; border-top: 1px solid #dcdcde; }
		.wookiee-setup-accordion { border: 1px solid #dcdcde; border-radius: 8px; overflow: hidden; }
		.wookiee-accordion-item { border-bottom: 1px solid #dcdcde; }
		.wookiee-accordion-item:last-child { border-bottom: none; }
		.wookiee-accordion-header {
			display: flex; align-items: center; gap: 12px; padding: 16px 20px; cursor: pointer; background: #fafafa;
		}
		.wookiee-accordion-item.is-open .wookiee-accordion-header { background: #fff; border-bottom: 1px solid #f0f0f1; }
		.wookiee-accordion-header:hover { background: #f0f0f1; }
		.wookiee-accordion-title { font-weight: 600; font-size: 15px; flex: 1 1 auto; }
		.wookiee-accordion-status { font-size: 13px; color: #646970; }
		.wookiee-accordion-chevron { transition: transform 0.15s; color: #8a7d6d; }
		.wookiee-accordion-item.is-open .wookiee-accordion-chevron { transform: rotate(180deg); }
		.wookiee-accordion-body { padding: 20px; }
	</style>
	<script>
	( function() {
		var STORAGE_KEY = 'wookiee_setup_active_step';
		var steps  = document.querySelectorAll( '#wookiee-setup-steps .wookiee-setup-progress-step' );
		var panels = document.querySelectorAll( '.wookiee-setup-step' );
		var stepOrder = Array.prototype.map.call( steps, function( s ) { return s.getAttribute( 'data-step' ); } );

		function activateStep( stepKey ) {
			var activeIndex = stepOrder.indexOf( stepKey );
			steps.forEach( function( s ) {
				var stepIndex = stepOrder.indexOf( s.getAttribute( 'data-step' ) );
				s.classList.toggle( 'is-active', stepIndex === activeIndex );
				s.classList.toggle( 'is-complete', stepIndex < activeIndex );
			} );
			panels.forEach( function( p ) {
				p.hidden = ( p.getAttribute( 'data-step-panel' ) !== stepKey );
			} );
			try { window.localStorage.setItem( STORAGE_KEY, stepKey ); } catch ( e ) {}
			window.scrollTo( { top: 0, behavior: 'instant' } );
		}

		steps.forEach( function( s ) {
			s.addEventListener( 'click', function() {
				activateStep( s.getAttribute( 'data-step' ) );
			} );
		} );

		document.querySelectorAll( '.wookiee-setup-nav-next' ).forEach( function( btn ) {
			btn.addEventListener( 'click', function() { activateStep( btn.getAttribute( 'data-next' ) ); } );
		} );
		document.querySelectorAll( '.wookiee-setup-nav-prev' ).forEach( function( btn ) {
			btn.addEventListener( 'click', function() { activateStep( btn.getAttribute( 'data-prev' ) ); } );
		} );

		// One "Save & Continue" per step: collects every real settings
		// field (and the site title, if present) within that step's own
		// panel and saves them all in a single request, then advances -
		// instead of a separate Settings-API form/button per options
		// group, which is what used to force 2-3 buttons on one step.
		var SAVE_STEP_NONCE = <?php echo wp_json_encode( wp_create_nonce( 'wookiee_save_setup_step' ) ); ?>;
		document.querySelectorAll( '.wookiee-setup-save-continue' ).forEach( function( btn ) {
			btn.addEventListener( 'click', function() {
				var panel  = btn.closest( '.wookiee-setup-step' );
				var status = panel.querySelector( '.wookiee-setup-save-status' );
				var data   = new FormData();
				data.append( 'action', 'wookiee_save_setup_step' );
				data.append( 'nonce', SAVE_STEP_NONCE );
				panel.querySelectorAll( '[name^="wookiee_setting_"], #blogname' ).forEach( function( field ) {
					data.append( field.name, field.value );
				} );

				btn.disabled = true;
				if ( status ) { status.textContent = 'Saving…'; }
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						btn.disabled = false;
						if ( ! res.success ) {
							if ( status ) { status.textContent = res.data && res.data.message ? res.data.message : 'Failed to save.'; }
							return;
						}
						if ( status ) { status.textContent = ''; }
						activateStep( btn.getAttribute( 'data-next' ) );
					} )
					.catch( function() {
						btn.disabled = false;
						if ( status ) { status.textContent = 'Failed — could not reach the server.'; }
					} );
			} );
		} );

		var hashKey   = window.location.hash ? window.location.hash.replace( '#', '' ) : '';
		var storedKey = '';
		try { storedKey = window.localStorage.getItem( STORAGE_KEY ) || ''; } catch ( e ) {}

		var targetKey = '';
		if ( hashKey && document.querySelector( '.wookiee-setup-step[data-step-panel="' + hashKey + '"]' ) ) {
			targetKey = hashKey;
		} else if ( storedKey && document.querySelector( '.wookiee-setup-step[data-step-panel="' + storedKey + '"]' ) ) {
			targetKey = storedKey;
		}
		if ( targetKey ) {
			activateStep( targetKey );
		}

		// Step 3's accordion (Policy Pages / Home / About / Contact) -
		// independent of the main 6-step nav above. Opening one section
		// closes the others, same as the main wizard steps only allow
		// one visible at a time.
		document.querySelectorAll( '.wookiee-accordion-header' ).forEach( function( header ) {
			header.addEventListener( 'click', function() {
				var item     = header.closest( '.wookiee-accordion-item' );
				var wasOpen  = item.classList.contains( 'is-open' );
				document.querySelectorAll( '.wookiee-accordion-item' ).forEach( function( i ) {
					i.classList.remove( 'is-open' );
					i.querySelector( '.wookiee-accordion-body' ).hidden = true;
				} );
				if ( ! wasOpen ) {
					item.classList.add( 'is-open' );
					item.querySelector( '.wookiee-accordion-body' ).hidden = false;
				}
			} );
		} );

		// Step 2: save the niche brief via AJAX (no page reload needed,
		// unlike the Settings-API forms elsewhere in this wizard).
		var nicheSaveBtn = document.getElementById( 'wookiee-setup-niche-save-btn' );
		if ( nicheSaveBtn ) {
			nicheSaveBtn.addEventListener( 'click', function() {
				var status = document.getElementById( 'wookiee-setup-niche-status' );
				var brief  = document.getElementById( 'wookiee-setup-niche-brief' ).value.trim();
				if ( ! brief ) {
					status.textContent = 'Describe the niche first.';
					return;
				}
				nicheSaveBtn.disabled = true;
				status.textContent = 'Saving…';
				var data = new FormData();
				data.append( 'action', 'wookiee_save_niche_brief' );
				data.append( 'nonce', <?php echo wp_json_encode( wp_create_nonce( 'wookiee_save_niche_brief' ) ); ?> );
				data.append( 'brief', brief );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						nicheSaveBtn.disabled = false;
						if ( ! res.success ) {
							status.textContent = res.data && res.data.message ? res.data.message : 'Failed to save.';
							return;
						}
						status.textContent = '';
						// Steps 3/4 embed the Content/Product Generator's own
						// niche-brief fields, rendered once at page load - keep
						// them in sync without needing a full page reload.
						[ 'wookiee-niche-brief', 'wookiee-niche-brief-2', 'wookiee-homepage-ai-brief', 'wookiee-about-ai-brief', 'wookiee-about-ai-brief-contact' ].forEach( function( id ) {
							var el = document.getElementById( id );
							if ( el ) { el.value = brief; }
						} );
						activateStep( 'content' );
					} )
					.catch( function() {
						nicheSaveBtn.disabled = false;
						status.textContent = 'Failed — could not reach the server.';
					} );
			} );
		}

		// Step 6: bulk-publish selected draft products (same AJAX action
		// the Product Generator's own results table already uses).
		var selectAll  = document.getElementById( 'wookiee-review-select-all' );
		var publishBtn = document.getElementById( 'wookiee-review-publish-btn' );
		var checks     = document.querySelectorAll( '.wookiee-review-product-check' );

		function refreshPublishBtn() {
			if ( ! publishBtn ) { return; }
			var anyChecked = Array.prototype.some.call( checks, function( c ) { return c.checked; } );
			publishBtn.disabled = ! anyChecked;
		}
		checks.forEach( function( c ) { c.addEventListener( 'change', refreshPublishBtn ); } );
		if ( selectAll ) {
			selectAll.addEventListener( 'change', function() {
				checks.forEach( function( c ) { c.checked = selectAll.checked; } );
				refreshPublishBtn();
			} );
		}
		if ( publishBtn ) {
			publishBtn.addEventListener( 'click', function() {
				var status = document.getElementById( 'wookiee-review-publish-status' );
				var ids = Array.prototype.filter.call( checks, function( c ) { return c.checked; } ).map( function( c ) { return c.value; } );
				if ( ! ids.length ) { return; }
				publishBtn.disabled = true;
				status.textContent = 'Publishing…';
				var data = new FormData();
				data.append( 'action', 'wookiee_publish_products' );
				data.append( 'nonce', <?php echo wp_json_encode( wp_create_nonce( 'wookiee_publish_products' ) ); ?> );
				ids.forEach( function( id ) { data.append( 'post_ids[]', id ); } );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						if ( res.success ) {
							status.textContent = 'Published ' + ids.length + ' product' + ( ids.length === 1 ? '' : 's' ) + '.';
							ids.forEach( function( id ) {
								var row = document.querySelector( '.wookiee-review-product-check[value="' + id + '"]' );
								if ( row ) { row.closest( 'tr' ).remove(); }
							} );
							checks = document.querySelectorAll( '.wookiee-review-product-check' );
							if ( ! checks.length ) {
								var table = document.querySelector( '#wookiee-review-publish-btn' ).closest( '.wookiee-setup-step' ).querySelector( 'table.widefat:not([style])' );
								if ( table ) { table.outerHTML = '<p class="description">No draft products waiting right now.</p>'; }
							}
							refreshPublishBtn();
						} else {
							status.textContent = res.data && res.data.message ? res.data.message : 'Failed to publish.';
							publishBtn.disabled = false;
						}
					} )
					.catch( function() {
						publishBtn.disabled = false;
						status.textContent = 'Failed — could not reach the server.';
					} );
			} );
		}
	} )();
	</script>
	<?php
}
