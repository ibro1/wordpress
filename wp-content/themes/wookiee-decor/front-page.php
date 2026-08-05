<?php
/**
 * Front page template for Wookiee Decor.
 */
get_header(); ?>

<main class="site-main">

	<!-- Hero Section -->
	<section class="hero-section">
		<div class="container hero-grid">
			<div class="hero-text-col">
				<div class="hero-eyebrow"><?php echo esc_html( wookiee_get_setting( 'hero_eyebrow' ) ); ?></div>
				<h1 class="hero-title"><?php echo esc_html( wookiee_get_setting( 'hero_headline' ) ); ?></h1>
				<p class="hero-lead"><?php echo esc_html( wookiee_get_setting( 'hero_subheadline' ) ); ?></p>
				<div class="hero-cta-row">
					<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn"><?php echo esc_html( wookiee_get_setting( 'hero_cta_primary' ) ); ?></a>
					<a href="#categories" class="btn btn-outline"><?php echo esc_html( wookiee_get_setting( 'hero_cta_secondary' ) ); ?></a>
				</div>
			</div>
			<div class="hero-image-col">
				<?php
				// Resolved through the slot helper so the hero, the About
				// images and anything that fills them later all read from one
				// place. Always returns a URL - the bundled asset when this
				// store has not set its own - so there is no placeholder path.
				$hero_url = wookiee_image_url( 'hero' );
				?>
				<img src="<?php echo esc_url( $hero_url ); ?>" alt="<?php echo esc_attr( wookiee_get_setting( 'hero_headline' ) ); ?>">
				<?php
				/*
				 * The shipping price badge that sat over this image has moved
				 * to the announcement bar. Review asked for shipping out of
				 * the hero, and the photograph carries the section better
				 * without a price plate across one corner of it.
				 */
				?>
			</div>
		</div>
	</section>

	<!-- Features / Trust Bar -->
	<section class="features-section">
		<div class="feature-item">
			<div class="feature-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2" ry="2"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
			</div>
			<?php // Dispatch time, not the price again - the hero badge above already carries that. ?>
			<div><?php echo esc_html( wookiee_get_setting( 'trust_1_title' ) ); ?><span class="feature-text-sub">Dispatched in <?php echo esc_html( wookiee_get_setting( 'handling_time' ) ); ?></span></div>
		</div>
		<div class="feature-item">
			<div class="feature-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path></svg>
			</div>
			<?php
			/*
			 * Derived, not written. This was AI-generated free text and it
			 * produced "Return within 30 days; statutory rights apply" - a
			 * legal claim on the homepage, composed independently of the
			 * Returns Policy it is supposed to summarise, and flagged in
			 * review as contradicting it. The same fault as the dispatch time
			 * saying 2-3 days while the policy said 1-2: a page stating a
			 * commitment in its own words will eventually state it differently.
			 * It now reads from the setting the policies are generated from,
			 * so the two cannot disagree.
			 */
			?>
			<div><?php echo esc_html( wookiee_get_setting( 'trust_2_title' ) ); ?><span class="feature-text-sub"><?php echo esc_html( wookiee_get_setting( 'returns_period_days' ) ); ?>-day returns</span></div>
		</div>
		<div class="feature-item">
			<div class="feature-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
			</div>
			<?php
			/*
			 * Sub-line dropped. It read "Pay with confidence using PayPal,
			 * Visa, or Mastercard" - the same list the footer already shows as
			 * payment marks, so it said nothing new, and it re-introduced the
			 * named-provider wording that was deliberately taken off the
			 * product page. The heading carries the reassurance on its own.
			 */
			?>
			<div><?php echo esc_html( wookiee_get_setting( 'trust_3_title' ) ); ?></div>
		</div>
	</section>

	<!-- Products Grid Section -->
	<section class="container home-section">
		<div class="section-header text-center">
			<div class="section-kicker"><?php echo esc_html( wookiee_get_setting( 'products_kicker' ) ); ?></div>
			<h2 class="section-title"><?php echo esc_html( wookiee_get_setting( 'products_title' ) ); ?></h2>
		</div>
		<div class="products-grid">
			<?php
			if ( class_exists( 'WooCommerce' ) ) {
				$args = array(
					'post_type'      => 'product',
					'posts_per_page' => 6,
				);
				$loop = new WP_Query( $args );
				if ( $loop->have_posts() ) {
					while ( $loop->have_posts() ) : $loop->the_post();
						global $product;
						?>
						<div class="product-card">
							<div class="product-image-wrapper">
								<a href="<?php the_permalink(); ?>" class="product-image-link">
									<?php
									if ( has_post_thumbnail() ) {
										the_post_thumbnail( 'woocommerce_thumbnail' );
									} else {
										echo '<div class="product-image-fallback">Image</div>';
									}
									?>
								</a>
							</div>

							<div class="product-content-wrapper">
								<h3 class="product-card-title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h3>

								<?php
								// Dynamic stars rating for catalog (left-aligned)
								$average_rating = $product->get_average_rating();
								$rating_count   = $product->get_rating_count();
								if ( $rating_count > 0 ) {
									$stars = str_repeat( '&#9733;', round( $average_rating ) ) . str_repeat( '&#9734;', 5 - round( $average_rating ) );
									echo '<div class="product-card-rating">' . $stars . ' <span>(' . (int) $rating_count . ')</span></div>';
								} else {
									echo '<div class="product-card-rating product-card-rating--empty">&#9734;&#9734;&#9734;&#9734;&#9734; <span>(0)</span></div>';
								}
								?>

								<div class="product-card-price">
									<?php echo $product->get_price_html(); ?>
								</div>

								<div class="product-card-cta">
									<?php woocommerce_template_loop_add_to_cart(); ?>
								</div>
							</div>
						</div>
						<?php
					endwhile;
				} else {
					echo '<p>No products found. Please add some products in WooCommerce.</p>';
				}
				wp_reset_postdata();
			} else {
				echo '<p>WooCommerce is not active. Please install and activate WooCommerce to see products here.</p>';
			}
			?>
		</div>
	</section>

	<!-- Categories Grid Section -->
	<?php $display_cats = wookiee_get_display_categories( 4 ); ?>
	<?php
	/*
	 * The "Shop by type" section was removed. It listed the same product
	 * categories as the Collections section further down - the two read the
	 * same $display_cats - so a store showed every category twice on one page,
	 * and a store with a single category showed the identical card twice with
	 * a different heading over each. Collections keeps the job.
	 */
	?>

	<!-- How it works Section -->
	<section class="container home-section wookiee-content-grid-2 how-it-works">
		<?php
		// Through the slot helper so the rebrand regenerates this alongside
		// the hero; it was the last homepage image still pinned to whatever
		// was uploaded at install time.
		$feature_url = wookiee_image_url( 'feature' );
		?>
		<div class="how-it-works-media" <?php echo $feature_url ? 'style="background-image:url(' . esc_url( $feature_url ) . ');"' : ''; ?>>
			<?php if ( ! $feature_url ) : ?>
			<span>Video / Image Placeholder</span>
			<?php endif; ?>
		</div>
		<div>
			<div class="section-kicker"><?php echo esc_html( wookiee_get_setting( 'how_it_works_kicker' ) ); ?></div>
			<h2 class="section-title how-it-works-title"><?php echo esc_html( wookiee_get_setting( 'how_it_works_title' ) ); ?></h2>
			<p class="how-it-works-lead"><?php echo esc_html( wookiee_get_setting( 'how_it_works_lead' ) ); ?></p>

			<div class="how-it-works-steps">
				<div class="how-it-works-step">
					<div class="step-number">1</div>
					<div><strong><?php echo esc_html( wookiee_get_setting( 'how_it_works_step1_title' ) ); ?></strong><br><span><?php echo esc_html( wookiee_get_setting( 'how_it_works_step1_desc' ) ); ?></span></div>
				</div>
				<div class="how-it-works-step">
					<div class="step-number">2</div>
					<div><strong><?php echo esc_html( wookiee_get_setting( 'how_it_works_step2_title' ) ); ?></strong><br><span><?php echo esc_html( wookiee_get_setting( 'how_it_works_step2_desc' ) ); ?></span></div>
				</div>
				<div class="how-it-works-step">
					<div class="step-number">3</div>
					<div><strong><?php echo esc_html( wookiee_get_setting( 'how_it_works_step3_title' ) ); ?></strong><br><span><?php echo esc_html( wookiee_get_setting( 'how_it_works_step3_desc' ) ); ?></span></div>
				</div>
			</div>

			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn how-it-works-cta"><?php echo esc_html( wookiee_get_setting( 'how_it_works_cta' ) ); ?></a>
		</div>
	</section>

	<!-- Philosophy Section -->
	<section class="philosophy-section">
		<div class="container philosophy-inner">
			<div class="section-kicker">Philosophy</div>
			<h2 class="section-title"><?php echo esc_html( wookiee_get_setting( 'homepage_philosophy_heading' ) ); ?></h2>
			<p class="philosophy-copy"><?php echo esc_html( wookiee_get_setting( 'homepage_philosophy' ) ); ?></p>
			<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline">Learn more</a>
		</div>
	</section>

	<!-- Collections Grid Section -->
	<?php if ( ! empty( $display_cats ) ) : ?>
	<?php // Carries the #categories id the hero's second button links to - it lived on the removed section. ?>
	<section class="container home-section" id="categories">
		<div class="section-header text-center">
			<div class="section-kicker"><?php echo esc_html( wookiee_get_setting( 'collections_kicker' ) ); ?></div>
			<h2 class="section-title"><?php echo esc_html( wookiee_get_setting( 'collections_title' ) ); ?></h2>
		</div>
		<div class="collections-grid">
			<?php foreach ( $display_cats as $cat ) :
				$img  = wookiee_get_category_image_url( $cat );
				$desc = $cat->description ? $cat->description : sprintf( '%d product%s', $cat->count, 1 === (int) $cat->count ? '' : 's' );
			?>
			<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="collection-card-link">
				<div class="collection-card<?php echo $img ? '' : ' collection-card-plain'; ?>">
					<?php if ( $img ) : ?>
						<div class="collection-bg" style="background-image: url('<?php echo esc_url( $img ); ?>');"></div>
						<div class="collection-overlay"></div>
					<?php endif; ?>
					<div class="collection-content">
						<h3><?php echo esc_html( $cat->name ); ?></h3>
						<p><?php echo esc_html( $desc ); ?></p>
					</div>
					<div class="collection-arrow">&#10132;</div>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
