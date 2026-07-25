<?php
/**
 * Membership Expired - Customer Email Template
 *
 * @since 2.5.0
 */
defined('ABSPATH') || exit;
?>
<?php // translators: %s: Customer Name ?>
<p><?php printf(esc_html__('Hey %s,', 'ultimate-multisite'), '{{customer_name}}'); ?></p>

<p><?php esc_html_e('Your membership has expired. If this was unexpected, it may be due to a failed payment that was not resolved during the grace period.', 'ultimate-multisite'); ?></p>

<p><?php esc_html_e('You can re-subscribe or contact support if you need any assistance.', 'ultimate-multisite'); ?></p>

<h2><b><?php esc_html_e('Membership', 'ultimate-multisite'); ?></b></h2>

<table cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
	<tbody>
	<tr>
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('Amount', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fff; border: 1px solid #eee;">
		{{membership_description}}
		</td>
	</tr>
	<tr>
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('Reference Code', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fdfdfd; border: 1px solid #eee;">
		{{membership_reference_code}}
		</td>
	</tr>
	<tr>
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('Expiration', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fff; border: 1px solid #eee;">{{membership_date_expiration}}</td>
	</tr>
	</tbody>
</table>
