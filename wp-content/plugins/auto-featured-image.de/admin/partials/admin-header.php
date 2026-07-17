<?php
/**
 * Admin Header Template
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_page = $_GET['page'] ?? '';
$page_title = $this->admin_pages[ str_replace( 'auto-featured-image-', '', $current_page ) ]['title'] ?? __( 'Auto Featured Image', 'auto-featured-image' );
?>

<div class="wrap auto-featured-image-admin">
    <h1>
        <span class="dashicons dashicons-format-image"></span>
        <?php echo esc_html( $page_title ); ?>
    </h1>

    <?php
    // Display admin notices
    settings_errors();
    ?>

    <div class="auto-featured-image-nav-tabs">
        <?php
        $current_tab = $_GET['tab'] ?? 'overview';
        $tabs = $this->get_page_tabs( $current_page );
        
        if ( ! empty( $tabs ) ) :
            foreach ( $tabs as $tab_key => $tab_data ) :
                $active_class = ( $current_tab === $tab_key ) ? ' nav-tab-active' : '';
                $tab_url = add_query_arg( 'tab', $tab_key );
                ?>
                <a href="<?php echo esc_url( $tab_url ); ?>" 
                   class="nav-tab<?php echo esc_attr( $active_class ); ?>"
                   data-tab="<?php echo esc_attr( $tab_key ); ?>"
                   data-target="#tab-<?php echo esc_attr( $tab_key ); ?>">
                    <?php echo esc_html( $tab_data['title'] ); ?>
                </a>
                <?php
            endforeach;
        endif;
        ?>
    </div>

    <div class="auto-featured-image-content">
        <?php
        // Status bar for real-time updates
        if ( in_array( $current_page, array( 'auto-featured-image-dashboard', 'auto-featured-image-queue-monitor' ) ) ) :
            ?>
            <div class="auto-featured-image-status-bar">
                <div class="auto-featured-image-status status-idle">
                    <span class="status-dot"></span>
                    <span class="status-text"><?php esc_html_e( 'Idle', 'auto-featured-image' ); ?></span>
                </div>
                <div class="auto-featured-image-last-update">
                    <?php esc_html_e( 'Last updated:', 'auto-featured-image' ); ?>
                    <span class="timestamp"><?php echo esc_html( current_time( 'H:i:s' ) ); ?></span>
                </div>
                <button type="button" class="auto-featured-image-button button-secondary auto-featured-image-refresh">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Refresh', 'auto-featured-image' ); ?>
                </button>
            </div>
            <?php
        endif;
        ?>
