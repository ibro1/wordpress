<?php
/**
 * Dave Bukar Technologies theme functions.
 */

defined( 'ABSPATH' ) || exit;

define( 'DBT_VERSION', '1.3.1' );
define( 'DBT_DIR', trailingslashit( get_template_directory() ) );
define( 'DBT_URI', trailingslashit( get_template_directory_uri() ) );
define( 'DBT_CONTACT_EMAIL', 'hello@davebukartechnologies.com' );

require_once DBT_DIR . 'inc/site-content.php';
require_once DBT_DIR . 'inc/lead-capture.php';

add_action( 'after_setup_theme', 'dbt_setup' );
function dbt_setup() {
	load_theme_textdomain( 'davebukar-cobalt' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 220,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'davebukar-cobalt' ),
		'footer'  => __( 'Footer Menu', 'davebukar-cobalt' ),
	) );
}

add_action( 'wp_enqueue_scripts', 'dbt_enqueue_assets' );
function dbt_enqueue_assets() {
	wp_enqueue_style( 'dbt-fonts', 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap', array(), null );
	wp_enqueue_style( 'dbt-tokens', DBT_URI . 'assets/css/tokens.css', array(), DBT_VERSION );
	wp_enqueue_style( 'dbt-main', DBT_URI . 'assets/css/main.css', array( 'dbt-tokens' ), DBT_VERSION );
	wp_enqueue_script( 'dbt-main', DBT_URI . 'assets/js/main.js', array(), DBT_VERSION, true );
	wp_localize_script( 'dbt-main', 'dbtBookCall', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'dbt_book_call' ),
	) );
}

/**
 * Skins the Ultimate Multisite [wu_checkout] form to match the site design.
 * Hooked late and gated on wu-checkout actually being enqueued, so this
 * never loads (or forces the plugin's own checkout.css) on pages that
 * don't have a checkout form on them.
 */
add_action( 'wp_enqueue_scripts', 'dbt_enqueue_checkout_assets', 20 );
function dbt_enqueue_checkout_assets() {
	if ( ! wp_style_is( 'wu-checkout', 'enqueued' ) ) {
		return;
	}

	wp_enqueue_style( 'dbt-checkout', DBT_URI . 'assets/css/checkout.css', array( 'dbt-tokens', 'wu-checkout' ), DBT_VERSION );
}

/**
 * Protects the checkout block's rendered output from wptexturize().
 *
 * the_content runs do_blocks() (priority 9) before wptexturize (priority 10),
 * so by the time wptexturize sees the page it's looking at this block's
 * fully-expanded HTML - including every Vue directive and numeric HTML
 * attribute inside it. wptexturize's "double prime" rule (formatting.php,
 * wptexturize_primes()) converts any digit immediately followed by a
 * straight " into &#8243; (a Unicode double-prime), assuming feet/inches
 * notation like 6'2" - with no awareness that the " it just ate was closing
 * an HTML attribute or a Vue v-if expression. That silently corrupts the
 * markup from that point in the document onward (the browser's parser
 * treats everything up to the next literal " as part of one broken
 * attribute value), which is exactly what caused the whole form to vanish
 * and Vue to throw "line_item is not defined" - the real closing quote of
 * a v-if got eaten and replaced with that entity.
 *
 * wptexturize already supports skipping specific tag names via the
 * no_texturize_tags filter (defaults to pre/code/kbd/style/script/tt) - so
 * this wraps the checkout block's own output in a fake, purely structural
 * tag name added to that list. display:contents in checkout.css makes the
 * wrapper invisible to layout - it isn't a real element, just a boundary
 * marker wptexturize's tag-stack tracking already knows how to skip.
 */
add_filter( 'no_texturize_tags', 'dbt_no_texturize_checkout_tag' );
function dbt_no_texturize_checkout_tag( $tags ) {
	$tags[] = 'dbt-no-texturize';
	return $tags;
}

add_filter( 'render_block_wp-ultimo/checkout', 'dbt_wrap_checkout_block_no_texturize', 10, 1 );
function dbt_wrap_checkout_block_no_texturize( $block_content ) {
	if ( '' === trim( (string) $block_content ) ) {
		return $block_content;
	}
	return '<dbt-no-texturize>' . $block_content . '</dbt-no-texturize>';
}

/**
 * Auto-provision the four service pages and two legal pages on theme
 * activation, so activating the theme is enough to get a working site
 * with real routable URLs - no manual page creation required. front-page.php
 * renders the homepage on its own regardless of the Reading setting, so
 * "home" never needs a Page post.
 */
add_action( 'after_switch_theme', 'dbt_create_starter_content' );
add_action( 'init', 'dbt_maybe_create_starter_content', 20 );

function dbt_maybe_create_starter_content() {
	if ( wp_installing() ) {
		return;
	}

	$needs_repair = get_option( 'dbt_pages_version', '' ) !== DBT_VERSION;

	if ( ! $needs_repair ) {
		foreach ( array_keys( dbt_starter_pages() ) as $slug ) {
			if ( ! get_page_by_path( $slug, OBJECT, 'page' ) ) {
				$needs_repair = true;
				break;
			}
		}
	}

	if ( $needs_repair ) {
		dbt_create_starter_content();
	}
}

/**
 * Combines the service pages (template-service.php) and legal pages
 * (template-legal.php) into one starter-page map keyed by slug.
 */
function dbt_starter_pages() {
	$pages = array();

	foreach ( dbt_services() as $slug => $service ) {
		$pages[ $slug ] = array(
			'title'    => $service['title'],
			'menu'     => $service['nav_label'],
			'template' => 'template-service.php',
			'content'  => '',
		);
	}

	foreach ( dbt_legal_pages() as $slug => $legal ) {
		$pages[ $slug ] = array(
			'title'    => $legal['title'],
			'menu'     => $legal['title'],
			'template' => 'template-legal.php',
			'content'  => $legal['content'],
		);
	}

	return $pages;
}

function dbt_create_starter_content() {
	$pages    = dbt_starter_pages();
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
			'post_content' => $page['content'],
			'page_template' => $page['template'],
		), true );

		if ( ! is_wp_error( $inserted ) ) {
			$page_ids[ $slug ] = (int) $inserted;
		}
	}

	dbt_sync_primary_menu( $page_ids, $pages );

	update_option( 'dbt_pages_version', DBT_VERSION );
	flush_rewrite_rules( false );
}

function dbt_sync_primary_menu( $page_ids = null, $pages = null ) {
	if ( wp_installing() ) {
		return;
	}

	if ( null === $pages ) {
		$pages = dbt_starter_pages();
	}
	if ( null === $page_ids ) {
		$page_ids = array();
		foreach ( array_keys( $pages ) as $slug ) {
			$existing = get_page_by_path( $slug, OBJECT, 'page' );
			if ( $existing instanceof WP_Post ) {
				$page_ids[ $slug ] = (int) $existing->ID;
			}
		}
	}

	$menu_id = 0;
	if ( has_nav_menu( 'primary' ) ) {
		$locations = get_nav_menu_locations();
		$menu_id   = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	}
	if ( ! $menu_id ) {
		$created = wp_create_nav_menu( 'DBT Main Menu' );
		$menu_id = is_wp_error( $created ) ? 0 : (int) $created;
	}
	if ( ! $menu_id ) {
		return;
	}

	$existing_items      = wp_get_nav_menu_items( $menu_id );
	$existing_object_ids = $existing_items ? array_map( 'intval', wp_list_pluck( $existing_items, 'object_id' ) ) : array();

	foreach ( array_keys( $pages ) as $slug ) {
		if ( empty( $page_ids[ $slug ] ) || in_array( (int) $page_ids[ $slug ], $existing_object_ids, true ) ) {
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

	$locations            = (array) get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Command-palette destinations for the N13 nav - every real route in
 * the theme, so the ⌘K palette actually finds pages rather than faking
 * a docs search index on a marketing site.
 */
function dbt_cmdk_destinations() {
	$destinations = array(
		array( 'label' => 'Home', 'url' => home_url( '/' ), 'group' => 'Pages' ),
	);

	foreach ( dbt_services() as $slug => $service ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		$destinations[] = array(
			'label' => $service['title'],
			'url'   => $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' ),
			'group' => 'Services',
		);
	}

	foreach ( dbt_legal_pages() as $slug => $legal ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		$destinations[] = array(
			'label' => $legal['title'],
			'url'   => $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' ),
			'group' => 'Legal',
		);
	}

	return $destinations;
}
