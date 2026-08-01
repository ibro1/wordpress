<footer class="site-footer">

	<!-- Newsletter Band -->
	<div class="footer-newsletter-wrap">
		<div class="container footer-newsletter">
			<div class="newsletter-text">
				<?php
				/*
				 * Was hardcoded to "Stay organised with X" and "Home-
				 * organisation ideas" - so a travel-accessories shop invited
				 * customers to get tidier, and no niche change could ever
				 * reach it because it was not a setting at all. Now editable
				 * and written by the homepage copy generator like the rest of
				 * the page, with a default that says nothing about any niche.
				 */
				?>
				<span class="newsletter-heading"><?php echo esc_html( wookiee_get_setting( 'newsletter_heading' ) ); ?></span>
				<span class="newsletter-sub"><?php echo esc_html( wookiee_get_setting( 'newsletter_sub' ) ); ?></span>
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
			<?php get_template_part( 'template-parts/site-logo', null, array( 'link_class' => 'footer-logo-link' ) ); ?>
			<p class="footer-about-copy">
				<?php
				/*
				 * Was hardcoded to "UK private-label home-storage brand" - so a
				 * skincare shop introduced itself as a storage brand in its own
				 * footer, on every page. Uses the About page's lead sentence,
				 * which is generated per store and says what this one actually
				 * sells; the plain operator line is the fallback until that
				 * exists.
				 */
				$wookiee_footer_about = trim( (string) get_option( 'wookiee_setting_about_hero_lead', '' ) );
				if ( '' !== $wookiee_footer_about ) {
					echo esc_html( $wookiee_footer_about );
				} else {
					printf(
						/* translators: %s: registered business name. */
						esc_html__( 'Operated by %s.', 'wookiee-commerce' ),
						esc_html( wookiee_get_setting( 'business_name' ) )
					);
				}
				?>
			</p>
			<?php
			$wookiee_socials = array(
				'facebook_url'  => array( 'label' => 'Facebook', 'icon' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>' ),
				'instagram_url' => array( 'label' => 'Instagram', 'icon' => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>' ),
				'linkedin_url'  => array( 'label' => 'LinkedIn', 'icon' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle>' ),
				'pinterest_url' => array( 'label' => 'Pinterest', 'icon' => '<path d="M8 22c-.5 0-.9-.2-1.1-.6-.5-.9-.1-2.2.8-3.7l1.7-2.7c-.5-.9-.8-2-.8-3.2 0-3.3 2.7-6 6-6s6 2.7 6 6c0 3.8-2.7 7-6.5 7-1.3 0-2.5-.5-3.3-1.4l-.8 3.1c-.4 1.5-1.4 3-1.5 3.1-.1.1-.3.2-.5.2z"></path><path d="M12 9c-.8 0-1.5.7-1.5 1.5s.7 1.5 1.5 1.5 1.5-.7 1.5-1.5S12.8 9 12 9z"></path>' ),
			);
			$wookiee_active_socials = array_filter( $wookiee_socials, function( $key ) {
				return '' !== trim( (string) wookiee_get_setting( $key ) );
			}, ARRAY_FILTER_USE_KEY );
			?>
			<?php if ( $wookiee_active_socials ) : ?>
			<div class="footer-socials">
				<?php foreach ( $wookiee_active_socials as $key => $social ) : ?>
				<a href="<?php echo esc_url( wookiee_get_setting( $key ) ); ?>" aria-label="<?php echo esc_attr( $social['label'] ); ?>" class="social-icon-btn" target="_blank" rel="noopener noreferrer">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $social['icon']; ?></svg>
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="footer-col">
			<div class="footer-eyebrow">Shop</div>
			<ul class="footer-links-list">
				<li><a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">All products</a></li>
				<?php foreach ( wookiee_get_display_categories( 3 ) as $footer_cat ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $footer_cat ) ); ?>"><?php echo esc_html( $footer_cat->name ); ?></a></li>
				<?php endforeach; ?>
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
			<p class="footer-info-text footer-contact-line"><span>&#9993;</span> <a href="mailto:<?php echo esc_attr( wookiee_get_setting( 'contact_email' ) ); ?>"><?php echo esc_html( wookiee_get_setting( 'contact_email' ) ); ?></a></p>
			<?php
			/*
			 * The phone was plain text while the email beside it was a link,
			 * so on a phone - where most of this traffic is - the one number a
			 * customer might actually want to press could not be pressed. The
			 * tel: target is digits only (plus a leading +), since spaces and
			 * brackets are for reading, not for dialling.
			 */
			$wookiee_phone     = trim( (string) wookiee_get_setting( 'contact_phone' ) );
			$wookiee_phone_href = preg_replace( '/[^\d+]/', '', $wookiee_phone );

			/*
			 * UK addresses routinely end at the home nation, which is not the
			 * country for anyone reading from outside it. Appended only when
			 * the address does not already name it, so an address that says
			 * "United Kingdom" is left exactly as typed.
			 */
			$wookiee_address = trim( (string) wookiee_get_setting( 'registered_address' ) );
			if ( '' !== $wookiee_address && ! preg_match( '/\b(united kingdom|uk)\b/i', $wookiee_address ) ) {
				$wookiee_address .= "\nUnited Kingdom";
			}
			?>
			<?php if ( '' !== $wookiee_phone ) : ?>
				<p class="footer-info-text footer-contact-line"><span>&#128222;</span> <a href="tel:<?php echo esc_attr( $wookiee_phone_href ); ?>"><strong><?php echo esc_html( $wookiee_phone ); ?></strong></a></p>
			<?php endif; ?>
			<p class="footer-info-text">
				<?php echo nl2br( esc_html( $wookiee_address ) ); ?><br>
				<span class="footer-info-small">Company No. <?php echo esc_html( wookiee_get_setting( 'company_number' ) ); ?></span>
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
				<a href="<?php echo esc_url( home_url( '/payment/' ) ); ?>">Payment</a>
				<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">Privacy</a>
				<a href="<?php echo esc_url( home_url( '/cookie/' ) ); ?>">Cookies</a>
				<a href="<?php echo esc_url( home_url( '/cookie-pref/' ) ); ?>">Cookie preferences</a>
			</div>

			<div class="footer-bottom-row">
				<div class="footer-copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( wookiee_get_setting( 'business_name' ) ); ?>.</div>
				<div class="footer-payments">
					<?php
					/*
					 * These were not icons. Four of the five were an <svg>
					 * containing a <text> node with the brand's NAME in white
					 * - literally the word "VISA" typeset in Georgia italic -
					 * which is why only Mastercard, the one real mark, looked
					 * like anything. They also inherited whatever font the
					 * device had, so the "logo" changed shape per browser.
					 *
					 * Replaced with drawn marks on light tiles: brand colours,
					 * fixed geometry, no text nodes and no font dependency, so
					 * they render identically everywhere and stay legible
					 * whatever the footer background is.
					 */
					?>
					<div class="payment-icon-wrapper" title="Visa">
						<svg viewBox="0 0 48 30" role="img" aria-label="Visa" focusable="false">
							<path fill="#1A1F71" d="M20.6 20.4h-3l1.9-11.2h3l-1.9 11.2Zm-5.5-11.2-2.9 7.7-.3-1.7-1-5.2s-.1-.8-1.1-.8H5.1l-.1.2s1.1.2 2.4 1l2.6 10h3.1l4.8-11.2h-2.8Zm16.4 7.3 1.6-4.3.9 4.3h-2.5Zm3.3 3.9h2.8l-2.4-11.2h-2.4c-1.1 0-1.4.9-1.4.9l-4.5 10.3h3.1l.6-1.7h3.8l.4 1.7Zm-6.7-8.4.4-2.5s-1.3-.5-2.7-.5c-1.5 0-5 .6-5 3.8 0 3 4.1 3 4.1 4.6s-3.7 1.3-4.9.3l-.4 2.6s1.3.6 3.3.6 5-1 5-3.9c0-3-4.2-3.3-4.2-4.6 0-1.3 2.9-1.1 4.4-.4Z"/>
						</svg>
					</div>
					<div class="payment-icon-wrapper" title="Mastercard">
						<svg viewBox="0 0 48 30" role="img" aria-label="Mastercard" focusable="false">
							<circle cx="19" cy="15" r="9" fill="#EB001B"/>
							<circle cx="29" cy="15" r="9" fill="#F79E1B"/>
							<path fill="#FF5F00" d="M24 8.1a9 9 0 0 0 0 13.8 9 9 0 0 0 0-13.8Z"/>
						</svg>
					</div>
					<div class="payment-icon-wrapper" title="PayPal">
						<svg viewBox="0 0 48 30" role="img" aria-label="PayPal" focusable="false">
							<path fill="#003087" d="M17.9 7.3h6.4c3.4 0 5.6 1.7 5.2 5.1-.4 3.9-3 5.7-6.5 5.7h-2.4l-.7 4.6h-3.6l1.6-15.4Zm2.9 2.9-.6 4.9h1.9c1.7 0 3-.7 3.2-2.6.2-1.6-.7-2.3-2.3-2.3h-2.2Z"/>
							<path fill="#009CDE" d="M25.7 10.6h6.4c3.4 0 5 1.7 4.6 5.1-.4 3.9-3 5.7-6.5 5.7h-2.4l-.7 4.6h-3.6l2.2-15.4Zm2.9 2.9-.6 4.9h1.9c1.7 0 3-.7 3.2-2.6.2-1.6-.7-2.3-2.3-2.3h-2.2Z"/>
						</svg>
					</div>
					<div class="payment-icon-wrapper" title="American Express">
						<svg viewBox="0 0 48 30" role="img" aria-label="American Express" focusable="false">
							<rect x="4" y="5" width="40" height="20" rx="2" fill="#006FCF"/>
							<path fill="#fff" d="M11 11h4.6l1 2.3 1-2.3h12v1l.7-1h3.4l.8 1.7.8-1.7H37v8h-2.9l-.8-1.7-.8 1.7H21.4v-1l-.6 1h-2.5l-.6-1v1H11l-.5-1.2H8.9L8.4 19H5.3l3.2-8H11Zm-1.2 2.1-.9 2.2h1.8l-.9-2.2Zm7.6-.6h-2.2v5h1.4v-3.4l1.5 3.4h1.2l1.5-3.4V17h1.4v-5h-2.2l-1.3 3-1.3-3Zm7 0v5h4.2v-1.2h-2.8v-.8h2.7v-1.2h-2.7v-.7h2.8V12h-4.2Z"/>
						</svg>
					</div>
					<div class="payment-icon-wrapper" title="Apple Pay">
						<svg viewBox="0 0 48 30" role="img" aria-label="Apple Pay" focusable="false">
							<path fill="#000" d="M14.6 10.8c.5-.6.8-1.4.7-2.2-.7 0-1.6.5-2.1 1.1-.5.5-.9 1.3-.7 2.1.8.1 1.6-.4 2.1-1Zm.7 1.2c-1.2-.1-2.2.7-2.7.7-.6 0-1.4-.6-2.3-.6-1.2 0-2.3.7-2.9 1.8-1.2 2.1-.3 5.3.9 7 .6.9 1.3 1.8 2.2 1.8.9 0 1.2-.6 2.3-.6s1.4.6 2.3.6c1 0 1.6-.9 2.2-1.7.7-1 .9-1.9 1-2-.1 0-1.9-.7-1.9-2.8 0-1.7 1.4-2.5 1.5-2.6-.8-1.2-2.1-1.4-2.6-1.6Z"/>
							<path fill="#000" d="M24.2 9.6c2.4 0 4 1.6 4 4 0 2.5-1.7 4.1-4.1 4.1h-2.6v4.2h-1.9V9.6h4.6Zm-2.7 6.5h2.2c1.6 0 2.6-.9 2.6-2.5s-1-2.4-2.6-2.4h-2.2v4.9Zm7.2 3.4c0-1.6 1.2-2.5 3.5-2.7l2.3-.1v-.7c0-1-.6-1.5-1.8-1.5-1 0-1.7.5-1.8 1.2h-1.8c.1-1.6 1.5-2.8 3.7-2.8 2.1 0 3.5 1.1 3.5 2.9v6h-1.8v-1.4h-.1c-.5 1-1.6 1.6-2.8 1.6-1.7 0-2.9-1-2.9-2.5Zm5.8-.8v-.7l-2 .1c-1.2.1-1.9.6-1.9 1.4s.7 1.3 1.6 1.3c1.3 0 2.3-.9 2.3-2.1Zm3.4 6.3v-1.5c.2 0 .5.1.7.1.9 0 1.3-.4 1.6-1.3l.2-.5-3.3-9.1h2l2.3 7.3h.1l2.3-7.3h2l-3.4 9.5c-.8 2.2-1.7 2.9-3.5 2.9-.2 0-.8 0-1-.1Z"/>
						</svg>
					</div>
				</div>
			</div>
		</div>
	</div>

</footer>

<?php wp_footer(); ?>
</body>
</html>
