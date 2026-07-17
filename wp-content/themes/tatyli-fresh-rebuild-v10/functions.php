<?php
/**
 * Tatyli Fresh Rebuild theme functions.
 */

defined( 'ABSPATH' ) || exit;

define( 'TATYLI_FRESH_VERSION', '1.8.0' );
define( 'TATYLI_FRESH_CONTACT_EMAIL', 'info@tatyli.be' );
define( 'TATYLI_FRESH_DIR', trailingslashit( get_template_directory() ) );
define( 'TATYLI_FRESH_URI', trailingslashit( get_template_directory_uri() ) );

require_once TATYLI_FRESH_DIR . 'inc/static-content.php';

add_action( 'after_setup_theme', 'tatyli_fresh_setup' );
function tatyli_fresh_setup() {
	load_theme_textdomain( 'tatyli-fresh', TATYLI_FRESH_DIR . 'languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 90,
		'width'       => 260,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'tatyli-fresh' ),
		'footer'  => __( 'Footer Menu', 'tatyli-fresh' ),
	) );
}


add_action( 'wp_head', 'tatyli_fresh_favicon_fallback', 1 );
function tatyli_fresh_favicon_fallback() {
	if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
		return;
	}

	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( TATYLI_FRESH_URI . 'assets/media/tatyli-symbol.svg' ) );
}

add_action( 'wp_enqueue_scripts', 'tatyli_fresh_enqueue_assets' );
function tatyli_fresh_enqueue_assets() {
	wp_enqueue_style( 'tatyli-fresh-main', TATYLI_FRESH_URI . 'assets/css/main.css', array(), TATYLI_FRESH_VERSION );
	wp_enqueue_script( 'tatyli-fresh-main', TATYLI_FRESH_URI . 'assets/js/main.js', array(), TATYLI_FRESH_VERSION, true );
}

add_action( 'after_switch_theme', 'tatyli_fresh_create_starter_pages' );
add_action( 'init', 'tatyli_fresh_maybe_create_starter_pages', 20 );

/**
 * Repair starter pages after theme updates too.
 * This helps when a ZIP is uploaded over an active theme and after_switch_theme does not run.
 */
function tatyli_fresh_maybe_create_starter_pages() {
	if ( wp_installing() ) {
		return;
	}

	$stored_version = get_option( 'tatyli_fresh_pages_version', '' );
	$needs_repair   = TATYLI_FRESH_VERSION !== $stored_version;

	if ( ! $needs_repair ) {
		foreach ( array_keys( tatyli_fresh_starter_pages() ) as $slug ) {
			if ( ! get_page_by_path( $slug, OBJECT, 'page' ) ) {
				$needs_repair = true;
				break;
			}
		}
	}

	if ( $needs_repair ) {
		tatyli_fresh_create_starter_pages();
	}
}

function tatyli_fresh_create_starter_pages() {
	$pages    = tatyli_fresh_starter_pages();
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

	if ( ! empty( $page_ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $page_ids['home'] );
	}

	if ( ! empty( $page_ids['privacy-policy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', (int) $page_ids['privacy-policy'] );
	}

	if ( ! has_nav_menu( 'primary' ) ) {
		$menu_id = wp_create_nav_menu( 'Tatyli Main Menu' );
		if ( ! is_wp_error( $menu_id ) ) {
			foreach ( array( 'home', 'about', 'mission', 'activities', 'contact' ) as $slug ) {
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

	update_option( 'tatyli_fresh_pages_version', TATYLI_FRESH_VERSION );
	flush_rewrite_rules( false );
}

function tatyli_fresh_asset( $relative_path ) {
	return esc_url( TATYLI_FRESH_URI . ltrim( $relative_path, '/' ) );
}

function tatyli_fresh_current_slug() {
	if ( is_front_page() ) {
		return 'home';
	}

	$post = get_post();
	if ( $post instanceof WP_Post ) {
		return $post->post_name;
	}

	return '';
}

function tatyli_fresh_is_static_page( $slug = '' ) {
	$slug = $slug ? $slug : tatyli_fresh_current_slug();
	return in_array( $slug, array_keys( tatyli_fresh_starter_pages() ), true );
}

function tatyli_fresh_nav_fallback() {
	$items = array(
		'/'             => __( 'Home', 'tatyli-fresh' ),
		'/about/'      => __( 'About', 'tatyli-fresh' ),
		'/mission/'    => __( 'Mission', 'tatyli-fresh' ),
		'/activities/' => __( 'Activities', 'tatyli-fresh' ),
		'/contact/'    => __( 'Contact', 'tatyli-fresh' ),
	);
	echo '<ul class="tat-fallback-menu">';
	foreach ( $items as $path => $label ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( home_url( $path ) ), esc_html( $label ) );
	}
	echo '</ul>';
}

function tatyli_fresh_logo_markup() {
	?>
	<span class="tat-logo-wrap" aria-hidden="true">
		<span class="tat-logo-mark">
			<svg viewBox="0 0 96 96" focusable="false" role="img">
				<circle class="tat-logo-ring" cx="48" cy="48" r="39" />
				<path class="tat-logo-star" d="M48 14c9 15 21 24 36 28-17 6-28 17-34 39-8-18-21-28-39-31 18-7 29-19 37-36z" />
				<circle class="tat-logo-dot" cx="48" cy="48" r="9" />
				<path class="tat-logo-smile" d="M26 70c13-11 31-12 45-1" />
			</svg>
		</span>
		<span class="tat-logo-word">
			<span>Tatyli</span>
			<small>Cultural Association</small>
		</span>
	</span>
	<span class="screen-reader-text"><?php esc_html_e( 'Tatyli', 'tatyli-fresh' ); ?></span>
	<?php
}

add_action( 'admin_post_tatyli_contact', 'tatyli_fresh_handle_contact' );
add_action( 'admin_post_nopriv_tatyli_contact', 'tatyli_fresh_handle_contact' );
function tatyli_fresh_handle_contact() {
	if ( ! isset( $_POST['tatyli_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tatyli_contact_nonce'] ) ), 'tatyli_contact' ) ) {
		wp_safe_redirect( home_url( '/contact/?contact=invalid' ) );
		exit;
	}

	$name    = isset( $_POST['tatyli_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tatyli_name'] ) ) : '';
	$email   = isset( $_POST['tatyli_email'] ) ? sanitize_email( wp_unslash( $_POST['tatyli_email'] ) ) : '';
	$message = isset( $_POST['tatyli_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tatyli_message'] ) ) : '';

	if ( '' === $name || '' === $email || '' === $message || ! is_email( $email ) ) {
		wp_safe_redirect( home_url( '/contact/?contact=missing' ) );
		exit;
	}

	$honeypot = isset( $_POST['tatyli_website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['tatyli_website'] ) ) ) : '';
	if ( '' !== $honeypot ) {
		wp_safe_redirect( home_url( '/contact/?contact=sent' ) );
		exit;
	}

	$recipient = sanitize_email( TATYLI_FRESH_CONTACT_EMAIL );
	if ( ! is_email( $recipient ) ) {
		wp_safe_redirect( home_url( '/contact/?contact=mail-error' ) );
		exit;
	}

	$subject = sprintf( 'Tatyli website message from %s', $name );
	$body    = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";
	$headers = array( sprintf( 'Reply-To: %s <%s>', $name, $email ) );
	$sent    = wp_mail( $recipient, $subject, $body, $headers );

	wp_safe_redirect( home_url( $sent ? '/contact/?contact=sent' : '/contact/?contact=mail-error' ) );
	exit;
}
