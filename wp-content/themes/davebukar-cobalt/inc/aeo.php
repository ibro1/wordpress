<?php
/**
 * AI-search visibility (AEO/GEO): crawler rules in robots.txt, /llms.txt,
 * IndexNow pings, the Bing verification slot and the sameAs list.
 * Only facts already on the site; nothing is invented.
 */

defined( 'ABSPATH' ) || exit;

// Public by design: IndexNow proves ownership by serving this key at /<key>.txt.
define( 'DBT_INDEXNOW_KEY', 'a186c313b18e3c71950a4ff470630458' );

/** Search and answer crawlers that are allowed explicitly. */
function dbt_ai_crawlers() {
	return array( 'OAI-SearchBot', 'ChatGPT-User', 'GPTBot', 'PerplexityBot', 'ClaudeBot', 'Claude-SearchBot', 'Google-Extended', 'Applebot-Extended', 'Bingbot' );
}

/** robots.txt: keep the * rules, and repeat them for the named crawlers (a named group ignores the * group). */
add_filter( 'robots_txt', 'dbt_robots_txt', 99, 2 );
function dbt_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	$rules = "Disallow: /wp-admin/\n"
		. "Allow: /wp-admin/admin-ajax.php\n"
		. "Disallow: /*?s=\n"
		. "Disallow: /talk\n";

	$out = "User-agent: *\n" . $rules . "\n# AI search and answer crawlers are allowed on purpose.\n";
	foreach ( dbt_ai_crawlers() as $bot ) {
		$out .= 'User-agent: ' . $bot . "\n";
	}
	return $out . "Allow: /\n" . $rules . "\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n";
}

/** Profiles that really exist. Empty until the owner supplies URLs; never guessed. */
function dbt_same_as() {
	return array_values( array_filter( (array) apply_filters( 'dbt_same_as', array() ) ) );
}

/** Serve /llms.txt and the IndexNow key file without needing real files. */
add_action( 'init', 'dbt_aeo_files', 1 );
function dbt_aeo_files() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' ) : '';
	if ( 'llms.txt' === $path ) {
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo dbt_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text
		exit;
	}
	if ( DBT_INDEXNOW_KEY . '.txt' === $path ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo DBT_INDEXNOW_KEY; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed hex string
		exit;
	}
}

function dbt_llms_txt() {
	$lines   = array();
	$lines[] = '# ' . DBT_ORG_NAME;
	$lines[] = '';
	$lines[] = '> ' . DBT_ORG_NAME . ' is a service of ' . DBT_COMPANY_NAME . '. We build web, mobile and desktop apps, run DevOps and infrastructure, do SEO and Google Ads management, and ship AI agents. We also make done-for-you short video clips. We work with clients in Nigeria and the United Kingdom.';
	$lines[] = '';
	$lines[] = '## Services';
	foreach ( dbt_services() as $slug => $service ) {
		$lines[] = '- [' . $service['title'] . '](' . home_url( '/' . $slug . '/' ) . '): ' . $service['lede'];
	}
	$lines[] = '- [Short clips](' . home_url( '/clips/' ) . '): Done-for-you short clips from lectures, tafsir and podcasts, captioned for TikTok, Facebook Reels and YouTube Shorts. 3 free clips to start.';
	$lines[] = '- [Short adverts](' . home_url( '/earn/' ) . '): Short video adverts for businesses, made from your phone footage.';
	$lines[] = '';
	$lines[] = '## Key pages';
	$lines[] = '- [Work](' . home_url( '/work/' ) . '): Logistics, government, transport, healthcare and AI systems we built and keep running. No client names.';
	$lines[] = '- [Privacy policy](' . home_url( '/privacy-policy/' ) . ')';
	$lines[] = '';
	$lines[] = '## Contact';
	$lines[] = '- Email: ' . DBT_CONTACT_EMAIL;
	$lines[] = '- WhatsApp: +' . DBT_WHATSAPP;
	$lines[] = '- Company: ' . DBT_COMPANY_NAME;
	return implode( "\n", $lines ) . "\n";
}

/** Bing Webmaster Tools verification slot: set the dbt_bing_verification option (or define DBT_BING_VERIFICATION). */
add_action( 'wp_head', 'dbt_bing_meta', 2 );
function dbt_bing_meta() {
	if ( ! is_front_page() ) {
		return;
	}
	$code = defined( 'DBT_BING_VERIFICATION' ) ? DBT_BING_VERIFICATION : get_option( 'dbt_bing_verification', '' );
	if ( $code ) {
		printf( '<meta name="msvalidate.01" content="%s">' . "\n", esc_attr( $code ) );
	}
}

/** IndexNow: tell Bing and partners when a public page is published or updated. */
add_action( 'transition_post_status', 'dbt_indexnow_on_publish', 10, 3 );
function dbt_indexnow_on_publish( $new, $old, $post ) {
	if ( 'publish' !== $new || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		return;
	}
	if ( in_array( $post->post_name, array_merge( dbt_noindex_slugs(), array( 'hello-world' ) ), true ) ) {
		return;
	}
	dbt_indexnow_ping( array( get_permalink( $post ) ) );
}

/** After a theme release, announce the key pages once. */
add_action( 'init', 'dbt_indexnow_release', 99 );
function dbt_indexnow_release() {
	if ( wp_installing() || is_admin() || get_option( 'dbt_indexnow_version' ) === DBT_VERSION ) {
		return;
	}
	update_option( 'dbt_indexnow_version', DBT_VERSION );
	$urls = array( home_url( '/' ), home_url( '/work/' ), home_url( '/clips/' ), home_url( '/earn/' ), home_url( '/privacy-policy/' ), home_url( '/llms.txt' ) );
	foreach ( array_keys( dbt_services() ) as $slug ) {
		$urls[] = home_url( '/' . $slug . '/' );
	}
	dbt_indexnow_ping( $urls );
}

function dbt_indexnow_ping( $urls ) {
	if ( ! get_option( 'blog_public' ) ) {
		return;
	}
	wp_remote_post( 'https://api.indexnow.org/indexnow', array(
		'timeout'  => 5,
		'blocking' => false,
		'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
		'body'     => wp_json_encode( array(
			'host'        => wp_parse_url( home_url(), PHP_URL_HOST ),
			'key'         => DBT_INDEXNOW_KEY,
			'keyLocation' => home_url( '/' . DBT_INDEXNOW_KEY . '.txt' ),
			'urlList'     => array_values( array_unique( $urls ) ),
		) ),
	) );
}
