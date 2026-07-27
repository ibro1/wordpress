<?php
/**
 * Cobalt override of Ultimate Multisite's pricing-table "list" field
 * template (wp-ultimo/checkout/templates/pricing-table/list.php).
 *
 * Keeps every Vue binding and field name from the original template
 * (views/checkout/templates/pricing-table/list.php in the plugin) -
 * only the markup/classes around them change.
 */

defined( 'ABSPATH' ) || exit;

foreach ( $products as $index => &$_product ) {
	$_product = wu_get_product( $_product['id'] );

	$product_variation = $_product->get_as_variation( $duration, $duration_unit );

	if ( false === $product_variation && ! $force_different_durations ) {
		unset( $products[ $index ] );

		$_product = $product_variation;
	}
}

$dbt_dc_active   = function_exists( 'dual_currency_is_active' ) && dual_currency_is_active();
$dbt_dc_base     = function_exists( 'dual_currency_get_base_currency' ) ? dual_currency_get_base_currency() : wu_get_setting( 'currency_symbol', 'USD' );
$dbt_dc_alt      = function_exists( 'dual_currency_get_alt_currency' ) ? dual_currency_get_alt_currency() : 'NGN';
$dbt_dc_selected = function_exists( 'dual_currency_get_selected_currency' ) ? dual_currency_get_selected_currency() : $dbt_dc_base;

?>
<div class="">

	<?php if ( $dbt_dc_active ) : ?>
		<div class="dbt-currency-toggle">
			<a
				class="dbt-currency-toggle__option <?php echo esc_attr( $dbt_dc_selected === $dbt_dc_base ? 'is-active' : '' ); ?>"
				href="<?php echo esc_url( add_query_arg( 'currency', $dbt_dc_base ) ); ?>"
			><?php echo esc_html( $dbt_dc_base ); ?></a>
			<a
				class="dbt-currency-toggle__option <?php echo esc_attr( $dbt_dc_selected === $dbt_dc_alt ? 'is-active' : '' ); ?>"
				href="<?php echo esc_url( add_query_arg( 'currency', $dbt_dc_alt ) ); ?>"
			><?php echo esc_html( $dbt_dc_alt ); ?></a>
		</div>
	<?php endif; ?>

	<div class="dbt-plans <?php echo esc_attr( $classes ); ?>">

	<?php foreach ( $products as $product ) : ?>
		<?php /** @var \WP_Ultimo\Models\Product $product */ ?>

		<label
			id="wu-product-<?php echo esc_attr( $product->get_id() ); ?>"
			class="cell dbt-plan<?php echo $product->is_featured_plan() ? ' dbt-plan--featured' : ''; ?>"
			:class="( $parent.has_product( <?php echo esc_attr( $product->get_id() ); ?> ) || $parent.has_product( '<?php echo esc_attr( $product->get_slug() ); ?>' ) ) ? 'dbt-plan--selected' : ''"
		>

		<?php if ( $product->is_featured_plan() ) : ?>
			<span class="dbt-plan__badge"><?php esc_html_e( 'Recommended', 'davebukar-cobalt' ); ?></span>
		<?php endif; ?>

		<input v-if="<?php echo wp_json_encode( $product->get_pricing_type() !== 'contact_us' ); ?>" v-on:click="$parent.add_plan(<?php echo esc_attr( $product->get_id() ); ?>)" type="checkbox" name="products[]" value="<?php echo esc_attr( $product->get_id() ); ?>" class="screen-reader-text wu-hidden">

		<input v-else v-on:click="$parent.open_url('<?php echo esc_url( $product->get_contact_us_link() ); ?>', '_blank');" type="checkbox" name="products[]" value="<?php echo esc_attr( $product->get_id() ); ?>" class="screen-reader-text wu-hidden">

		<?php
		/*
		 * The product "description" is a single wp-admin textarea, so we
		 * treat its first non-empty line as the one-line tagline and every
		 * line after that as a feature to list with a checkmark - lets the
		 * merchant write a real feature list without needing a dedicated
		 * repeater field the plugin doesn't expose in wp-admin anyway
		 * (Product::get_feature_list()/set_feature_list() exist on the
		 * model but have no admin UI - REST-API-only).
		 */
		$dbt_desc_lines    = array_values( array_filter( array_map( 'trim', explode( "\n", (string) $product->get_description() ) ) ) );
		$dbt_tagline       = $dbt_desc_lines ? array_shift( $dbt_desc_lines ) : '';
		$dbt_feature_lines = $dbt_desc_lines;
		?>

		<div class="dbt-plan__body">
			<div class="dbt-plan__name"><?php echo esc_html( $product->get_name() ); ?></div>
			<?php if ( $dbt_tagline ) : ?>
				<div class="dbt-plan__desc"><?php echo wp_kses( $dbt_tagline, wu_kses_allowed_html() ); ?></div>
			<?php endif; ?>

			<?php if ( ! $product->is_pay_what_you_want() ) : ?>
				<div class="dbt-plan__price">
					<?php
					if ( $product->is_free() || $product->get_pricing_type() === 'contact_us' ) {
						echo esc_html( $product->get_formatted_amount() );
					} else {
						$dbt_amount = function_exists( 'dual_currency_convert_amount' )
							? dual_currency_convert_amount( $product->get_amount(), $dbt_dc_selected )
							: $product->get_amount();

						echo esc_html( wu_format_currency( $dbt_amount, $dbt_dc_selected ) );
					}
					?>
				</div>
				<div class="dbt-plan__period"><?php echo esc_html( $product->get_recurring_description() ); ?></div>
			<?php endif; ?>

			<?php if ( $dbt_feature_lines ) : ?>
				<ul class="dbt-plan__features">
					<?php foreach ( $dbt_feature_lines as $dbt_feature_line ) : ?>
						<li><?php echo wp_kses( $dbt_feature_line, wu_kses_allowed_html() ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( $product->is_pay_what_you_want() ) : ?>
			<div class="dbt-plan__pwyw" v-show="$parent.has_product(<?php echo esc_attr( $product->get_id() ); ?>)">

				<div class="dbt-plan__pwyw-input">
					<span><?php echo esc_html( function_exists( 'wu_get_currency_symbol' ) ? wu_get_currency_symbol( $dbt_dc_selected ) : $dbt_dc_selected ); ?></span>
					<input
						type="number"
						step="0.01"
						min="<?php echo esc_attr( $product->get_pwyw_minimum_amount() ); ?>"
						:value="$parent.get_custom_amount(<?php echo esc_attr( $product->get_id() ); ?>)"
						@input="$parent.set_custom_amount(<?php echo esc_attr( $product->get_id() ); ?>, $event.target.value)"
						@click.stop
						placeholder="<?php echo esc_attr( $product->get_pwyw_suggested_amount() ?: '0.00' ); ?>"
					>
				</div>

				<?php if ( $product->allows_customer_recurring_choice() ) : ?>
					<label class="dbt-plan__pwyw-recurring" @click.stop>
						<input
							type="checkbox"
							:checked="$parent.get_pwyw_recurring(<?php echo esc_attr( $product->get_id() ); ?>)"
							@change="$parent.set_pwyw_recurring(<?php echo esc_attr( $product->get_id() ); ?>, $event.target.checked)"
						>
						<?php esc_html_e( 'Make this a recurring payment', 'davebukar-cobalt' ); ?>
					</label>
				<?php elseif ( 'force_recurring' === $product->get_pwyw_recurring_mode() ) : ?>
					<span class="dbt-plan__pwyw-recurring"><?php esc_html_e( '(Recurring subscription)', 'davebukar-cobalt' ); ?></span>
				<?php endif; ?>

			</div>
		<?php endif; ?>

		<div class="dbt-plan__footer">
			<span class="dbt-plan__radio" aria-hidden="true"></span>
			<span
				v-if="$parent.has_product( <?php echo esc_attr( $product->get_id() ); ?> ) || $parent.has_product( '<?php echo esc_attr( $product->get_slug() ); ?>' )"
			><?php esc_html_e( 'Selected', 'davebukar-cobalt' ); ?></span>
			<span v-else><?php echo $product->get_pricing_type() === 'contact_us' ? esc_html__( 'Contact us', 'davebukar-cobalt' ) : esc_html__( 'Select plan', 'davebukar-cobalt' ); ?></span>
		</div>

		</label>

	<?php endforeach; ?>

	</div>

</div>
