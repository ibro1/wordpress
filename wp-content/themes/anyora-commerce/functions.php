<?php
/**
 * Anyora Commerce theme functions.
 */

defined( 'ABSPATH' ) || exit;

define( 'ANYORA_VERSION', '1.0.2' );
define( 'ANYORA_DIR', trailingslashit( get_template_directory() ) );
define( 'ANYORA_URI', trailingslashit( get_template_directory_uri() ) );

require_once ANYORA_DIR . 'inc/static-content.php';

add_action( 'after_setup_theme', 'anyora_setup' );
function anyora_setup() {
	load_theme_textdomain( 'anyora-commerce' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'custom-logo', array(
		'height'      => 90,
		'width'       => 260,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'anyora-commerce' ),
		'footer'  => __( 'Footer Menu', 'anyora-commerce' ),
	) );
}

add_action( 'wp_enqueue_scripts', 'anyora_enqueue_assets' );
function anyora_enqueue_assets() {
	// Google Fonts: Inter
	wp_enqueue_style( 'anyora-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'anyora-main', ANYORA_URI . 'assets/css/main.css', array(), ANYORA_VERSION );
	wp_enqueue_script( 'anyora-main', ANYORA_URI . 'assets/js/main.js', array(), ANYORA_VERSION, true );
}

add_action( 'after_switch_theme', 'anyora_create_starter_content' );
add_action( 'init', 'anyora_maybe_create_starter_content', 20 );

function anyora_maybe_create_starter_content() {
	if ( wp_installing() ) {
		return;
	}
	$stored_version = get_option( 'anyora_pages_version', '' );
	if ( ANYORA_VERSION !== $stored_version ) {
		anyora_create_starter_content();
	}
}

function anyora_create_starter_content() {
	// 1. Create Starter Pages
	$pages    = anyora_starter_pages();
	$page_ids = array();

	foreach ( $pages as $slug => $page ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing instanceof WP_Post ) {
			$page_ids[ $slug ] = (int) $existing->ID;
			continue;
		}
		$inserted = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $page['title'],
			'post_name'    => $slug,
			'post_content' => isset($page['content']) ? $page['content'] : '',
		), true );

		if ( ! is_wp_error( $inserted ) ) {
			$page_ids[ $slug ] = (int) $inserted;
		}
	}

	// 2. Set Homepage
	if ( ! empty( $page_ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $page_ids['home'] );
	}
    // Set Shop Page if WooCommerce is active
    if ( class_exists( 'WooCommerce' ) && ! empty( $page_ids['shop'] ) ) {
        update_option( 'woocommerce_shop_page_id', (int) $page_ids['shop'] );
    }

	// 3. Setup Menu
	if ( ! has_nav_menu( 'primary' ) ) {
		$menu_id = wp_create_nav_menu( 'Anyora Main Menu' );
		if ( ! is_wp_error( $menu_id ) ) {
			foreach ( array( 'home', 'shop', 'about', 'contact' ) as $slug ) {
				if ( empty( $page_ids[ $slug ] ) ) {
					continue;
				}
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => $pages[ $slug ]['menu'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => (int) $page_ids[ $slug ],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				) );
			}
			$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
			$locations['primary'] = (int) $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

    // 4. Create Dummy Products
    anyora_create_dummy_products();

	update_option( 'anyora_pages_version', ANYORA_VERSION );
	flush_rewrite_rules( false );
}

/**
 * Ensure cart contents update when products are added to the cart via AJAX
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'anyora_woocommerce_header_add_to_cart_fragment' );
function anyora_woocommerce_header_add_to_cart_fragment( $fragments ) {
	ob_start();
	?>
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="header-icon-btn cart-icon-btn" aria-label="Cart" style="color: var(--anyora-navy); display: flex; align-items: center; position: relative; transition: opacity 0.2s;">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
		<span class="cart-badge"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
	</a>
	<?php
	$fragments['a.cart-icon-btn'] = ob_get_clean();
	return $fragments;
}

/**
 * Include the TGM Plugin Activation class.
 */
require_once ANYORA_DIR . 'inc/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'anyora_register_required_plugins' );
function anyora_register_required_plugins() {
	$plugins = array(
		array(
			'name'      => 'WooCommerce',
			'slug'      => 'woocommerce',
			'required'  => true,
		),
	);

	$config = array(
		'id'           => 'anyora-commerce',
		'default_path' => '',
		'menu'         => 'tgmpa-install-plugins',
		'has_notices'  => true,
		'dismissable'  => true,
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '',
	);

	tgmpa( $plugins, $config );
}

// Remove trailing slashes from all URLs
add_filter('user_trailingslashit', 'untrailingslashit');

// 1. Add Rating Mockup on Single Product page
add_action( 'woocommerce_single_product_summary', 'anyora_single_product_rating_mockup', 9 );
function anyora_single_product_rating_mockup() {
    echo '<div style="display: flex; align-items: center; gap: 5px; margin-bottom: 15px; font-size: 14px; color: #dcb37b; box-sizing: border-box;">';
    echo '★★★★★';
    echo '<span style="color: #666; font-size: 13px; margin-left: 5px;">4.9 (18 reviews)</span>';
    echo '</div>';
}

// 2. Add Stock & Shipping Message below price
add_action( 'woocommerce_single_product_summary', 'anyora_single_product_stock_message', 16 );
function anyora_single_product_stock_message() {
    echo '<div style="display: flex; align-items: center; gap: 8px; margin: 15px 0 25px 0; font-size: 14px; color: #2e7d32; font-weight: 600; box-sizing: border-box;">';
    echo '<span style="width: 8px; height: 8px; background-color: #2e7d32; border-radius: 50%; display: inline-block;"></span>';
    echo 'In stock — Dispatch within 24 hours from Bilston';
    echo '</div>';
}

// 3. Add Trust Badges below Add to Cart
add_action( 'woocommerce_single_product_summary', 'anyora_single_product_trust_badges', 35 );
function anyora_single_product_trust_badges() {
    echo '
    <div style="margin-top: 30px; padding-top: 25px; border-top: 1px solid var(--anyora-border); display: flex; flex-direction: column; gap: 15px; box-sizing: border-box;">
        <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--anyora-navy); font-weight: 600;">
            <span style="font-size: 16px;">🚚</span> Free UK delivery on orders over £50
        </div>
        <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--anyora-navy); font-weight: 600;">
            <span style="font-size: 16px;">🔄</span> 30-day hassle-free returns policy
        </div>
        <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--anyora-navy); font-weight: 600;">
            <span style="font-size: 16px;">🔒</span> Secure checkout via Stripe & PayPal
        </div>
    </div>
    ';
}

// Remove standard tabs
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );

// Add modern accordion tabs in the summary column
add_action( 'woocommerce_single_product_summary', 'anyora_single_product_accordions', 38 );
function anyora_single_product_accordions() {
    global $product;
    $desc = get_the_content();
    if ( empty( $desc ) ) {
        $desc = "Organize your space with this practical and high-quality " . esc_html($product->get_name()) . ". Crafted from premium, durable materials and designed to fit seamlessly into any modern home layout.";
    }
    ?>
    <div style="margin-top: 30px; display: flex; flex-direction: column; border-top: 1px solid var(--anyora-border); box-sizing: border-box;">
        <!-- Details Accordion -->
        <details style="padding: 15px 0; border-bottom: 1px solid var(--anyora-border); outline: none; cursor: pointer;">
            <summary style="font-weight: 700; color: var(--anyora-navy); font-size: 15px; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                Product details
                <span class="accordion-icon" style="font-weight: 300; font-size: 18px;">+</span>
            </summary>
            <div style="padding-top: 12px; font-size: 14px; color: #555; line-height: 1.6; cursor: default;">
                <?php echo wp_kses_post( wpautop($desc) ); ?>
            </div>
        </details>
        
        <!-- Shipping & Returns Accordion -->
        <details style="padding: 15px 0; border-bottom: 1px solid var(--anyora-border); outline: none; cursor: pointer;">
            <summary style="font-weight: 700; color: var(--anyora-navy); font-size: 15px; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                Shipping & returns
                <span class="accordion-icon" style="font-weight: 300; font-size: 18px;">+</span>
            </summary>
            <div style="padding-top: 12px; font-size: 14px; color: #555; line-height: 1.6; cursor: default;">
                <p style="margin: 0 0 10px 0;"><strong>Delivery:</strong> Free UK standard delivery on orders over £50. Orders under £50 incur a shipping charge of £3.99. Dispatched from Bilston facility within 24 hours.</p>
                <p style="margin: 0;"><strong>Returns:</strong> 30-day hassle-free returns. Simply contact support@anyora.uk to request a return shipping label.</p>
            </div>
        </details>
    </div>
    <?php
}
