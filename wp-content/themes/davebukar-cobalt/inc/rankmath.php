<?php
/**
 * Rank Math (free features only): when it is active the theme's own meta, canonical,
 * Open Graph and JSON-LD step aside so nothing prints twice, and the theme's facts
 * are fed to Rank Math instead. Without Rank Math the theme output in seo.php is the fallback.
 */

defined( 'ABSPATH' ) || exit;

function dbt_rank_math_active() {
	return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
}

/** Remove the theme's duplicates once Rank Math is loaded. */
add_action( 'wp', 'dbt_rank_math_step_aside' );
function dbt_rank_math_step_aside() {
	if ( ! dbt_rank_math_active() ) {
		return;
	}
	remove_action( 'wp_head', 'dbt_head_meta', 3 );
	remove_action( 'wp_head', 'dbt_jsonld', 4 );
	remove_filter( 'pre_get_document_title', 'dbt_custom_post_title', 20 );
}

/** Rank Math serves the only sitemap; the core one stays off. */
add_filter( 'wp_sitemaps_enabled', function ( $enabled ) {
	return dbt_rank_math_active() ? false : $enabled;
} );

/** Keep noindex pages out of Rank Math's sitemap even when its cached copy or exclude list lags. */
add_filter( 'rank_math/sitemap/enable_caching', '__return_false' );
add_filter( 'rank_math/sitemap/entry', function ( $url, $type, $object ) {
	if ( 'post' === $type && is_object( $object ) && isset( $object->post_name ) && in_array( $object->post_name, dbt_noindex_slugs(), true ) ) {
		return false;
	}
	return $url;
}, 10, 3 );

/** Pages the theme keeps out of search stay out when Rank Math prints the robots tag. */
add_filter( 'rank_math/frontend/robots', function ( $robots ) {
	if ( dbt_is_noindex_page() ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
} );

/** The intake page keeps its own title under Rank Math. */
add_filter( 'rank_math/frontend/title', function ( $title ) {
	return is_page( 'register' ) ? 'Start a project - ' . DBT_ORG_NAME : $title;
} );

/** Curated descriptions for the front page and fixed pages beat Rank Math's auto text. */
add_filter( 'rank_math/frontend/description', function ( $description ) {
	if ( is_front_page() || ( is_page() && ! get_post_meta( get_the_ID(), 'rank_math_description', true ) ) ) {
		$mine = dbt_meta_description();
		if ( $mine ) {
			return $mine;
		}
	}
	return $description;
} );

/** Organization schema: add the legal name and the markets. sameAs only when real URLs exist. */
add_filter( 'rank_math/json_ld', function ( $data ) {
	foreach ( array( 'publisher', 'organization' ) as $key ) {
		if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
			$data[ $key ]['name']       = DBT_ORG_NAME;
			$data[ $key ]['legalName']  = DBT_COMPANY_NAME;
			$data[ $key ]['areaServed'] = array(
				array( '@type' => 'Country', 'name' => 'Nigeria' ),
				array( '@type' => 'Country', 'name' => 'United Kingdom' ),
			);
			if ( dbt_same_as() ) {
				$data[ $key ]['sameAs'] = dbt_same_as();
			}
		}
	}
	return $data;
}, 99 );

/**
 * Rank Math reads its logo and default social image from the media library, so
 * copy a theme brand file in once (per file name + content hash) and return its ID.
 */
function dbt_brand_attachment_id( $file ) {
	$path = DBT_DIR . 'assets/brand/' . $file;
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	$key = 'dbt_att_' . md5( $file . filesize( $path ) . md5_file( $path ) );
	$id  = (int) get_option( $key, 0 );
	if ( $id && get_post( $id ) ) {
		return $id;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( $file );
	copy( $path, $tmp );
	$id = media_handle_sideload( array( 'name' => 'linkfa-' . $file, 'tmp_name' => $tmp ), 0, 'Linkfa brand: ' . $file );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		return 0;
	}
	update_option( $key, (int) $id );
	return (int) $id;
}

/**
 * One-off Rank Math settings per DBT_VERSION (no paid modules): Organization identity,
 * homepage title and description, noindex for tags, author archives and attachments,
 * and noindex on the pages the theme keeps out of search so the sitemap drops them.
 */
add_action( 'init', 'dbt_maybe_configure_rank_math', 50 );
function dbt_maybe_configure_rank_math() {
	if ( wp_installing() || ! dbt_rank_math_active() || get_option( 'dbt_rankmath_version', '' ) === DBT_VERSION ) {
		return;
	}

	$logo   = DBT_URI . 'assets/brand/icon-512.png';
	$logo_id = dbt_brand_attachment_id( 'icon-512.png' );
	$og_id   = dbt_brand_attachment_id( 'og-default.png' );
	$titles = (array) get_option( 'rank-math-options-titles', array() );
	$titles = array_merge( $titles, array(
		'knowledgegraph_type'       => 'company',
		'knowledgegraph_name'       => DBT_ORG_NAME,
		'knowledgegraph_logo'       => $logo,
		'knowledgegraph_logo_id'    => $logo_id,
		'website_name'              => DBT_ORG_NAME,
		'email'                     => DBT_CONTACT_EMAIL,
		'organization_description'  => 'Web, mobile and desktop apps, DevOps and infrastructure, SEO and Google Ads management, online advertising, and AI agents.',
		'open_graph_image'          => DBT_URI . 'assets/brand/og-default.png',
		'open_graph_image_id'       => $og_id,
		'homepage_title'            => DBT_ORG_NAME . ' — software, DevOps, ads and AI agents',
		'homepage_description'      => 'We build web, mobile and desktop apps, run zero-downtime DevOps, do SEO and Google Ads management, and ship AI agents — and stay reachable after launch.',
		'author_custom_robots'      => 'on',
		'author_robots'             => array( 'noindex' ),
		'tax_post_tag_custom_robots' => 'on',
		'tax_post_tag_robots'       => array( 'noindex' ),
		'pt_attachment_custom_robots' => 'on',
		'pt_attachment_robots'      => array( 'noindex' ),
	) );
	update_option( 'rank-math-options-titles', $titles );

	$general = (array) get_option( 'rank-math-options-general', array() );
	$general['attachment_redirect_urls'] = 'on';
	update_option( 'rank-math-options-general', $general );

	$sitemap = (array) get_option( 'rank-math-options-sitemap', array() );
	$sitemap['pt_attachment_sitemap'] = 'off';
	$sitemap['tax_post_tag_sitemap']  = 'off';
	update_option( 'rank-math-options-sitemap', $sitemap );

	$exclude = array();
	foreach ( dbt_noindex_slugs() as $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page ) {
			update_post_meta( $page->ID, 'rank_math_robots', array( 'noindex' ) );
			$exclude[] = $page->ID;
		}
	}
	// Rank Math's sitemap keeps listing pages unless they are in its own exclude list.
	$sitemap['exclude_posts'] = implode( ',', $exclude );
	update_option( 'rank-math-options-sitemap', $sitemap );

	update_option( 'dbt_rankmath_version', DBT_VERSION );
}
