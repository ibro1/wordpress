<?php
/**
 * Template Name: Clips That Earn
 *
 * The clips service pitched on money rather than reach: students, buyers
 * and sales. Written for people who have been burned by schemes, so it
 * says plainly what is paid for and promises no numbers. Prices come from
 * inc/clips-content.php, shared with template-clips.php.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$src       = ' (from the earn page)';
$trial_msg = 'Salam, I want the 3 free clips. Here is my video or product link: ';
$amounts   = array();
foreach ( dbt_clips_plans() as $plan ) {
	if ( $plan['amount'] ) {
		$amounts[ $plan['name'] ] = $plan['amount'];
	}
}
?>

<section class="hero hero--lite hero--single">
	<div class="hero__inner">
		<div class="hero__copy reveal">
			<p class="mono-label">CLIPS THAT BRING CUSTOMERS</p>
			<h1 class="hero__title">Short videos that bring you students, buyers and sales.</h1>
			<p class="hero__lede">People scroll TikTok, Facebook and WhatsApp status all day. We turn what you already have — your lectures, your classes, your products — into short clips that send those people to you. You pay for the work, monthly. Nothing else.</p>
			<div class="hero__actions">
				<a class="btn btn--primary" href="<?php echo esc_url( dbt_wa_link( $trial_msg . $src ) ); ?>" target="_blank" rel="noopener">Get 3 clips free on WhatsApp</a>
				<a class="btn btn--outline" href="#payback">Will it pay for itself?</a>
			</div>
			<p class="hero__fine">3 finished clips within 48 hours, free. See them before you spend anything.</p>
		</div>
	</div>
</section>

<section class="bento bento--detail reveal" aria-label="How clips bring money">
	<article class="cell span-1x1">
		<p class="cell__label">Islamiyya &amp; schools</p>
		<h3 class="cell__title">More students, more fees</h3>
		<p class="cell__body">Parents forward clips of your classes and graduations. Every forward is a family who now knows your school.</p>
	</article>
	<article class="cell span-1x1">
		<p class="cell__label">Shops &amp; sellers</p>
		<h3 class="cell__title">Buyers message you</h3>
		<p class="cell__body">Your products shown working, with your WhatsApp number on screen. A clip keeps selling long after you post it.</p>
	</article>
	<article class="cell span-1x1">
		<p class="cell__label">Malamai &amp; teachers</p>
		<h3 class="cell__title">Sell books and classes</h3>
		<p class="cell__body">One strong minute of your lecture, ending with where to buy your book or join your paid class.</p>
	</article>
	<article class="cell span-1x1">
		<p class="cell__label">Channels</p>
		<h3 class="cell__title">Grow toward being paid</h3>
		<p class="cell__body">YouTube pays creators in Nigeria once a channel qualifies. Regular Shorts are how most channels get there.</p>
	</article>
</section>

<section class="payback reveal" id="payback" aria-label="Will it pay for itself?">
	<div class="payback__inner">
		<div>
			<h2 class="plans__title">Will it pay for itself?</h2>
			<p class="payback__lede">Type what one new customer, student or sale brings you in a month. We’ll show how many the clips need to bring in to cover their cost.</p>
		</div>
		<div class="payback__calc" data-payback='<?php echo esc_attr( wp_json_encode( $amounts ) ); ?>'>
			<div class="field">
				<label for="payback-value">One customer brings me (₦)</label>
				<input id="payback-value" type="number" inputmode="numeric" min="1" step="500" value="10000">
			</div>
			<ul class="payback__out" aria-live="polite"></ul>
			<p class="payback__fine">This is arithmetic, not a promise. Nobody can promise you sales — anyone who does is selling something else.</p>
		</div>
	</div>
</section>

<section class="band reveal" aria-label="What this is, and what it is not">
	<div class="band__inner">
		<h2 class="band__title">What you are paying for</h2>
		<ol class="band__steps">
			<li class="band__step">
				<span class="band__step-no">IT IS</span>
				<h3 class="band__step-title">Work, done every month</h3>
				<p class="band__step-body">Finished clips you can see, approve and keep. You own every one of them.</p>
			</li>
			<li class="band__step">
				<span class="band__step-no">IT IS NOT</span>
				<h3 class="band__step-title">An investment</h3>
				<p class="band__step-body">No deposit, no returns, no “bring two people”. You pay for clips, like you pay a tailor for clothes.</p>
			</li>
			<li class="band__step">
				<span class="band__step-no">YOU CAN</span>
				<h3 class="band__step-title">Stop any month</h3>
				<p class="band__step-body">No contract. Try 3 clips free first, then pay month by month only while it works for you.</p>
			</li>
		</ol>
	</div>
</section>

<section class="plans reveal" id="plans" aria-label="Prices">
	<h2 class="plans__title">Prices</h2>
	<?php dbt_render_clips_plans( $src ); ?>
	<p class="work__note">Pay monthly by bank transfer. Clips are made with <a href="https://klipara.linkfa.de" target="_blank" rel="noopener">Klipara</a>, our own clipping engine, and checked by a person before you see them.</p>
</section>

<section class="cta reveal">
	<div class="cta__inner">
		<h2 class="cta__title">See 3 clips before you pay anything.</h2>
		<div class="cta__actions">
			<a class="btn btn--primary" href="<?php echo esc_url( dbt_wa_link( $trial_msg . $src ) ); ?>" target="_blank" rel="noopener">WhatsApp us</a>
			<a class="cta__email" href="tel:+<?php echo esc_attr( DBT_WHATSAPP ); ?>">+<?php echo esc_html( DBT_WHATSAPP ); ?></a>
		</div>
	</div>
</section>

<script>
( function () {
	var calc = document.querySelector( '[data-payback]' );
	if ( ! calc ) return;
	var plans = JSON.parse( calc.getAttribute( 'data-payback' ) );
	var input = calc.querySelector( 'input' );
	var out = calc.querySelector( '.payback__out' );
	var naira = new Intl.NumberFormat( 'en-NG' );
	function render() {
		var v = Math.floor( Number( input.value ) );
		out.textContent = '';
		if ( ! v || v < 1 ) {
			var li = document.createElement( 'li' );
			li.textContent = 'Enter an amount above to see the answer.';
			out.appendChild( li );
			return;
		}
		Object.keys( plans ).forEach( function ( name ) {
			var n = Math.ceil( plans[ name ] / v );
			var li = document.createElement( 'li' );
			var strong = document.createElement( 'strong' );
			strong.textContent = n + ( 1 === n ? ' customer' : ' customers' ) + ' a month';
			li.appendChild( document.createTextNode( name + ' (₦' + naira.format( plans[ name ] ) + '): ' ) );
			li.appendChild( strong );
			out.appendChild( li );
		} );
	}
	input.addEventListener( 'input', render );
	render();
} )();
</script>

<?php get_footer(); ?>
