<?php
/**
 * Anyora Commerce theme functions.
 */

defined( 'ABSPATH' ) || exit;

define( 'ANYORA_VERSION', '1.0.0' );
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
			'post_content' => '',
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
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="header-icon cart-icon" aria-label="Cart">
		🛒 
		<span class="cart-count">(<?php echo WC()->cart->get_cart_contents_count(); ?>)</span>
	</a>
	<?php
	$fragments['a.cart-icon'] = ob_get_clean();
	return $fragments;
}
