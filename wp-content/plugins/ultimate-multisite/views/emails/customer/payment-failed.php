<?php
/**
 * Payment Failed - Customer Email Template
 *
 * @since 2.5.0
 */
defined('ABSPATH') || exit;
?>
<?php // translators: %s: Customer Name ?>
<p><?php printf(esc_html__('Hey %s,', 'ultimate-multisite'), '{{customer_name}}'); ?></p>

<p><?php esc_html_e('We were unable to process your recent payment. This may be due to an expired card, insufficient funds, or a temporary issue with your payment provider.', 'ultimate-multisite'); ?></p>

<p><?php esc_html_e('Please update your payment method to avoid any interruption to your service.', 'ultimate-multisite'); ?></p>

<p><a href="{{membership_manage_url}}" style="text-decoration: none;" rel="nofollow"><?php esc_html_e('Update Payment Method', 'ultimate-multisite'); ?></a></p>

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
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('Expiration', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fff; border: 1px solid #eee;">{{membership_date_expiration}}</td>
	</tr>
	<tr>
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('Payment Gateway', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fff; border: 1px solid #eee;">{{payment_gateway}}</td>
	</tr>
	</tbody>
</table>
