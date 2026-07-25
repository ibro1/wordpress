<?php
/**
 * Select2 field view.
 *
 * @since 2.0.0
 */
defined('ABSPATH') || exit;
?>
<?php

$setting = wu_get_setting($field_slug);

$setting = is_array($setting) ? $setting : [];

$placeholder = $field['placeholder'] ?? '';

?>

<tr>
	<th scope="row"><label for="<?php echo esc_attr($field_slug); ?>"><?php echo esc_html($field['title']); ?></label> <?php wu_tooltip($field['tooltip']); ?> </th>
	<td>

	<select data-width="350px" multiple="multiple" placeholder="<?php echo esc_attr($placeholder); ?>"  class="wu-select" name="<?php echo esc_attr($field_slug); ?>[]" id="<?php echo esc_attr($field_slug); ?>">

		<?php // Render selected options first in saved order to preserve ordering. ?>
		<?php foreach ($setting as $selected_value) : ?>
			<?php if (isset($field['options'][ $selected_value ])) : ?>
		<option selected value="<?php echo esc_attr($selected_value); ?>"><?php echo esc_html($field['options'][ $selected_value ]); ?></option>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php // Then render unselected options. ?>
		<?php foreach ($field['options'] as $value => $option) : ?>
			<?php if (! in_array($value, $setting, true)) : ?>
		<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($option); ?></option>
			<?php endif; ?>
		<?php endforeach; ?>

	</select>

	<?php if ( ! empty($field['desc'])) : ?>
	<p class="description" id="<?php echo esc_attr($field_slug); ?>-desc">
		<?php echo esc_html($field['desc']); ?>
	</p>
	<?php endif; ?>

	</td>
</tr>
