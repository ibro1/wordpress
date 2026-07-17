<?php
/**
 * Front page template.
 */

defined( 'ABSPATH' ) || exit;
get_header();
tatyli_fresh_render_static_page( 'home' );
get_footer();
