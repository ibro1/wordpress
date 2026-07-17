<?php
/**
 * Front page template for Anyora Commerce.
 */
get_header(); ?>

<main class="site-main">
	
	<!-- Hero Section -->
	<section class="hero-section">
		<div class="container hero-content">
			<h1 class="hero-title">A more organised<br>home starts <span>here.</span></h1>
			<p class="hero-subtitle">Discover our collection of thoughtful storage solutions and home accessories designed for modern living.</p>
			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn">Shop all storage</a>
		</div>
		<div class="container">
			<div class="hero-image-wrap" style="background: #e9ece6; height: 500px; display: flex; align-items: center; justify-content: center; position: relative;">
				<!-- Placeholder for hero image -->
				<span style="color: #999; font-size: 24px;">Hero Image (Storage Shelves)</span>
				<div class="hero-search">
					<span aria-hidden="true">🔍</span>
					<input type="text" placeholder="Search for products...">
				</div>
			</div>
		</div>
	</section>

	<!-- Features Section -->
	<section class="features-section">
		<div class="feature-item">
			<div class="feature-icon">🚚</div>
			Free delivery<br>over £50
		</div>
		<div class="feature-item">
			<div class="feature-icon">📦</div>
			30 day<br>returns
		</div>
		<div class="feature-item">
			<div class="feature-icon">🔒</div>
			Secure<br>payments
		</div>
	</section>

	<!-- Products Grid Section -->
	<section class="container" style="padding: 60px 20px;">
		<div class="section-header text-center">
			<div class="section-kicker">New Collection</div>
			<h2 class="section-title">Organisation made easier.</h2>
		</div>
		<div class="products-grid">
			<?php
			if ( class_exists( 'WooCommerce' ) ) {
				$args = array(
					'post_type'      => 'product',
					'posts_per_page' => 4,
				);
				$loop = new WP_Query( $args );
				if ( $loop->have_posts() ) {
					while ( $loop->have_posts() ) : $loop->the_post();
						global $product;
						?>
						<div class="product-card">
							<div class="product-image">
								<?php 
								if ( has_post_thumbnail() ) {
									the_post_thumbnail( 'woocommerce_thumbnail' );
								} else {
									echo '<div style="background:#f4f5f0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#999;">Image</div>';
								}
								?>
							</div>
							<h3 class="product-title"><?php the_title(); ?></h3>
							<div class="product-price"><?php echo $product->get_price_html(); ?></div>
							<a href="<?php the_permalink(); ?>" class="btn btn-outline" style="width: 100%; box-sizing: border-box;">View product</a>
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
	<section class="container" style="padding: 40px 20px;">
		<div class="section-header text-center">
			<div class="section-kicker">Categories</div>
			<h2 class="section-title">Selected for everyday business.</h2>
		</div>
		<div class="categories-list" style="margin-top: 40px;">
			<?php
			$categories = array(
				array('icon' => '📦', 'title' => 'Storage solutions', 'desc' => 'Smart storage for every room'),
				array('icon' => '🪴', 'title' => 'Home accessories', 'desc' => 'Finishing touches that make a house a home'),
				array('icon' => '🚿', 'title' => 'Bathroom storage', 'desc' => 'Keep your bathroom neat and tidy'),
				array('icon' => '💻', 'title' => 'Office & Desk', 'desc' => 'Organise your workspace'),
			);
			foreach ( $categories as $cat ) {
				?>
				<div class="cat-item">
					<div class="cat-info">
						<div class="cat-icon"><?php echo $cat['icon']; ?></div>
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
		<div style="background: #e9ece6; border-radius: 20px; height: 400px; display: flex; align-items: center; justify-content: center; color: #999;">
			Video / Image Placeholder
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
	<section class="container" style="padding: 80px 20px;">
		<div class="section-header text-center">
			<div class="section-kicker">Collections</div>
			<h2 class="section-title">Find the right collection.</h2>
		</div>
		<div class="collections-grid" style="margin-top: 40px;">
			<div class="collection-card">
				<h3>Indoor Storage</h3>
				<p>Shelves, baskets, and cabinets</p>
				<div class="collection-arrow">➔</div>
			</div>
			<div class="collection-card">
				<h3>Home Accessories</h3>
				<p>Finishing touches for every room</p>
				<div class="collection-arrow">➔</div>
			</div>
			<div class="collection-card">
				<h3>Bathroom</h3>
				<p>Towels, mats, and organizers</p>
				<div class="collection-arrow">➔</div>
			</div>
			<div class="collection-card">
				<h3>Office & Desk</h3>
				<p>Workspace organization</p>
				<div class="collection-arrow">➔</div>
			</div>
		</div>
	</section>

	<!-- Newsletter Section -->
	<section class="newsletter-section">
		<div class="container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
			<div>
				<h2 style="font-size: 48px; margin: 0 0 20px 0; line-height: 1.1;">More organisation.<br>Less clutter.</h2>
				<p style="font-size: 18px; opacity: 0.8; margin: 0;">Join our newsletter to receive tips on home organization and exclusive early access to new collections.</p>
			</div>
			<div class="newsletter-box">
				<h3 style="margin: 0 0 10px 0;">Join our community</h3>
				<p style="margin: 0 0 20px 0; color: #666;">Get 10% off your first order when you sign up.</p>
				<form class="newsletter-form" action="#" onsubmit="event.preventDefault(); alert('Subscribed!');">
					<input type="email" placeholder="Email address" required>
					<button type="submit">Sign up</button>
				</form>
				<p style="font-size: 12px; color: #999; margin-top: 15px;">By signing up, you agree to our Terms of Service and Privacy Policy.</p>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
