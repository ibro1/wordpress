<?php
/**
 * CPanel instructions view.
 *
 * @since 2.5.0
 */
defined('ABSPATH') || exit;
?>
<h1><?php esc_html_e('Instructions', 'ultimate-multisite'); ?></h1>

<p class="wu-text-lg wu-text-gray-600 wu-my-4 wu-mb-6">
	<?php esc_html_e('You\'ll need your', 'ultimate-multisite'); ?> <strong><?php esc_html_e('cPanel Username', 'ultimate-multisite'); ?></strong>, <strong><?php esc_html_e('API Token', 'ultimate-multisite'); ?></strong> <?php esc_html_e('(recommended)', 'ultimate-multisite'); ?> <?php esc_html_e('or', 'ultimate-multisite'); ?> <strong><?php esc_html_e('Password', 'ultimate-multisite'); ?></strong>, <?php esc_html_e('and', 'ultimate-multisite'); ?> <strong><?php esc_html_e('Host URL', 'ultimate-multisite'); ?></strong> <?php esc_html_e('to enable automatic domain mapping.', 'ultimate-multisite'); ?>
</p>

<div class="wu-bg-green-100 wu-border-l-4 wu-border-green-500 wu-p-4 wu-my-4">
	<p class="wu-text-sm wu-m-0">
		<strong><?php esc_html_e('Recommended:', 'ultimate-multisite'); ?></strong>
		<?php esc_html_e('Use an API Token instead of your password for better security. API tokens can be created, scoped, and revoked independently without changing your main password.', 'ultimate-multisite'); ?>
	</p>
</div>

<h3 class="wu-m-0 wu-py-4 wu-text-lg" id="creating-api-token">
	<?php esc_html_e('Creating an API Token (Recommended)', 'ultimate-multisite'); ?>
</h3>

<p class="wu-text-sm">
	<?php esc_html_e('API tokens provide secure, password-free authentication. Follow these steps to create one:', 'ultimate-multisite'); ?>
</p>

<ol class="wu-list-decimal wu-ml-6 wu-text-sm wu-space-y-2">
	<li><?php esc_html_e('Log in to your cPanel account', 'ultimate-multisite'); ?></li>
	<li><?php esc_html_e('Navigate to', 'ultimate-multisite'); ?> <strong><?php esc_html_e('Security', 'ultimate-multisite'); ?></strong> → <strong><?php esc_html_e('Manage API Tokens', 'ultimate-multisite'); ?></strong></li>
	<li><?php esc_html_e('Click', 'ultimate-multisite'); ?> <strong><?php esc_html_e('Create', 'ultimate-multisite'); ?></strong></li>
	<li><?php esc_html_e('Enter a name for the token (e.g., "Ultimate Multisite")', 'ultimate-multisite'); ?></li>
	<li><?php esc_html_e('Click', 'ultimate-multisite'); ?> <strong><?php esc_html_e('Create', 'ultimate-multisite'); ?></strong></li>
	<li><?php esc_html_e('Copy the generated token immediately - it won\'t be shown again!', 'ultimate-multisite'); ?></li>
</ol>

<div class="wu-bg-blue-100 wu-border-l-4 wu-border-blue-500 wu-p-4 wu-my-4">
	<p class="wu-text-sm wu-m-0">
		<strong><?php esc_html_e('Note:', 'ultimate-multisite'); ?></strong>
		<?php esc_html_e('The API Token option may not be available on all cPanel installations. If you don\'t see it, use password authentication instead.', 'ultimate-multisite'); ?>
	</p>
</div>

<div class="wu-bg-yellow-100 wu-border-l-4 wu-border-yellow-500 wu-p-4 wu-my-4">
	<p class="wu-text-sm wu-m-0">
		<strong><?php esc_html_e('Important:', 'ultimate-multisite'); ?></strong>
		<?php esc_html_e('Many hosting providers use SSO (Single Sign-On) to access cPanel. If you normally access cPanel through your hosting dashboard without entering separate credentials, you may need to contact your host to get direct cPanel login credentials or enable API access.', 'ultimate-multisite'); ?>
	</p>
</div>

<h3 class="wu-m-0 wu-py-4 wu-text-lg" id="finding-cpanel-access">
	<?php esc_html_e('Finding Your cPanel Access', 'ultimate-multisite'); ?>
</h3>

<p class="wu-text-sm">
	<strong><?php esc_html_e('Option 1: Direct URL Access', 'ultimate-multisite'); ?></strong>
</p>

<p class="wu-text-sm">
	<?php esc_html_e('Try accessing cPanel directly at:', 'ultimate-multisite'); ?>
</p>

<ul class="wu-list-disc wu-ml-6 wu-text-sm">
	<li><code>https://yourdomain.com:2083</code></li>
	<li><code>https://cpanel.yourdomain.com</code></li>
</ul>

<p class="wu-text-sm wu-mt-4">
	<strong><?php esc_html_e('Option 2: Through Your Hosting Dashboard', 'ultimate-multisite'); ?></strong>
</p>

<p class="wu-text-sm">
	<?php esc_html_e('Most hosting providers have a "cPanel" or "Control Panel" link in their customer portal.', 'ultimate-multisite'); ?>
</p>

<p class="wu-text-sm wu-mt-4">
	<strong><?php esc_html_e('Option 3: Check Your Welcome Email', 'ultimate-multisite'); ?></strong>
</p>

<p class="wu-text-sm">
	<?php esc_html_e('Your hosting provider typically sends cPanel credentials in your account setup email. Search your email for "cPanel" or "control panel".', 'ultimate-multisite'); ?>
</p>

<h3 class="wu-m-0 wu-py-4 wu-text-lg" id="getting-credentials">
	<?php esc_html_e('Getting Your Credentials', 'ultimate-multisite'); ?>
</h3>

<p class="wu-text-sm">
	<strong><?php esc_html_e('Username:', 'ultimate-multisite'); ?></strong>
</p>

<ul class="wu-list-disc wu-ml-6 wu-text-sm">
	<li><?php esc_html_e('Your cPanel username is usually shown when logged into cPanel (top right corner)', 'ultimate-multisite'); ?></li>
	<li><?php esc_html_e('Check your hosting welcome email for the username', 'ultimate-multisite'); ?></li>
	<li><?php esc_html_e('If you use SSO, contact your host to request direct cPanel credentials', 'ultimate-multisite'); ?></li>
</ul>

<p class="wu-text-sm wu-mt-4">
	<strong><?php esc_html_e('Password (Alternative to API Token):', 'ultimate-multisite'); ?></strong>
</p>

<ul class="wu-list-disc wu-ml-6 wu-text-sm">
	<li><?php esc_html_e('Only needed if you\'re not using an API token', 'ultimate-multisite'); ?></li>
	<li><?php esc_html_e('Some hosts allow you to set a cPanel password in their control panel', 'ultimate-multisite'); ?></li>
	<li><?php esc_html_e('Look for "cPanel Access" or "Technical Settings" in your hosting dashboard', 'ultimate-multisite'); ?></li>
</ul>

<p class="wu-text-sm wu-mt-4">
	<strong><?php esc_html_e('Host URL:', 'ultimate-multisite'); ?></strong>
</p>

<p class="wu-text-sm">
	<?php esc_html_e('Usually your primary domain or server hostname. Common formats:', 'ultimate-multisite'); ?>
</p>

<ul class="wu-list-disc wu-ml-6 wu-text-sm">
	<li><code>yourdomain.com</code></li>
	<li><code>server123.hostingprovider.com</code></li>
</ul>

<h3 class="wu-m-0 wu-py-4 wu-text-lg" id="popular-hosts">
	<?php esc_html_e('Popular cPanel Hosting Providers', 'ultimate-multisite'); ?>
</h3>

<div class="wu-bg-blue-100 wu-border-l-4 wu-border-blue-500 wu-p-4 wu-my-4">
	<p class="wu-text-sm wu-m-0">
		<?php esc_html_e('If you\'re hosted with one of these providers and need help finding your credentials:', 'ultimate-multisite'); ?>
	</p>
	<ul class="wu-list-disc wu-ml-6 wu-text-sm wu-mt-2 wu-mb-0">
		<li><strong><?php esc_html_e('Bluehost, HostGator, GoDaddy:', 'ultimate-multisite'); ?></strong> <?php esc_html_e('Look for "cPanel" in your hosting dashboard', 'ultimate-multisite'); ?></li>
		<li><strong><?php esc_html_e('SiteGround:', 'ultimate-multisite'); ?></strong> <?php esc_html_e('Use Site Tools or contact support for cPanel credentials', 'ultimate-multisite'); ?></li>
		<li><strong><?php esc_html_e('Hosting.com, InMotion:', 'ultimate-multisite'); ?></strong> <?php esc_html_e('Check your welcome email or client area', 'ultimate-multisite'); ?></li>
		<li><strong><?php esc_html_e('Others:', 'ultimate-multisite'); ?></strong> <?php esc_html_e('Contact your hosting provider\'s support', 'ultimate-multisite'); ?></li>
	</ul>
</div>

<h3 class="wu-m-0 wu-py-4 wu-text-lg" id="configure-integration">
	<?php esc_html_e('Configure the Integration', 'ultimate-multisite'); ?>
</h3>

<p class="wu-text-sm">
	<?php esc_html_e('In the next step, you will enter:', 'ultimate-multisite'); ?>
</p>

<h3 class="wu-m-0 wu-py-4 wu-text-lg" id="features">
	<?php esc_html_e('What This Integration Does', 'ultimate-multisite'); ?>
</h3>

<p class="wu-text-sm">
	<?php esc_html_e('Once configured, this integration will:', 'ultimate-multisite'); ?>
</p>

<ul class="wu-list-disc wu-ml-6 wu-text-sm wu-space-y-1">
	<li><?php esc_html_e('Automatically add custom domains to your cPanel account when mapped in Ultimate Multisite', 'ultimate-multisite'); ?></li>
	<li><?php esc_html_e('Automatically remove domains from cPanel when unmapped', 'ultimate-multisite'); ?></li>
	<li><?php esc_html_e('Enable automatic SSL certificate provisioning via AutoSSL', 'ultimate-multisite'); ?></li>
	<li><?php esc_html_e('Keep your server configuration in sync with your WordPress multisite network', 'ultimate-multisite'); ?></li>
</ul>

<h3 class="wu-m-0 wu-py-4 wu-text-lg" id="resources">
	<?php esc_html_e('Additional Resources', 'ultimate-multisite'); ?>
</h3>

<ul class="wu-list-disc wu-ml-6 wu-text-sm">
	<li>
		<a href="https://docs.cpanel.net/cpanel/security/manage-api-tokens-in-cpanel/" target="_blank" rel="noopener noreferrer" class="wu-text-blue-600 hover:wu-underline">
			<?php esc_html_e('cPanel API Tokens Guide', 'ultimate-multisite'); ?>
		</a>
	</li>
	<li>
		<a href="https://docs.cpanel.net/knowledge-base/accounts/how-to-log-in-to-your-cpanel-account/" target="_blank" rel="noopener noreferrer" class="wu-text-blue-600 hover:wu-underline">
			<?php esc_html_e('cPanel Login Guide', 'ultimate-multisite'); ?>
		</a>
	</li>
	<li>
		<a href="https://docs.cpanel.net/cpanel/domains/addon-domains/" target="_blank" rel="noopener noreferrer" class="wu-text-blue-600 hover:wu-underline">
			<?php esc_html_e('cPanel Domain Management', 'ultimate-multisite'); ?>
		</a>
	</li>
</ul>
