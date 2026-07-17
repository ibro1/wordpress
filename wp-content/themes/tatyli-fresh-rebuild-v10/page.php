<?php
/**
 * Page template.
 */

defined( 'ABSPATH' ) || exit;
get_header();

if ( ! tatyli_fresh_render_static_page() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'tat-default-page' ); ?>>
			<div class="tat-container">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</div>
		</article>
		<?php
	endwhile;
endif;

get_footer();
