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
		'support_hours'      => array( 'label' => 'Support hours', 'default' => 'Monday to Friday, 9am - 5pm', 'type' => 'text' ),
		'business_name'      => array( 'label' => 'Registered company name', 'default' => 'Wookiee Decor Ltd', 'type' => 'text' ),
		'registered_address'  => array( 'label' => 'Registered office address', 'default' => "Wookiee Decor Ltd\n28 Johnston Park, Cowdenbeath\nKY4 9AZ, United Kingdom", 'type' => 'textarea' ),
		'company_number'     => array( 'label' => 'Company number or name', 'default' => 'SC769264 or Netlinko Ltd', 'type' => 'text' ),
		'companies_house_api_key' => array( 'label' => 'Companies House API key', 'default' => '', 'type' => 'password' ),
		'spaceship_api_key'    => array( 'label' => 'Spaceship API key', 'default' => '', 'type' => 'password' ),
		'spaceship_api_secret' => array( 'label' => 'Spaceship API secret', 'default' => '', 'type' => 'password' ),
		'llm_api_key'        => array( 'label' => 'LLM API key', 'default' => '', 'type' => 'password' ),
		'llm_base_url'       => array( 'label' => 'LLM base URL', 'default' => 'https://api.openai.com/v1', 'type' => 'text' ),
		'llm_default_model'  => array( 'label' => 'LLM default model', 'default' => 'gpt-4o-mini', 'type' => 'text' ),
		'cj_email'           => array( 'label' => 'CJ Dropshipping account email', 'default' => '', 'type' => 'email' ),
		'cj_api_key'         => array( 'label' => 'CJ Dropshipping API key', 'default' => '', 'type' => 'password' ),
		'product_markup_percent' => array( 'label' => 'Product markup (%)', 'default' => '0', 'type' => 'text' ),
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
		'hero_cta_primary'   => array( 'label' => 'Hero button: primary', 'default' => 'Shop all storage', 'type' => 'text' ),
		'hero_cta_secondary' => array( 'label' => 'Hero button: secondary', 'default' => 'Explore categories', 'type' => 'text' ),
		'hero_stat_label'    => array( 'label' => 'Hero stat badge text', 'default' => 'flat-rate UK shipping, dispatched from Cowdenbeath', 'type' => 'text' ),
		'trust_1_title'      => array( 'label' => 'Trust bar item 1: title', 'default' => 'Flat-rate shipping', 'type' => 'text' ),
		'trust_2_title'      => array( 'label' => 'Trust bar item 2: title', 'default' => '30 day returns', 'type' => 'text' ),
		'trust_2_desc'       => array( 'label' => 'Trust bar item 2: subtext', 'default' => 'Hassle-free refunds', 'type' => 'text' ),
		'trust_3_title'      => array( 'label' => 'Trust bar item 3: title', 'default' => 'Secure payments', 'type' => 'text' ),
		'trust_3_desc'       => array( 'label' => 'Trust bar item 3: subtext', 'default' => 'Fully SSL encrypted', 'type' => 'text' ),
		'products_kicker'    => array( 'label' => 'Best-sellers section kicker', 'default' => 'Curated Catalog', 'type' => 'text' ),
		'products_title'     => array( 'label' => 'Best-sellers section title', 'default' => 'Premium Storage Best-Sellers', 'type' => 'text' ),
		'categories_kicker'  => array( 'label' => 'Categories section kicker', 'default' => 'Organise Every Space', 'type' => 'text' ),
		'categories_title'   => array( 'label' => 'Categories section title', 'default' => 'Explore Our Categories', 'type' => 'text' ),
		'categories_subtitle' => array( 'label' => 'Categories section subtitle', 'default' => 'Everything you need to bring calm and order to every corner of your home.', 'type' => 'textarea' ),
		'how_it_works_kicker' => array( 'label' => '"How it works" kicker', 'default' => 'How it works', 'type' => 'text' ),
		'how_it_works_title' => array( 'label' => '"How it works" title', 'default' => 'See how it works in your space.', 'type' => 'text' ),
		'how_it_works_lead'  => array( 'label' => '"How it works" lead paragraph', 'default' => 'Our storage solutions are designed to blend seamlessly into your home. Watch how easily they assemble and transform cluttered spaces into calm, organised areas.', 'type' => 'textarea' ),
		'how_it_works_step1_title' => array( 'label' => '"How it works" step 1 title', 'default' => 'Versatile use', 'type' => 'text' ),
		'how_it_works_step1_desc' => array( 'label' => '"How it works" step 1 text', 'default' => 'Perfect for living rooms, bedrooms, or home offices.', 'type' => 'text' ),
		'how_it_works_step2_title' => array( 'label' => '"How it works" step 2 title', 'default' => 'Easy to assemble', 'type' => 'text' ),
		'how_it_works_step2_desc' => array( 'label' => '"How it works" step 2 text', 'default' => 'No complex tools required, put it together in minutes.', 'type' => 'text' ),
		'how_it_works_step3_title' => array( 'label' => '"How it works" step 3 title', 'default' => 'Durable materials', 'type' => 'text' ),
		'how_it_works_step3_desc' => array( 'label' => '"How it works" step 3 text', 'default' => 'Built to last with sustainable bamboo and sturdy metals.', 'type' => 'text' ),
		'how_it_works_cta'   => array( 'label' => '"How it works" button', 'default' => 'Shop the collection', 'type' => 'text' ),
		'collections_kicker' => array( 'label' => 'Collections section kicker', 'default' => 'Product Lineup', 'type' => 'text' ),
		'collections_title'  => array( 'label' => 'Collections section title', 'default' => 'Shop by Collection', 'type' => 'text' ),
		'about_hero_kicker'  => array( 'label' => 'About: hero kicker', 'default' => 'About our business', 'type' => 'text' ),
		'about_hero_heading' => array( 'label' => 'About: hero heading', 'default' => 'About Wookiee', 'type' => 'text' ),
		'about_hero_lead'    => array( 'label' => 'About: hero lead sentence', 'default' => 'Wookiee is a UK private-label home-storage brand and online retailer operated by Wookiee Decor Ltd.', 'type' => 'textarea' ),
		'about_hero_body'    => array( 'label' => 'About: hero paragraph', 'default' => 'We offer practical storage products for everyday areas of the home, with clear product, delivery and returns information to help customers make informed purchasing decisions.', 'type' => 'textarea' ),
		'about_cta_primary'  => array( 'label' => 'About: primary button', 'default' => 'Shop our products', 'type' => 'text' ),
		'about_cta_secondary' => array( 'label' => 'About: secondary button', 'default' => 'Contact us', 'type' => 'text' ),
		'about_stat_kicker'  => array( 'label' => 'About: stat badge kicker', 'default' => 'UK private-label retailer', 'type' => 'text' ),
		'about_legal_note'   => array( 'label' => 'About: legal business note', 'default' => 'Registered in Scotland', 'type' => 'text' ),
		'about_fulfilment_title' => array( 'label' => 'About: fulfilment title', 'default' => 'Wookiee in Cowdenbeath', 'type' => 'text' ),
		'about_fulfilment_note' => array( 'label' => 'About: fulfilment note', 'default' => 'Stored, packed and dispatched in the UK', 'type' => 'text' ),
		'about_delivery_note' => array( 'label' => 'About: delivery note', 'default' => '3-5 working days normally', 'type' => 'text' ),
		'about_section2_kicker' => array( 'label' => 'About: 2nd section kicker', 'default' => 'Our range and approach', 'type' => 'text' ),
		'about_section2_heading' => array( 'label' => 'About: 2nd section heading', 'default' => 'Practical storage, clearly presented.', 'type' => 'text' ),
		'about_section2_lead' => array( 'label' => 'About: 2nd section lead', 'default' => 'Our range focuses on useful storage products for kitchens, bathrooms, drawers, footwear and other everyday spaces.', 'type' => 'textarea' ),
		'about_section2_body1' => array( 'label' => 'About: 2nd section paragraph 1', 'default' => 'Products sold as Wookiee-branded goods form part of our private-label range. These products may be produced for the Wookiee brand by selected third-party manufacturers.', 'type' => 'textarea' ),
		'about_section2_body2' => array( 'label' => 'About: 2nd section paragraph 2', 'default' => 'Wookiee Decor Ltd operates the brand and is the retailer responsible for purchases made through this website. We manage the customer-facing product information, pricing, order administration, delivery arrangements and customer support.', 'type' => 'textarea' ),
		'about_highlight_title' => array( 'label' => 'About: highlight card title', 'default' => 'Practical selection', 'type' => 'text' ),
		'about_highlight_desc' => array( 'label' => 'About: highlight card text', 'default' => 'Products are selected for useful home organisation and everyday storage.', 'type' => 'text' ),
		'contact_kicker'     => array( 'label' => 'Contact: kicker', 'default' => 'Get In Touch', 'type' => 'text' ),
		'contact_heading'    => array( 'label' => 'Contact: heading', 'default' => 'Contact our team', 'type' => 'text' ),
		'contact_lead'       => array( 'label' => 'Contact: lead sentence', 'default' => "Have a question about an order, shipping, or returns? We're here to help. Drop us a line below or reach out via email or phone.", 'type' => 'textarea' ),
		'contact_form_subtitle' => array( 'label' => 'Contact: form subtitle', 'default' => 'We typically reply within 24 business hours.', 'type' => 'text' ),
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
			'fields' => array( 'company_number', 'companies_house_api_key', 'business_name', 'registered_address', 'countries_served', 'product_markup_percent' ),
		),
		'contact' => array(
			'label'  => 'Contact & Support',
			'fields' => array( 'contact_email', 'contact_phone', 'support_hours' ),
		),
		'shipping' => array(
			'label'  => 'Shipping & Returns',
			'fields' => array( 'shipping_rate', 'shipping_dispatch', 'returns_address', 'returns_period_days' ),
		),
		'homepage' => array(
			'label'  => 'Homepage Copy',
			'fields' => array(
				'hero_eyebrow', 'hero_headline', 'hero_subheadline', 'hero_cta_primary', 'hero_cta_secondary', 'hero_stat_label',
				'trust_1_title', 'trust_2_title', 'trust_2_desc', 'trust_3_title', 'trust_3_desc',
				'products_kicker', 'products_title',
				'categories_kicker', 'categories_title', 'categories_subtitle',
				'how_it_works_kicker', 'how_it_works_title', 'how_it_works_lead',
				'how_it_works_step1_title', 'how_it_works_step1_desc',
				'how_it_works_step2_title', 'how_it_works_step2_desc',
				'how_it_works_step3_title', 'how_it_works_step3_desc', 'how_it_works_cta',
				'collections_kicker', 'collections_title',
				'homepage_philosophy_heading', 'homepage_philosophy',
			),
		),
		'about_contact' => array(
			'label'  => 'About & Contact Copy',
			'fields' => array(
				'about_hero_kicker', 'about_hero_heading', 'about_hero_lead', 'about_hero_body', 'about_cta_primary', 'about_cta_secondary',
				'about_stat_kicker', 'about_legal_note', 'about_fulfilment_title', 'about_fulfilment_note', 'about_delivery_note',
				'about_section2_kicker', 'about_section2_heading', 'about_section2_lead', 'about_section2_body1', 'about_section2_body2',
				'about_highlight_title', 'about_highlight_desc',
				'contact_kicker', 'contact_heading', 'contact_lead', 'contact_form_subtitle',
			),
		),
		'social' => array(
			'label'  => 'Social Media',
			'fields' => array( 'facebook_url', 'instagram_url', 'linkedin_url', 'pinterest_url' ),
		),
		'integrations' => array(
			'label'  => 'Activation',
			'fields' => array( 'llm_api_key', 'llm_base_url', 'llm_default_model', 'cj_email', 'cj_api_key', 'bg_removal_provider', 'cloudinary_cloud_name', 'cloudinary_api_key', 'cloudinary_api_secret', 'rembg_endpoint_url', 'google_ads_developer_token', 'google_ads_client_id', 'google_ads_client_secret', 'google_ads_refresh_token', 'google_ads_customer_id', 'google_ads_login_customer_id', 'spaceship_api_key', 'spaceship_api_secret' ),
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
			<?php elseif ( 'password' === $field['type'] ) : ?>
				<input type="password" name="wookiee_setting_<?php echo esc_attr( $key ); ?>" id="wookiee_setting_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_option( 'wookiee_setting_' . $key, '' ) ); ?>" placeholder="<?php echo esc_attr( $field['default'] ); ?>" class="regular-text wookiee-reveal-input" autocomplete="off">
				<button type="button" class="button wookiee-reveal-btn" data-target="wookiee_setting_<?php echo esc_attr( $key ); ?>">Show</button>
			<?php else : ?>
				<input type="<?php echo 'url' === $field['type'] ? 'url' : ( 'email' === $field['type'] ? 'email' : 'text' ); ?>" name="wookiee_setting_<?php echo esc_attr( $key ); ?>" id="wookiee_setting_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_option( 'wookiee_setting_' . $key, '' ) ); ?>" placeholder="<?php echo esc_attr( $field['default'] ); ?>" class="regular-text" autocomplete="off">
			<?php endif; ?>
			<?php if ( 'company_number' === $key ) : ?>
				<p>
					<button type="button" class="button" id="wookiee-ch-lookup-btn">Look up on Companies House</button>
					<span id="wookiee-ch-lookup-status" style="margin-left:8px;"></span>
				</p>
				<p class="description">Fills in the registered company name and address below from the official Companies House register — enter the exact company number, or just the company name to search a list of matches. <?php echo wookiee_central_api_configured() ? '' : 'Requires an API key (below) — get one free at <a href="https://developer.company-information.service.gov.uk/" target="_blank" rel="noopener">developer.company-information.service.gov.uk</a>. '; ?>Review the filled-in fields before saving.</p>
				<div id="wookiee-ch-search-results" class="wookiee-ch-search-results" hidden></div>
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
			<?php if ( 'spaceship_api_key' === $key ) : ?>
				<p class="description">Powers the domain-availability check and registration on the Setup wizard's Business Identity step, when a company is looked up or picked from search - suggests a short, brandable site title, lists available <code>.com</code>/<code>.uk</code> options, and can register one directly. Create a key + secret pair in the <a href="https://www.spaceship.com/application/api-manager/" target="_blank" rel="noopener">Spaceship API Manager</a> with the <code>domains:read</code> scope for the check, plus <code>contacts:write</code> and <code>domains:billing</code> for the Register button to work (that's a real, billable purchase, not a preview), and <code>domains:write</code> / <code>dnsrecords:write</code> if you also want to set custom nameservers or DNS records at registration time. Leave blank to skip the domain check entirely (the site title is still suggested).</p>
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
			<?php if ( in_array( $key, array( 'business_name', 'registered_address', 'company_number', 'contact_email' ), true ) ) : ?>
				<p class="description">Shown elsewhere on the site as a placeholder until you set this - but policy page generation on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-content-generator' ) ); ?>">Content Generator</a> requires a real value here first.</p>
			<?php elseif ( '' !== $field['default'] ) : ?>
				<p class="description">Default if left blank: <?php echo esc_html( is_string( $field['default'] ) ? str_replace( "\n", ' / ', $field['default'] ) : '' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * The "Generate with AI" notice + niche-brief-with-sparkle + button for
 * the Homepage Copy / About & Contact Copy tabs - extracted so the
 * Setup wizard's equivalent step can render the exact same markup
 * instead of a second hand-copied version. The click-wiring JS lives
 * once in wookiee_enqueue_niche_suggest_assets() (inc/admin-menu.php),
 * shared across every screen this can appear on.
 */
function wookiee_render_ai_copy_generator_notice( $tab_key, $id_suffix = '' ) {
	$config = array(
		'homepage'      => array(
			'brief_id'  => 'wookiee-homepage-ai-brief',
			'btn_id'    => 'wookiee-homepage-ai-btn',
			'status_id' => 'wookiee-homepage-ai-status',
			'desc'      => 'rewrites every field below (hero, trust bar, section headers, "how it works", philosophy) to match a one-line description of the store\'s niche, keeping the page\'s design/layout exactly as-is.',
		),
		'about_contact' => array(
			'brief_id'  => 'wookiee-about-ai-brief',
			'btn_id'    => 'wookiee-about-ai-btn',
			'status_id' => 'wookiee-about-ai-status',
			'desc'      => 'rewrites every field below (About page hero/copy, Contact page intro) to match a one-line description of the store\'s niche, keeping both pages\' existing design/layout exactly as-is - only the text changes.',
		),
	);
	if ( ! isset( $config[ $tab_key ] ) ) {
		return;
	}
	$c = $config[ $tab_key ];
	if ( '' !== $id_suffix ) {
		// A second independent copy of this same control (e.g. the Setup
		// wizard's Contact tab triggering the same about_contact
		// generation as the About tab) needs its own unique element IDs
		// to avoid colliding with the first one on the same page.
		$c['brief_id']  .= $id_suffix;
		$c['btn_id']    .= $id_suffix;
		$c['status_id'] .= $id_suffix;
	}
	$has_llm_key = wookiee_central_api_configured() || '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	?>
	<div style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;padding:12px 16px;margin-bottom:20px;max-width:900px;">
		<p style="margin-top:0;"><strong>Generate with AI</strong> — <?php echo $c['desc']; ?> Review and edit before clicking Save Changes at the bottom; nothing changes on the live site until then.</p>
		<p>
			<span class="wookiee-niche-input-wrap">
				<input type="text" id="<?php echo esc_attr( $c['brief_id'] ); ?>" class="regular-text" value="<?php echo esc_attr( get_option( 'wookiee_niche_brief', '' ) ); ?>" placeholder="e.g. UK home-storage and organisation products - baskets, shelving, drawer organisers">
				<?php wookiee_niche_suggest_button( $c['brief_id'] ); ?>
			</span>
			<button type="button" class="button button-primary" id="<?php echo esc_attr( $c['btn_id'] ); ?>" <?php disabled( ! $has_llm_key ); ?>>Generate with AI</button>
			<span id="<?php echo esc_attr( $c['status_id'] ); ?>" style="margin-left:8px;"></span>
		</p>
		<?php if ( ! $has_llm_key ) : ?>
			<p class="description">Needs an LLM API key on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#integrations' ) ); ?>">Activation tab</a> first.</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * A plain <table class="form-table"> of settings field rows for an
 * arbitrary list of field keys - the same rendering the tabbed Settings
 * page uses per-tab, extracted so any other screen (the Setup wizard)
 * can render the same real, saveable fields for just the keys it needs,
 * without re-implementing wookiee_render_settings_field_row() calls by
 * hand or duplicating field definitions.
 */
function wookiee_render_settings_fields_table( array $keys ) {
	$all_fields = wookiee_settings_fields();
	?>
	<table class="form-table" role="presentation">
		<?php foreach ( $keys as $key ) : ?>
			<?php if ( isset( $all_fields[ $key ] ) ) : ?>
				<?php wookiee_render_settings_field_row( $key, $all_fields[ $key ] ); ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</table>
	<?php
}

function wookiee_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs = wookiee_settings_tabs();
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
					<?php wookiee_render_ai_copy_generator_notice( $tab_key ); ?>
					<?php if ( 'integrations' === $tab_key ) : ?>
						<?php wookiee_render_activation_section(); ?>
					<?php endif; ?>
					<?php
					$fields_to_show = $tab['fields'];
					if ( wookiee_central_api_configured() ) {
						$operator_only  = wookiee_operator_only_settings_keys();
						$fields_to_show = array_values( array_diff( $fields_to_show, $operator_only ) );
					}
					?>
					<?php wookiee_render_settings_fields_table( $fields_to_show ); ?>
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
	<?php
	// The Companies House lookup button's click-wiring, and the
	// "Generate with AI" buttons' wiring, both live in
	// wookiee_enqueue_niche_suggest_assets() (inc/admin-menu.php) instead
	// of here, since the Setup wizard renders these exact same fields/
	// buttons too and needs the same wiring - one shared script instead
	// of two copies that could drift apart.
}

/**
 * Generates every homepage copy field directly from the Homepage Copy
 * tab, instead of the admin having to go to a separate page and click
 * an extra "Apply" step - this writes straight into the same fields
 * that tab already shows, so generating and reviewing happen in one
 * place, and nothing reaches the live site until Save Changes is
 * clicked. Reuses the prompt/parsing built in inc/content-generator.php
 * (wookiee_build_content_prompt(), wookiee_parse_homepage_copy()) rather
 * than duplicating that logic here.
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

	update_option( 'wookiee_niche_brief', $brief );

	$prompt = wookiee_build_content_prompt( 'homepage_copy', $brief );
	$text   = wookiee_call_llm( $prompt, 3072 );

	if ( is_wp_error( $text ) ) {
		wp_send_json_error( array( 'message' => $text->get_error_message() ) );
	}

	update_option( 'wookiee_homepage_ai_generated', 1 );

	wp_send_json_success( array( 'fields' => wookiee_parse_homepage_copy( $text ) ) );
}

/**
 * Same pattern as the homepage generator above, but for the About &
 * Contact Copy tab's fields - both pages have a real visual design
 * (hero, stat badge, facts strip, sidebar cards) that stays fixed;
 * only the text embedded via [wookiee_field] merge tags changes.
 */
add_action( 'wp_ajax_wookiee_inline_generate_about_contact_copy', 'wookiee_inline_generate_about_contact_copy_handler' );
function wookiee_inline_generate_about_contact_copy_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_inline_about_contact_copy', 'nonce' );

	$brief = isset( $_POST['brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['brief'] ) ) : '';
	if ( '' === trim( $brief ) ) {
		wp_send_json_error( array( 'message' => 'Describe the niche first.' ) );
	}

	update_option( 'wookiee_niche_brief', $brief );

	$prompt = wookiee_build_content_prompt( 'about_contact', $brief );
	$text   = wookiee_call_llm( $prompt, 2048 );

	if ( is_wp_error( $text ) ) {
		wp_send_json_error( array( 'message' => $text->get_error_message() ) );
	}

	update_option( 'wookiee_about_contact_ai_generated', 1 );

	wp_send_json_success( array( 'fields' => wookiee_parse_copy_fields( $text, wookiee_about_contact_copy_fields() ) ) );
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

	if ( '' === $company_number ) {
		wp_send_json_error( array( 'message' => 'Enter a company number first.' ) );
	}

	if ( wookiee_central_api_configured() ) {
		$result = wookiee_central_api_request( 'GET', '/companies-house/lookup?company_number=' . rawurlencode( $company_number ) );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	$api_key = wookiee_get_setting( 'companies_house_api_key' );
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
 * Server-side proxy for Companies House's name search, for admins who
 * don't have the company number to hand. Filters to active companies
 * only (a name search on a defunct/dissolved company isn't useful here)
 * and caps the list at 10 - picking one just fills the company_number
 * field and re-uses the existing number lookup above rather than
 * duplicating the name/address-filling logic.
 */
add_action( 'wp_ajax_wookiee_ch_search', 'wookiee_ch_search_handler' );
function wookiee_ch_search_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_ch_search', 'nonce' );

	$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

	if ( '' === trim( $query ) ) {
		wp_send_json_error( array( 'message' => 'Enter a company name first.' ) );
	}

	if ( wookiee_central_api_configured() ) {
		$result = wookiee_central_api_request( 'GET', '/companies-house/search?query=' . rawurlencode( $query ) );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	$api_key = wookiee_get_setting( 'companies_house_api_key' );
	if ( '' === trim( (string) $api_key ) ) {
		wp_send_json_error( array( 'message' => 'Add your Companies House API key below, click Save Changes, then try the search again.' ) );
	}

	$response = wp_remote_get(
		'https://api.company-information.service.gov.uk/search/companies?q=' . rawurlencode( $query ) . '&items_per_page=20',
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
	if ( 401 === $code ) {
		wp_send_json_error( array( 'message' => 'Companies House rejected the API key - check it and try again.' ) );
	}
	if ( 200 !== $code ) {
		wp_send_json_error( array( 'message' => 'Companies House returned an unexpected error (HTTP ' . intval( $code ) . ').' ) );
	}

	$data  = json_decode( wp_remote_retrieve_body( $response ), true );
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();

	$active = array();
	foreach ( $items as $item ) {
		if ( isset( $item['company_status'] ) && 'active' === $item['company_status'] ) {
			$active[] = array(
				'company_number' => isset( $item['company_number'] ) ? $item['company_number'] : '',
				'title'          => isset( $item['title'] ) ? $item['title'] : '',
				'address'        => isset( $item['address_snippet'] ) ? $item['address_snippet'] : '',
			);
		}
	}

	if ( empty( $active ) ) {
		wp_send_json_error( array( 'message' => 'No active companies found matching "' . esc_html( $query ) . '".' ) );
	}

	wp_send_json_success( array( 'results' => array_slice( $active, 0, 10 ) ) );
}

/**
 * Turns a registered company name into short, brandable site-title
 * candidates - strips only true legal/corporate-structure suffixes
 * (Ltd, Group, Holdings, etc.), keeps every other word (a descriptive
 * word like "Enterprise" is part of the brand, not boilerplate),
 * concatenates what's left, and offers a few truncation lengths from
 * most-descriptive to shortest so a domain check has fallbacks to try
 * if the longest one is taken. E.g. "Netlinko Ltd" -> "netlinko";
 * "Netlinko Enterprise Group" -> "netlinkoenterp" (14-char cap).
 */
function wookiee_generate_site_name_candidates( $company_name ) {
	$suffixes = array( 'ltd', 'limited', 'llp', 'llc', 'plc', 'inc', 'incorporated', 'corp', 'corporation', 'group', 'holdings', 'holding', 'co', 'company' );

	$name  = strtolower( trim( (string) $company_name ) );
	$name  = str_replace( '&', ' and ', $name );
	$words = preg_split( '/[^a-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY );

	$kept = array_values( array_diff( $words, $suffixes ) );
	if ( empty( $kept ) ) {
		$kept = ! empty( $words ) ? $words : array( 'mystore' );
	}

	$base = preg_replace( '/[^a-z0-9]/', '', implode( '', $kept ) );
	if ( '' === $base ) {
		$base = 'mystore';
	}
	$first_word_len = strlen( preg_replace( '/[^a-z0-9]/', '', $kept[0] ) );

	$candidates = array();
	foreach ( array( 14, 10, 8 ) as $len ) {
		$slug = substr( $base, 0, $len );
		if ( strlen( $slug ) >= 3 && ! in_array( $slug, $candidates, true ) ) {
			$candidates[] = $slug;
		}
	}
	return array(
		'candidates'     => ! empty( $candidates ) ? $candidates : array( $base ),
		'first_word_len' => $first_word_len,
	);
}

/**
 * Turns a slug like "netlinkeuropel" back into a readable display name
 * like "Netlink Europel" - splits after the first kept word (tracked
 * from wookiee_generate_site_name_candidates()) so the site title
 * doesn't read as one squashed-together blob, without trying to guess
 * word boundaries any deeper than that (a truncated trailing word, e.g.
 * "l" from "Logistics", stays attached to the second part rather than
 * becoming its own stray one-letter "word").
 */
function wookiee_prettify_slug( $slug, $first_word_len ) {
	$slug = (string) $slug;
	if ( $first_word_len > 0 && $first_word_len < strlen( $slug ) ) {
		return ucfirst( substr( $slug, 0, $first_word_len ) ) . ' ' . ucfirst( substr( $slug, $first_word_len ) );
	}
	return ucfirst( $slug );
}

/**
 * Server-side proxy for Spaceship's domain-availability check (one
 * domain per call - docs.spaceship.dev, 5 req/domain per 300s and
 * 30 req/user per 30s). Returns true/false, or a WP_Error if the keys
 * aren't configured or the API call itself fails - callers treat that
 * as "couldn't check" rather than "taken".
 */
function wookiee_check_domain_availability( $domain ) {
	$api_key    = wookiee_get_setting( 'spaceship_api_key' );
	$api_secret = wookiee_get_setting( 'spaceship_api_secret' );

	if ( '' === trim( (string) $api_key ) || '' === trim( (string) $api_secret ) ) {
		return new WP_Error( 'wookiee_no_spaceship_key', 'Spaceship API key/secret not configured.' );
	}

	$response = wp_remote_get(
		'https://spaceship.dev/api/v1/domains/' . rawurlencode( $domain ) . '/available',
		array(
			'headers' => array(
				'X-Api-Key'    => $api_key,
				'X-Api-Secret' => $api_secret,
			),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'wookiee_spaceship_http_error', 'Spaceship API returned HTTP ' . intval( wp_remote_retrieve_response_code( $response ) ) . '.' );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || ! isset( $data['result'] ) ) {
		return new WP_Error( 'wookiee_spaceship_bad_response', 'Could not read the Spaceship response.' );
	}

	return 'available' === $data['result'];
}

/**
 * Expands the 3 base name candidates (14/10/8-char truncations) into a
 * larger ordered pool to search through - the plain forms first (most
 * on-brand), then generic suffix variants - so there's enough runway to
 * find several genuinely available domains without the admin having to
 * manually keep retrying different names.
 */
function wookiee_expand_site_name_candidates( array $base_candidates ) {
	$expanded = $base_candidates;
	foreach ( $base_candidates as $base ) {
		foreach ( array( 'hq', 'shop', 'store', 'co', 'online' ) as $suffix ) {
			$slug = $base . $suffix;
			if ( ! in_array( $slug, $expanded, true ) ) {
				$expanded[] = $slug;
			}
		}
	}
	return array_slice( $expanded, 0, 12 );
}

/**
 * Suggests a short site title from a just-selected/looked-up company
 * name, and (if Spaceship keys are configured) searches until it finds
 * up to 3 available .com domains and 3 available .uk domains -
 * .com checked first per candidate since that's the stated preference.
 * Without Spaceship keys configured, still returns the generated name
 * with checked=false rather than blocking the feature entirely on a
 * third integration.
 */
add_action( 'wp_ajax_wookiee_suggest_site_name', 'wookiee_suggest_site_name_handler' );
function wookiee_suggest_site_name_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_suggest_site_name', 'nonce' );

	$company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
	if ( '' === trim( $company_name ) ) {
		wp_send_json_error( array( 'message' => 'No company name to work from.' ) );
	}

	if ( wookiee_central_api_configured() ) {
		$result = wookiee_central_api_request( 'POST', '/domains/suggest-site-name', array( 'company_name' => $company_name ) );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	$generated       = wookiee_generate_site_name_candidates( $company_name );
	$base_candidates = $generated['candidates'];
	$first_word_len  = $generated['first_word_len'];
	$has_spaceship   = '' !== trim( (string) wookiee_get_setting( 'spaceship_api_key' ) ) && '' !== trim( (string) wookiee_get_setting( 'spaceship_api_secret' ) );

	if ( ! $has_spaceship ) {
		wp_send_json_success( array( 'site_name' => wookiee_prettify_slug( $base_candidates[0], $first_word_len ), 'checked' => false, 'suggestions' => null ) );
	}

	$candidates = wookiee_expand_site_name_candidates( $base_candidates );
	$found      = array( 'com' => array(), 'uk' => array() );

	foreach ( $candidates as $slug ) {
		if ( count( $found['com'] ) >= 3 && count( $found['uk'] ) >= 3 ) {
			break;
		}
		foreach ( array( 'com', 'uk' ) as $tld ) {
			if ( count( $found[ $tld ] ) >= 3 ) {
				continue;
			}
			$available = wookiee_check_domain_availability( $slug . '.' . $tld );
			if ( is_wp_error( $available ) ) {
				wp_send_json_success( array(
					'site_name'   => wookiee_prettify_slug( $base_candidates[0], $first_word_len ),
					'checked'     => false,
					'suggestions' => null,
					'message'     => $available->get_error_message(),
				) );
			}
			if ( $available ) {
				$found[ $tld ][] = array( 'domain' => $slug . '.' . $tld, 'slug' => $slug, 'site_name' => wookiee_prettify_slug( $slug, $first_word_len ) );
			}
		}
	}

	$site_name = ! empty( $found['com'] ) ? $found['com'][0]['site_name'] : ( ! empty( $found['uk'] ) ? $found['uk'][0]['site_name'] : wookiee_prettify_slug( $base_candidates[0], $first_word_len ) );

	wp_send_json_success( array(
		'site_name'   => $site_name,
		'checked'     => true,
		'suggestions' => array( 'com' => $found['com'], 'uk' => $found['uk'] ),
	) );
}

/**
 * Creates a Spaceship registrant/admin/tech/billing contact record -
 * required before a domain can actually be registered (as opposed to
 * just checked for availability). Returns the new contactId, which is
 * single-use input to the registration call right after - nothing here
 * is stored long-term on the Spaceship side beyond what registration
 * itself requires.
 */
function wookiee_ch_create_spaceship_contact( array $contact ) {
	$api_key    = wookiee_get_setting( 'spaceship_api_key' );
	$api_secret = wookiee_get_setting( 'spaceship_api_secret' );

	$response = wp_remote_request(
		'https://spaceship.dev/api/v1/contacts',
		array(
			'method'  => 'PUT',
			'headers' => array(
				'X-Api-Key'    => $api_key,
				'X-Api-Secret' => $api_secret,
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $contact ),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== $code && 201 !== $code ) {
		$detail = is_array( $data ) && isset( $data['detail'] ) ? $data['detail'] : ( 'HTTP ' . intval( $code ) );
		return new WP_Error( 'wookiee_spaceship_contact_error', 'Spaceship rejected the contact details: ' . $detail );
	}
	if ( ! is_array( $data ) || empty( $data['contactId'] ) ) {
		return new WP_Error( 'wookiee_spaceship_contact_error', 'Spaceship did not return a contact ID.' );
	}
	return $data['contactId'];
}

/**
 * Registers a domain through Spaceship - a real, billed purchase
 * against whatever payment method is on file for that Spaceship
 * account, not a preview or a WordPress-side simulation. Requires the
 * API key to also carry contacts:write and domains:billing scopes
 * (domains:read alone, used for the availability check, isn't enough).
 * Auto-renew defaults to off client-side so a click here can't quietly
 * turn into a recurring charge unless the admin explicitly opts in.
 */
add_action( 'wp_ajax_wookiee_register_domain', 'wookiee_register_domain_handler' );
function wookiee_register_domain_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_register_domain', 'nonce' );

	$domain     = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';
	$years      = isset( $_POST['years'] ) ? max( 1, min( 10, intval( $_POST['years'] ) ) ) : 1;
	$auto_renew = ! empty( $_POST['auto_renew'] );

	$raw_contact = array(
		'first_name'  => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
		'last_name'   => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
		'organization' => isset( $_POST['organization'] ) ? sanitize_text_field( wp_unslash( $_POST['organization'] ) ) : '',
		'email'       => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'address1'    => isset( $_POST['address1'] ) ? sanitize_text_field( wp_unslash( $_POST['address1'] ) ) : '',
		'address2'    => isset( $_POST['address2'] ) ? sanitize_text_field( wp_unslash( $_POST['address2'] ) ) : '',
		'city'        => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
		'state'       => isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '',
		'postal_code' => isset( $_POST['postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['postal_code'] ) ) : '',
		'country'     => isset( $_POST['country'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country'] ) ) ) : '',
		'phone'       => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
	);

	if ( '' === $domain ) {
		wp_send_json_error( array( 'message' => 'No domain specified.' ) );
	}
	foreach ( array( 'first_name', 'last_name', 'email', 'address1', 'city', 'country', 'phone' ) as $required ) {
		if ( '' === trim( (string) $raw_contact[ $required ] ) ) {
			wp_send_json_error( array( 'message' => 'Fill in every required registrant field (first/last name, email, address, city, country, phone).' ) );
		}
	}

	if ( wookiee_central_api_configured() ) {
		$result = wookiee_central_api_request( 'POST', '/domains/register', array_merge( $raw_contact, array(
			'domain'     => $domain,
			'years'      => $years,
			'auto_renew' => $auto_renew,
		) ) );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	$api_key    = wookiee_get_setting( 'spaceship_api_key' );
	$api_secret = wookiee_get_setting( 'spaceship_api_secret' );
	if ( '' === trim( (string) $api_key ) || '' === trim( (string) $api_secret ) ) {
		wp_send_json_error( array( 'message' => 'Add your Spaceship API key/secret on Settings first.' ) );
	}

	$contact = array(
		'firstName'     => $raw_contact['first_name'],
		'lastName'      => $raw_contact['last_name'],
		'organization'  => $raw_contact['organization'],
		'email'         => $raw_contact['email'],
		'address1'      => $raw_contact['address1'],
		'address2'      => $raw_contact['address2'],
		'city'          => $raw_contact['city'],
		'stateProvince' => $raw_contact['state'],
		'postalCode'    => $raw_contact['postal_code'],
		'country'       => $raw_contact['country'],
		'phone'         => $raw_contact['phone'],
	);

	$contact_id = wookiee_ch_create_spaceship_contact( array_filter( $contact, function( $v ) { return '' !== $v; } ) );
	if ( is_wp_error( $contact_id ) ) {
		wp_send_json_error( array( 'message' => $contact_id->get_error_message() ) );
	}

	$response = wp_remote_request(
		'https://spaceship.dev/api/v1/domains/' . rawurlencode( $domain ),
		array(
			'method'  => 'POST',
			'headers' => array(
				'X-Api-Key'    => $api_key,
				'X-Api-Secret' => $api_secret,
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'autoRenew' => $auto_renew,
				'years'     => $years,
				'privacyProtection' => array( 'level' => 'high', 'userConsent' => true ),
				'contacts'  => array(
					'registrant' => $contact_id,
					'admin'      => $contact_id,
					'tech'       => $contact_id,
					'billing'    => $contact_id,
				),
			) ),
			'timeout' => 20,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => $response->get_error_message() ) );
	}
	$code = wp_remote_retrieve_response_code( $response );
	if ( 202 !== $code ) {
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		$detail = is_array( $data ) && isset( $data['detail'] ) ? $data['detail'] : ( 'HTTP ' . intval( $code ) );
		wp_send_json_error( array( 'message' => 'Spaceship declined the registration: ' . $detail ) );
	}

	$operation_id = wp_remote_retrieve_header( $response, 'spaceship-async-operationid' );
	if ( ! $operation_id ) {
		wp_send_json_error( array( 'message' => 'Spaceship accepted the request but returned no operation ID to track it.' ) );
	}

	wp_send_json_success( array( 'operation_id' => $operation_id ) );
}

/**
 * Polls a domain-registration operation. Registration itself is async
 * on Spaceship's side, so the register handler above returns as soon as
 * the request is accepted - this is called repeatedly from the browser
 * (not looped/slept on the server) until it reports success or failed.
 */
add_action( 'wp_ajax_wookiee_poll_domain_registration', 'wookiee_poll_domain_registration_handler' );
function wookiee_poll_domain_registration_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_register_domain', 'nonce' );

	$operation_id = isset( $_POST['operation_id'] ) ? sanitize_text_field( wp_unslash( $_POST['operation_id'] ) ) : '';
	if ( '' === $operation_id ) {
		wp_send_json_error( array( 'message' => 'No operation ID given.' ) );
	}

	if ( wookiee_central_api_configured() ) {
		$result = wookiee_central_api_request( 'GET', '/domains/operations/' . rawurlencode( $operation_id ) );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	$response = wp_remote_get(
		'https://spaceship.dev/api/v1/async-operations/' . rawurlencode( $operation_id ),
		array(
			'headers' => array(
				'X-Api-Key'    => wookiee_get_setting( 'spaceship_api_key' ),
				'X-Api-Secret' => wookiee_get_setting( 'spaceship_api_secret' ),
			),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => $response->get_error_message() ) );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data['status'] ) ) {
		wp_send_json_error( array( 'message' => 'Could not read the operation status.' ) );
	}

	wp_send_json_success( array( 'status' => $data['status'], 'details' => isset( $data['details'] ) ? $data['details'] : '' ) );
}

/**
 * Points a just-registered domain at custom nameservers, replacing
 * Spaceship's default ones. Only fires after registration has actually
 * completed (the domain doesn't exist in the account before then), and
 * needs the API key to also carry domains:write on top of the scopes
 * registration itself needs.
 */
add_action( 'wp_ajax_wookiee_set_domain_nameservers', 'wookiee_set_domain_nameservers_handler' );
function wookiee_set_domain_nameservers_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_register_domain', 'nonce' );

	$domain    = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';
	$hosts_raw = isset( $_POST['hosts'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hosts'] ) ) : '';
	if ( '' === $domain ) {
		wp_send_json_error( array( 'message' => 'No domain specified.' ) );
	}

	$hosts = array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $hosts_raw ) ) ) );
	if ( count( $hosts ) < 2 || count( $hosts ) > 12 ) {
		wp_send_json_error( array( 'message' => 'Provide between 2 and 12 nameservers.' ) );
	}

	if ( wookiee_central_api_configured() ) {
		$result = wookiee_central_api_request( 'PUT', '/domains/' . rawurlencode( $domain ) . '/nameservers', array( 'hosts' => implode( "\n", $hosts ) ) );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success();
	}

	$response = wp_remote_request(
		'https://spaceship.dev/api/v1/domains/' . rawurlencode( $domain ) . '/nameservers',
		array(
			'method'  => 'PUT',
			'headers' => array(
				'X-Api-Key'    => wookiee_get_setting( 'spaceship_api_key' ),
				'X-Api-Secret' => wookiee_get_setting( 'spaceship_api_secret' ),
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( array( 'provider' => 'custom', 'hosts' => $hosts ) ),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => $response->get_error_message() ) );
	}
	$code = wp_remote_retrieve_response_code( $response );
	if ( ! in_array( $code, array( 200, 202 ), true ) ) {
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		$detail = is_array( $data ) && isset( $data['detail'] ) ? $data['detail'] : ( 'HTTP ' . intval( $code ) );
		wp_send_json_error( array( 'message' => 'Nameserver update was rejected: ' . $detail ) );
	}

	wp_send_json_success();
}

/**
 * Adds DNS records to a just-registered domain still on the default
 * nameservers (custom nameservers manage their own zone elsewhere, so
 * this is skipped client-side when custom nameservers were chosen).
 * Needs the API key to also carry dnsrecords:write.
 */
add_action( 'wp_ajax_wookiee_set_domain_dns_records', 'wookiee_set_domain_dns_records_handler' );
function wookiee_set_domain_dns_records_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_register_domain', 'nonce' );

	$domain       = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';
	$records_json = isset( $_POST['records'] ) ? wp_unslash( $_POST['records'] ) : '';
	if ( '' === $domain ) {
		wp_send_json_error( array( 'message' => 'No domain specified.' ) );
	}

	$records = json_decode( $records_json, true );
	if ( ! is_array( $records ) || empty( $records ) ) {
		wp_send_json_error( array( 'message' => 'No DNS records to add.' ) );
	}

	$valid_types = array( 'A', 'AAAA', 'ALIAS', 'CNAME', 'HTTPS', 'MX', 'NS', 'PTR', 'SRV', 'SVCB', 'TXT' );
	$items       = array();
	foreach ( $records as $r ) {
		$type    = isset( $r['type'] ) ? strtoupper( sanitize_text_field( $r['type'] ) ) : '';
		$name    = isset( $r['name'] ) ? sanitize_text_field( $r['name'] ) : '@';
		$address = isset( $r['address'] ) ? sanitize_text_field( $r['address'] ) : '';
		$ttl     = isset( $r['ttl'] ) ? max( 60, intval( $r['ttl'] ) ) : 3600;
		if ( ! in_array( $type, $valid_types, true ) || '' === $address ) {
			continue;
		}
		$item = array( 'type' => $type, 'name' => '' !== $name ? $name : '@', 'address' => $address, 'ttl' => $ttl );
		if ( 'MX' === $type && isset( $r['priority'] ) ) {
			$item['priority'] = intval( $r['priority'] );
		}
		$items[] = $item;
	}
	if ( empty( $items ) ) {
		wp_send_json_error( array( 'message' => 'No valid DNS records to add.' ) );
	}

	if ( wookiee_central_api_configured() ) {
		$result = wookiee_central_api_request( 'PUT', '/domains/' . rawurlencode( $domain ) . '/dns-records', array( 'records' => $items ) );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success();
	}

	$response = wp_remote_request(
		'https://spaceship.dev/api/v1/dns/records/' . rawurlencode( $domain ),
		array(
			'method'  => 'PUT',
			'headers' => array(
				'X-Api-Key'    => wookiee_get_setting( 'spaceship_api_key' ),
				'X-Api-Secret' => wookiee_get_setting( 'spaceship_api_secret' ),
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( array( 'force' => false, 'items' => $items ) ),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => $response->get_error_message() ) );
	}
	$code = wp_remote_retrieve_response_code( $response );
	if ( ! in_array( $code, array( 200, 202 ), true ) ) {
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		$detail = is_array( $data ) && isset( $data['detail'] ) ? $data['detail'] : ( 'HTTP ' . intval( $code ) );
		wp_send_json_error( array( 'message' => 'DNS records were rejected: ' . $detail ) );
	}

	wp_send_json_success();
}

/**
 * The Activation tab's whole reason to exist - nothing else in this
 * theme works (AI generation, domain search, product sourcing) until
 * this site has a valid, activated code. Validates against the
 * backend's public activate endpoint before ever saving anything
 * locally - a wrong or already-exhausted code shows an error right
 * here and is never written to wp_options. Key management itself
 * (Companies House, LLM, CJ, Cloudinary/rembg, Google Ads, Spaceship)
 * happens entirely on the backend's own settings page from here on -
 * nothing to migrate or clear on the WordPress side anymore.
 */
function wookiee_render_activation_section() {
	$activated = wookiee_central_api_configured();
	$masked    = $activated ? wookiee_central_api_shared_secret() : '';
	?>
	<div style="background:#fff; border:1px solid <?php echo $activated ? '#00a32a' : '#d63638'; ?>; border-left-width:4px; border-radius:2px; padding:16px 20px; margin:0 0 24px;">
		<?php if ( $activated ) : ?>
			<p style="color:#00622e;">&#10003; This site is activated.</p>
		<?php else : ?>
			<p style="color:#8a2424;"><strong>Not activated yet</strong> - AI generation, Companies House lookup, domain search/registration, and CJ product sourcing are unavailable until an activation code is entered below.</p>
		<?php endif; ?>
		<p>
			<input type="password" id="wookiee-activation-code" class="regular-text wookiee-reveal-input" value="<?php echo esc_attr( $masked ); ?>" placeholder="WOOK-XXXX-XXXX-XXXX-XXXX" autocomplete="off">
			<button type="button" class="button wookiee-reveal-btn" data-target="wookiee-activation-code">Show</button>
			<button type="button" class="button button-primary" id="wookiee-activate-btn"><?php echo $activated ? 'Update code' : 'Activate'; ?></button>
			<span id="wookiee-activate-status" style="margin-left:8px;"></span>
		</p>
		<p class="description">Get this from whoever provides your Wookiee subscription. Checked against the backend immediately - it's only saved here if it's valid.</p>
	</div>

	<script>
	( function() {
		var activateBtn = document.getElementById( 'wookiee-activate-btn' );
		activateBtn.addEventListener( 'click', function() {
			var input  = document.getElementById( 'wookiee-activation-code' );
			var status = document.getElementById( 'wookiee-activate-status' );
			var code   = input.value.trim();
			if ( ! code ) {
				status.textContent = 'Enter a code first.';
				return;
			}
			activateBtn.disabled = true;
			status.textContent = 'Checking…';
			var data = new FormData();
			data.append( 'action', 'wookiee_activate_backend' );
			data.append( 'nonce', <?php echo wp_json_encode( wp_create_nonce( 'wookiee_activate_backend' ) ); ?> );
			data.append( 'code', code );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					activateBtn.disabled = false;
					if ( ! res.success ) {
						status.textContent = res.data && res.data.message ? res.data.message : 'Activation failed.';
						return;
					}
					status.textContent = 'Activated - reloading…';
					window.location.reload();
				} )
				.catch( function() {
					activateBtn.disabled = false;
					status.textContent = 'Activation failed — could not reach the server.';
				} );
		} );
	} )();
	</script>
	<?php
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
