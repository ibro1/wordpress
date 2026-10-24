<?php
/**
 * YouTube Channel Manager: prices, the draft pages and their legal text.
 *
 * Prices are never stored here. They are read server-side from the harness's public offers list
 * (DBT_CM_OFFERS_URL), which the owner edits in the Chief app, and kept for 5 minutes in a transient.
 * When the list cannot be read, the last good copy is used, and failing that the bundled copy in
 * assets/data/channel-manager-offers.json, so a price is never blank. A tier whose live price is
 * missing or not a number takes the bundled price of the same tier. A USD price of null is deliberate (the offer
 * has no dollar price) and renders as an "Ask for USD price" link; only NGN must always be a number.
 *
 * The three pages are provisioned as drafts and never published by the theme; the owner publishes
 * them from wp-admin. While a page is not published it is noindex (see dbt_is_noindex_page()).
 */

defined( 'ABSPATH' ) || exit;

define( 'DBT_CM_OFFERS_URL', 'https://harness.linkfa.de/api/public/offers' );
define( 'DBT_CM_OFFER_ID', 'channel-manager' );
define( 'DBT_CM_CACHE_KEY', 'dbt_cm_offer' );
define( 'DBT_CM_LAST_GOOD', 'dbt_cm_offer_last_good' );

/** The offers list address; filterable so a staging harness can be used. */
function dbt_cm_offers_url() {
	return (string) apply_filters( 'dbt_cm_offers_url', DBT_CM_OFFERS_URL );
}

/** A positive price, or null. */
function dbt_cm_price( $value ) {
	return ( is_int( $value ) || is_float( $value ) || ( is_string( $value ) && is_numeric( $value ) ) ) && (float) $value > 0 ? (float) $value : null;
}

/** The Channel Manager offer in an offers document: id first, then a name that says "channel manager". */
function dbt_cm_pick_offer( $doc ) {
	if ( ! is_array( $doc ) || ! isset( $doc['offers'] ) || ! is_array( $doc['offers'] ) ) {
		return null;
	}
	$by_name = null;
	foreach ( $doc['offers'] as $offer ) {
		if ( ! is_array( $offer ) || ! isset( $offer['tiers'] ) || ! is_array( $offer['tiers'] ) || array() === $offer['tiers'] ) {
			continue;
		}
		if ( isset( $offer['id'] ) && DBT_CM_OFFER_ID === $offer['id'] ) {
			return $offer;
		}
		if ( null === $by_name && isset( $offer['name'] ) && is_string( $offer['name'] ) && preg_match( '/channel\s*manager/i', $offer['name'] ) ) {
			$by_name = $offer;
		}
	}
	return $by_name;
}

/**
 * The USD price of a tier or setup fee: an explicit null stays null (no dollar price), a usable number is kept, and
 * anything else (missing, empty, not a number) takes the bundled value.
 */
function dbt_cm_usd( $source, $bundled ) {
	if ( is_array( $source ) && array_key_exists( 'usd', $source ) && null === $source['usd'] ) {
		return null;
	}
	return dbt_cm_price( $source['usd'] ?? null ) ?? ( is_array( $bundled ) ? dbt_cm_price( $bundled['usd'] ?? null ) : null );
}

/** The bundled offer, read once per request. */
function dbt_cm_bundled_offer() {
	static $offer = null;
	if ( null === $offer ) {
		$raw   = file_get_contents( DBT_DIR . 'assets/data/channel-manager-offers.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$offer = dbt_cm_pick_offer( json_decode( (string) $raw, true ) );
	}
	return $offer;
}

/** A list of non-empty strings, or null. */
function dbt_cm_strings( $value ) {
	if ( ! is_array( $value ) ) {
		return null;
	}
	$out = array();
	foreach ( $value as $item ) {
		if ( is_string( $item ) && '' !== trim( $item ) ) {
			$out[] = trim( $item );
		}
	}
	return $out;
}

/**
 * Make an offer safe to render: every tier has both prices (the bundled tier's where the live one
 * is unusable), a name, a list and an availability. A live tier with no usable price and no bundled
 * twin is left out rather than shown blank.
 */
function dbt_cm_normalize( $offer ) {
	$bundled = dbt_cm_bundled_offer();
	$twins   = array();
	foreach ( (array) ( $bundled['tiers'] ?? array() ) as $tier ) {
		$twins[ (string) $tier['id'] ] = $tier;
	}
	$text = function ( $key ) use ( $offer, $bundled ) {
		return isset( $offer[ $key ] ) && is_string( $offer[ $key ] ) && '' !== trim( $offer[ $key ] ) ? trim( $offer[ $key ] ) : (string) ( $bundled[ $key ] ?? '' );
	};

	$tiers = array();
	foreach ( (array) $offer['tiers'] as $tier ) {
		if ( ! is_array( $tier ) || ! isset( $tier['id'] ) || ! is_string( $tier['id'] ) ) {
			continue;
		}
		$twin = $twins[ $tier['id'] ] ?? null;
		$ngn  = dbt_cm_price( $tier['ngn'] ?? null ) ?? ( $twin ? dbt_cm_price( $twin['ngn'] ) : null );
		$usd  = dbt_cm_usd( $tier, $twin );
		if ( null === $ngn ) {
			continue;
		}
		$name     = isset( $tier['name'] ) && is_string( $tier['name'] ) && '' !== trim( $tier['name'] ) ? trim( $tier['name'] ) : (string) ( $twin['name'] ?? $tier['id'] );
		$includes = dbt_cm_strings( $tier['includes'] ?? null );
		$tiers[]  = array(
			'id'        => $tier['id'],
			'name'      => $name,
			'ngn'       => $ngn,
			'usd'       => $usd,
			'includes'  => null !== $includes && array() !== $includes ? $includes : (array) ( $twin['includes'] ?? array() ),
			'available' => isset( $tier['available'] ) && is_bool( $tier['available'] ) ? $tier['available'] : (bool) ( $twin['available'] ?? true ),
		);
	}
	if ( array() === $tiers ) {
		return null;
	}

	// An explicit null means no setup fee; anything else unusable takes the bundled fee.
	$setup = null;
	if ( ! array_key_exists( 'setup', $offer ) || null !== $offer['setup'] ) {
		$live  = is_array( $offer['setup'] ?? null ) ? $offer['setup'] : array();
		$base  = is_array( $bundled['setup'] ?? null ) ? $bundled['setup'] : array();
		$ngn   = dbt_cm_price( $live['ngn'] ?? null ) ?? dbt_cm_price( $base['ngn'] ?? null );
		$usd   = dbt_cm_usd( $live, $base );
		$setup = null !== $ngn ? array( 'ngn' => $ngn, 'usd' => $usd ) : null;
	}

	return array(
		'name'     => $text( 'name' ),
		'pitch'    => $text( 'pitch' ),
		'freeHook' => $text( 'freeHook' ),
		'setup'    => $setup,
		'tiers'    => $tiers,
	);
}

/**
 * The offer to render and where it came from: `live`, `last-good` or `bundled`.
 * @return array{offer: array, source: string, at: int}
 */
function dbt_cm_offer() {
	$cached = get_transient( DBT_CM_CACHE_KEY );
	if ( is_array( $cached ) && isset( $cached['offer']['tiers'] ) ) {
		return $cached;
	}

	$response = wp_remote_get( dbt_cm_offers_url(), array(
		'timeout'             => 4,
		'redirection'         => 2,
		'limit_response_size' => 262144,
		'headers'             => array( 'Accept' => 'application/json' ),
	) );
	if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
		$picked = dbt_cm_pick_offer( json_decode( (string) wp_remote_retrieve_body( $response ), true ) );
		$offer  = null === $picked ? null : dbt_cm_normalize( $picked );
		if ( null !== $offer ) {
			$result = array( 'offer' => $offer, 'source' => 'live', 'at' => time() );
			set_transient( DBT_CM_CACHE_KEY, $result, 5 * MINUTE_IN_SECONDS );
			update_option( DBT_CM_LAST_GOOD, $result, false );
			return $result;
		}
	}

	// The list could not be read: the last good copy, then the bundled one. Kept for a minute so a
	// harness that is down does not cost every visitor a 4-second wait.
	$last = get_option( DBT_CM_LAST_GOOD );
	if ( is_array( $last ) && isset( $last['offer']['tiers'] ) ) {
		$result = array( 'offer' => $last['offer'], 'source' => 'last-good', 'at' => (int) ( $last['at'] ?? 0 ) );
	} else {
		$result = array( 'offer' => dbt_cm_normalize( dbt_cm_bundled_offer() ), 'source' => 'bundled', 'at' => 0 );
	}
	set_transient( DBT_CM_CACHE_KEY, $result, MINUTE_IN_SECONDS );
	return $result;
}

/** A price in one currency, such as ₦60,000 or $59. */
function dbt_cm_money( $amount, $currency ) {
	$decimals = floor( $amount ) === $amount ? 0 : 2;
	return ( 'usd' === $currency ? '$' : '₦' ) . number_format( $amount, $decimals );
}

/**
 * Both prices, one shown at a time by the currency switch; NGN shows without JavaScript. With no USD price the USD
 * side is an "Ask for USD price" link through /talk, never a blank or $0.
 * @param float      $ngn    the naira price.
 * @param float|null $usd    the dollar price, or null when the offer has none.
 * @param string     $suffix plain text after each price, such as " / month".
 * @param string     $ref    the /talk source for the ask link.
 */
function dbt_cm_amount_html( $ngn, $usd, $suffix = '', $ref = 'usd' ) {
	$small = '' === $suffix ? '' : '<small>' . esc_html( $suffix ) . '</small>';
	$dollar = null === $usd
		? '<a class="cm-ask" href="' . esc_url( dbt_cm_talk( 'usd-' . $ref ) ) . '">Ask for USD price</a>'
		: esc_html( dbt_cm_money( $usd, 'usd' ) ) . $small;
	return '<span class="cm-cur" data-cur="ngn">' . esc_html( dbt_cm_money( $ngn, 'ngn' ) ) . $small . '</span>'
		. '<span class="cm-cur" data-cur="usd">' . $dollar . '</span>';
}

/** The /talk redirect for one call to action, so the click is logged with its source. */
function dbt_cm_talk( $where ) {
	return home_url( '/talk/?ref=channel-manager-' . rawurlencode( $where ) );
}

// ----- Draft pages -----

/** The Channel Manager pages: created as drafts, never published by the theme. */
function dbt_cm_pages() {
	return array(
		'channel-manager'         => array(
			'title'    => 'YouTube Channel Manager',
			'template' => 'template-channel-manager.php',
		),
		'channel-manager-terms'   => array(
			'title'    => 'Channel Manager Terms',
			'template' => 'template-cm-legal.php',
		),
		'channel-manager-privacy' => array(
			'title'    => 'Channel Manager Privacy and YouTube Data',
			'template' => 'template-cm-legal.php',
		),
	);
}

add_action( 'init', 'dbt_cm_create_draft_pages', 21 );
/**
 * Once per DBT_VERSION, create any missing Channel Manager page as a draft. An existing page is left
 * exactly as it is (its status included), so a page the owner published stays published and a page
 * he deleted comes back only as a draft. The pages are not added to any menu.
 */
function dbt_cm_create_draft_pages() {
	if ( wp_installing() || get_option( 'dbt_cm_pages_version', '' ) === DBT_VERSION ) {
		return;
	}
	foreach ( dbt_cm_pages() as $slug => $page ) {
		if ( get_page_by_path( $slug, OBJECT, 'page' ) instanceof WP_Post ) {
			continue;
		}
		wp_insert_post( array(
			'post_type'     => 'page',
			'post_status'   => 'draft',
			'post_title'    => $page['title'],
			'post_name'     => $slug,
			'post_content'  => '',
			'page_template' => $page['template'],
		) );
	}
	update_option( 'dbt_cm_pages_version', DBT_VERSION );
}

/** Whether this request shows a Channel Manager page that is not published (a draft preview). */
function dbt_cm_is_unpublished_page() {
	if ( ! is_page( array_keys( dbt_cm_pages() ) ) ) {
		return false;
	}
	return 'publish' !== get_post_status( get_queried_object_id() );
}

add_action( 'wp_enqueue_scripts', 'dbt_cm_enqueue', 20 );
function dbt_cm_enqueue() {
	if ( is_page_template( array( 'template-channel-manager.php', 'template-cm-legal.php' ) ) ) {
		wp_enqueue_style( 'dbt-channel-manager', DBT_URI . 'assets/css/channel-manager.css', array( 'dbt-main' ), DBT_VERSION );
	}
}

/** The draft notice shown on an unpublished Channel Manager page (only editors can open one). */
function dbt_cm_draft_notice() {
	if ( 'publish' === get_post_status() ) {
		return;
	}
	echo '<div class="cm-draft" role="note">DRAFT · not published and not indexed · publish it from Pages in wp-admin once the owner has reviewed it</div>';
}

// ----- Legal text -----

/** The Channel Manager terms and privacy pages, keyed by slug. Drafts for the owner's review. */
function dbt_cm_legal_pages() {
	$updated = 'October 24, 2026';
	$email   = esc_html( DBT_CONTACT_EMAIL );
	$contact = '<p>Questions: <a href="mailto:' . $email . '">' . $email . '</a>, or WhatsApp +234 811 323 5992.</p>';
	$privacy_url = home_url( '/channel-manager-privacy/' );
	$terms_url   = home_url( '/channel-manager-terms/' );
	$refund_url  = home_url( '/refund/' );

	$terms = array(
		'<p><em>Last updated: ' . $updated . '</em></p>',
		'<p>These terms cover the YouTube Channel Manager service from Linkfa, a service of DaveBukar Technologies ("we", "us"). They apply to you, the owner of the YouTube channel ("your channel"), from the day you connect your channel or accept a free audit.</p>',
		'<h2>1. What the service does</h2>',
		'<p>Our AI employee reviews your channel and, for each video we work on (a "makeover"), proposes a new title, description with chapters, tags and thumbnail, based on a transcript of the video. A person at Linkfa reviews every proposal before it reaches you. We also send a channel audit and a monthly report.</p>',
		'<h2>2. What it never does</h2>',
		'<ul><li>We never upload, edit, delete or change the visibility of a video.</li><li>We never buy or fake views, likes, comments or subscribers.</li><li>We never change your channel page (banner, picture, description); we only advise.</li></ul>',
		'<h2>3. Your approval</h2>',
		'<p>Nothing on your channel changes until you reply <strong>YES</strong> with the proposal\'s code on WhatsApp (for example YES M12). Reply NO, or do not reply, and the video stays as it is. After a change is applied you can ask us to revert it at any time; we put back the exact previous title, description, tags and thumbnail.</p>',
		'<h2>4. Access</h2>',
		'<p>You give us access either by adding Linkfa as a Manager in YouTube Studio, or by signing in with Google and granting access to your channel. You can remove that access at any time (see the <a href="' . esc_url( $privacy_url ) . '">Channel Manager privacy notice</a>). By connecting a channel you also agree to the <a href="https://www.youtube.com/t/terms">YouTube Terms of Service</a>.</p>',
		'<h2>5. Plans and payment</h2>',
		'<ul>',
		'<li>The service is a plain monthly subscription for the work done that month, at the price shown on our Channel Manager page when you join. An optional one-off setup fee covers connecting your channel, the first full audit and your thumbnail brand kit.</li>',
		'<li>Each plan includes a number of makeovers a month. Unused makeovers do not carry over to the next month.</li>',
		'<li>We send a payment link (Paystack) or bank details each month. There is no interest, no late fee and no penalty of any kind.</li>',
		'<li>If a month is not paid, the work simply pauses. Changes already applied stay on your channel, and nothing is undone.</li>',
		'</ul>',
		'<h2>6. Free audit and free makeover</h2>',
		'<p>The free audit summary and one free makeover are offered once per channel. The free makeover follows the same approval rule: it is applied only after your YES, and can be reverted.</p>',
		'<h2>7. Cancelling</h2>',
		'<p>Tell us on WhatsApp in any month. Your subscription stops at the end of the month you have paid for. You receive a final report and a list of every change we made, and we delete your files as the privacy notice describes.</p>',
		'<h2>8. Refunds</h2>',
		'<p>The setup fee is not refundable once the first makeover has been applied. Monthly payments are handled under our <a href="' . esc_url( $refund_url ) . '">Refund Policy</a>, including how to ask for a refund and how quickly it is paid.</p>',
		'<h2>9. No guaranteed results</h2>',
		'<p>We promise the work, on time and approved by you. We do not promise views, subscribers, watch time, search ranking, click-through rate or monetisation, and YouTube may take some hours to show a new thumbnail everywhere. Every report says what could not be measured.</p>',
		'<h2>10. Your content</h2>',
		'<p>Your videos and your channel remain yours. The titles, descriptions, tags and thumbnails we make for you, once applied, are yours to keep and use, including after you leave.</p>',
		'<h2>11. Liability</h2>',
		'<p>We are not liable for indirect or consequential losses. Our total liability is limited to the fees you paid us in the three months before the claim.</p>',
		'<h2>12. Changes and law</h2>',
		'<p>We will tell you on WhatsApp before these terms change. These terms are governed by the laws of the Federal Republic of Nigeria; nothing in them limits rights you have under those laws.</p>',
		'<h2>Contact</h2>',
		$contact,
	);

	$privacy = array(
		'<p><em>Last updated: ' . $updated . '</em></p>',
		'<p>This notice explains what the Linkfa YouTube Channel Manager, a service of DaveBukar Technologies, does with data from your YouTube channel. It adds to our general <a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">Privacy Policy</a>. The service terms are on the <a href="' . esc_url( $terms_url ) . '">Channel Manager Terms</a> page.</p>',
		'<h2>YouTube API Services</h2>',
		'<p>The Channel Manager uses YouTube API Services. By connecting your channel you agree to be bound by the <a href="https://www.youtube.com/t/terms">YouTube Terms of Service</a>. Google\'s use of your data is described in the <a href="https://policies.google.com/privacy">Google Privacy Policy</a>. Our use and transfer of information received from Google APIs adheres to the <a href="https://developers.google.com/terms/api-services-user-data-policy">Google API Services User Data Policy</a>, including the Limited Use requirements.</p>',
		'<h2>What we access</h2>',
		'<ul>',
		'<li><strong>Channel and video information:</strong> your channel\'s name, ID, handle, description, keywords, banner and picture addresses, subscriber count, and for your videos their titles, descriptions, tags, chapters, thumbnails, length, publish date, privacy status and public view, like and comment counts.</li>',
		'<li><strong>Channel statistics</strong> (only if you sign in with Google and grant it): views, watch time and average view duration from YouTube Analytics.</li>',
		'<li><strong>Video audio:</strong> the audio of each video we make over, downloaded to create a transcript.</li>',
		'</ul>',
		'<h2>What we store</h2>',
		'<ul>',
		'<li>Transcripts, the audio files they were made from, and thumbnails (the old one and the proposed one) for each video we work on.</li>',
		'<li>A change log: each proposal, your answer, and the title, description, tags and thumbnail before and after every change, so any change can be reverted.</li>',
		'<li>Audits and monthly reports.</li>',
		'<li>Your access: either the Manager role you gave our Google account in YouTube Studio, or, if you signed in with Google, an OAuth refresh token for your channel. Tokens are stored on our own server and never shown to anyone.</li>',
		'<li>Your WhatsApp number, for proposals, approvals and reports, and your billing email and payment records.</li>',
		'</ul>',
		'<h2>How we use it</h2>',
		'<p>Only to provide the Channel Manager to you: to audit your channel, write proposals, apply the changes you approve, read them back to confirm them, revert changes you ask us to undo, and write your reports. Transcripts and video information are processed by AI services (speech-to-text and language models) that perform these tasks for us; they receive only what each task needs.</p>',
		'<h2>What we never do</h2>',
		'<p>We do not sell your data, use it for advertising, or share it with anyone except the service providers that perform the tasks above. We do not use your data to train AI models, and we do not combine it with other clients\' data.</p>',
		'<h2>How long we keep it, and deletion</h2>',
		'<ul>',
		'<li>While you are a client we keep what is listed above.</li>',
		'<li>When you leave, or when you revoke our access, we delete your audio files, transcripts, thumbnails, audits and stored access token within 30 days. We first send you the change log so you can undo anything by hand later.</li>',
		'<li>We keep payment records as the law requires. Data read from the YouTube API is not kept for longer than these rules allow.</li>',
		'<li>You can ask us to delete your data at any time by email or WhatsApp; we do so within 30 days.</li>',
		'</ul>',
		'<h2>How to remove our access</h2>',
		'<ul>',
		'<li>If you added Linkfa as a Manager: YouTube Studio → Settings → Permissions → remove the Linkfa account.</li>',
		'<li>If you signed in with Google: open <a href="https://myaccount.google.com/permissions">myaccount.google.com/permissions</a> and remove Linkfa\'s access.</li>',
		'</ul>',
		'<p>Removing access stops all work on your channel at once. Changes already applied stay on your channel.</p>',
		'<h2>Your rights</h2>',
		'<p>You may ask for a copy of, correction to, or deletion of your data, or object to its use. We handle requests in line with applicable data-protection law, including the Nigeria Data Protection Act 2023.</p>',
		'<h2>Contact</h2>',
		$contact,
	);

	return array(
		'channel-manager-terms'   => implode( '', $terms ),
		'channel-manager-privacy' => implode( '', $privacy ),
	);
}
