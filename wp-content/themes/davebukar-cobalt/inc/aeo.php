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
	return $out . "Allow: /\n" . $rules . "\nSitemap: " . home_url( function_exists( 'dbt_rank_math_active' ) && dbt_rank_math_active() ? '/sitemap_index.xml' : '/wp-sitemap.xml' ) . "\n";
}

/** Profiles that really exist. Empty until the owner supplies URLs; never guessed. */
function dbt_same_as() {
	$profiles = array(
		'https://www.linkedin.com/in/abubakaribro1',
		'https://github.com/ibro1',
	);
	return array_values( array_filter( (array) apply_filters( 'dbt_same_as', $profiles ) ) );
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
	$lines[] = '- [Pricing & FAQ](' . home_url( '/pricing-faq/' ) . '): Published prices where we have them, and "on request" where scope decides the price.';
	$lines[] = '- [Refund policy](' . home_url( '/refund/' ) . ')';
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
	$urls = array( home_url( '/' ), home_url( '/work/' ), home_url( '/clips/' ), home_url( '/earn/' ), home_url( '/privacy-policy/' ), home_url( '/refund/' ), home_url( '/pricing-faq/' ), home_url( '/llms.txt' ) );
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

/**
 * Answer-ready copy per service page: an "at a glance" block and FAQs.
 * Prices and timelines are "on request" until the owner sets them.
 */
function dbt_service_answers() {
	$price    = 'On request. We quote after a short scoping call, and offer fixed pricing where possible.';
	$timeline = 'Depends on scope. We put the timeline in writing after a scoping call.';
	$q_price  = array( 'q' => 'How much does it cost?', 'a' => $price );
	$q_time   = array( 'q' => 'How long does it take?', 'a' => $timeline );

	return array(
		'software-development' => array(
			'glance' => array(
				'What it is'       => 'Custom web, mobile and desktop applications, built, deployed and maintained by the same engineers.',
				'Who it is for'    => 'Founders and teams in Nigeria and the UK who need a product or internal system built.',
				'How it works'     => 'A scoping call, a written proposal, then build, test and deploy. We stay reachable after launch.',
				'Typical timeline' => $timeline,
				'Price'            => $price,
			),
			'faq'    => array(
				array( 'q' => 'What kinds of software do you build?', 'a' => 'Web apps (React, Next.js, Node, Python), mobile apps for iOS and Android (native or React Native), and desktop software and admin tools (Electron and native tooling).' ),
				array( 'q' => 'Can you connect my app to payments and other services?', 'a' => 'Yes. Payments and third-party API integrations are part of the work.' ),
				array( 'q' => 'Do you support the software after launch?', 'a' => 'Yes. We stay reachable after launch, and several of the systems on our Work page are ones we still keep running.' ),
				$q_price,
				$q_time,
			),
		),
		'devops' => array(
			'glance' => array(
				'What it is'       => 'Server provisioning, deployment pipelines, CI/CD, monitoring and hardening, run for you.',
				'Who it is for'    => 'Teams whose releases are manual or risky, or who have no one to run their servers.',
				'How it works'     => 'We provision it, script it, document it and hand it over, and we keep it running after launch.',
				'Typical timeline' => $timeline,
				'Price'            => $price,
			),
			'faq'    => array(
				array( 'q' => 'What does DevOps and infrastructure cover?', 'a' => 'cPanel and WHM setup, domains, DNS and SSL, deployment scripts, GitHub Actions or GitLab CI, uptime monitoring, log aggregation, firewall rules and backups.' ),
				array( 'q' => 'Can you deploy without downtime?', 'a' => 'Yes. We build zero-downtime deploy pipelines with staging environments and rollbacks.' ),
				array( 'q' => 'Do you keep the servers running after setup?', 'a' => 'Yes. We provision the infrastructure and keep it running after launch.' ),
				$q_price,
				$q_time,
			),
		),
		'advertising' => array(
			'glance' => array(
				'What it is'       => 'SEO, to get your website ranking on Google, and management of your Google Ads and Meta ads campaigns.',
				'Who it is for'    => 'Businesses in Nigeria and the UK that want customers to find them online.',
				'How it works'     => 'We tie the work to real conversion tracking and report in plain language.',
				'Typical timeline' => 'SEO is gradual. We put a plan and timeline in writing after a scoping call.',
				'Price'            => $price,
			),
			'faq'    => array(
				array( 'q' => 'What is SEO?', 'a' => 'Search engine optimisation: getting your website to rank on Google through the pages, structure and content that search engines and customers look for.' ),
				array( 'q' => 'Do you manage Google Ads?', 'a' => 'Yes. We run Google Ads campaigns for clients (Search, Shopping and Performance Max), tied to conversion tracking.' ),
				array( 'q' => 'Do you run Facebook and Instagram ads?', 'a' => 'Yes. We manage Meta ads, including campaign structure, audience testing and creative rotation.' ),
				array( 'q' => 'Do you promise rankings or results?', 'a' => 'No. We do not promise specific rankings or results. We report what happened in plain language.' ),
				$q_price,
			),
		),
		'ai-agents' => array(
			'glance' => array(
				'What it is'       => 'LLM-backed agents connected to the systems you already run: support, internal tools and workflow automation.',
				'Who it is for'    => 'Teams that want routine work automated while a person keeps the final say.',
				'How it works'     => 'We train the agent on your docs and policies, connect it to your tools, test it, and set when it hands over to a human.',
				'Typical timeline' => $timeline,
				'Price'            => $price,
			),
			'faq'    => array(
				array( 'q' => 'What can an AI agent do for my business?', 'a' => 'Answer support questions from your docs and policies, read your database and draft reports, and automate work across a CRM, helpdesk, inventory or billing system.' ),
				array( 'q' => 'Which AI models do you use?', 'a' => 'Claude, GPT or open-weight models, chosen per task on cost and accuracy.' ),
				array( 'q' => 'Will it hand over to a human?', 'a' => 'Yes. We set an escalation path so it hands over when it should, and decisions stay with a person.' ),
				$q_price,
				$q_time,
			),
		),
	);
}

/** Visible FAQ list (details/summary, works without JavaScript). */
function dbt_render_faq( $faq, $heading = 'Frequently asked questions' ) {
	?>
	<section class="faq reveal" aria-label="<?php echo esc_attr( $heading ); ?>">
		<h2 class="faq__title"><?php echo esc_html( $heading ); ?></h2>
		<?php foreach ( $faq as $i => $item ) : ?>
			<details class="faq__item"<?php echo 0 === $i ? ' open' : ''; ?>>
				<summary><?php echo esc_html( $item['q'] ); ?></summary>
				<p><?php echo esc_html( $item['a'] ); ?></p>
			</details>
		<?php endforeach; ?>
	</section>
	<?php
}

function dbt_current_faq() {
	if ( ! is_singular( 'page' ) ) {
		return array();
	}
	$slug = get_post_field( 'post_name', get_the_ID() );
	if ( 'pricing-faq' === $slug && function_exists( 'dbt_pricing_faq' ) ) {
		return dbt_pricing_faq();
	}
	$all = dbt_service_answers();
	return isset( $all[ $slug ] ) ? $all[ $slug ]['faq'] : array();
}

/** FAQPage schema that mirrors the visible FAQ exactly. */
add_action( 'wp_head', 'dbt_faq_jsonld', 5 );
function dbt_faq_jsonld() {
	$faq = dbt_current_faq();
	if ( ! $faq ) {
		return;
	}
	$entities = array();
	foreach ( $faq as $item ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $item['q'],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $item['a'] ),
		);
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/** Pricing & FAQ page: published prices only where the site already states them. */
function dbt_pricing_faq() {
	return array(
		array( 'q' => 'Who is Linkfa?', 'a' => 'Linkfa is a service of ' . DBT_COMPANY_NAME . '. We build web, mobile and desktop apps, run DevOps and infrastructure, do SEO and Google Ads management, ship AI agents, and make short video clips.' ),
		array( 'q' => 'Which countries do you work in?', 'a' => 'We work with clients in Nigeria and the United Kingdom.' ),
		array( 'q' => 'How do I get a price for a software, DevOps, SEO, Google Ads or AI agent project?', 'a' => 'Book a call or message us on WhatsApp. We scope the work first, then quote. We offer fixed pricing where possible.' ),
		array( 'q' => 'How much do the short clips cost?', 'a' => 'Starter is ₦50,000 a month for 12 clips. Standard is ₦100,000 a month for 30 clips. Ramadan and event series are priced per series. The first 3 clips are free.' ),
		array( 'q' => 'How much does the 3-advert pack cost?', 'a' => 'The pack is ₦15,000 for 3 short adverts, with one round of changes included. You pay after you see the first advert.' ),
		array( 'q' => 'Is there a contract for the clips?', 'a' => 'No. You can stop in any month.' ),
		array( 'q' => 'How do I contact you?', 'a' => 'Email ' . DBT_CONTACT_EMAIL . ' or message +' . DBT_WHATSAPP . ' on WhatsApp.' ),
	);
}
