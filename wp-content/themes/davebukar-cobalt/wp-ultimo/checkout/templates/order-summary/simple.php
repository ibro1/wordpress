<?php
/**
 * Cobalt override of Ultimate Multisite's order-summary "simple" field
 * template. Every v-for/v-show/{{ }} binding and wu_format_money() call is
 * preserved verbatim from the stock template
 * (views/checkout/templates/order-summary/simple.php) - only markup/classes
 * changed. This card becomes the sticky rail on desktop (see checkout.css,
 * grid placement on #wrapper-field-order_summary - no markup change needed
 * here for that).
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="wu-order-summary-content" class="dbt-summary">

	<div v-show="!order" class="dbt-summary__loading">
		<?php esc_html_e( 'Generating order summary…', 'davebukar-cobalt' ); ?>
	</div>

	<div v-if="order" v-cloak>

		<h3 class="dbt-summary__title"><?php esc_html_e( 'Order summary', 'davebukar-cobalt' ); ?></h3>

		<div v-if="order.line_items.length === 0" class="dbt-summary__empty">
			<?php esc_html_e( 'No products in shopping cart.', 'davebukar-cobalt' ); ?>
		</div>

		<ul class="dbt-summary__lines" v-if="order.line_items.length > 0">

			<li class="dbt-summary__line" v-for="line_item in order.line_items">

				<div class="dbt-summary__line-desc">

					<span v-show="line_item.recurring">
						<?php // translators: %s: name of the subscription ?>
						<?php printf( esc_html__( 'Subscription — %s', 'davebukar-cobalt' ), '{{ line_item.title }}' ); ?>
					</span>
					<span v-show="!line_item.recurring">{{ line_item.title }}</span>

					<button
						v-if="line_item.type == 'product'"
						type="button"
						class="dbt-summary__remove"
						v-on:click.prevent="remove_product( line_item.product_id, line_item.product_slug )"
					><?php esc_html_e( 'Remove', 'davebukar-cobalt' ); ?></button>

				</div>

				<div class="dbt-summary__line-amount">
					<span v-show="line_item.recurring">{{ wu_format_money( line_item.subtotal ) }} / {{ line_item.recurring_description }}</span>
					<span v-show="!line_item.recurring">{{ wu_format_money( line_item.subtotal ) }}</span>
				</div>

			</li>

		</ul>

		<dl class="dbt-summary__totals">

			<div class="dbt-summary__totals-row" v-if="order.totals.total_discounts">
				<dt><?php esc_html_e( 'Discounts', 'davebukar-cobalt' ); ?></dt>
				<dd>{{ wu_format_money( order.totals.total_discounts ) }}</dd>
			</div>

			<div class="dbt-summary__totals-row" v-if="order.totals.total_taxes">
				<dt><?php esc_html_e( 'Taxes', 'davebukar-cobalt' ); ?></dt>
				<dd>{{ wu_format_money( order.totals.total_taxes ) }}</dd>
			</div>

			<div class="dbt-summary__totals-row dbt-summary__totals-row--grand" v-show="order.has_trial">
				<dt><?php esc_html_e( "Today's total", 'davebukar-cobalt' ); ?></dt>
				<dd>{{ wu_format_money( 0 ) }}</dd>
			</div>

			<div class="dbt-summary__totals-row dbt-summary__totals-row--grand" v-show="!order.has_trial">
				<dt><?php esc_html_e( "Today's total", 'davebukar-cobalt' ); ?></dt>
				<dd>{{ wu_format_money( order.totals.total ) }}</dd>
			</div>

			<div class="dbt-summary__totals-row" v-if="order.has_trial">
				<dt>
					<?php // translators: %1$s relative date string ?>
					<?php printf( wp_kses_post( __( 'Total in %1$s — end of trial period', 'davebukar-cobalt' ) ), '{{ $moment.unix(order.dates.date_trial_end).format(`LL`) }}' ); ?>
				</dt>
				<dd>{{ wu_format_money( order.totals.total ) }}</dd>
			</div>

		</dl>

		<p class="dbt-summary__note" v-if="!order.has_trial && order.has_recurring">
			<?php // translators: %1$s order total, %2$s relative date string. ?>
			<?php printf( esc_html__( 'Next charge of %1$s on %2$s.', 'davebukar-cobalt' ), '{{ wu_format_money(order.totals.recurring.total) }}', '{{ $moment.unix(order.dates.date_next_charge).format(`LL`) }}' ); ?>
		</p>

		<p class="dbt-summary__note" v-if="order.totals.total_discounts < 0">
			<?php
			// translators: 1 is the discount name, 2 is the coupon code, 3 is the coupon amount, 4 is the discount total.
			printf( esc_html__( 'Discount applied: %1$s — %2$s (%3$s) %4$s', 'davebukar-cobalt' ), '{{ order.discount_code.name }}', '{{ order.discount_code.code }}', '{{ order.discount_code.discount_description }}', '{{ wu_format_money(-order.totals.total_discounts) }}' );
			?>
			<button type="button" class="dbt-summary__remove" v-on:click.prevent="discount_code = ''; toggle_discount_code = false">
				<?php esc_html_e( 'Remove', 'davebukar-cobalt' ); ?>
			</button>
		</p>

	</div>

</div>
