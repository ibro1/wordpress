=== Auto Featured Image ===
Contributors: (your name)
Tags: featured image, automatic, background, batch, performance
Requires at least: 5.5
Tested up to: 6.1
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A high-performance WordPress solution to automatically assign featured images to posts that lack them, designed for very large websites.

== Description ==

The Auto Featured Image plugin is a powerful solution designed to automatically find and assign featured images to your posts. It is specifically engineered for websites with very large datasets (tens of thousands to millions of posts and images), utilizing asynchronous background processing via Action Scheduler to prevent server timeouts and maintain optimal site performance.

**Key Features:**

*   **Asynchronous Background Processing:** Never worry about server timeouts. All processing happens in small batches in the background.
*   **Real-time Progress Monitoring:** Watch the progress live from the admin dashboard.
*   **High-Performance:** Optimized database queries and batching logic designed for scalability.
*   **Configurable:** Choose which post types to scan, the image selection method, batch size, and processing interval.
*   **Comprehensive Logging:** See a detailed log of every action the plugin takes.

**IMPORTANT:** This plugin requires the [Action Scheduler](https://wordpress.org/plugins/action-scheduler/) plugin to be installed and activated.

== Installation ==

1.  Make sure you have the **Action Scheduler** plugin installed and activated.
2.  Upload the `auto-featured-image` folder to the `/wp-content/plugins/` directory.
3.  Activate the plugin through the 'Plugins' menu in WordPress.
4.  Go to **Tools -> Auto Featured Image** to configure the settings and start the process.

== Frequently Asked Questions ==

= Does this slow down my site? =

No. All the heavy lifting is done via background processing using Action Scheduler. This means it has a very low impact on front-end performance and won't cause your server to time out during processing.

= Where do I configure it? =

Navigate to **Tools -> Auto Featured Image** in your WordPress admin dashboard.

== Changelog ==

= 1.0.0 =
*   Initial release.