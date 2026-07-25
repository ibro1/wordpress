<?php
/**
 * Demo Site Expiring Email Template - Customer
 *
 * @since 2.5.0
 */
defined('ABSPATH') || exit;
?>
<?php // translators: %s: Customer Name ?>
<p><?php printf(esc_html__('Hey %s,', 'ultimate-multisite'), '{{customer_name}}'); ?></p>
<?php // translators: %1$s: Site Title, %2$s: Time Remaining ?>
<p><?php echo wp_kses(sprintf(__('Your demo site <b>%1$s</b> will expire in <b>%2$s</b>.', 'ultimate-multisite'), '{{site_title}}', '{{demo_time_remaining}}'), 'pre_user_description'); ?></p>

<p><?php esc_html_e('After expiration, this demo site and all its content will be automatically deleted.', 'ultimate-multisite'); ?></p>

<p><?php esc_html_e('If you would like to keep your site and content, please upgrade to a paid plan before the demo expires.', 'ultimate-multisite'); ?></p>

<h2><b><?php esc_html_e('Demo Site Details', 'ultimate-multisite'); ?></b></h2>

<table cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
	<tbody>
	<tr>
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('Title', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fff; border: 1px solid #eee;">
		{{site_title}}
		</td>
	</tr>
	<tr>
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('URL', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fff; border: 1px solid #eee;">
		<a href="{{site_url}}" style="text-decoration: none;" rel="nofollow"><?php esc_html_e('Visit Site', 'ultimate-multisite'); ?></a>
		</td>
	</tr>
	<tr>
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('Admin Panel', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fff; border: 1px solid #eee;">
		<a href="{{site_admin_url}}" style="text-decoration: none;" rel="nofollow"><?php esc_html_e('Visit Admin Panel', 'ultimate-multisite'); ?></a>
		</td>
	</tr>
	<tr>
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('Expires At', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fff; border: 1px solid #eee;">
		{{demo_expires_at}}
		</td>
	</tr>
	<tr>
		<td style="text-align: right; width: 160px; padding: 8px; background: #f9f9f9; border: 1px solid #eee;"><b><?php esc_html_e('Time Remaining', 'ultimate-multisite'); ?></b></td>
		<td style="padding: 8px; background: #fff; border: 1px solid #eee;">
		{{demo_time_remaining}}
		</td>
	</tr>
	</tbody>
</table>
