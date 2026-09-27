<?php
/**
 * Shared content for the two clips pages: template-clips.php (reach, for
 * lecture channels) and template-earn.php (money, for sellers, schools and
 * channels that want customers). One plan list so the prices never drift.
 */

defined( 'ABSPATH' ) || exit;

function dbt_wa_link( $text ) {
	return 'https://wa.me/' . DBT_WHATSAPP . '?text=' . rawurlencode( $text );
}

/**
 * 'amount' is the monthly price in naira, used by the break-even
 * calculator; null means priced per job.
 */
function dbt_clips_plans() {
	return array(
		array(
			'name'   => 'Starter',
			'price'  => '₦50,000',
			'per'    => '/ month',
			'amount' => 50000,
			'items'  => array( '12 clips a month (3 a week)', 'Captions in Hausa or English', 'Vertical 9:16 for TikTok, Reels and Shorts', 'You approve each clip on WhatsApp' ),
			'msg'    => 'Salam, I am interested in the Starter plan (₦50,000/month).',
		),
		array(
			'name'     => 'Standard',
			'price'    => '₦100,000',
			'per'      => '/ month',
			'amount'   => 100000,
			'items'    => array( '30 clips a month (1 a day)', 'Captions in Hausa or English, or both', 'We post to TikTok, Facebook and YouTube Shorts for you', 'Monthly report: views and new followers' ),
			'msg'      => 'Salam, I am interested in the Standard plan (₦100,000/month).',
			'featured' => true,
		),
		array(
			'name'   => 'Ramadan & events',
			'price'  => 'Custom',
			'per'    => '',
			'amount' => null,
			'items'  => array( 'Daily tafsir or a full event, clipped the same day', 'Any number of clips', 'Priced per series' ),
			'msg'    => 'Salam, I want a quote for a Ramadan/event series.',
		),
	);
}

/**
 * Renders the plan cards. $source tags the WhatsApp message so a chat
 * shows which page it came from.
 */
function dbt_render_clips_plans( $source = '' ) {
	?>
	<div class="plans__grid">
		<?php foreach ( dbt_clips_plans() as $plan ) : ?>
			<article class="plan<?php echo empty( $plan['featured'] ) ? '' : ' plan--featured'; ?>">
				<p class="cell__label"><?php echo esc_html( $plan['name'] ); ?></p>
				<p class="plan__price"><?php echo esc_html( $plan['price'] ); ?> <span><?php echo esc_html( $plan['per'] ); ?></span></p>
				<ul class="plan__items">
					<?php foreach ( $plan['items'] as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<a class="btn <?php echo empty( $plan['featured'] ) ? 'btn--outline' : 'btn--primary'; ?>" href="<?php echo esc_url( dbt_wa_link( $plan['msg'] . $source ) ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
			</article>
		<?php endforeach; ?>
	</div>
	<?php
}
