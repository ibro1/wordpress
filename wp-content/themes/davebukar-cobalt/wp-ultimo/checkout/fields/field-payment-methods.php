<?php
/**
 * Cobalt override of Ultimate Multisite's payment-methods field view
 * (wp-ultimo/checkout/fields/field-payment-methods.php). Keeps the same
 * gateway inputs/bindings as the original template - restyled wrapper only.
 */

defined( 'ABSPATH' ) || exit;

$active_gateways = wu_get_active_gateway_as_options();

?>
<div class="<?php echo esc_attr( trim( $field->wrapper_classes ) ); ?>" v-cloak v-show="order && order.should_collect_payment" <?php $field->print_wrapper_html_attributes(); ?>>

	<?php
	wu_get_template(
		'checkout/fields/partials/field-title',
		array( 'field' => $field )
	);
	?>

	<div class="dbt-payment-methods">

	<?php foreach ( $active_gateways as $option_value => $option_name ) : ?>

		<?php if ( count( $active_gateways ) === 1 ) : ?>

			<input
					id="field-gateway"
					type="hidden"
					name="gateway"
					value="<?php echo esc_attr( $option_value ); ?>"
					v-model="gateway"
				<?php $field->print_html_attributes(); ?>
			>

			<?php if ( ! empty( $option_name ) ) : ?>
				<div class="dbt-payment-badge">
					<?php echo wp_kses_post( $option_name ); ?>
				</div>
			<?php endif; ?>

		<?php else : ?>

			<label class="dbt-payment-method" for="field-<?php echo esc_attr( $field->id ); ?>-<?php echo esc_attr( $option_value ); ?>">

				<input
						id="field-<?php echo esc_attr( $field->id ); ?>-<?php echo esc_attr( $option_value ); ?>"
						type="radio"
						name="gateway"
						value="<?php echo esc_attr( $option_value ); ?>"
						v-model="gateway"
						class="<?php echo esc_attr( trim( $field->classes ) ); ?>"
					<?php $field->print_html_attributes(); ?>
					<?php checked( (string) $field->value === (string) $option_value, true ); ?>
				>

				<?php echo wp_kses_post( $option_name ); ?>

			</label>

		<?php endif; ?>

	<?php endforeach; ?>

	</div>

	<?php
	wu_get_template(
		'checkout/fields/partials/field-errors',
		array( 'field' => $field )
	);

	do_action( 'wu_checkout_gateway_fields' );
	?>

</div>
