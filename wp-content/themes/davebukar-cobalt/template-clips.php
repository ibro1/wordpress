<?php
/**
 * Template Name: Clips Service
 *
 * Done-for-you short clips for lecture, tafsir and podcast channels,
 * cut with Klipara. Sold over WhatsApp, so every CTA opens a chat.
 * Hausa lines are kept short and plain; English carries the detail.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$wa = function ( $text ) {
	return 'https://wa.me/' . DBT_WHATSAPP . '?text=' . rawurlencode( $text );
};
$trial_msg = 'Salam, I want the 3 free clips. Here is my lecture/video link: ';

$plans = array(
	array(
		'name'  => 'Starter',
		'price' => '₦50,000',
		'per'   => '/ month',
		'items' => array( '12 clips a month (3 a week)', 'Captions in Hausa or English', 'Vertical 9:16 for TikTok, Reels and Shorts', 'You approve each clip on WhatsApp' ),
		'msg'   => 'Salam, I am interested in the Starter plan (₦50,000/month).',
	),
	array(
		'name'  => 'Standard',
		'price' => '₦100,000',
		'per'   => '/ month',
		'items' => array( '30 clips a month (1 a day)', 'Captions in Hausa or English, or both', 'We post to TikTok, Facebook and YouTube Shorts for you', 'Monthly report: views and new followers' ),
		'msg'   => 'Salam, I am interested in the Standard plan (₦100,000/month).',
		'featured' => true,
	),
	array(
		'name'  => 'Ramadan & events',
		'price' => 'Custom',
		'per'   => '',
		'items' => array( 'Daily tafsir or a full event, clipped the same day', 'Any number of clips', 'Priced per series' ),
		'msg'   => 'Salam, I want a quote for a Ramadan/event series.',
	),
);
?>

<section class="hero hero--lite hero--single">
	<div class="hero__inner">
		<div class="hero__copy reveal">
			<p class="mono-label">SHORT CLIPS · GAJERUN BIDIYO</p>
			<h1 class="hero__title">Your long lectures, turned into short clips people share.</h1>
			<p class="hero__lede" lang="ha">Daga dogon karatu zuwa gajerun bidiyo.</p>
			<p class="hero__lede">You already record hours of tafsir, lectures and podcasts. We cut the strongest moments into captioned vertical clips for TikTok, Facebook Reels and YouTube Shorts — every month, without you editing anything.</p>
			<div class="hero__actions">
				<a class="btn btn--primary" href="<?php echo esc_url( $wa( $trial_msg ) ); ?>" target="_blank" rel="noopener">Get 3 clips free on WhatsApp</a>
				<a class="btn btn--outline" href="#plans">See prices</a>
			</div>
			<p class="hero__fine">Send one video link. You get 3 finished clips within 48 hours, free. Pay only if you want more.</p>
		</div>
	</div>
</section>

<section class="bento bento--detail reveal" aria-label="Who it is for">
	<article class="cell span-1x1">
		<h3 class="cell__title">Malamai &amp; tafsir channels</h3>
		<p class="cell__body">Reach people who will never sit through a two-hour recording but will watch — and forward — one minute of it.</p>
	</article>
	<article class="cell span-1x1">
		<h3 class="cell__title">Islamiyya &amp; schools</h3>
		<p class="cell__body">Turn class recordings and graduations into clips parents share, and that bring in new students.</p>
	</article>
	<article class="cell span-1x1">
		<h3 class="cell__title">Podcasts &amp; interviews</h3>
		<p class="cell__body">Hausa, English or both. Hausa speech can carry English captions, so the clip travels further.</p>
	</article>
	<article class="cell span-1x1">
		<h3 class="cell__title">Businesses that go live</h3>
		<p class="cell__body">Product demos and live sales cut into short adverts you can post or boost.</p>
	</article>
</section>

<section class="band reveal" aria-label="How it works">
	<div class="band__inner">
		<h2 class="band__title">How it works</h2>
		<ol class="band__steps band__steps--4">
			<li class="band__step">
				<span class="band__step-no">01</span>
				<h3 class="band__step-title">Send the link</h3>
				<p class="band__step-body">A YouTube link, a Facebook video, or the file itself — straight on WhatsApp.</p>
			</li>
			<li class="band__step">
				<span class="band__step-no">02</span>
				<h3 class="band__step-title">We find the moments</h3>
				<p class="band__step-body">Our own engine, <a href="https://klipara.linkfa.de" target="_blank" rel="noopener">Klipara</a>, picks the parts that stand on their own. A person checks every one.</p>
			</li>
			<li class="band__step">
				<span class="band__step-no">03</span>
				<h3 class="band__step-title">You approve</h3>
				<p class="band__step-body">Clips arrive on WhatsApp. Nothing is posted until you say yes — your words are never cut out of context.</p>
			</li>
			<li class="band__step">
				<span class="band__step-no">04</span>
				<h3 class="band__step-title">We post, or you do</h3>
				<p class="band__step-body">On your pages, at the times your audience is online.</p>
			</li>
		</ol>
	</div>
</section>

<section class="plans reveal" id="plans" aria-label="Prices">
	<h2 class="plans__title">Prices</h2>
	<div class="plans__grid">
		<?php foreach ( $plans as $plan ) : ?>
			<article class="plan<?php echo empty( $plan['featured'] ) ? '' : ' plan--featured'; ?>">
				<p class="cell__label"><?php echo esc_html( $plan['name'] ); ?></p>
				<p class="plan__price"><?php echo esc_html( $plan['price'] ); ?> <span><?php echo esc_html( $plan['per'] ); ?></span></p>
				<ul class="plan__items">
					<?php foreach ( $plan['items'] as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<a class="btn <?php echo empty( $plan['featured'] ) ? 'btn--outline' : 'btn--primary'; ?>" href="<?php echo esc_url( $wa( $plan['msg'] ) ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
			</article>
		<?php endforeach; ?>
	</div>
	<p class="work__note">No contract. Pay monthly by bank transfer; stop any month. You keep full rights to every clip.</p>
</section>

<section class="cta reveal">
	<div class="cta__inner">
		<h2 class="cta__title">Start with 3 free clips.</h2>
		<div class="cta__actions">
			<a class="btn btn--primary" href="<?php echo esc_url( $wa( $trial_msg ) ); ?>" target="_blank" rel="noopener">WhatsApp us</a>
			<a class="cta__email" href="tel:+<?php echo esc_attr( DBT_WHATSAPP ); ?>">+<?php echo esc_html( DBT_WHATSAPP ); ?></a>
		</div>
	</div>
</section>

<?php get_footer(); ?>
