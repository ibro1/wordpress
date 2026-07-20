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
				<div class="hero-eyebrow">Premium Home Storage</div>
				<h1 class="hero-title">A more organised<br>home starts <em>here.</em></h1>
				<p class="hero-lead">Discover our collection of thoughtful storage solutions and home accessories designed for modern living.</p>
				<div class="hero-cta-row">
					<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn">Shop all storage</a>
					<a href="#categories" class="btn btn-outline">Explore categories</a>
				</div>
			</div>
			<div class="hero-image-col">
				<?php
				$hero_id = get_option( 'wookiee_hero_image_id' );
				$hero_url = $hero_id ? wp_get_attachment_url( $hero_id ) : '';
				if ( $hero_url ) :
				?>
				<img src="<?php echo esc_url( $hero_url ); ?>" alt="Wookiee Storage Shelving">
				<?php else : ?>
				<div class="hero-image-placeholder">Hero Image Placeholder</div>
				<?php endif; ?>
				<div class="hero-stat-badge">
					<div class="stat-number">3-5</div>
					<div class="stat-label">day UK delivery, dispatched from Cowdenbeath</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Features / Trust Bar -->
	<section class="features-section">
		<div class="feature-item">
			<div class="feature-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2" ry="2"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
			</div>
			<div>Free delivery<span class="feature-text-sub">On orders over &pound;50</span></div>
		</div>
		<div class="feature-item">
			<div class="feature-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path></svg>
			</div>
			<div>30 day returns<span class="feature-text-sub">Hassle-free refunds</span></div>
		</div>
		<div class="feature-item">
			<div class="feature-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
			</div>
			<div>Secure payments<span class="feature-text-sub">Fully SSL encrypted</span></div>
		</div>
	</section>

	<!-- Products Grid Section -->
	<section class="container home-section">
		<div class="section-header text-center">
			<div class="section-kicker">Curated Catalog</div>
			<h2 class="section-title">Premium Storage Best-Sellers</h2>
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
	<section class="container home-section" id="categories">
		<div class="section-header text-center">
			<div class="section-kicker">Organise Every Space</div>
			<h2 class="section-title">Explore Our Categories</h2>
			<p class="section-subtitle">Everything you need to bring calm and order to every corner of your home.</p>
		</div>

		<div class="wookiee-cat-grid">

			<!-- Card 1 -->
			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="wookiee-cat-card" style="--cat-color:#e8f4f4; --cat-accent:#6fbdbd;">
				<div class="cat-card-icon-wrap">
					<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6fbdbd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
				</div>
				<div class="cat-card-body">
					<h3 class="cat-card-title">Storage Solutions</h3>
					<p class="cat-card-desc">Smart storage for every room — from drawer organisers to shelving units.</p>
				</div>
				<div class="cat-card-arrow">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
				</div>
			</a>

			<!-- Card 2 -->
			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="wookiee-cat-card" style="--cat-color:#fef6ec; --cat-accent:#dcb37b;">
				<div class="cat-card-icon-wrap">
					<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dcb37b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
				</div>
				<div class="cat-card-body">
					<h3 class="cat-card-title">Home Accessories</h3>
					<p class="cat-card-desc">Finishing touches that elevate every space and make your house feel like home.</p>
				</div>
				<div class="cat-card-arrow" style="color: #dcb37b;">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
				</div>
			</a>

			<!-- Card 3 -->
			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="wookiee-cat-card" style="--cat-color:#edf4fb; --cat-accent:#5b9ecf;">
				<div class="cat-card-icon-wrap">
					<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#5b9ecf" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M5 12a7 7 0 0 0 7 7"/><path d="M5 12a7 7 0 0 1 7-7"/><circle cx="12" cy="12" r="3"/></svg>
				</div>
				<div class="cat-card-body">
					<h3 class="cat-card-title">Bathroom Storage</h3>
					<p class="cat-card-desc">Keep your bathroom neat and tidy with clever cabinet and counter solutions.</p>
				</div>
				<div class="cat-card-arrow" style="color: #5b9ecf;">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
				</div>
			</a>

			<!-- Card 4 -->
			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="wookiee-cat-card" style="--cat-color:#f0f0f8; --cat-accent:#7b7bcc;">
				<div class="cat-card-icon-wrap">
					<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7b7bcc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
				</div>
				<div class="cat-card-body">
					<h3 class="cat-card-title">Office &amp; Desk</h3>
					<p class="cat-card-desc">Organise your workspace and boost productivity with premium desk solutions.</p>
				</div>
				<div class="cat-card-arrow" style="color: #7b7bcc;">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
				</div>
			</a>

		</div>
	</section>

	<!-- How it works Section -->
	<section class="container home-section wookiee-content-grid-2 how-it-works">
		<?php
		$feature_id   = get_option( 'wookiee_feature_image_id' );
		$feature_url  = $feature_id ? wp_get_attachment_url( $feature_id ) : '';
		?>
		<div class="how-it-works-media" <?php echo $feature_url ? 'style="background-image:url(' . esc_url( $feature_url ) . ');"' : ''; ?>>
			<?php if ( ! $feature_url ) : ?>
			<span>Video / Image Placeholder</span>
			<?php endif; ?>
		</div>
		<div>
			<div class="section-kicker">How it works</div>
			<h2 class="section-title how-it-works-title">See how it works in your space.</h2>
			<p class="how-it-works-lead">Our storage solutions are designed to blend seamlessly into your home. Watch how easily they assemble and transform cluttered spaces into calm, organised areas.</p>

			<div class="how-it-works-steps">
				<div class="how-it-works-step">
					<div class="step-number">1</div>
					<div><strong>Versatile use</strong><br><span>Perfect for living rooms, bedrooms, or home offices.</span></div>
				</div>
				<div class="how-it-works-step">
					<div class="step-number">2</div>
					<div><strong>Easy to assemble</strong><br><span>No complex tools required, put it together in minutes.</span></div>
				</div>
				<div class="how-it-works-step">
					<div class="step-number">3</div>
					<div><strong>Durable materials</strong><br><span>Built to last with sustainable bamboo and sturdy metals.</span></div>
				</div>
			</div>

			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn how-it-works-cta">Shop the collection</a>
		</div>
	</section>

	<!-- Philosophy Section -->
	<section class="philosophy-section">
		<div class="container philosophy-inner">
			<div class="section-kicker">Philosophy</div>
			<h2 class="section-title">Organisation should feel simple.</h2>
			<p class="philosophy-copy">We believe that a tidy home leads to a clearer mind. That's why we design products that are not only functional but also beautiful, helping you create spaces you love to spend time in without the stress of clutter.</p>
			<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline">Learn more</a>
		</div>
	</section>

	<!-- Collections Grid Section -->
	<section class="container home-section">
		<div class="section-header text-center">
			<div class="section-kicker">Product Lineup</div>
			<h2 class="section-title">Shop by Collection</h2>
		</div>
		<div class="collections-grid">
			<?php
			$cols = array(
				array(
					'title' => 'Indoor Storage',
					'desc'  => 'Shelves, baskets, and cabinets',
					'img'   => '/wp-content/themes/wookiee-decor/assets/images/wookiee-prod-shelves.png',
					'slug'  => 'kitchen-storage',
				),
				array(
					'title' => 'Home Accessories',
					'desc'  => 'Finishing touches for every room',
					'img'   => '/wp-content/themes/wookiee-decor/assets/images/drawer-organizer.png',
					'slug'  => 'drawer-organisers',
				),
				array(
					'title' => 'Bathroom',
					'desc'  => 'Towels, mats, and organizers',
					'img'   => '/wp-content/themes/wookiee-decor/assets/images/bathroom-shelf.png',
					'slug'  => 'bathroom-storage',
				),
				array(
					'title' => 'Office & Desk',
					'desc'  => 'Workspace organization',
					'img'   => '/wp-content/themes/wookiee-decor/assets/images/wookiee-prod-organizer.png',
					'slug'  => 'office-desk',
				),
			);
			foreach ( $cols as $c ) :
				$term_link = get_term_link( $c['slug'], 'product_cat' );
				$url       = ! is_wp_error( $term_link ) ? esc_url( $term_link ) : esc_url( home_url( '/shop/' ) );
			?>
			<a href="<?php echo $url; ?>" class="collection-card-link">
				<div class="collection-card">
					<div class="collection-bg" style="background-image: url('<?php echo esc_url( $c['img'] ); ?>');"></div>
					<div class="collection-overlay"></div>
					<div class="collection-content">
						<h3><?php echo esc_html( $c['title'] ); ?></h3>
						<p><?php echo esc_html( $c['desc'] ); ?></p>
					</div>
					<div class="collection-arrow">&#10132;</div>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
