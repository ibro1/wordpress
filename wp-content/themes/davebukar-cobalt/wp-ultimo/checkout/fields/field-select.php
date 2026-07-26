<?php
/**
 * Cobalt override of the checkout select-field view (billing_country, and
 * any other 'select'-family field). Same .field/.field__control language as
 * field-text.php. Every value/binding preserved verbatim from the stock
 * template (views/checkout/fields/field-select.php).
 */

defined( 'ABSPATH' ) || exit;

$has_vue_name = isset( $field->html_attr['v-bind:name'] );

?>
<div class="field <?php echo esc_attr( trim( $field->wrapper_classes ) ); ?>" <?php $field->print_wrapper_html_attributes(); ?>>

	<?php
	wu_get_template(
		'checkout/fields/partials/field-title',
		array( 'field' => $field )
	);
	?>

	<div class="field__control">

		<select
			class="<?php echo esc_attr( trim( $field->classes ) ); ?>"
			id="field-<?php echo esc_attr( $field->id ); ?>"
			<?php if ( ! $has_vue_name ) : ?>
				name="<?php echo esc_attr( $field->id ); ?>"
			<?php endif; ?>
			value="<?php echo esc_attr( $field->value ); ?>"
			:aria-invalid="get_error( '<?php echo esc_attr( $field->id ); ?>' ) ? 'true' : 'false'"
			<?php $field->print_html_attributes(); ?>
		>

			<?php if ( $field->placeholder ) : ?>
				<option value="" <?php selected( '', (string) $field->value ); ?>><?php echo esc_html( $field->placeholder ); ?></option>
			<?php endif; ?>

			<?php foreach ( $field->options as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $field->value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>

			<?php if ( $field->options_template ) : ?>
				<?php echo wp_kses( $field->options_template, wu_kses_allowed_html() ); ?>
			<?php endif; ?>

		</select>

	</div>

	<?php
	wu_get_template(
		'checkout/fields/partials/field-errors',
		array( 'field' => $field )
	);
	?>

</div>
