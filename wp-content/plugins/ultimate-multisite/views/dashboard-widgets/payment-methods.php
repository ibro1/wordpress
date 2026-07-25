<?php
/**
 * Payment Methods
 *
 * @since 2.5.0
 */
defined('ABSPATH') || exit;

?>
<div class="wu-styling <?php echo esc_attr($className ?? ''); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase ?>">

	<div class="<?php echo esc_attr(wu_env_picker('', 'wu-widget-inset')); ?>">

		<!-- Title Element -->
		<?php if ( ! empty($title)) : ?>

			<div class="wu-p-4 wu-flex wu-items-center <?php echo esc_attr(wu_env_picker('', 'wu-bg-gray-100')); ?>">

				<h3 class="wu-m-0 <?php echo esc_attr(wu_env_picker('', 'wu-widget-title')); ?>">
					<?php echo esc_html($title); ?>
				</h3>

			</div>

		<?php endif; ?>

		<div class="wu-p-4">

			<?php if ( ! $membership) : ?>

				<p class="wu-text-gray-600 wu-m-0">
					<?php esc_html_e('No active membership.', 'ultimate-multisite'); ?>
				</p>

			<?php elseif ($payment_info && ! empty($payment_info['brand'])) : ?>

				<div class="wu-flex wu-items-center wu-justify-between">

					<div>
						<span class="wu-font-bold"><?php echo esc_html($payment_info['brand']); ?></span>
						<?php if ( ! empty($payment_info['last4'])) : ?>
							<span class="wu-text-gray-600">
								<?php
								// translators: %s: Last 4 digits of card number.
								printf(esc_html__('ending in %s', 'ultimate-multisite'), esc_html($payment_info['last4']));
								?>
							</span>
						<?php endif; ?>
					</div>

					<?php if ($change_url) : ?>
						<a href="<?php echo esc_url($change_url); ?>" class="wu-no-underline button button-primary">
							<?php esc_html_e('Change', 'ultimate-multisite'); ?>
						</a>
					<?php endif; ?>

				</div>

			<?php elseif ($gateway_display) : ?>

				<div class="wu-flex wu-items-center wu-justify-between">

					<span class="wu-text-gray-600">
						<?php
						// translators: %s: Payment gateway name.
						printf(esc_html__('Paid via %s', 'ultimate-multisite'), esc_html($gateway_display));
						?>
					</span>

					<?php if ($change_url) : ?>
						<a href="<?php echo esc_url($change_url); ?>" class="wu-no-underline button button-primary">
							<?php esc_html_e('Change', 'ultimate-multisite'); ?>
						</a>
					<?php endif; ?>

				</div>

			<?php else : ?>

				<p class="wu-text-gray-600 wu-m-0">
					<?php esc_html_e('No payment method on file.', 'ultimate-multisite'); ?>
				</p>

			<?php endif; ?>

		</div>

	</div>

</div>
