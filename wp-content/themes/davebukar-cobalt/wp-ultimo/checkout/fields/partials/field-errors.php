<?php
/**
 * Cobalt override of the field-errors partial - shared by every checkout
 * field type (text, password, payment-methods, etc). Reserves a stable
 * one-line height whether or not an error is showing, so an error appearing
 * never shifts the rest of the form down (interaction-and-states.md).
 */

defined( 'ABSPATH' ) || exit;

?>
<?php
/*
 * Deliberately no v-if/v-show on the <p> itself - either would remove it
 * from layout when there's no error, defeating the whole point of
 * reserving the line's height. The element always stays mounted; only its
 * content toggles, via a ternary so an absent error renders as an empty
 * string rather than the literal word "false".
 */
$field_id = esc_attr( $field->id );
?>
<p
	class="field__error"
	v-cloak
	v-html="get_error( '<?php echo $field_id; ?>' ) ? get_error( '<?php echo $field_id; ?>' ).message : ''"
	aria-live="polite"
></p>
