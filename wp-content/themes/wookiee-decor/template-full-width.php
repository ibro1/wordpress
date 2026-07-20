<?php
/**
 * Template Name: Full Width
 */
echo '<!-- FULL_WIDTH_LOADED -->';
get_header();
?>

<main id="primary" class="site-main">
    <?php
    while ( have_posts() ) :
        the_post();
        remove_filter( 'the_content', 'wpautop' );
        the_content();
        add_filter( 'the_content', 'wpautop' );
    endwhile;
    ?>
</main>

<?php
get_footer();
