<?php
/**
 * Fallback index template
 */
get_header();
?>

<div class="container" style="padding: 100px 0; min-height: 50vh;">
    <main id="primary" class="site-main">
        <?php
        if ( have_posts() ) :
            while ( have_posts() ) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header" style="text-align: center; padding: 60px 20px 40px; border-bottom: 1px solid #eee; margin-bottom: 0;">
                        <?php the_title( '<h1 class="entry-title" style="font-size:40px;font-weight:800;color:#081d34;margin:0;letter-spacing:-0.5px;line-height:1.2;">', '</h1>' ); ?>
                    </header>
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </article>
                <?php
            endwhile;
            the_posts_navigation();
        else :
            echo '<p>No content found.</p>';
        endif;
        ?>
    </main>
</div>

<?php
get_footer();
