<?php
/**
 * Cobalt override of the checkout text-field view - covers email, username,
 * site_title, site_url, and any other 'text'-family field. Reuses the same
 * .field/.field__control/.field__glyph pattern already built for the
 * Book-a-call lead form (header.php), so the checkout doesn't invent a
 * second input language for the same site.
 *
 * Every value/binding below is preserved verbatim from the stock template
 * (views/checkout/fields/field-text.php) - only markup/classes changed.
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

	<div class="field__control<?php echo ( $field->prefix || $field->suffix ) ? ' field__control--grouped' : ''; ?>">

		<?php if ( $field->prefix ) : ?>
			<div class="field__addon field__addon--prefix" <?php wu_print_html_attributes( $field->prefix_html_attr ?? array() ); ?>>
				<?php echo wp_kses( $field->prefix, wu_kses_allowed_html() ); ?>
			</div>
		<?php endif; ?>

		<input
			class="<?php echo esc_attr( trim( $field->classes ) ); ?>"
			id="field-<?php echo esc_attr( $field->id ); ?>"
			<?php if ( ! $has_vue_name ) : ?>
				name="<?php echo esc_attr( $field->id ); ?>"
			<?php endif; ?>
			type="<?php echo esc_attr( $field->type ); ?>"
			placeholder="<?php echo esc_attr( $field->placeholder ); ?>"
			value="<?php echo esc_attr( $field->value ); ?>"
			:aria-invalid="get_error( '<?php echo esc_attr( $field->id ); ?>' ) ? 'true' : 'false'"
			<?php $field->print_html_attributes(); ?>
		>

		<?php if ( $field->suffix ) : ?>
			<div class="field__addon field__addon--suffix" <?php wu_print_html_attributes( $field->suffix_html_attr ?? array() ); ?>>
				<?php echo wp_kses( $field->suffix, wu_kses_allowed_html() ); ?>
			</div>
		<?php endif; ?>

		<span class="field__glyph" aria-hidden="true"></span>

	</div>

	<?php
	wu_get_template(
		'checkout/fields/partials/field-errors',
		array( 'field' => $field )
	);
	?>

</div>
