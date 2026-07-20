<?php
/**
 * Fallback index template
 */
get_header();
?>

<main id="primary" class="site-main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            $is_page = is_page();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php echo $is_page ? '' : 'style="max-width:1200px;margin:0 auto;padding:100px 20px;min-height:50vh;box-sizing:border-box;"'; ?>>
                <?php if ( ! $is_page ) : ?>
                <header class="entry-header" style="text-align: center; padding: 0 20px 40px; border-bottom: 1px solid #eee; margin-bottom: 40px;">
                    <?php the_title( '<h1 class="entry-title" style="font-size:40px;font-weight:800;color:#081d34;margin:0;letter-spacing:-0.5px;line-height:1.2;">', '</h1>' ); ?>
                </header>
                <?php endif; ?>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
            <?php
        endwhile;
        the_posts_navigation();
    else :
        echo '<p style="max-width:1200px;margin:0 auto;padding:100px 20px;">No content found.</p>';
    endif;
    ?>
</main>

<?php
get_footer();
