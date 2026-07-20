<?php
/**
 * Wookiee Decor theme functions.
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOKIEE_VERSION', '1.0.3' );
define( 'WOOKIEE_DIR', trailingslashit( get_template_directory() ) );
define( 'WOOKIEE_URI', trailingslashit( get_template_directory_uri() ) );
define( 'WOOKIEE_CONTACT_EMAIL', 'info@wookied.com' );

require_once WOOKIEE_DIR . 'inc/static-content.php';

/**
 * Safely resolve a product category link, falling back to the shop page
 * when the term doesn't exist yet (get_term_link() returns WP_Error then,
 * which must never be passed straight into esc_url()).
 */
function wookiee_product_cat_url( $slug ) {
	$term_link = get_term_link( $slug, 'product_cat' );
	return ! is_wp_error( $term_link ) ? esc_url( $term_link ) : esc_url( home_url( '/shop/' ) );
}

/**
 * Contact page form handler.
 *
 * Note: the Contact page's HTML is baked into post_content once at page
 * creation (see wookiee_starter_pages()), not rendered live by PHP, so a
 * wp_nonce_field() can't be embedded there — it would go stale immediately.
 * A hidden honeypot field is used instead for basic bot filtering.
 */
add_action( 'admin_post_wookiee_contact_form', 'wookiee_handle_contact_form' );
add_action( 'admin_post_nopriv_wookiee_contact_form', 'wookiee_handle_contact_form' );
function wookiee_handle_contact_form() {
	$honeypot = isset( $_POST['wookiee_website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['wookiee_website'] ) ) ) : '';
	if ( '' !== $honeypot ) {
		wp_safe_redirect( home_url( '/contact/?contact=sent' ) );
		exit;
	}

	$first_name   = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last_name    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
	$message      = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( '' === $first_name || '' === $last_name || '' === $email || '' === $message || ! is_email( $email ) ) {
		wp_safe_redirect( home_url( '/contact/?contact=missing' ) );
		exit;
	}

	$recipient = sanitize_email( WOOKIEE_CONTACT_EMAIL );
	if ( ! is_email( $recipient ) ) {
		wp_safe_redirect( home_url( '/contact/?contact=mail-error' ) );
		exit;
	}

	$subject = sprintf( 'Wookiee website message from %s %s', $first_name, $last_name );
	$body    = "Name: {$first_name} {$last_name}\nEmail: {$email}\n" . ( $order_number ? "Order number: {$order_number}\n" : '' ) . "\nMessage:\n{$message}";
	$headers = array( sprintf( 'Reply-To: %s %s <%s>', $first_name, $last_name, $email ) );
	$sent    = wp_mail( $recipient, $subject, $body, $headers );

	wp_safe_redirect( home_url( $sent ? '/contact/?contact=sent' : '/contact/?contact=mail-error' ) );
	exit;
}

add_action( 'after_setup_theme', 'wookiee_setup' );
function wookiee_setup() {
	load_theme_textdomain( 'wookiee-commerce' );
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
		'primary' => __( 'Primary Menu', 'wookiee-commerce' ),
		'footer'  => __( 'Footer Menu', 'wookiee-commerce' ),
	) );
}

add_action( 'wp_enqueue_scripts', 'wookiee_enqueue_assets' );
function wookiee_enqueue_assets() {
	// Google Fonts: Inter
	wp_enqueue_style( 'wookiee-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'wookiee-main', WOOKIEE_URI . 'assets/css/main.css', array(), WOOKIEE_VERSION );
	wp_enqueue_script( 'wookiee-main', WOOKIEE_URI . 'assets/js/main.js', array( 'jquery' ), WOOKIEE_VERSION, true );
}

add_action( 'after_switch_theme', 'wookiee_create_starter_content' );
add_action( 'init', 'wookiee_maybe_create_starter_content', 20 );

function wookiee_maybe_create_starter_content() {
	if ( wp_installing() ) {
		return;
	}

	$stored_version = get_option( 'wookiee_pages_version', '' );
	$needs_repair    = WOOKIEE_VERSION !== $stored_version;

	// Also repair if a starter page (e.g. Contact) was deleted after the
	// version was already recorded — otherwise it never comes back and
	// the menu never gets it re-added.
	if ( ! $needs_repair ) {
		foreach ( array_keys( wookiee_starter_pages() ) as $slug ) {
			if ( ! get_page_by_path( $slug, OBJECT, 'page' ) ) {
				$needs_repair = true;
				break;
			}
		}
	}

	if ( $needs_repair ) {
		wookiee_create_starter_content();
	}
}

function wookiee_create_starter_content() {
	// 1. Create Starter Pages
	$pages    = wookiee_starter_pages();
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

	// 3. Setup Menu — create it if missing, and keep it in sync if new
	// starter pages were added after the menu already existed (otherwise
	// pages like Contact/Mission/Activities silently never appear).
	$menu_id = 0;
	if ( has_nav_menu( 'primary' ) ) {
		$locations = get_nav_menu_locations();
		$menu_id   = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	}
	if ( ! $menu_id ) {
		$created = wp_create_nav_menu( 'Wookiee Main Menu' );
		$menu_id = is_wp_error( $created ) ? 0 : (int) $created;
	}

	if ( $menu_id ) {
		$existing_items     = wp_get_nav_menu_items( $menu_id );
		$existing_object_ids = $existing_items ? wp_list_pluck( $existing_items, 'object_id' ) : array();
		$existing_object_ids = array_map( 'intval', $existing_object_ids );

		foreach ( array( 'home', 'shop', 'about', 'mission', 'activities', 'contact' ) as $slug ) {
			if ( empty( $page_ids[ $slug ] ) ) {
				continue;
			}
			if ( in_array( (int) $page_ids[ $slug ], $existing_object_ids, true ) ) {
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

		$locations             = (array) get_theme_mod( 'nav_menu_locations', array() );
		$locations['primary'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

    // 4. Create Dummy Products
    wookiee_create_dummy_products();

    // 5. Set Homepage Hero Image
    if ( function_exists( 'wookiee_sideload_theme_image' ) ) {
        $hero_attach_id = wookiee_sideload_theme_image( 'hero-banner.png', 'Wookiee Hero Banner' );
        if ( $hero_attach_id ) {
            update_option( 'wookiee_hero_image_id', $hero_attach_id );
        }
    }

	update_option( 'wookiee_pages_version', WOOKIEE_VERSION );
	flush_rewrite_rules( false );
}

/**
 * Ensure cart contents update when products are added to the cart via AJAX
 */
if ( class_exists( 'WooCommerce' ) ) {
	add_filter( 'woocommerce_add_to_cart_fragments', 'wookiee_woocommerce_header_add_to_cart_fragment' );
}
function wookiee_woocommerce_header_add_to_cart_fragment( $fragments ) {
	ob_start();
	?>
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="header-icon-btn cart-icon-btn" aria-label="Cart" style="color: var(--wookiee-navy); display: flex; align-items: center; position: relative; transition: opacity 0.2s;">
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
require_once WOOKIEE_DIR . 'inc/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'wookiee_register_required_plugins' );
function wookiee_register_required_plugins() {
	$plugins = array(
		array(
			'name'      => 'WooCommerce',
			'slug'      => 'woocommerce',
			'required'  => true,
		),
	);

	$config = array(
		'id'           => 'wookiee-commerce',
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

// 1. Add Dynamic Rating on Single Product page
if ( class_exists( 'WooCommerce' ) ) {
    add_action( 'woocommerce_single_product_summary', 'wookiee_single_product_rating_mockup', 9 );
}
function wookiee_single_product_rating_mockup() {
    global $product;
    if ( ! is_a( $product, 'WC_Product' ) ) {
        return;
    }
    
    $rating_count = $product->get_rating_count();
    $review_count = $product->get_review_count();
    $average      = $product->get_average_rating();
    
    if ( $rating_count > 0 ) {
        $stars = '';
        $int_rating = floor($average);
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $int_rating) {
                $stars .= '★';
            } else {
                $stars .= '☆';
            }
        }
        echo '<div class="wookiee-dynamic-rating" style="display: flex; align-items: center; gap: 5px; margin-bottom: 15px; font-size: 14px; color: #dcb37b; box-sizing: border-box;">';
        echo esc_html( $stars );
        echo '<span style="color: #666; font-size: 13px; margin-left: 5px;">' . esc_html( number_format( $average, 1 ) ) . ' (' . esc_html( $review_count ) . ' ' . esc_html( _n( 'review', 'reviews', $review_count, 'wookiee-commerce' ) ) . ')</span>';
        echo '</div>';
    } else {
        echo '<div class="wookiee-dynamic-rating" style="display: flex; align-items: center; gap: 5px; margin-bottom: 15px; font-size: 14px; color: #ccc; box-sizing: border-box;">';
        echo '☆☆☆☆☆';
        echo '<span style="color: #888; font-size: 13px; margin-left: 5px;">No reviews yet</span>';
        echo '</div>';
    }
}

// 2. Add Stock & Shipping Message below price
if ( class_exists( 'WooCommerce' ) ) {
    add_action( 'woocommerce_single_product_summary', 'wookiee_single_product_stock_message', 16 );
}
function wookiee_single_product_stock_message() {
    echo '<div style="display: flex; align-items: center; gap: 8px; margin: 15px 0 25px 0; font-size: 14px; color: #2e7d32; font-weight: 600; box-sizing: border-box;">';
    echo '<span style="width: 8px; height: 8px; background-color: #2e7d32; border-radius: 50%; display: inline-block;"></span>';
    echo 'In stock — Dispatch within 24 hours from Cowdenbeath';
    echo '</div>';
}

// 3. Add Trust Badges below Add to Cart
if ( class_exists( 'WooCommerce' ) ) {
    add_action( 'woocommerce_single_product_summary', 'wookiee_single_product_trust_badges', 35 );
}
function wookiee_single_product_trust_badges() {
    echo '
    <div style="margin-top: 30px; padding-top: 25px; border-top: 1px solid var(--wookiee-border); display: flex; flex-direction: column; gap: 15px; box-sizing: border-box;">
        <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--wookiee-navy); font-weight: 600;">
            <span style="font-size: 16px;">🚚</span> Free UK delivery on orders over £50
        </div>
        <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--wookiee-navy); font-weight: 600;">
            <span style="font-size: 16px;">🔄</span> 30-day hassle-free returns policy
        </div>
        <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--wookiee-navy); font-weight: 600;">
            <span style="font-size: 16px;">🔒</span> Secure checkout via Stripe & PayPal
        </div>
    </div>
    ';
}

// Customize single product tabs (horizontal section below the product)
if ( class_exists( 'WooCommerce' ) ) {
    add_filter( 'woocommerce_product_tabs', 'wookiee_custom_product_tabs' );
}
function wookiee_custom_product_tabs( $tabs ) {
    // Rename Description to Product Details
    if ( isset( $tabs['description'] ) ) {
        $tabs['description']['title'] = __( 'Product Details', 'wookiee-commerce' );
        $tabs['description']['priority'] = 10;
    }
    
    // Add custom Shipping & Returns tab
    $tabs['shipping_returns'] = array(
        'title'    => __( 'Shipping & Returns', 'wookiee-commerce' ),
        'priority' => 20,
        'callback' => 'wookiee_shipping_returns_tab_content'
    );
    
    // Ensure Reviews tab is active and has priority 30
    if ( isset( $tabs['reviews'] ) ) {
        $tabs['reviews']['priority'] = 30;
    }
    
    // Remove default additional information tab if present
    unset( $tabs['additional_information'] );
    
    return $tabs;
}

// Shipping & Returns Tab Callback
function wookiee_shipping_returns_tab_content() {
    ?>
    <div class="wookiee-tab-content-wrapper" style="max-width: 800px; line-height: 1.7; color: #555;">
        <p style="margin: 0 0 15px 0;"><strong>Fulfillment & Delivery:</strong> All orders are stored, packed, and dispatched directly from our dedicated facility in Cowdenbeath, United Kingdom. We offer free standard UK delivery on all orders over £50. Standard delivery normally takes 3-5 working days.</p>
        <p style="margin: 0;"><strong>Hassle-Free Returns:</strong> We offer a 30-day return policy. If you are not completely satisfied with your storage products, please email info@wookied.com to request a returns authorization and prepaid shipping label.</p>
    </div>
    <?php
}

// AJAX Add to Cart handler for Single Product Page
if ( class_exists( 'WooCommerce' ) ) {
    add_action( 'wp_ajax_woocommerce_ajax_add_to_cart', 'wookiee_ajax_add_to_cart_handler' );
    add_action( 'wp_ajax_nopriv_woocommerce_ajax_add_to_cart', 'wookiee_ajax_add_to_cart_handler' );
}
function wookiee_ajax_add_to_cart_handler() {
    $product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_POST['product_id'] ) );
    $quantity = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( $_POST['quantity'] );
    $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
    $product_status = get_post_status( $product_id );
    
    if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity ) && 'publish' === $product_status ) {
        do_action( 'woocommerce_ajax_added_to_cart', $product_id );
        if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
            wc_add_to_cart_message( array( $product_id => $quantity ), true );
        }
        WC_AJAX::get_refreshed_fragments();
    } else {
        wp_send_json_error( array(
            'error' => true,
            'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id )
        ) );
    }
    wp_die();
}

// Enqueue Custom Checkout Funnel CSS
add_action( 'wp_enqueue_scripts', 'wookiee_enqueue_checkout_funnel_css', 15 );
function wookiee_enqueue_checkout_funnel_css() {
    if ( is_page( 'checkout' ) || is_page_template( 'page-checkout.php' ) ||
         is_page( 'cart' )     || is_page_template( 'page-cart.php' ) ) {
        wp_enqueue_style( 'wookiee-checkout-funnel', WOOKIEE_URI . 'assets/css/checkout-funnel.css', array(), WOOKIEE_VERSION );
    }
}

// Custom WordPress Login Page styling
add_action( 'login_enqueue_scripts', 'wookiee_custom_login_styles' );
function wookiee_custom_login_styles() {
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $logo_url = '';
    if ( $custom_logo_id ) {
        $logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
    }
    ?>
    <style type="text/css">
        body.login {
            background-color: #f4f5f0 !important;
            font-family: 'Inter', sans-serif !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        #login {
            background: #ffffff !important;
            padding: 40px !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03) !important;
            border: 1px solid rgba(0,0,0,0.04) !important;
            width: 320px !important;
        }
        body.login h1 a {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 160 38' width='150' height='34' fill='none'> <path d='M8 8 L12 30 L17.5 17 L23 30 L27 8' stroke='%23081d34' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/> <rect x='10' y='11' width='4.5' height='4' rx='1' fill='%236fbdbd'/> <rect x='20.5' y='11' width='4.5' height='4' rx='1' fill='%236fbdbd'/> <line x1='11' y1='13' x2='13.5' y2='13' stroke='%23081d34' stroke-width='1.2' stroke-linecap='round'/> <line x1='21.5' y1='13' x2='24' y2='13' stroke='%23081d34' stroke-width='1.2' stroke-linecap='round'/> <text x='36' y='28' font-family=''Outfit', 'Inter', system-ui, sans-serif' font-weight='800' font-size='22' fill='%23081d34' letter-spacing='-0.5px'>Wookiee</text> </svg>") !important;
            background-size: contain !important;
            background-position: center !important;
            width: 200px !important;
            height: 60px !important;
            display: block !important;
        }
        .login form {
            background: none !important;
            border: none !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        .login label {
            font-weight: 600 !important;
            color: #081d34 !important;
        }
        .login input[type="text"], .login input[type="password"] {
            border-radius: 12px !important;
            border: 1px solid #ddd !important;
            padding: 12px !important;
            font-size: 16px !important;
            background: #fdfdfd !important;
            box-shadow: none !important;
            margin-top: 5px !important;
        }
        .login input[type="text"]:focus, .login input[type="password"]:focus {
            border-color: #081d34 !important;
            box-shadow: 0 0 0 3px rgba(8, 29, 52, 0.1) !important;
            outline: none !important;
        }
        .login input[type="submit"] {
            background-color: #081d34 !important;
            color: #fff !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            padding: 12px !important;
            font-size: 16px !important;
            border: none !important;
            box-shadow: 0 5px 15px rgba(8, 29, 52, 0.15) !important;
            width: 100% !important;
            text-shadow: none !important;
            height: auto !important;
        }
        .login input[type="submit"]:hover {
            background-color: #6fbdbd !important;
            color: #081d34 !important;
        }
        .login #nav a, .login #backtoblog a {
            color: #081d34 !important;
            font-weight: 600 !important;
        }
    </style>
    <?php
}

add_filter( 'login_headerurl', 'wookiee_login_logo_url' );
function wookiee_login_logo_url() {
    return home_url();
}

add_filter( 'login_headertext', 'wookiee_login_logo_url_title' );
function wookiee_login_logo_url_title() {
    return get_bloginfo( 'name' );
}

