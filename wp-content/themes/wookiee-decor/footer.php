<footer class="site-footer">

	<!-- Newsletter Band -->
	<div class="footer-newsletter-wrap">
		<div class="container footer-newsletter">
			<div class="newsletter-text">
				<span class="newsletter-heading">Stay organised with Wookiee</span>
				<span class="newsletter-sub">Home-organisation ideas and new-product updates, occasionally.</span>
			</div>
			<form class="newsletter-form">
				<input type="email" placeholder="Your email address" required>
				<button type="submit">Subscribe</button>
			</form>
		</div>
	</div>

	<!-- Main Footer Columns -->
	<div class="container footer-columns-grid">

		<div class="footer-col footer-col-brand">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 38" width="150" height="34" fill="none" class="footer-logo">
				<path d="M8 8 L12 30 L17.5 17 L23 30 L27 8" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
				<rect x="10" y="11" width="4.5" height="4" rx="1" fill="#c1704a"/>
				<rect x="20.5" y="11" width="4.5" height="4" rx="1" fill="#c1704a"/>
				<line x1="11" y1="13" x2="13.5" y2="13" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round"/>
				<line x1="21.5" y1="13" x2="24" y2="13" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round"/>
				<text x="36" y="28" font-family="'Outfit', 'Inter', system-ui, sans-serif" font-weight="800" font-size="22" fill="#ffffff" letter-spacing="-0.5px">Wookiee</text>
			</svg>
			<p class="footer-about-copy">
				UK private-label home-storage brand operated by Wookiee Decor Ltd.
			</p>
			<div class="footer-socials">
				<a href="#" aria-label="Facebook" class="social-icon-btn">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
				</a>
			</div>
		</div>

		<div class="footer-col">
			<div class="footer-eyebrow">Shop</div>
			<ul class="footer-links-list">
				<li><a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">All products</a></li>
				<li><a href="<?php echo wookiee_product_cat_url( 'kitchen-storage' ); ?>">Kitchen storage</a></li>
				<li><a href="<?php echo wookiee_product_cat_url( 'bathroom-storage' ); ?>">Bathroom storage</a></li>
				<li><a href="<?php echo wookiee_product_cat_url( 'drawer-organisers' ); ?>">Drawer organisers</a></li>
			</ul>
		</div>

		<div class="footer-col">
			<div class="footer-eyebrow">Customer Care</div>
			<ul class="footer-links-list">
				<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About us</a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact us</a></li>
				<li><a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>">My account</a></li>
			</ul>
		</div>

		<div class="footer-col">
			<div class="footer-eyebrow">Get In Touch</div>
			<p class="footer-info-text footer-contact-line"><span>&#9993;</span> <a href="mailto:info@wookied.com">info@wookied.com</a></p>
			<p class="footer-info-text footer-contact-line"><span>&#128222;</span> <strong>+44 20 8472 6126</strong></p>
			<p class="footer-info-text">
				Wookiee Decor Ltd, 28 Johnston Park,<br>
				Cowdenbeath, KY4 9AZ, United Kingdom<br>
				<span class="footer-info-small">Company No. SC769264</span>
			</p>
		</div>

	</div>

	<!-- Sub Footer -->
	<div class="footer-subfooter">
		<div class="container footer-subfooter-inner">
			<div class="sub-footer-links">
				<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms</a>
				<a href="<?php echo esc_url( home_url( '/shipping/' ) ); ?>">Shipping</a>
				<a href="<?php echo esc_url( home_url( '/returns/' ) ); ?>">Returns</a>
				<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">Privacy</a>
				<a href="<?php echo esc_url( home_url( '/cookie/' ) ); ?>">Cookies</a>
			</div>

			<div class="footer-bottom-row">
				<div class="footer-copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> Wookiee Decor Ltd.</div>
				<div class="footer-payments">
					<div class="payment-icon-wrapper" title="Visa">
						<svg width="30" height="18" viewBox="0 0 30 18"><text x="15" y="13" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-weight="700" font-style="italic" font-size="10" fill="#ffffff">VISA</text></svg>
					</div>
					<div class="payment-icon-wrapper" title="Mastercard">
						<svg width="30" height="18" viewBox="0 0 24 16"><circle cx="9" cy="8" r="6" fill="#EB001B" fill-opacity="0.85"/><circle cx="15" cy="8" r="6" fill="#F79E1B" fill-opacity="0.85"/></svg>
					</div>
					<div class="payment-icon-wrapper" title="PayPal">
						<svg width="30" height="18" viewBox="0 0 30 18"><text x="15" y="13" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-weight="700" font-style="italic" font-size="9" fill="#ffffff">PayPal</text></svg>
					</div>
					<div class="payment-icon-wrapper" title="American Express">
						<svg width="30" height="18" viewBox="0 0 30 18"><text x="15" y="12" text-anchor="middle" fill="#ffffff" font-size="7" font-weight="700" font-family="sans-serif">AMEX</text></svg>
					</div>
					<div class="payment-icon-wrapper" title="Apple Pay">
						<svg width="30" height="18" viewBox="0 0 30 18"><text x="15" y="12.5" text-anchor="middle" fill="#ffffff" font-size="8" font-weight="600" font-family="sans-serif"> Pay</text></svg>
					</div>
				</div>
			</div>
		</div>
	</div>

</footer>

<?php wp_footer(); ?>
</body>
</html>
