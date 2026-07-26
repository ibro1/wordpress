<?php
/**
 * Cobalt override of the checkout password-field view (password,
 * password_conf). Same .field/.field__control language as field-text.php,
 * keeping the plugin's own working show/hide toggle button and strength
 * meter - restyled, not rebuilt.
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="field <?php echo esc_attr( trim( $field->wrapper_classes ) ); ?>" <?php $field->print_wrapper_html_attributes(); ?>>

	<?php
	wu_get_template(
		'checkout/fields/partials/field-title',
		array( 'field' => $field )
	);
	?>

	<div class="field__control">
		<input
			class="<?php echo esc_attr( trim( $field->classes ) ); ?>"
			id="field-<?php echo esc_attr( $field->id ); ?>"
			name="<?php echo esc_attr( $field->id ); ?>"
			type="<?php echo esc_attr( $field->type ); ?>"
			placeholder="<?php echo esc_attr( $field->placeholder ); ?>"
			value="<?php echo esc_attr( $field->value ); ?>"
			:aria-invalid="get_error( '<?php echo esc_attr( $field->id ); ?>' ) ? 'true' : 'false'"
			<?php $field->print_html_attributes(); ?>
		>
		<button
			type="button"
			class="field__pwd-toggle wu-pwd-toggle hide-if-no-js"
			data-toggle="0"
			aria-label="<?php esc_attr_e( 'Show password', 'davebukar-cobalt' ); ?>"
		>
			<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
		</button>
	</div>

	<?php if ( $field->meter ) : ?>
		<div class="field__pwd-meter">
			<span id="pass-strength-result" class="field__pwd-meter-label"><?php esc_html_e( 'Strength Meter', 'davebukar-cobalt' ); ?></span>
		</div>
	<?php endif; ?>

	<?php
	wu_get_template(
		'checkout/fields/partials/field-errors',
		array( 'field' => $field )
	);
	?>

</div>
