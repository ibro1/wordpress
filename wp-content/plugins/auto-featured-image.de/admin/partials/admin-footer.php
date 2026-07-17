<?php
/**
 * Admin Footer Template
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

    </div> <!-- .auto-featured-image-content -->
    
    <div class="auto-featured-image-footer">
        <p class="auto-featured-image-version">
            <?php
            printf(
                /* translators: %s: Plugin version */
                esc_html__( 'Auto Featured Image v%s', 'auto-featured-image' ),
                esc_html( AUTO_FEATURED_IMAGE_VERSION )
            );
            ?>
        </p>
    </div>
    
</div> <!-- .wrap -->
