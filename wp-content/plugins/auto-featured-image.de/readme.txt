=== Auto Featured Image ===
Contributors: (your-username)
Tags: featured image, automation, performance, bulk processing, images
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically assign featured images to posts that lack them using high-performance background processing.

== Description ==

Auto Featured Image is a high-performance WordPress plugin designed to automatically assign featured images to posts that lack them. The plugin is specifically engineered for websites with very large datasets (tens of thousands to millions of posts and images), utilizing asynchronous background processing to prevent server timeouts and maintain optimal site performance.

= Key Features =

* **Asynchronous Background Processing**: Uses Action Scheduler for reliable background job processing
* **Scalable Architecture**: Handles millions of posts and images efficiently without server strain
* **Real-time Progress Monitoring**: Admin dashboard with live progress updates and statistics
* **Intelligent Image Selection**: Multiple algorithms for optimal image assignment based on content
* **Performance Optimized**: Configurable batch processing to prevent memory and timeout issues
* **Comprehensive Error Handling**: Built-in error recovery and detailed logging system
* **Security First**: Follows WordPress security best practices with proper validation and sanitization

= Technical Specifications =

* Minimum WordPress Version: 5.0
* PHP Version: 7.4 or higher
* Dependencies: Action Scheduler (bundled with plugin)
* Database: Custom tables for efficient job queue and progress tracking
* Memory: Optimized for low memory usage during large-scale processing
* Performance: Designed for high-volume processing without server timeouts

= Use Cases =

* Bulk assignment of featured images for existing content
* Automated featured image assignment for imported posts
* Content migration with automatic image assignment
* Large-scale content management and optimization
* SEO improvement through consistent featured image presence

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/auto-featured-image` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to Tools > Auto Featured Image to configure and start processing.
4. Configure your settings and start the automated processing.

== Frequently Asked Questions ==

= Will this plugin slow down my website? =

No, the plugin uses asynchronous background processing to ensure your website performance is not affected during image assignment operations.

= How many posts can this plugin handle? =

The plugin is designed to handle millions of posts efficiently through its batch processing architecture and optimized database operations.

= Can I stop the processing once it starts? =

Yes, you can pause, resume, or stop the processing at any time through the admin dashboard.

= What happens if the process is interrupted? =

The plugin includes comprehensive error recovery mechanisms and will resume processing from where it left off.

== Screenshots ==

1. Admin dashboard showing processing progress and statistics
2. Settings configuration panel
3. Job queue management interface
4. Progress monitoring with real-time updates

== Changelog ==

= 1.0.0 =
* Initial release
* Asynchronous background processing with Action Scheduler
* Real-time progress monitoring dashboard
* Intelligent image selection algorithms
* Comprehensive error handling and logging
* Performance optimization for large datasets

== Upgrade Notice ==

= 1.0.0 =
Initial release of Auto Featured Image plugin with high-performance background processing capabilities.
