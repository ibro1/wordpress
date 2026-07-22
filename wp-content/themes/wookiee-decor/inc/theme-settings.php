<?php
/**
 * Wookiee theme settings: a single admin page for business details that
 * change over time (contact info, addresses, social links, shipping rate)
 * without needing a code change every time.
 *
 * Static page content (Contact, Returns, Shipping, About, etc.) is baked
 * into the database once at page creation and does not re-run PHP, so a
 * raw get_option() call embedded in that content would just print as
 * literal text, not execute. Shortcodes are the correct WordPress
 * mechanism here: the_content() runs do_shortcode() on stored content,
 * so a [wookiee_field key="..."] left in already-baked content keeps
 * reflecting whatever is saved on this settings page, in real time,
 * with no need to delete and regenerate the page.
 */

defined( 'ABSPATH' ) || exit;

function wookiee_settings_fields() {
	return array(
		'contact_email'      => array( 'label' => 'Contact email', 'default' => 'info@wookied.com', 'type' => 'email' ),
		'contact_phone'      => array( 'label' => 'Contact phone', 'default' => '+44 20 8472 6126', 'type' => 'text' ),
		'business_name'      => array( 'label' => 'Registered company name', 'default' => 'Wookiee Decor Ltd', 'type' => 'text' ),
		'registered_address'  => array( 'label' => 'Registered office address', 'default' => "Wookiee Decor Ltd\n28 Johnston Park, Cowdenbeath\nKY4 9AZ, United Kingdom", 'type' => 'textarea' ),
		'company_number'     => array( 'label' => 'Company number', 'default' => 'SC769264', 'type' => 'text' ),
		'companies_house_api_key' => array( 'label' => 'Companies House API key', 'default' => '', 'type' => 'password' ),
		'llm_api_key'        => array( 'label' => 'LLM API key', 'default' => '', 'type' => 'password' ),
		'llm_base_url'       => array( 'label' => 'LLM base URL', 'default' => 'https://api.openai.com/v1', 'type' => 'text' ),
		'llm_default_model'  => array( 'label' => 'LLM default model', 'default' => 'gpt-4o-mini', 'type' => 'text' ),
		'cj_email'           => array( 'label' => 'CJ Dropshipping account email', 'default' => '', 'type' => 'email' ),
		'cj_api_key'         => array( 'label' => 'CJ Dropshipping API key', 'default' => '', 'type' => 'password' ),
		'product_markup_percent' => array( 'label' => 'CJ product markup (%)', 'default' => '0', 'type' => 'text' ),
		'bg_removal_provider' => array( 'label' => 'Featured image white-background provider', 'default' => 'none', 'type' => 'select', 'options' => array( 'none' => 'Disabled', 'cloudinary' => 'Cloudinary', 'rembg' => 'Self-hosted rembg' ) ),
		'cloudinary_cloud_name' => array( 'label' => 'Cloudinary cloud name', 'default' => '', 'type' => 'text' ),
		'cloudinary_api_key' => array( 'label' => 'Cloudinary API key', 'default' => '', 'type' => 'text' ),
		'cloudinary_api_secret' => array( 'label' => 'Cloudinary API secret', 'default' => '', 'type' => 'password' ),
		'rembg_endpoint_url' => array( 'label' => 'Self-hosted rembg URL', 'default' => 'http://rembg:7000', 'type' => 'text' ),
		'google_ads_developer_token' => array( 'label' => 'Google Ads developer token', 'default' => '', 'type' => 'password' ),
		'google_ads_client_id' => array( 'label' => 'Google Ads OAuth client ID', 'default' => '', 'type' => 'text' ),
		'google_ads_client_secret' => array( 'label' => 'Google Ads OAuth client secret', 'default' => '', 'type' => 'password' ),
		'google_ads_refresh_token' => array( 'label' => 'Google Ads OAuth refresh token', 'default' => '', 'type' => 'password' ),
		'google_ads_customer_id' => array( 'label' => 'Google Ads customer ID', 'default' => '', 'type' => 'text' ),
		'google_ads_login_customer_id' => array( 'label' => 'Google Ads manager (MCC) customer ID (if applicable)', 'default' => '', 'type' => 'text' ),
		'returns_address'    => array( 'label' => 'Returns address (leave blank to use registered office address)', 'default' => '', 'type' => 'textarea' ),
		'returns_period_days' => array( 'label' => 'Returns period (days)', 'default' => '30', 'type' => 'text' ),
		'countries_served'   => array( 'label' => 'Countries served', 'default' => 'United Kingdom', 'type' => 'text' ),
		'hero_eyebrow'       => array( 'label' => 'Homepage hero eyebrow tag', 'default' => 'Premium Home Storage', 'type' => 'text' ),
		'hero_headline'      => array( 'label' => 'Homepage hero headline', 'default' => 'A more organised home starts here.', 'type' => 'text' ),
		'hero_subheadline'   => array( 'label' => 'Homepage hero subheadline', 'default' => 'Discover our collection of thoughtful storage solutions and home accessories designed for modern living.', 'type' => 'textarea' ),
		'homepage_philosophy_heading' => array( 'label' => 'Homepage philosophy heading', 'default' => 'Organisation should feel simple.', 'type' => 'text' ),
		'homepage_philosophy' => array( 'label' => 'Homepage philosophy paragraph', 'default' => "We believe that a tidy home leads to a clearer mind. That's why we design products that are not only functional but also beautiful, helping you create spaces you love to spend time in without the stress of clutter.", 'type' => 'textarea' ),
		'shipping_rate'      => array( 'label' => 'Flat shipping rate (£)', 'default' => '5.99', 'type' => 'text' ),
		'shipping_dispatch'  => array( 'label' => 'Dispatch / transit time', 'default' => 'Dispatched within 24 hours, 3-5 working days transit', 'type' => 'text' ),
		'facebook_url'       => array( 'label' => 'Facebook URL (leave blank to hide the icon)', 'default' => '', 'type' => 'url' ),
		'instagram_url'      => array( 'label' => 'Instagram URL (leave blank to hide the icon)', 'default' => '', 'type' => 'url' ),
		'linkedin_url'       => array( 'label' => 'LinkedIn URL (leave blank to hide the icon)', 'default' => '', 'type' => 'url' ),
		'pinterest_url'      => array( 'label' => 'Pinterest URL (leave blank to hide the icon)', 'default' => '', 'type' => 'url' ),
	);
}

/**
 * Groups settings fields into tabs for the settings page. Business
 * Identity leads with company_number + the Companies House lookup so
 * that's the first thing an admin sees, with the manual name/address
 * fields already visible right below - not gated behind a separate
 * "enter manually" toggle, since not every install of this theme will
 * have a UK company number to look up.
 */
function wookiee_settings_tabs() {
	return array(
		'business' => array(
			'label'  => 'Business Identity',
			'fields' => array( 'company_number', 'companies_house_api_key', 'business_name', 'registered_address', 'countries_served' ),
		),
		'contact' => array(
			'label'  => 'Contact & Support',
			'fields' => array( 'contact_email', 'contact_phone' ),
		),
		'shipping' => array(
			'label'  => 'Shipping & Returns',
			'fields' => array( 'shipping_rate', 'shipping_dispatch', 'returns_address', 'returns_period_days' ),
		),
		'homepage' => array(
			'label'  => 'Homepage Copy',
			'fields' => array( 'hero_eyebrow', 'hero_headline', 'hero_subheadline', 'homepage_philosophy_heading', 'homepage_philosophy' ),
		),
		'social' => array(
			'label'  => 'Social Media',
			'fields' => array( 'facebook_url', 'instagram_url', 'linkedin_url', 'pinterest_url' ),
		),
		'integrations' => array(
			'label'  => 'AI & Integrations',
			'fields' => array( 'llm_api_key', 'llm_base_url', 'llm_default_model', 'cj_email', 'cj_api_key', 'product_markup_percent', 'bg_removal_provider', 'cloudinary_cloud_name', 'cloudinary_api_key', 'cloudinary_api_secret', 'rembg_endpoint_url', 'google_ads_developer_token', 'google_ads_client_id', 'google_ads_client_secret', 'google_ads_refresh_token', 'google_ads_customer_id', 'google_ads_login_customer_id' ),
		),
	);
}

/**
 * Read a single setting, falling back to its declared default.
 */
function wookiee_get_setting( $key, $default_override = null ) {
	$fields = wookiee_settings_fields();
	$default = null !== $default_override ? $default_override : ( isset( $fields[ $key ] ) ? $fields[ $key ]['default'] : '' );
	$value = get_option( 'wookiee_setting_' . $key, '' );
	return '' !== $value ? $value : $default;
}

/**
 * Returns address falls back to the registered office address if not set
 * separately - most small businesses ship returns to the same place.
 */
function wookiee_get_returns_address() {
	$returns = wookiee_get_setting( 'returns_address' );
	return '' !== trim( (string) $returns ) ? $returns : wookiee_get_setting( 'registered_address' );
}

add_action( 'admin_init', 'wookiee_register_settings' );
function wookiee_register_settings() {
	foreach ( wookiee_settings_fields() as $key => $field ) {
		register_setting( 'wookiee_settings_group', 'wookiee_setting_' . $key, array(
			'type'              => 'string',
			'sanitize_callback' => wookiee_sanitizer_for( $field['type'] ),
			'default'           => '',
		) );
	}
}

function wookiee_sanitizer_for( $type ) {
	switch ( $type ) {
		case 'email':
			return 'sanitize_email';
		case 'url':
			return 'esc_url_raw';
		case 'textarea':
			return 'sanitize_textarea_field';
		case 'password':
			return 'sanitize_text_field';
		default:
			return 'sanitize_text_field';
	}
}

/**
 * Renders one settings field's <tr> - the input/textarea itself, plus
 * any field-specific helper UI (the Companies House lookup button, the
 * various provider descriptions) that used to live inline in the big
 * foreach loop. Pulled into its own function so the tabbed loop below
 * doesn't have to duplicate it per tab.
 */
function wookiee_render_settings_field_row( $key, $field ) {
	?>
	<tr>
		<th scope="row"><label for="wookiee_setting_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<?php if ( 'textarea' === $field['type'] ) : ?>
				<textarea name="wookiee_setting_<?php echo esc_attr( $key ); ?>" id="wookiee_setting_<?php echo esc_attr( $key ); ?>" rows="3" class="large-text" placeholder="<?php echo esc_attr( $field['default'] ); ?>"><?php echo esc_textarea( get_option( 'wookiee_setting_' . $key, '' ) ); ?></textarea>
			<?php elseif ( 'select' === $field['type'] ) : ?>
				<?php $current = get_option( 'wookiee_setting_' . $key, '' ); $current = '' !== $current ? $current : $field['default']; ?>
				<select name="wookiee_setting_<?php echo esc_attr( $key ); ?>" id="wookiee_setting_<?php echo esc_attr( $key ); ?>">
					<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $current, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input type="<?php echo 'url' === $field['type'] ? 'url' : ( 'email' === $field['type'] ? 'email' : ( 'password' === $field['type'] ? 'password' : 'text' ) ); ?>" name="wookiee_setting_<?php echo esc_attr( $key ); ?>" id="wookiee_setting_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_option( 'wookiee_setting_' . $key, '' ) ); ?>" placeholder="<?php echo esc_attr( $field['default'] ); ?>" class="regular-text" autocomplete="off">
			<?php endif; ?>
			<?php if ( 'company_number' === $key ) : ?>
				<p>
					<button type="button" class="button" id="wookiee-ch-lookup-btn">Look up on Companies House</button>
					<span id="wookiee-ch-lookup-status" style="margin-left:8px;"></span>
				</p>
				<p class="description">Fills in the registered company name and address below from the official Companies House register. Requires an API key (below) — get one free at <a href="https://developer.company-information.service.gov.uk/" target="_blank" rel="noopener">developer.company-information.service.gov.uk</a>. Review the filled-in fields before saving.</p>
			<?php endif; ?>
			<?php if ( 'companies_house_api_key' === $key ) : ?>
				<p class="description">Only needed to use the lookup button above. Free to obtain, one per WordPress install.</p>
			<?php endif; ?>
			<?php if ( 'cj_api_key' === $key ) : ?>
				<p class="description">From your CJ Dropshipping account: My CJ → API Setting. Used with the email above to authenticate the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-supplier-catalog' ) ); ?>">Wookiee Supplier Catalog</a> page.</p>
			<?php endif; ?>
			<?php if ( 'llm_api_key' === $key ) : ?>
				<p class="description">Powers the Product Generator, Content Generator, and policy audit. Works with any OpenAI-compatible provider (OpenAI itself, OpenRouter, Groq, a self-hosted vLLM/llama.cpp server, etc.) — just match the base URL and model below to whichever provider this key is for.</p>
			<?php endif; ?>
			<?php if ( 'llm_base_url' === $key ) : ?>
				<p class="description">The API root, without a trailing <code>/chat/completions</code> — e.g. <code>https://api.openai.com/v1</code> for OpenAI, or your provider's equivalent.</p>
			<?php endif; ?>
			<?php if ( 'product_markup_percent' === $key ) : ?>
				<p class="description">Applied automatically to CJ Dropshipping's supplier price when importing - e.g. 50 turns a &pound;10 supplier cost into a &pound;15 selling price. Leave at 0 to import at the raw supplier price with no markup.</p>
			<?php endif; ?>
			<?php if ( 'bg_removal_provider' === $key ) : ?>
				<p class="description">When enabled, the first/featured image on every CJ Dropshipping import gets its background removed and replaced with solid white - real segmentation of the actual product photo, not AI-regenerated. If the chosen provider fails, the other one (if configured below) is tried automatically before falling back to the original supplier photo.</p>
			<?php endif; ?>
			<?php if ( 'cloudinary_cloud_name' === $key ) : ?>
				<p class="description">From your Cloudinary account dashboard. Requires the "AI Background Removal" add-on enabled on the account.</p>
			<?php endif; ?>
			<?php if ( 'rembg_endpoint_url' === $key ) : ?>
				<p class="description">The internal address of the self-hosted rembg container on your Docker network - see the compose service added alongside this feature. Default assumes a service named <code>rembg</code> on the same network as WordPress.</p>
			<?php endif; ?>
			<?php if ( 'google_ads_developer_token' === $key ) : ?>
				<p class="description">Powers real keyword search-volume and CPC data for the Product Generator, grounding its AI concept picks in actual demand instead of guessing. From your Google Ads Manager account - "Basic access" is needed for real (non-test) data; Google reviews that application separately from creating the token itself.</p>
			<?php endif; ?>
			<?php if ( 'google_ads_client_id' === $key || 'google_ads_client_secret' === $key ) : ?>
				<p class="description">From a Google Cloud project with the Google Ads API enabled (APIs &amp; Services → Credentials → OAuth client ID).</p>
			<?php endif; ?>
			<?php if ( 'google_ads_refresh_token' === $key ) : ?>
				<?php $google_client_id = wookiee_get_setting( 'google_ads_client_id' ); $google_client_secret = wookiee_get_setting( 'google_ads_client_secret' ); ?>
				<?php if ( '' !== trim( (string) $google_client_id ) && '' !== trim( (string) $google_client_secret ) ) : ?>
					<p>
						<a href="<?php echo esc_url( wookiee_google_ads_oauth_start_url() ); ?>" class="button button-primary">Connect to Google Ads</a>
					</p>
					<p class="description">Redirects to Google to authorize access, then fills in this field automatically - no manual token copying. Before clicking, add this exact URL to the OAuth client's "Authorized redirect URIs" in Google Cloud Console (Credentials → your OAuth client), or Google will reject the connection:<br><code><?php echo esc_html( wookiee_google_ads_oauth_redirect_uri() ); ?></code></p>
				<?php else : ?>
					<p class="description">Enter the Client ID and Client Secret above, click Save Changes, then reload this page - a "Connect to Google Ads" button will appear here to fetch this automatically instead of generating it by hand.</p>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( 'google_ads_customer_id' === $key ) : ?>
				<p class="description">The Google Ads account ID to run keyword queries against, digits only (no dashes) - e.g. <code>1234567890</code> for an account shown as 123-456-7890.</p>
			<?php endif; ?>
			<?php if ( 'google_ads_login_customer_id' === $key ) : ?>
				<p class="description">Only needed if the customer ID above is a client account under a Manager (MCC) account - set this to the Manager's own customer ID. Leave blank otherwise.</p>
			<?php endif; ?>
			<?php if ( '' !== $field['default'] ) : ?>
				<p class="description">Default if left blank: <?php echo esc_html( is_string( $field['default'] ) ? str_replace( "\n", ' / ', $field['default'] ) : '' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function wookiee_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$all_fields = wookiee_settings_fields();
	$tabs       = wookiee_settings_tabs();
	?>
	<div class="wrap">
		<h1>Wookiee Settings</h1>
		<p>These values are used across the site (footer, contact form, shipping messaging, policy pages) and update everywhere immediately when saved — including on pages that were already created.</p>

		<?php if ( ! empty( $_GET['wookiee_google_ads_connected'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Connected to Google Ads — the refresh token was saved automatically.</p></div>
		<?php elseif ( ! empty( $_GET['wookiee_google_ads_error'] ) ) : ?>
			<div class="notice notice-error"><p>Google Ads connection failed: <?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['wookiee_google_ads_error'] ) ) ); ?></p></div>
		<?php endif; ?>

		<h2 class="nav-tab-wrapper" id="wookiee-settings-tabs" role="tablist">
			<?php $is_first = true; ?>
			<?php foreach ( $tabs as $tab_key => $tab ) : ?>
				<a href="#<?php echo esc_attr( $tab_key ); ?>" id="wookiee-tab-<?php echo esc_attr( $tab_key ); ?>" class="nav-tab<?php echo $is_first ? ' nav-tab-active' : ''; ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>" role="tab" aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>" aria-controls="wookiee-panel-<?php echo esc_attr( $tab_key ); ?>"><?php echo esc_html( $tab['label'] ); ?></a>
				<?php $is_first = false; ?>
			<?php endforeach; ?>
		</h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'wookiee_settings_group' ); ?>
			<?php $is_first = true; ?>
			<?php foreach ( $tabs as $tab_key => $tab ) : ?>
				<div class="wookiee-tab-panel" id="wookiee-panel-<?php echo esc_attr( $tab_key ); ?>" data-tab-panel="<?php echo esc_attr( $tab_key ); ?>" role="tabpanel" aria-labelledby="wookiee-tab-<?php echo esc_attr( $tab_key ); ?>" <?php echo $is_first ? '' : 'hidden'; ?>>
					<?php if ( 'homepage' === $tab_key ) : $has_llm_key = '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) ); ?>
						<div style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;padding:12px 16px;margin-bottom:20px;max-width:900px;">
							<p style="margin-top:0;"><strong>Generate with AI</strong> — drafts all 5 fields below from a one-line description of the store's niche. Review and edit before clicking Save Changes at the bottom; nothing changes on the live site until then.</p>
							<p>
								<input type="text" id="wookiee-homepage-ai-brief" class="regular-text" value="<?php echo esc_attr( get_option( 'wookiee_niche_brief', '' ) ); ?>" placeholder="e.g. UK home-storage and organisation products - baskets, shelving, drawer organisers">
								<button type="button" class="button button-primary" id="wookiee-homepage-ai-btn" <?php disabled( ! $has_llm_key ); ?>>Generate with AI</button>
								<span id="wookiee-homepage-ai-status" style="margin-left:8px;"></span>
							</p>
							<?php if ( ! $has_llm_key ) : ?>
								<p class="description">Needs an LLM API key on the <a href="#integrations">AI &amp; Integrations</a> tab first.</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<table class="form-table" role="presentation">
						<?php foreach ( $tab['fields'] as $key ) : ?>
							<?php if ( isset( $all_fields[ $key ] ) ) : ?>
								<?php wookiee_render_settings_field_row( $key, $all_fields[ $key ] ); ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</table>
				</div>
				<?php $is_first = false; ?>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<script>
	( function() {
		var STORAGE_KEY = 'wookiee_settings_active_tab';
		var tabs   = document.querySelectorAll( '#wookiee-settings-tabs .nav-tab' );
		var panels = document.querySelectorAll( '.wookiee-tab-panel' );

		function rememberTab( tabKey ) {
			try {
				window.localStorage.setItem( STORAGE_KEY, tabKey );
			} catch ( e ) {
				// Storage unavailable (privacy mode, disabled, etc.) - tab switching still works, it just won't persist.
			}
		}

		function activateTab( tabKey ) {
			tabs.forEach( function( t ) {
				var isActive = t.getAttribute( 'data-tab' ) === tabKey;
				t.classList.toggle( 'nav-tab-active', isActive );
				t.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			} );
			panels.forEach( function( p ) {
				p.hidden = ( p.getAttribute( 'data-tab-panel' ) !== tabKey );
			} );
		}

		tabs.forEach( function( t ) {
			t.addEventListener( 'click', function( e ) {
				e.preventDefault();
				var key = t.getAttribute( 'data-tab' );
				activateTab( key );
				rememberTab( key );
				history.replaceState( null, '', '#' + key );
			} );
		} );

		// Priority: URL hash (direct links/bookmarks) > last-used tab in
		// localStorage (survives the full-page reload that Save Changes
		// causes, since the browser never sends the hash to the server -
		// options.php's redirect has no way to know which tab was active)
		// > the first tab, already active by default in the markup above.
		var hashKey = window.location.hash ? window.location.hash.replace( '#', '' ) : '';
		var storedKey = '';
		try {
			storedKey = window.localStorage.getItem( STORAGE_KEY ) || '';
		} catch ( e ) {}

		var targetKey = '';
		if ( hashKey && document.querySelector( '.wookiee-tab-panel[data-tab-panel="' + hashKey + '"]' ) ) {
			targetKey = hashKey;
		} else if ( storedKey && document.querySelector( '.wookiee-tab-panel[data-tab-panel="' + storedKey + '"]' ) ) {
			targetKey = storedKey;
		}

		if ( targetKey ) {
			activateTab( targetKey );
		}
		rememberTab( targetKey || tabs[ 0 ].getAttribute( 'data-tab' ) );

		// Covers hash changes that don't go through the tab-bar click
		// handler above (e.g. the "AI & Integrations" link in the
		// homepage-copy notice below) and native back/forward navigation.
		window.addEventListener( 'hashchange', function() {
			var key = window.location.hash.replace( '#', '' );
			if ( key && document.querySelector( '.wookiee-tab-panel[data-tab-panel="' + key + '"]' ) ) {
				activateTab( key );
				rememberTab( key );
			}
		} );
	} )();
	</script>
	<script>
	( function() {
		var btn = document.getElementById( 'wookiee-ch-lookup-btn' );
		if ( ! btn ) {
			return;
		}
		btn.addEventListener( 'click', function() {
			var status = document.getElementById( 'wookiee-ch-lookup-status' );
			var numberField = document.getElementById( 'wookiee_setting_company_number' );
			var number = numberField ? numberField.value.trim() : '';
			if ( ! number ) {
				status.textContent = 'Enter a company number first.';
				return;
			}
			btn.disabled = true;
			status.textContent = 'Looking up…';
			var data = new FormData();
			data.append( 'action', 'wookiee_ch_lookup' );
			data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'wookiee_ch_lookup' ) ); ?>' );
			data.append( 'company_number', number );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					btn.disabled = false;
					if ( ! res.success ) {
						status.textContent = res.data && res.data.message ? res.data.message : 'Lookup failed.';
						return;
					}
					var nameField = document.getElementById( 'wookiee_setting_business_name' );
					var addrField = document.getElementById( 'wookiee_setting_registered_address' );
					if ( nameField ) {
						nameField.value = res.data.company_name;
					}
					if ( addrField ) {
						addrField.value = res.data.address;
					}
					status.textContent = 'Found: ' + res.data.company_name + ' (status: ' + res.data.company_status + '). Review the fields, then click Save Changes.';
				} )
				.catch( function() {
					btn.disabled = false;
					status.textContent = 'Lookup failed — could not reach the server.';
				} );
		} );
	} )();
	</script>
	<script>
	( function() {
		var btn = document.getElementById( 'wookiee-homepage-ai-btn' );
		if ( ! btn ) {
			return;
		}
		var fieldMap = {
			eyebrow: 'wookiee_setting_hero_eyebrow',
			headline: 'wookiee_setting_hero_headline',
			subheadline: 'wookiee_setting_hero_subheadline',
			philosophy_heading: 'wookiee_setting_homepage_philosophy_heading',
			philosophy: 'wookiee_setting_homepage_philosophy'
		};
		btn.addEventListener( 'click', function() {
			var status = document.getElementById( 'wookiee-homepage-ai-status' );
			var brief  = document.getElementById( 'wookiee-homepage-ai-brief' ).value.trim();
			if ( ! brief ) {
				status.textContent = 'Describe the niche first.';
				return;
			}
			btn.disabled = true;
			status.textContent = 'Generating… this can take up to a minute.';
			var data = new FormData();
			data.append( 'action', 'wookiee_inline_generate_homepage_copy' );
			data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'wookiee_inline_homepage_copy' ) ); ?>' );
			data.append( 'brief', brief );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					btn.disabled = false;
					if ( ! res.success ) {
						status.textContent = res.data && res.data.message ? res.data.message : 'Generation failed.';
						return;
					}
					Object.keys( fieldMap ).forEach( function( key ) {
						var field = document.getElementById( fieldMap[ key ] );
						if ( field && res.data.fields[ key ] ) {
							field.value = res.data.fields[ key ];
						}
					} );
					status.textContent = 'Drafted below. Review, then click Save Changes.';
				} )
				.catch( function() {
					btn.disabled = false;
					status.textContent = 'Generation failed — could not reach the server.';
				} );
		} );
	} )();
	</script>
	<?php
}

/**
 * Generates homepage hero/philosophy copy directly from the Homepage
 * Copy tab, instead of the admin having to go to the Content Generator
 * page and click a separate "Apply" step - this writes straight into
 * the same 5 fields that tab already shows, so generating and reviewing
 * happen in one place. Reuses the prompt/parsing already built for the
 * Content Generator (wookiee_build_content_prompt(), wookiee_parse_homepage_copy())
 * rather than duplicating that logic here.
 */
add_action( 'wp_ajax_wookiee_inline_generate_homepage_copy', 'wookiee_inline_generate_homepage_copy_handler' );
function wookiee_inline_generate_homepage_copy_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_inline_homepage_copy', 'nonce' );

	$brief = isset( $_POST['brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['brief'] ) ) : '';
	if ( '' === trim( $brief ) ) {
		wp_send_json_error( array( 'message' => 'Describe the niche first.' ) );
	}
	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the AI & Integrations tab first.' ) );
	}

	update_option( 'wookiee_niche_brief', $brief );

	$prompt = wookiee_build_content_prompt( 'homepage_copy', $brief );
	$text   = wookiee_call_llm( $prompt, 2048 );

	if ( is_wp_error( $text ) ) {
		wp_send_json_error( array( 'message' => $text->get_error_message() ) );
	}

	wp_send_json_success( array( 'fields' => wookiee_parse_homepage_copy( $text ) ) );
}

/**
 * Server-side proxy for the Companies House lookup so the API key never
 * reaches the browser. Populates business_name and registered_address on
 * the settings screen only - nothing is saved until the admin clicks
 * Save Changes, since this is business-identity data worth a human check.
 */
add_action( 'wp_ajax_wookiee_ch_lookup', 'wookiee_ch_lookup_handler' );
function wookiee_ch_lookup_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_ch_lookup', 'nonce' );

	$company_number = isset( $_POST['company_number'] ) ? sanitize_text_field( wp_unslash( $_POST['company_number'] ) ) : '';
	$api_key        = wookiee_get_setting( 'companies_house_api_key' );

	if ( '' === $company_number ) {
		wp_send_json_error( array( 'message' => 'Enter a company number first.' ) );
	}
	if ( '' === trim( (string) $api_key ) ) {
		wp_send_json_error( array( 'message' => 'Add your Companies House API key below, click Save Changes, then try the lookup again.' ) );
	}

	$response = wp_remote_get(
		'https://api.company-information.service.gov.uk/company/' . rawurlencode( $company_number ),
		array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':' ),
			),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => $response->get_error_message() ) );
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 404 === $code ) {
		wp_send_json_error( array( 'message' => 'No company found with that number.' ) );
	}
	if ( 401 === $code ) {
		wp_send_json_error( array( 'message' => 'Companies House rejected the API key - check it and try again.' ) );
	}
	if ( 200 !== $code ) {
		wp_send_json_error( array( 'message' => 'Companies House returned an unexpected error (HTTP ' . intval( $code ) . ').' ) );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		wp_send_json_error( array( 'message' => 'Could not read the Companies House response.' ) );
	}

	$addr  = isset( $data['registered_office_address'] ) && is_array( $data['registered_office_address'] ) ? $data['registered_office_address'] : array();
	$lines = array_filter( array(
		isset( $addr['premises'] ) ? $addr['premises'] : '',
		isset( $addr['address_line_1'] ) ? $addr['address_line_1'] : '',
		isset( $addr['address_line_2'] ) ? $addr['address_line_2'] : '',
		isset( $addr['locality'] ) ? $addr['locality'] : '',
		isset( $addr['region'] ) ? $addr['region'] : '',
		isset( $addr['postal_code'] ) ? $addr['postal_code'] : '',
		isset( $addr['country'] ) ? $addr['country'] : '',
	) );

	wp_send_json_success( array(
		'company_name'   => isset( $data['company_name'] ) ? $data['company_name'] : '',
		'company_status' => isset( $data['company_status'] ) ? $data['company_status'] : '',
		'address'        => implode( "\n", $lines ),
	) );
}

/**
 * [wookiee_field key="contact_email"] - prints a settings value anywhere,
 * including inside already-baked page content (About, Contact, Returns,
 * Shipping, etc.) since shortcodes run through do_shortcode() on every
 * page load via the_content(), unlike raw PHP left in stored content.
 */
add_shortcode( 'wookiee_field', 'wookiee_field_shortcode' );
function wookiee_field_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'key' => '' ), $atts );
	if ( 'returns_address' === $atts['key'] ) {
		$value = wookiee_get_returns_address();
	} else {
		$value = wookiee_get_setting( $atts['key'] );
	}
	return nl2br( esc_html( $value ) );
}
