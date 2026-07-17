<?php
/**
 * Front page template for Anyora Commerce.
 */
get_header(); ?>

<main class="site-main">
	
	<!-- Hero Section -->
	<section class="hero-section" style="background-color: var(--anyora-bg); padding: 50px 20px; font-family: var(--font-primary); box-sizing: border-box;">
		<div class="container hero-grid" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; align-items: center; box-sizing: border-box;">
			<div class="hero-text-col" style="text-align: left; box-sizing: border-box;">
				<div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
					<span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> PREMIUM HOME STORAGE
				</div>
				<h1 style="font-size: 48px; color: var(--anyora-navy); margin: 0 0 20px 0; font-weight: 800; letter-spacing: -2px; line-height: 1.1;">A more organised<br>home starts <span>here.</span></h1>
				<p style="font-size: 18px; color: #555; line-height: 1.6; margin-bottom: 30px;">
					Discover our collection of thoughtful storage solutions and home accessories designed for modern living.
				</p>
				<div style="display: flex; gap: 15px;">
					<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn">Shop all storage</a>
				</div>
			</div>
			<div class="hero-image-col" style="position: relative;">
				<?php
				$hero_id = get_option('anyora_hero_image_id');
				$hero_url = $hero_id ? wp_get_attachment_url($hero_id) : '';
				if ( $hero_url ) :
				?>
				<img src="<?php echo esc_url($hero_url); ?>" alt="Anyora Storage Shelving" style="border-radius: 20px; width: 100%; max-height: 400px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.08); display: block;">
				<?php else : ?>
				<div style="background: #e9ece6; height: 400px; border-radius: 20px; display: flex; align-items: center; justify-content: center; color: #999;">Hero Image Placeholder</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<!-- Features Section -->
	<section class="features-section" style="padding: 50px 20px; background: var(--anyora-white); display: flex; justify-content: center; gap: 60px; border-bottom: 1px solid var(--anyora-border); flex-wrap: wrap;">
		<div class="feature-item" style="display: flex; flex-direction: column; align-items: center; text-align: center; max-width: 180px;">
			<div class="feature-icon" style="color: #6fbdbd; margin-bottom: 12px; display: flex; align-items: center; justify-content: center;">
				<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2" ry="2"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
			</div>
			<div style="font-weight: 700; color: var(--anyora-navy); font-size: 15px; margin-bottom: 4px;">Free delivery</div>
			<div style="color: #666; font-size: 13px;">On orders over £50</div>
		</div>
		<div class="feature-item" style="display: flex; flex-direction: column; align-items: center; text-align: center; max-width: 180px;">
			<div class="feature-icon" style="color: #6fbdbd; margin-bottom: 12px; display: flex; align-items: center; justify-content: center;">
				<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path></svg>
			</div>
			<div style="font-weight: 700; color: var(--anyora-navy); font-size: 15px; margin-bottom: 4px;">30 day returns</div>
			<div style="color: #666; font-size: 13px;">Hassle-free refunds</div>
		</div>
		<div class="feature-item" style="display: flex; flex-direction: column; align-items: center; text-align: center; max-width: 180px;">
			<div class="feature-icon" style="color: #6fbdbd; margin-bottom: 12px; display: flex; align-items: center; justify-content: center;">
				<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
			</div>
			<div style="font-weight: 700; color: var(--anyora-navy); font-size: 15px; margin-bottom: 4px;">Secure payments</div>
			<div style="color: #666; font-size: 13px;">Fully SSL encrypted</div>
		</div>
	</section>

	<!-- Products Grid Section -->
	<section class="container" style="padding: 60px 20px; max-width: 1200px; margin: 0 auto;">
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
								<a href="<?php the_permalink(); ?>" style="display: block; width: 100%; height: 100%;">
									<?php 
									if ( has_post_thumbnail() ) {
										the_post_thumbnail( 'woocommerce_thumbnail', array(
											'style' => 'width: 100%; height: 100%; object-fit: cover; display: block;'
										) );
									} else {
										echo '<div style="background:#f4f5f0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#999;">Image</div>';
									}
									?>
								</a>
							</div>
							
							<div class="product-content-wrapper" style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1; text-align: left; box-sizing: border-box;">
								<h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 700; line-height: 1.4; min-height: 44px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
									<a href="<?php the_permalink(); ?>" style="text-decoration: none; color: var(--anyora-navy); transition: color 0.2s;"><?php the_title(); ?></a>
								</h3>
								
								<?php
								// Dynamic stars rating for catalog (left-aligned)
								$average_rating = $product->get_average_rating();
								$rating_count   = $product->get_rating_count();
								if ( $rating_count > 0 ) {
									$stars = str_repeat('★', round($average_rating)) . str_repeat('☆', 5 - round($average_rating));
									echo '<div style="color:#dcb37b; font-size:13px; margin-bottom:12px; display:flex; align-items:center; gap:4px;">' . $stars . ' <span style="color:#666; font-size:11px;">(' . $rating_count . ')</span></div>';
								} else {
									echo '<div style="color:#ccc; font-size:13px; margin-bottom:12px; display:flex; align-items:center; gap:4px;">☆☆☆☆☆ <span style="color:#999; font-size:11px;">(0)</span></div>';
								}
								?>

								<div style="font-size: 18px; font-weight: 800; color: var(--anyora-navy); margin-bottom: 15px;">
									<?php echo $product->get_price_html(); ?>
								</div>
								
								<div style="margin-top: auto; width: 100%;">
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

	<!-- Categories List Section -->
	<section class="container" style="padding: 40px 20px; max-width: 1200px; margin: 0 auto;">
		<div class="section-header text-center">
			<div class="section-kicker">Organise Every Space</div>
			<h2 class="section-title">Explore Our Categories</h2>
		</div>
		<div class="categories-list" style="margin-top: 40px;">
			<?php
			$categories = array(
				array(
					'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #6fbdbd;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
					'title' => 'Storage solutions',
					'desc' => 'Smart storage for every room'
				),
				array(
					'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #6fbdbd;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>',
					'title' => 'Home accessories',
					'desc' => 'Finishing touches that make a house a home'
				),
				array(
					'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #6fbdbd;"><path d="M12 22a7 7 0 0 0 7-7H5a7 7 0 0 0 7 7zM12 2a3 3 0 0 0-3 3v3h6V5a3 3 0 0 0-3-3z"></path></svg>',
					'title' => 'Bathroom storage',
					'desc' => 'Keep your bathroom neat and tidy'
				),
				array(
					'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #6fbdbd;"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>',
					'title' => 'Office & Desk',
					'desc' => 'Organise your workspace'
				),
			);
			foreach ( $categories as $cat ) {
				?>
				<div class="cat-item">
					<div class="cat-info">
						<div class="cat-icon" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: white; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.02);"><?php echo $cat['icon']; ?></div>
						<div>
							<div class="cat-title"><?php echo $cat['title']; ?></div>
							<div class="cat-desc"><?php echo $cat['desc']; ?></div>
						</div>
					</div>
					<div class="cat-arrow">➔</div>
				</div>
				<?php
			}
			?>
		</div>
	</section>

	<!-- How it works Section -->
	<section class="container" style="padding: 60px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
		<?php
		$feature_id = get_option('anyora_feature_image_id');
		$feature_url = $feature_id ? wp_get_attachment_url($feature_id) : '';
		$feature_style = $feature_url ? 'background: url(' . esc_url($feature_url) . ') center/cover no-repeat;' : 'background: #e9ece6;';
		?>
		<div style="<?php echo $feature_style; ?> border-radius: 20px; height: 400px; display: flex; align-items: center; justify-content: center; color: #999; overflow: hidden;">
			<?php if ( ! $feature_url ) : ?>
			Video / Image Placeholder
			<?php endif; ?>
		</div>
		<div>
			<div class="section-kicker">How it works</div>
			<h2 class="section-title" style="margin-bottom: 20px;">See how it works in your space.</h2>
			<p style="margin-bottom: 30px;">Our storage solutions are designed to blend seamlessly into your home. Watch how easily they assemble and transform cluttered spaces into calm, organised areas.</p>
			
			<div style="display: flex; flex-direction: column; gap: 20px;">
				<div style="display: flex; gap: 15px; align-items: flex-start;">
					<div style="background: var(--anyora-navy); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">1</div>
					<div><strong>Versatile use</strong><br><span style="color: #666; font-size: 14px;">Perfect for living rooms, bedrooms, or home offices.</span></div>
				</div>
				<div style="display: flex; gap: 15px; align-items: flex-start;">
					<div style="background: var(--anyora-navy); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">2</div>
					<div><strong>Easy to assemble</strong><br><span style="color: #666; font-size: 14px;">No complex tools required, put it together in minutes.</span></div>
				</div>
				<div style="display: flex; gap: 15px; align-items: flex-start;">
					<div style="background: var(--anyora-navy); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">3</div>
					<div><strong>Durable materials</strong><br><span style="color: #666; font-size: 14px;">Built to last with sustainable bamboo and sturdy metals.</span></div>
				</div>
			</div>
			
			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn" style="margin-top: 30px;">Shop the collection</a>
		</div>
	</section>

	<!-- Simple Text Section -->
	<section style="background: var(--anyora-white); padding: 80px 20px; text-align: center;">
		<div class="container" style="max-width: 800px;">
			<div class="section-kicker">Philosophy</div>
			<h2 class="section-title" style="margin-bottom: 20px;">Organisation should feel simple.</h2>
			<p style="font-size: 18px; color: #555; margin-bottom: 30px;">We believe that a tidy home leads to a clearer mind. That's why we design products that are not only functional but also beautiful, helping you create spaces you love to spend time in without the stress of clutter.</p>
			<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline">Learn more</a>
		</div>
	</section>

	<!-- Collections Grid Section -->
	<section class="container" style="padding: 80px 20px; max-width: 1200px; margin: 0 auto;">
		<div class="section-header text-center">
			<div class="section-kicker">Product Lineup</div>
			<h2 class="section-title">Shop by Collection</h2>
		</div>
		<div class="collections-grid" style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
			<?php
			$cols = array(
				array(
					'title' => 'Indoor Storage',
					'desc' => 'Shelves, baskets, and cabinets',
					'img' => '/wp-content/themes/anyora-commerce/assets/images/anyora-prod-shelves.png',
					'slug' => 'kitchen-storage'
				),
				array(
					'title' => 'Home Accessories',
					'desc' => 'Finishing touches for every room',
					'img' => '/wp-content/themes/anyora-commerce/assets/images/drawer-organizer.png',
					'slug' => 'drawer-organisers'
				),
				array(
					'title' => 'Bathroom',
					'desc' => 'Towels, mats, and organizers',
					'img' => '/wp-content/themes/anyora-commerce/assets/images/bathroom-shelf.png',
					'slug' => 'bathroom-storage'
				),
				array(
					'title' => 'Office & Desk',
					'desc' => 'Workspace organization',
					'img' => '/wp-content/themes/anyora-commerce/assets/images/anyora-prod-organizer.png',
					'slug' => 'office-desk'
				)
			);
			foreach ($cols as $c) :
				$term_link = get_term_link($c['slug'], 'product_cat');
				$url = !is_wp_error($term_link) ? esc_url($term_link) : esc_url(home_url('/shop/'));
			?>
			<a href="<?php echo $url; ?>" class="collection-card-link" style="text-decoration: none; display: block;">
				<div class="collection-card">
					<div class="collection-bg" style="background-image: url('<?php echo esc_url($c['img']); ?>');"></div>
					<div class="collection-overlay"></div>
					<div class="collection-content">
						<h3 style="margin: 0 0 5px 0; font-size: 24px; font-weight: 700; color: #ffffff;"><?php echo esc_html($c['title']); ?></h3>
						<p style="margin: 0; opacity: 0.9; color: #f4f5f0; font-size: 14px;"><?php echo esc_html($c['desc']); ?></p>
					</div>
					<div class="collection-arrow">➔</div>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
	</section>



</main>

<?php get_footer(); ?>
