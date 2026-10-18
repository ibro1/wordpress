<?php
/**
 * Blog index at /blog/ (a page on template-blog.php, provisioned by
 * dbt_starter_pages), the shared post card, search results and the
 * 404 page. Posts keep their /%postname%/ permalinks at the site root;
 * /blog/ only lists them.
 */

defined( 'ABSPATH' ) || exit;

define( 'DBT_BLOG_PER_PAGE', 9 );
define( 'DBT_BLOG_TITLE', 'Blog — guides to websites, ads, software and AI costs' );
define( 'DBT_BLOG_DESCRIPTION', 'Practical guides from Linkfa on websites, Google Ads, software, DevOps and AI agents: what they cost in Nigeria and what to ask before you pay.' );

function dbt_is_blog_page() {
	return is_page( 'blog' );
}

function dbt_blog_paged() {
	return max( 1, (int) get_query_var( 'paged' ) );
}

/** Published posts, newest first, for the page of /blog/ being shown. */
function dbt_blog_query( $paged = null ) {
	return new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'orderby'             => 'date',
		'order'               => 'DESC',
		'posts_per_page'      => DBT_BLOG_PER_PAGE,
		'paged'               => null === $paged ? dbt_blog_paged() : (int) $paged,
		'ignore_sticky_posts' => true,
		'post__not_in'        => dbt_blog_hidden_post_ids(),
	) );
}

/** The WordPress sample post stays out of the listing even if it is republished. */
function dbt_blog_hidden_post_ids() {
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	return $hello ? array( (int) $hello->ID ) : array();
}

/** /blog/page/N/ past the last page is a real 404, not an empty list. */
add_action( 'template_redirect', 'dbt_blog_out_of_range_404', 5 );
function dbt_blog_out_of_range_404() {
	if ( ! dbt_is_blog_page() || dbt_blog_paged() < 2 ) {
		return;
	}
	$query = dbt_blog_query();
	if ( dbt_blog_paged() > max( 1, (int) $query->max_num_pages ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}

function dbt_blog_title( $paged = 1 ) {
	return DBT_BLOG_TITLE . ( $paged > 1 ? ' — page ' . $paged : '' ) . ' | ' . DBT_ORG_NAME;
}

add_filter( 'pre_get_document_title', 'dbt_blog_document_title', 30 );
function dbt_blog_document_title( $title ) {
	return dbt_is_blog_page() ? dbt_blog_title( dbt_blog_paged() ) : $title;
}

add_filter( 'rank_math/frontend/title', 'dbt_blog_rank_math_title', 20 );
function dbt_blog_rank_math_title( $title ) {
	return dbt_is_blog_page() ? dbt_blog_title( dbt_blog_paged() ) : $title;
}

/** One post in a listing: featured image, date, title, excerpt, link. */
function dbt_post_card( $eager = false ) {
	$url     = get_permalink();
	$title   = get_the_title();
	$excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 30, '…' );
	?>
	<article class="post-card">
		<?php if ( has_post_thumbnail() ) : ?>
			<a class="post-card__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
				<?php
				the_post_thumbnail( 'medium_large', array(
					'class'   => 'post-card__img',
					'loading' => $eager ? 'eager' : 'lazy',
					'sizes'   => '(max-width: 640px) 100vw, (max-width: 1000px) 50vw, 380px',
				) );
				?>
			</a>
		<?php endif; ?>
		<div class="post-card__body">
			<p class="post-card__meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
			<h2 class="post-card__title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h2>
			<?php if ( $excerpt ) : ?>
				<p class="post-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
			<a class="cell__link post-card__link" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( 'Read: ' . $title ); ?>">Read the article →</a>
		</div>
	</article>
	<?php
}

/** Site search box (WordPress core search, ?s=). */
function dbt_search_form( $id = 'site-search' ) {
	?>
	<form class="searchbox" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="searchbox__label" for="<?php echo esc_attr( $id ); ?>">Search the site</label>
		<div class="searchbox__row">
			<input class="searchbox__input" type="search" id="<?php echo esc_attr( $id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Website cost, Google Ads…">
			<button type="submit" class="btn btn--primary">Search</button>
		</div>
	</form>
	<?php
}

/** Numbered pagination for a listing. */
function dbt_pagination( $total, $current, $base ) {
	if ( $total < 2 ) {
		return;
	}
	$links = paginate_links( array(
		'base'      => $base,
		'format'    => '',
		'current'   => $current,
		'total'     => $total,
		'type'      => 'array',
		'prev_text' => '← Newer',
		'next_text' => 'Older →',
	) );
	if ( ! $links ) {
		return;
	}
	echo '<nav class="pager" aria-label="Pages">';
	foreach ( $links as $link ) {
		echo $link; // phpcs:ignore WordPress.Security.EscapeOutput -- paginate_links escapes its own output
	}
	echo '</nav>';
}
