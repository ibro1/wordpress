<footer class="site-footer">

	<!-- Newsletter Section -->
	<div class="footer-newsletter-wrap">
		<div class="container footer-newsletter">
			<div class="newsletter-text">
				<div class="footer-eyebrow">Join the Wookiee mailing list</div>
				<h2 class="newsletter-heading">More organisation.<br>Less clutter.</h2>
				<p class="newsletter-copy">Receive home-organisation ideas, new-product updates and occasional Wookiee offers directly in your inbox.</p>

				<div class="newsletter-bullets">
					<div class="newsletter-bullet"><span>&#10003;</span> Organisation ideas</div>
					<div class="newsletter-bullet"><span>&#10003;</span> New-product updates</div>
					<div class="newsletter-bullet"><span>&#10003;</span> Occasional offers</div>
				</div>
			</div>

			<div class="newsletter-card">
				<h3 class="newsletter-card-title">Stay organised with Wookiee.</h3>
				<p class="newsletter-card-copy">Enter your email address to join our marketing mailing list.</p>

				<form class="newsletter-form">
					<input type="email" placeholder="Your email address" required>
					<button type="submit">Subscribe <span>&#10132;</span></button>
				</form>

				<p class="newsletter-disclaimer">By entering your email address and selecting "Subscribe", you consent to receive marketing emails from Wookiee. Marketing is optional and you can withdraw your consent at any time. <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">Read our Privacy Policy.</a> <a href="<?php echo esc_url( home_url( '/cookie/' ) ); ?>">Read our Cookie Policy.</a></p>
			</div>
		</div>
	</div>

	<!-- Main Footer Columns -->
	<div class="container footer-columns-grid">

		<div class="footer-col footer-col-brand">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 38" width="150" height="34" fill="none" class="footer-logo">
				<path d="M8 8 L12 30 L17.5 17 L23 30 L27 8" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
				<rect x="10" y="11" width="4.5" height="4" rx="1" fill="#6fbdbd"/>
				<rect x="20.5" y="11" width="4.5" height="4" rx="1" fill="#6fbdbd"/>
				<line x1="11" y1="13" x2="13.5" y2="13" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round"/>
				<line x1="21.5" y1="13" x2="24" y2="13" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round"/>
				<text x="36" y="28" font-family="'Outfit', 'Inter', system-ui, sans-serif" font-weight="800" font-size="22" fill="#ffffff" letter-spacing="-0.5px">Wookiee</text>
			</svg>
			<p class="footer-about-copy">
				Wookiee is a UK private-label home-storage brand operated by Wookiee Decor Ltd. wookied.com is the official online store of Wookiee Decor Ltd. We offer practical storage products selected to help make everyday spaces tidier and easier to use.
			</p>

			<div class="footer-eyebrow">Find Wookiee Online</div>
			<div class="footer-socials">
				<a href="#" aria-label="Facebook" class="social-icon-btn">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
				</a>
				<a href="#" aria-label="Instagram" class="social-icon-btn">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
				</a>
				<a href="#" aria-label="LinkedIn" class="social-icon-btn">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
				</a>
				<a href="#" aria-label="Pinterest" class="social-icon-btn">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 22c-.5 0-.9-.2-1.1-.6-.5-.9-.1-2.2.8-3.7l1.7-2.7c-.5-.9-.8-2-.8-3.2 0-3.3 2.7-6 6-6s6 2.7 6 6c0 3.8-2.7 7-6.5 7-1.3 0-2.5-.5-3.3-1.4l-.8 3.1c-.4 1.5-1.4 3-1.5 3.1-.1.1-.3.2-.5.2z"></path><path d="M12 9c-.8 0-1.5.7-1.5 1.5s.7 1.5 1.5 1.5 1.5-.7 1.5-1.5S12.8 9 12 9z"></path></svg>
				</a>
			</div>
		</div>

		<div class="footer-col">
			<div class="footer-eyebrow">Shop</div>
			<ul class="footer-links-list">
				<li><a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">All products</a></li>
				<li><a href="<?php echo esc_url( get_term_link( 'kitchen-storage', 'product_cat' ) ); ?>">Kitchen storage</a></li>
				<li><a href="<?php echo esc_url( get_term_link( 'bathroom-storage', 'product_cat' ) ); ?>">Bathroom storage</a></li>
				<li><a href="<?php echo esc_url( get_term_link( 'drawer-organisers', 'product_cat' ) ); ?>">Drawer organisers</a></li>
				<li><a href="<?php echo esc_url( get_term_link( 'shoe-storage', 'product_cat' ) ); ?>">Shoe storage</a></li>
			</ul>
		</div>

		<div class="footer-col">
			<div class="footer-eyebrow">Customer Care</div>
			<ul class="footer-links-list">
				<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About us</a></li>
				<li><a href="<?php echo esc_url( home_url( '/activities/' ) ); ?>">Our activities</a></li>
				<li><a href="<?php echo esc_url( home_url( '/mission/' ) ); ?>">Our mission</a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact us</a></li>
				<li><a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>">My account</a></li>
			</ul>
		</div>

		<div class="footer-col footer-col-info">
			<div>
				<div class="footer-eyebrow">Registered Office</div>
				<p class="footer-info-text">
					<strong>Wookiee Decor Ltd</strong><br>
					28 Johnston Park, Cowdenbeath, Scotland,<br>
					KY4 9AZ, United Kingdom
				</p>
			</div>

			<div>
				<div class="footer-eyebrow">Support Channels</div>
				<p class="footer-info-text footer-contact-line"><span>&#9993;</span> <a href="mailto:info@wookied.com">info@wookied.com</a></p>
				<p class="footer-info-text footer-contact-line"><span>&#128222;</span> <strong>+442084726126</strong></p>
			</div>

			<div>
				<div class="footer-eyebrow">Company Details</div>
				<p class="footer-info-text">
					Company number: <strong>SC769264</strong><br>
					<span class="footer-info-small">Registered in Scotland</span>
				</p>
			</div>
		</div>

	</div>

	<!-- Sub Footer -->
	<div class="footer-subfooter">
		<div class="container footer-subfooter-inner">
			<div class="sub-footer-links">
				<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms and conditions</a>
				<a href="<?php echo esc_url( home_url( '/shipping/' ) ); ?>">Shipping policy</a>
				<a href="<?php echo esc_url( home_url( '/returns/' ) ); ?>">Returns, refunds and cancellations</a>
				<a href="<?php echo esc_url( home_url( '/payment/' ) ); ?>">Payment policy</a>
				<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">Privacy policy</a>
				<a href="<?php echo esc_url( home_url( '/cookie/' ) ); ?>">Cookie policy</a>
				<a href="<?php echo esc_url( home_url( '/cookie-pref/' ) ); ?>">Cookie preferences</a>
			</div>

			<div class="footer-bottom-row">
				<div class="footer-copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> Wookiee Decor Ltd. All rights reserved.</div>
				<div class="footer-payments">
					<span class="footer-payments-label">Accepted payments</span>

					<div class="payment-icon-wrapper" title="Visa">
						<svg width="24" height="16" viewBox="0 0 24 16" fill="currentColor" style="color: #ffffff;"><path d="M9.1 11.2l1-4.8c.1-.3.3-.4.6-.4h2.2c.1 0 .2.1.2.2l-2.4 5c0 .1-.2.2-.3.2H9.3c-.1 0-.2-.1-.2-.2zm-5.4.2c0-.2.2-.4.4-.4h2.9c.2 0 .4-.1.5-.3l1.8-4.7c0-.1-.1-.2-.2-.2h-2c-.3 0-.5.2-.6.4l-1.3 4h-.1L3.9 6.2c0-.1-.1-.2-.2-.2H1.5c-.1 0-.2.1-.2.2l2.1 5c0 .1.2.2.3.2h0zm12.3-.2l.6-3c.1-.3.3-.5.6-.5h1.9c.1 0 .2.1.2.2l-1 4.8c0 .1-.2.2-.3.2h-2c-.1 0-.2-.1-.2-.2l.2-1.5zm6.5.2l.6-2.8c.2-.9.9-1.2 1.6-1.2.4 0 .7.1.8.2.1.1.1.2.1.3l-.9 4.3c0 .1-.2.2-.3.2h-2c-.1 0-.2-.1-.2-.2l.3-1z"/></svg>
					</div>
					<div class="payment-icon-wrapper" title="Mastercard">
						<svg width="24" height="16" viewBox="0 0 24 16" fill="none"><circle cx="8" cy="8" r="6" fill="#EB001B" fill-opacity="0.8"/><circle cx="16" cy="8" r="6" fill="#F79E1B" fill-opacity="0.8"/><path d="M12 11.3a5.9 5.9 0 0 1 0-6.6 5.9 5.9 0 0 1 0 6.6z" fill="#FF5F00"/></svg>
					</div>
					<div class="payment-icon-wrapper" title="PayPal">
						<svg width="24" height="16" viewBox="0 0 24 16" fill="none"><path d="M7 13.5l1.5-6.5h3c1.5 0 2.5.5 2.8 1.5s0 2-1 2.8c-.8.8-1.8 1.2-3 1.2H8.3L7 13.5z" fill="#003087"/><path d="M9 14.5l1.5-6.5h3c1.5 0 2.5.5 2.8 1.5s0 2-1 2.8c-.8.8-1.8 1.2-3 1.2h-2L9 14.5z" fill="#0079C1"/></svg>
					</div>
					<div class="payment-icon-wrapper" title="American Express">
						<svg width="24" height="16" viewBox="0 0 24 16" fill="none"><rect width="24" height="16" rx="2" fill="#0070CD"/><text x="3" y="11" fill="#fff" font-size="6" font-weight="900" font-family="sans-serif">AMEX</text></svg>
					</div>
					<div class="payment-icon-wrapper" title="Apple Pay">
						<svg width="24" height="16" viewBox="0 0 24 16" fill="none" stroke="#ffffff" stroke-width="1.2"><rect width="23" height="15" x="0.5" y="0.5" rx="2" fill="none"/><text x="4" y="10.5" fill="#ffffff" font-size="7" font-weight="bold" font-family="sans-serif"> Pay</text></svg>
					</div>
				</div>
			</div>
		</div>
	</div>

</footer>

<?php wp_footer(); ?>
</body>
</html>
