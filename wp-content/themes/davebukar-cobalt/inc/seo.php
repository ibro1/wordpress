<?php
/**
 * SEO basics: meta description, canonical, Open Graph, Organization schema,
 * noindex for non-marketing pages, robots.txt and sitemap clean-up.
 * Every fact here already appears on the site; nothing is invented.
 */

defined( 'ABSPATH' ) || exit;

define( 'DBT_ORG_NAME', 'DaveBukar Technologies' );

/** Pages that must stay out of search results and the sitemap. */
function dbt_noindex_slugs() {
	return array( 'shop', 'cart', 'checkout', 'my-account', 'login', 'sample-page', 'register', 'terms-of-service' );
}

function dbt_is_noindex_page() {
	return is_page( dbt_noindex_slugs() ) || is_search() || is_404() || is_attachment();
}

add_filter( 'wp_robots', 'dbt_robots_meta' );
function dbt_robots_meta( $robots ) {
	if ( dbt_is_noindex_page() || ( is_single() && 'hello-world' === get_post_field( 'post_name' ) ) ) {
		$robots['noindex']  = true;
		$robots['follow']   = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
}

add_filter( 'document_title_parts', 'dbt_home_title' );
function dbt_home_title( $parts ) {
	if ( is_front_page() ) {
		$parts['title']   = DBT_ORG_NAME . ' — software, DevOps, ads and AI agents';
		unset( $parts['tagline'], $parts['site'] );
	}
	return $parts;
}

function dbt_meta_description() {
	if ( is_front_page() ) {
		return 'We build web, mobile and desktop apps, run zero-downtime DevOps, and ship online advertising and AI agents — and stay reachable after launch.';
	}
	if ( ! is_singular() ) {
		return '';
	}

	$slug     = get_post_field( 'post_name', get_the_ID() );
	$services = dbt_services();
	if ( isset( $services[ $slug ] ) ) {
		return $services[ $slug ]['title'] . ' from ' . DBT_ORG_NAME . '. ' . $services[ $slug ]['lede'];
	}

	$fixed = array(
		'work'             => 'Logistics, government, transport, healthcare and AI systems we designed, built and still keep running — from empty repo to production, including the servers.',
		'clips'            => 'Done-for-you short clips from your lectures, tafsir and podcasts, captioned for TikTok, Facebook Reels and YouTube Shorts. Get 3 free clips on WhatsApp.',
		'earn'             => 'Short videos that bring you students, buyers and sales. We make the first advert, you see it, then you pay.',
		'privacy-policy'   => 'How ' . DBT_ORG_NAME . ' handles personal data, Google and YouTube API data, and cookies.',
	);
	if ( isset( $fixed[ $slug ] ) ) {
		return $fixed[ $slug ];
	}

	$text = has_excerpt() ? get_the_excerpt() : wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', get_the_ID() ) ) );
	return wp_html_excerpt( trim( preg_replace( '/\s+/', ' ', $text ) ), 155, '…' );
}

add_action( 'wp_head', 'dbt_head_meta', 3 );
function dbt_head_meta() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$desc = dbt_meta_description();
	$url  = is_front_page() ? home_url( '/' ) : ( is_singular() ? get_permalink() : '' );
	$title = wp_get_document_title();

	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	// WordPress prints rel=canonical for singular pages itself; the front page is the gap.
	if ( is_front_page() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
	}
	if ( dbt_is_noindex_page() || ! $url ) {
		return;
	}

	printf( '<meta property="og:type" content="%s">' . "\n", is_front_page() ? 'website' : 'article' );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( DBT_ORG_NAME ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( DBT_URI . 'assets/brand/icon-512.png' ) );
	echo '<meta name="twitter:card" content="summary">' . "\n";
}

/** Organization + WebSite schema on the front page only. */
add_action( 'wp_head', 'dbt_jsonld', 4 );
function dbt_jsonld() {
	if ( ! is_front_page() ) {
		return;
	}

	$org = array(
		'@type'        => 'Organization',
		'@id'          => home_url( '/#organization' ),
		'name'         => DBT_ORG_NAME,
		'url'          => home_url( '/' ),
		'logo'         => DBT_URI . 'assets/brand/icon-512.png',
		'email'        => DBT_CONTACT_EMAIL,
		'telephone'    => '+' . DBT_WHATSAPP,
		'description'  => 'Web, mobile and desktop apps, DevOps and infrastructure, online advertising, and AI agents.',
		'contactPoint' => array(
			array(
				'@type'       => 'ContactPoint',
				'contactType' => 'sales',
				'email'       => DBT_CONTACT_EMAIL,
				'telephone'   => '+' . DBT_WHATSAPP,
			),
		),
	);
	$site = array(
		'@type'     => 'WebSite',
		'@id'       => home_url( '/#website' ),
		'url'       => home_url( '/' ),
		'name'      => DBT_ORG_NAME,
		'publisher' => array( '@id' => home_url( '/#organization' ) ),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => array( $org, $site ) ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/** robots.txt: drop the WooCommerce rules, keep it to what this site needs. */
add_filter( 'robots_txt', 'dbt_robots_txt', 99, 2 );
function dbt_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	return "User-agent: *\n"
		. "Disallow: /wp-admin/\n"
		. "Allow: /wp-admin/admin-ajax.php\n"
		. "Disallow: /*?s=\n"
		. "\n"
		. 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
}

/** Sitemap: no user list, and none of the noindex pages or the sample post. */
add_filter( 'wp_sitemaps_add_provider', 'dbt_sitemap_providers', 10, 2 );
function dbt_sitemap_providers( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}

add_filter( 'wp_sitemaps_posts_query_args', 'dbt_sitemap_exclusions', 10, 2 );
function dbt_sitemap_exclusions( $args, $post_type ) {
	$exclude = array();
	if ( 'page' === $post_type ) {
		foreach ( dbt_noindex_slugs() as $slug ) {
			$page = get_page_by_path( $slug, OBJECT, 'page' );
			if ( $page ) {
				$exclude[] = $page->ID;
			}
		}
	}
	if ( 'post' === $post_type ) {
		$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
		if ( $hello ) {
			$exclude[] = $hello->ID;
		}
	}
	if ( $exclude ) {
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $exclude );
	}
	return $args;
}
