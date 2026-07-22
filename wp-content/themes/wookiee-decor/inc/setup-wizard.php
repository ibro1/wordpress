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
 * Draft products created by the AI or supplier tools, so the dashboard
 * can show "N items waiting for review" instead of the admin having to
 * remember to check separately. Page content generation used to have
 * an equivalent "(AI Draft) pages waiting for review" count here, but
 * that concept no longer exists - policy pages are edited live in
 * place, and Home/About/Contact copy is generated straight into
 * Settings fields for review there, so there's no draft-page backlog
 * to count anymore.
 */
function wookiee_count_pending_ai_drafts() {
	$ai_products = 0;
	$cj_products = 0;
	$draft_ids   = array();
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
				$draft_ids[] = $product_id;
			} elseif ( get_post_meta( $product_id, '_wookiee_cj_pid', true ) ) {
				$cj_products++;
				$draft_ids[] = $product_id;
			}
		}
	}

	return array(
		'ai_products' => $ai_products,
		'cj_products' => $cj_products,
		'draft_ids'   => $draft_ids,
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
	$pending       = wookiee_count_pending_ai_drafts();
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

		<h2 class="nav-tab-wrapper" id="wookiee-setup-steps" role="tablist">
			<?php $is_first = true; ?>
			<?php foreach ( $steps as $step_key => $label ) : ?>
				<a href="#<?php echo esc_attr( $step_key ); ?>" class="nav-tab<?php echo $is_first ? ' nav-tab-active' : ''; ?>" data-step="<?php echo esc_attr( $step_key ); ?>" role="tab"><?php echo esc_html( $label ); ?></a>
				<?php $is_first = false; ?>
			<?php endforeach; ?>
		</h2>

		<?php // ---------------- Step 1: Business identity ---------------- ?>
		<div class="wookiee-setup-step" data-step-panel="business">
			<h2>Business identity</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'wookiee_settings_group' ); ?>
				<?php wookiee_render_settings_fields_table( array( 'company_number', 'companies_house_api_key', 'business_name', 'registered_address', 'countries_served' ) ); ?>
				<p><button type="submit" class="button button-primary">Save changes</button></p>
			</form>
			<?php if ( ! $has_ch_key ) : ?>
				<p class="description">No Companies House API key yet — add one above, then the lookup button will auto-fill the registered name/address.</p>
			<?php endif; ?>

			<h3>Site title &amp; logo</h3>
			<form method="post" action="options.php">
				<?php settings_fields( 'general' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="blogname">Site title</label></th>
						<td><input type="text" name="blogname" id="blogname" value="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="regular-text">
						<p class="description">Shown in the header, footer, and browser tab.</p></td>
					</tr>
				</table>
				<p><button type="submit" class="button">Save site title</button></p>
			</form>
			<table class="widefat" style="max-width:700px;">
				<tr>
					<td><strong>Logo:</strong> <?php echo has_custom_logo() ? 'Custom logo set' : 'Using the default wordmark'; ?></td>
					<td><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>" class="button" target="_blank" rel="noopener">Upload logo</a></td>
				</tr>
			</table>

			<p class="wookiee-setup-nav"><button type="button" class="button button-primary wookiee-setup-nav-next" data-next="niche">Continue to Niche brief &rarr;</button></p>
		</div>

		<?php // ---------------- Step 2: Niche brief ---------------- ?>
		<div class="wookiee-setup-step" data-step-panel="niche" hidden>
			<h2>Niche brief</h2>
			<p class="description">Shared by the Content Generator, Product Generator, and Supplier Catalog search — one niche for the whole site.</p>
			<span class="wookiee-niche-input-wrap is-textarea">
				<textarea id="wookiee-setup-niche-brief" rows="3" class="large-text" placeholder="e.g. UK home-storage and organisation products - baskets, shelving, drawer organisers, aimed at small flats"><?php echo esc_textarea( $brief ); ?></textarea>
				<?php wookiee_niche_suggest_button( 'wookiee-setup-niche-brief' ); ?>
			</span>
			<p>
				<button type="button" class="button button-primary" id="wookiee-setup-niche-save-btn">Save changes</button>
				<span id="wookiee-setup-niche-status" style="margin-left:8px;"></span>
			</p>
			<p class="wookiee-setup-nav">
				<button type="button" class="button wookiee-setup-nav-prev" data-prev="business">&larr; Back</button>
				<button type="button" class="button button-primary wookiee-setup-nav-next" data-next="content">Continue to Page content &rarr;</button>
			</p>
		</div>

		<?php // ---------------- Step 3: Generate page content ---------------- ?>
		<div class="wookiee-setup-step" data-step-panel="content" hidden>
			<h2>Page content</h2>
			<p><?php echo intval( $policy_live_count ); ?> of 7 policy pages currently exist (Terms, Privacy, Shipping, Returns, Payment, Cookie Policy, Cookie Preferences).</p>
			<?php if ( ! $has_ai_key ) : ?>
				<p class="description">Needs an LLM API key on <a href="<?php echo esc_url( $settings_url . '#integrations' ); ?>">Wookiee Settings</a> first.</p>
			<?php else : ?>
				<?php wookiee_render_content_generator_page(); ?>

				<h3>Homepage copy</h3>
				<form method="post" action="options.php">
					<?php settings_fields( 'wookiee_settings_group' ); ?>
					<?php wookiee_render_ai_copy_generator_notice( 'homepage' ); ?>
					<?php wookiee_render_settings_fields_table( $tabs['homepage']['fields'] ); ?>
					<p><button type="submit" class="button button-primary">Save homepage copy</button></p>
				</form>

				<h3>About &amp; Contact copy</h3>
				<form method="post" action="options.php">
					<?php settings_fields( 'wookiee_settings_group' ); ?>
					<?php wookiee_render_ai_copy_generator_notice( 'about_contact' ); ?>
					<?php wookiee_render_settings_fields_table( $tabs['about_contact']['fields'] ); ?>
					<p><button type="submit" class="button button-primary">Save About &amp; Contact copy</button></p>
				</form>
			<?php endif; ?>
			<p class="wookiee-setup-nav">
				<button type="button" class="button wookiee-setup-nav-prev" data-prev="niche">&larr; Back</button>
				<button type="button" class="button button-primary wookiee-setup-nav-next" data-next="products">Continue to Source products &rarr;</button>
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
			<form method="post" action="options.php">
				<?php settings_fields( 'wookiee_settings_group' ); ?>
				<?php wookiee_render_settings_fields_table( array( 'shipping_rate', 'shipping_dispatch', 'returns_address', 'returns_period_days' ) ); ?>
				<p><button type="submit" class="button button-primary">Save changes</button></p>
			</form>
			<p>
				<?php if ( $shipping_zone ) : ?>
					Live checkout shipping is active: £<?php echo esc_html( wookiee_get_setting( 'shipping_rate' ) ); ?> flat rate for United Kingdom, kept in sync with the rate above.
				<?php else : ?>
					<em>Not active yet<?php echo $has_woo ? ' — it self-creates the next time any page loads.' : ' — needs WooCommerce active.'; ?></em>
				<?php endif; ?>
			</p>
			<p class="wookiee-setup-nav">
				<button type="button" class="button wookiee-setup-nav-prev" data-prev="products">&larr; Back</button>
				<button type="button" class="button button-primary wookiee-setup-nav-next" data-next="review">Continue to Review &amp; publish &rarr;</button>
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
				<tr><td>Draft products awaiting review</td><td><?php echo intval( $pending['ai_products'] ); ?> AI-sourced, <?php echo intval( $pending['cj_products'] ); ?> CJ-sourced</td></tr>
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
			</p>
		</div>
	</div>
	<style>
		.wookiee-setup-nav { margin-top: 20px; padding-top: 16px; border-top: 1px solid #dcdcde; }
	</style>
	<script>
	( function() {
		var STORAGE_KEY = 'wookiee_setup_active_step';
		var tabs   = document.querySelectorAll( '#wookiee-setup-steps .nav-tab' );
		var panels = document.querySelectorAll( '.wookiee-setup-step' );

		function activateStep( stepKey ) {
			tabs.forEach( function( t ) {
				t.classList.toggle( 'nav-tab-active', t.getAttribute( 'data-step' ) === stepKey );
			} );
			panels.forEach( function( p ) {
				p.hidden = ( p.getAttribute( 'data-step-panel' ) !== stepKey );
			} );
			try { window.localStorage.setItem( STORAGE_KEY, stepKey ); } catch ( e ) {}
			window.scrollTo( { top: 0, behavior: 'instant' } );
		}

		tabs.forEach( function( t ) {
			t.addEventListener( 'click', function( e ) {
				e.preventDefault();
				activateStep( t.getAttribute( 'data-step' ) );
			} );
		} );

		document.querySelectorAll( '.wookiee-setup-nav-next' ).forEach( function( btn ) {
			btn.addEventListener( 'click', function() { activateStep( btn.getAttribute( 'data-next' ) ); } );
		} );
		document.querySelectorAll( '.wookiee-setup-nav-prev' ).forEach( function( btn ) {
			btn.addEventListener( 'click', function() { activateStep( btn.getAttribute( 'data-prev' ) ); } );
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
						status.textContent = res.success ? 'Saved.' : ( res.data && res.data.message ? res.data.message : 'Failed to save.' );
						if ( res.success ) {
							// Steps 3/4 embed the Content/Product Generator's own
							// niche-brief fields, rendered once at page load - keep
							// them in sync without needing a full page reload.
							[ 'wookiee-niche-brief', 'wookiee-niche-brief-2', 'wookiee-homepage-ai-brief', 'wookiee-about-ai-brief' ].forEach( function( id ) {
								var el = document.getElementById( id );
								if ( el ) { el.value = brief; }
							} );
						}
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
						status.textContent = res.success ? 'Done — reload this page to refresh the list.' : ( res.data && res.data.message ? res.data.message : 'Failed to publish.' );
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
