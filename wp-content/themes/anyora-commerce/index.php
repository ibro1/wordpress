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
                    <header class="entry-header">
                        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
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
