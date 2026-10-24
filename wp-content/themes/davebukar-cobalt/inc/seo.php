<?php
/**
 * SEO basics: meta description, canonical, Open Graph, Organization schema,
 * noindex for non-marketing pages, robots.txt and sitemap clean-up.
 * Every fact here already appears on the site; nothing is invented.
 */

defined( 'ABSPATH' ) || exit;

define( 'DBT_ORG_NAME', 'Linkfa' );
define( 'DBT_COMPANY_NAME', 'DaveBukar Technologies' );

/** Pages that must stay out of search results and the sitemap. */
function dbt_noindex_slugs() {
	return array( 'shop', 'cart', 'checkout', 'my-account', 'login', 'sample-page', 'register' );
}

function dbt_is_noindex_page() {
	return is_page( dbt_noindex_slugs() ) || is_search() || is_404() || is_attachment() || dbt_cm_is_unpublished_page();
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
		return 'We build web, mobile and desktop apps, run zero-downtime DevOps, do SEO and Google Ads management, and ship AI agents — and stay reachable after launch.';
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
		'pricing-faq'      => 'Linkfa prices: short clips from ₦50,000 a month, the 3-advert pack at ₦15,000, and software, DevOps, SEO, Google Ads and AI agent work quoted after a scoping call. Plus answers to common questions.',
		'blog'             => DBT_BLOG_DESCRIPTION,
		'earn'             => 'Short videos that bring you students, buyers and sales. We make the first advert, you see it, then you pay.',
		'refund'           => 'Refund policy for Linkfa short clips, the 3-advert pack and custom work: what is refunded, when, and how to ask.',
		'privacy-policy'   => 'How ' . DBT_ORG_NAME . ' handles personal data, Google and YouTube API data, and cookies.',
	);
	if ( isset( $fixed[ $slug ] ) ) {
		return $fixed[ $slug ];
	}

	// Description written by an SEO plugin or the SEO employee, when present.
	foreach ( array( '_yoast_wpseo_metadesc', 'rank_math_description', '_dbt_meta_description' ) as $key ) {
		$custom = trim( (string) get_post_meta( get_the_ID(), $key, true ) );
		if ( '' !== $custom ) {
			return $custom;
		}
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
	$image = ( is_singular() && has_post_thumbnail() ) ? get_the_post_thumbnail_url( null, 'large' ) : '';
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ? $image : DBT_URI . 'assets/brand/og-default.png' ) );
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}

/** Article schema on published posts (no plugin needed). */
function dbt_article_jsonld() {
	$post  = get_post();
	$image = has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'large' ) : DBT_URI . 'assets/brand/og-default.png';
	$data  = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'Article',
		'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
		'description'      => dbt_meta_description(),
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'image'            => array( $image ),
		'mainEntityOfPage' => get_permalink( $post ),
		'author'           => array( '@type' => 'Organization', 'name' => DBT_ORG_NAME, 'url' => home_url( '/' ) ),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => DBT_ORG_NAME,
			'logo'  => array( '@type' => 'ImageObject', 'url' => DBT_URI . 'assets/brand/icon-512.png' ),
		),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/** Let REST writers set SEO title/description even with no SEO plugin installed. */
add_action( 'init', 'dbt_register_seo_meta' );
function dbt_register_seo_meta() {
	foreach ( array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc', 'rank_math_title', 'rank_math_description', '_dbt_meta_description' ) as $key ) {
		register_post_meta( 'post', $key, array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
}

/** Use a custom SEO title when one is set on the post. */
add_filter( 'pre_get_document_title', 'dbt_custom_post_title', 20 );
function dbt_custom_post_title( $title ) {
	if ( ! is_singular( 'post' ) ) {
		return $title;
	}
	foreach ( array( '_yoast_wpseo_title', 'rank_math_title' ) as $key ) {
		$custom = trim( (string) get_post_meta( get_the_ID(), $key, true ) );
		if ( '' !== $custom && false === strpos( $custom, '%%' ) && false === strpos( $custom, '%' ) ) {
			return $custom;
		}
	}
	return $title;
}

/** Organization + WebSite schema on the front page only. */
add_action( 'wp_head', 'dbt_jsonld', 4 );
function dbt_jsonld() {
	if ( is_singular( 'post' ) && ! dbt_is_noindex_page() ) {
		dbt_article_jsonld();
		return;
	}
	if ( ! is_front_page() ) {
		return;
	}

	$org = array(
		'@type'        => 'Organization',
		'@id'          => home_url( '/#organization' ),
		'name'         => DBT_ORG_NAME,
		'legalName'    => DBT_COMPANY_NAME,
		'url'          => home_url( '/' ),
		'logo'         => DBT_URI . 'assets/brand/icon-512.png',
		'email'        => DBT_CONTACT_EMAIL,
		'telephone'    => '+' . DBT_WHATSAPP,
		'areaServed'   => array(
			array( '@type' => 'Country', 'name' => 'Nigeria' ),
			array( '@type' => 'Country', 'name' => 'United Kingdom' ),
		),
		'description'  => 'Web, mobile and desktop apps, DevOps and infrastructure, SEO and Google Ads management, online advertising, and AI agents.',
		'contactPoint' => array(
			array(
				'@type'       => 'ContactPoint',
				'contactType' => 'sales',
				'email'       => DBT_CONTACT_EMAIL,
				'telephone'   => '+' . DBT_WHATSAPP,
			),
		),
	);
	if ( dbt_same_as() ) {
		$org['sameAs'] = dbt_same_as();
	}
	$site = array(
		'@type'     => 'WebSite',
		'@id'       => home_url( '/#website' ),
		'url'       => home_url( '/' ),
		'name'      => DBT_ORG_NAME,
		'publisher' => array( '@id' => home_url( '/#organization' ) ),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => array( $org, $site ) ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
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
