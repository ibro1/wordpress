<?php
/**
 * Template Name: Channel Manager
 *
 * The YouTube Channel Manager sales page. Prices come from dbt_cm_offer() (the harness's offers
 * list, cached, with a last-good and a bundled fallback); nothing here hard-codes a price. Both
 * currencies are rendered; the switch shows one, NGN without JavaScript, USD with ?cur=usd.
 */

defined( 'ABSPATH' ) || exit;

$cm       = dbt_cm_offer();
$offer    = $cm['offer'];
$currency = isset( $_GET['cur'] ) && 'usd' === strtolower( sanitize_key( wp_unslash( $_GET['cur'] ) ) ) ? 'usd' : 'ngn'; // phpcs:ignore WordPress.Security.NonceVerification

// Short lines under each plan name, by tier id; a tier the list adds later simply has none.
$cm_for = array(
	'tune-up' => 'Fix the back catalogue, steadily.',
	'growth'  => 'The back catalogue, plus every new upload.',
	'studio'  => 'For channels that publish a lot.',
);
$cm_recommended = 'growth';

get_header();
?>
<!-- channel-manager prices: <?php echo esc_html( $cm['source'] ); ?> -->
<div class="cm" data-cur="<?php echo esc_attr( $currency ); ?>">
	<?php dbt_cm_draft_notice(); ?>

	<section class="cm-hero">
		<div class="cm-wrap cm-hero__grid">
			<div>
				<p class="cm-eyebrow">YouTube Channel Manager · AI employee</p>
				<h1>Your videos are good. Their titles aren't helping.</h1>
				<p class="cm-lede">Our AI employee listens to each of your videos, then rewrites its title, description, chapters, tags and thumbnail around what people actually search. A person at Linkfa checks every change, and nothing on your channel changes until you reply <strong>YES</strong> on WhatsApp.</p>
				<div class="cm-cta-row">
					<a class="btn btn--primary" href="<?php echo esc_url( dbt_cm_talk( 'hero' ) ); ?>">Get a free audit on WhatsApp</a>
					<a class="btn btn--outline" href="#plans">See plans</a>
				</div>
				<p class="cm-fine"><?php echo esc_html( $offer['freeHook'] ); ?> Pay only if you want more.</p>
			</div>
			<div class="cm-term" aria-label="Example of a proposed makeover">
				<div class="cm-term__bar"><span>channel-manager · proposal M12</span><b>example</b></div>
<pre><span class="t-m">$ cm listen  video=lecture-03   # 47 min, Hausa</span>
==> transcript… done   chapters found: 9
<span class="t-m">$ cm research "hada sallah"</span>
==> best phrase: <span class="t-a">hada sallah saboda rashin lafiya</span>
<span class="t-m">$ cm propose</span>
  title  <span class="t-old">Karatun Littafi part 3</span>
         <span class="t-new">Hada Sallah Saboda Rashin Lafiya | Darasi na 3</span>
  chapters 0:00 · 2:41 · 9:15 · 17:30 · …
  tags     12 · thumbnail: new
==> sent to you on WhatsApp: <span class="t-a">reply YES M12</span>
<span class="t-m">client: YES M12</span>
==> applied… read back… <span class="t-new">match</span>
==> logged. REVERT M12 any time.</pre>
			</div>
		</div>
	</section>

	<section class="cm-sec cm-sec--tight" id="what">
		<div class="cm-wrap">
			<div class="cm-head">
				<p class="cm-eyebrow">What it does</p>
				<h2>The packaging work, done properly, every month.</h2>
				<p>Most channels have dozens of videos that are worth watching but impossible to find. That's fixable, one video at a time.</p>
			</div>
			<div class="cm-bento">
				<div class="cm-card"><span class="cm-tag">Listen first</span><h3>It hears the whole video</h3><p>Every makeover starts from a transcript, in Hausa or English, so the title says what the video really teaches. No guessing from the old title.</p></div>
				<div class="cm-card"><span class="cm-tag">Titles</span><h3>Words people search</h3><p>It checks what viewers type into YouTube search, picks the phrase your video truly answers, and puts it first. No clickbait the video doesn't deliver.</p></div>
				<div class="cm-card"><span class="cm-tag">Descriptions</span><h3>Descriptions with chapters</h3><p>The first two lines sell the video in search. Then come the details, sources and your links, plus chapters from 0:00 so viewers jump straight to the part they need.</p></div>
				<div class="cm-card"><span class="cm-tag">Tags</span><h3>Tags that match how people spell</h3><p>The phrase, its variants and the common misspellings, plus your series name. Within YouTube's limits, checked before anything is sent.</p></div>
				<div class="cm-card"><span class="cm-tag">Thumbnails</span><h3>Thumbnails that add to the title</h3><p>Up to four bold words over the best frame. On Growth, some thumbnails are fully designed in <em>your</em> brand colours, not ours.</p></div>
				<div class="cm-card"><span class="cm-tag">Audit</span><h3>A ranked fix list</h3><p>Your channel page and latest 50 videos are checked every month. Weak videos with real reach come first, because that's where a fix pays.</p></div>
			</div>
		</div>
	</section>

	<section class="cm-sec cm-sec--alt" id="how">
		<div class="cm-wrap">
			<div class="cm-head">
				<p class="cm-eyebrow">How it works</p>
				<h2>You stay in charge of your channel.</h2>
			</div>
			<ol class="cm-steps">
				<li><span class="cm-n">01</span><h3>Connect</h3><p>Add Linkfa as a <strong>Manager</strong> in YouTube Studio. You never share your password, and you can remove us in one click.</p></li>
				<li><span class="cm-n">02</span><h3>We audit</h3><p>You get the ranked fix list, plus advice for your banner, avatar and channel description.</p></li>
				<li><span class="cm-n">03</span><h3>You approve</h3><p>Each makeover arrives on WhatsApp: old next to new, with a code.</p><span class="cm-chat">YES M12 · NO M12</span></li>
				<li><span class="cm-n">04</span><h3>Applied &amp; checked</h3><p>We apply it, read it back from YouTube to confirm, and log the old values. Want it undone? One message.</p></li>
				<li><span class="cm-n">05</span><h3>Monthly report</h3><p>What changed, how each video did before and after, what's next, and what YouTube doesn't let us measure.</p></li>
			</ol>
		</div>
	</section>

	<section class="cm-sec cm-band" id="rules">
		<div class="cm-wrap">
			<div class="cm-head">
				<p class="cm-eyebrow">The rules it can't break</p>
				<h2>Written into the software, not just the contract.</h2>
				<p>The AI employee only has tools for titles, descriptions, tags and thumbnails. Everything else is out of its reach.</p>
			</div>
			<ul class="cm-rules">
				<li><strong>Nothing without your YES</strong><span>A change to your channel needs our review and your reply on WhatsApp. Both are checked by the system.</span></li>
				<li><strong>Never deletes a video</strong><span>There is no delete tool. Not for old videos, not for anything.</span></li>
				<li><strong>Never changes visibility</strong><span>Public stays public, private stays private, scheduled stays scheduled.</span></li>
				<li><strong>Never fakes engagement</strong><span>No bought views, likes, comments or subscribers. That's how channels get terminated.</span></li>
				<li><strong>Never uploads for you</strong><span>Your videos are yours to publish. We work on what's already there.</span></li>
				<li><strong>Every change can be undone</strong><span>Old values are kept for every change, so a revert puts back exactly what was there.</span></li>
			</ul>
		</div>
	</section>

	<section class="cm-sec" id="who">
		<div class="cm-wrap">
			<div class="cm-head">
				<p class="cm-eyebrow">Who it's for</p>
				<h2>Channels with good videos and a back catalogue nobody can find.</h2>
			</div>
			<div class="cm-who">
				<div class="cm-card"><h3>Malamai, tafsir &amp; lecture channels</h3><p>Hours of teaching under titles like "Karatu part 7". We title each lesson for the question it answers.</p></div>
				<div class="cm-card"><h3>Schools &amp; Islamiyya</h3><p>Class recordings, events and graduations, packaged so parents and new students find them.</p></div>
				<div class="cm-card"><h3>Businesses with a channel</h3><p>Product demos, tours and explainers that should be bringing in customers from search.</p></div>
				<div class="cm-card"><h3>Podcasts &amp; creators</h3><p>Long episodes with chapters, so new viewers find the part they came for and stay.</p></div>
			</div>
		</div>
	</section>

	<section class="cm-sec cm-sec--alt" id="plans">
		<div class="cm-wrap">
			<div class="cm-plans-head">
				<div class="cm-head">
					<p class="cm-eyebrow">Plans</p>
					<h2>One price a month. Stop any month.</h2>
					<p>A "makeover" is one video's new title, description, chapters, tags and thumbnail, approved by you.</p>
				</div>
				<div class="cm-switch" role="group" aria-label="Currency">
					<a class="cm-switch__opt" data-set-cur="ngn" href="?cur=ngn#plans" aria-pressed="<?php echo 'ngn' === $currency ? 'true' : 'false'; ?>">₦ NGN</a>
					<a class="cm-switch__opt" data-set-cur="usd" href="?cur=usd#plans" aria-pressed="<?php echo 'usd' === $currency ? 'true' : 'false'; ?>">$ USD</a>
				</div>
			</div>
			<div class="cm-prices">
				<?php foreach ( $offer['tiers'] as $tier ) : ?>
					<?php
					$is_pick  = $cm_recommended === $tier['id'] && $tier['available'];
					$includes = array_values( array_filter( $tier['includes'], function ( $line ) {
						return ! preg_match( '/^coming (later|soon)\.?$/i', $line );
					} ) );
					?>
					<article class="cm-plan<?php echo $is_pick ? ' is-pick' : ''; ?><?php echo $tier['available'] ? '' : ' is-later'; ?>">
						<h3><?php echo esc_html( $tier['name'] ); ?>
							<?php if ( ! $tier['available'] ) : ?><span class="cm-badge cm-badge--grey">Coming later</span><?php elseif ( $is_pick ) : ?><span class="cm-badge">Recommended</span><?php endif; ?>
						</h3>
						<?php if ( isset( $cm_for[ $tier['id'] ] ) ) : ?><p class="cm-for"><?php echo esc_html( $cm_for[ $tier['id'] ] ); ?></p><?php endif; ?>
						<div class="cm-price"><?php echo dbt_cm_amount_html( $tier['ngn'], $tier['usd'], ' / month', $tier['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?></div>
						<ul>
							<?php foreach ( $includes as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
							<li>You approve each change on WhatsApp</li>
						</ul>
						<?php if ( $tier['available'] ) : ?>
							<a class="btn <?php echo $is_pick ? 'btn--primary' : 'btn--outline'; ?>" href="<?php echo esc_url( dbt_cm_talk( $tier['id'] ) ); ?>">Start with a free audit</a>
						<?php else : ?>
							<p class="cm-later">Not open yet. It opens once designed thumbnails for every video are ready.</p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
			<p class="cm-terms">
				<?php if ( null !== $offer['setup'] ) : ?>
					One-off setup fee per channel: <strong><?php echo dbt_cm_amount_html( $offer['setup']['ngn'], $offer['setup']['usd'], '', 'setup' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?></strong>, covering connection, the full audit and your thumbnail brand kit.
				<?php else : ?>
					No setup fee.
				<?php endif; ?>
				Pay monthly by card through Paystack, or by bank transfer. A plain monthly subscription: no contract, no interest and no late fees. If a month isn't paid, the work simply pauses, and everything already applied stays on your channel. Unused makeovers don't carry over.
				<a href="<?php echo esc_url( home_url( '/channel-manager-terms/' ) ); ?>">Terms</a> · <a href="<?php echo esc_url( home_url( '/channel-manager-privacy/' ) ); ?>">Privacy and YouTube data</a>
			</p>
		</div>
	</section>

	<section class="cm-sec" id="honest">
		<div class="cm-wrap cm-honest">
			<div class="cm-head cm-head--flush">
				<p class="cm-eyebrow">What we promise</p>
				<h2>The work, on time, and the truth about the results.</h2>
			</div>
			<div class="cm-callout">
				<p>We don't promise views, subscribers or rankings, and nobody honest can. We promise that every video we touch gets a title, description and thumbnail built from what it actually says, approved by you. Your report shows each video before and after, including what YouTube doesn't let us measure.</p>
			</div>
		</div>
	</section>

	<section class="cm-sec cm-sec--top0" id="faq">
		<div class="cm-wrap cm-faq">
			<div class="cm-head"><p class="cm-eyebrow">Questions</p><h2>Before you ask</h2></div>
			<details><summary>Do you need my YouTube password?</summary><p>No. You add Linkfa as a Manager in YouTube Studio (Settings → Permissions). You can remove us at any time, and we can never remove you.</p></details>
			<details><summary>Is it really AI? Who checks the work?</summary><p>An AI employee does the listening, research and drafting. Ibrahim, Linkfa's lead engineer, reviews every proposal before it reaches you, and nothing applies until you reply YES.</p></details>
			<details><summary>Which languages?</summary><p>Hausa and English today, including Hausa videos with an English section in the description. Ask us about other languages.</p></details>
			<details><summary>Will you upload, edit or delete my videos?</summary><p>No. We never upload, edit, delete or change who can see a video. If you want your long videos cut into short clips, that's our <a href="<?php echo esc_url( dbt_page_url( 'clips' ) ); ?>">Clips service</a>.</p></details>
			<details><summary>What if I don't like a change?</summary><p>Reply NO before it's applied. After it's applied, ask us to revert it and the old title, description, tags and thumbnail go back exactly as they were.</p></details>
			<details><summary>How fast is it?</summary><p>On Growth, a new upload gets its proposal within one working day. Once you reply YES, it's applied within a few hours. YouTube can take a few hours to show a new thumbnail everywhere.</p></details>
			<details><summary>What happens if I miss a payment?</summary><p>The work pauses until the month is paid. There is no late fee and no interest, and nothing we already applied is undone.</p></details>
			<details><summary>How do I stop?</summary><p>Tell us on WhatsApp. The subscription stops at the end of the month you've paid for. You get a final report and a list of every change we made, and we delete your video files from our systems.</p></details>
		</div>
	</section>

	<section class="cm-sec cm-final">
		<div class="cm-wrap">
			<p class="cm-eyebrow">Start free</p>
			<h2>Send your channel link. Get one video made over, free.</h2>
			<p class="cm-muted">You'll see the top fixes for your channel and a finished makeover to approve. It goes live only if you reply YES.</p>
			<div class="cm-cta-row cm-cta-row--center"><a class="btn btn--primary" href="<?php echo esc_url( dbt_cm_talk( 'footer' ) ); ?>">Get a free audit on WhatsApp</a></div>
		</div>
	</section>
</div>
<script>
(function () {
	var root = document.querySelector('.cm');
	if (!root) return;
	root.querySelectorAll('[data-set-cur]').forEach(function (opt) {
		opt.addEventListener('click', function (event) {
			event.preventDefault();
			var cur = opt.getAttribute('data-set-cur');
			root.setAttribute('data-cur', cur);
			root.querySelectorAll('[data-set-cur]').forEach(function (o) { o.setAttribute('aria-pressed', o === opt ? 'true' : 'false'); });
			try { var url = new URL(window.location.href); url.searchParams.set('cur', cur); history.replaceState(null, '', url); } catch (e) { /* the switch still works without the address update */ }
		});
	});
})();
</script>
<?php get_footer(); ?>
