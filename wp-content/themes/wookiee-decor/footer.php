<footer class="site-footer">

	<!-- Newsletter Band -->
	<?php
	/*
	 * A statement on the left, the form in a card on the right.
	 *
	 * This was a heading, a line of subtext and an input on one row - and no
	 * consent notice at all, which is the part that actually mattered. A UK
	 * shop collecting an address for marketing needs consent under PECR, and
	 * the notice has to say what is being consented to, that it is optional
	 * and not a condition of buying, and how to withdraw it. That wording is
	 * fixed below rather than generated: it is a legal statement, and it is
	 * not something an AI should be inventing per store.
	 *
	 * The copy around it IS generated, so it describes this shop rather than
	 * inviting a travel-accessories customer to get tidier.
	 */
	// Deduplicated as well as emptied: the generator is told that repeating a
	// reason is how it declines to invent a third, so that has to be true
	// here or it will pad the list rather than leave it short.
	$wookiee_nl_benefits = array_values( array_unique( array_filter( array_map( 'trim', array(
		(string) wookiee_get_setting( 'newsletter_benefit_1' ),
		(string) wookiee_get_setting( 'newsletter_benefit_2' ),
		(string) wookiee_get_setting( 'newsletter_benefit_3' ),
	) ) ) ) );
	$wookiee_nl_brand = trim( (string) wookiee_get_setting( 'business_name' ) );
	$wookiee_nl_brand = '' !== $wookiee_nl_brand ? $wookiee_nl_brand : get_bloginfo( 'name' );
	?>
	<div class="footer-newsletter-wrap">
		<div class="container footer-newsletter">
			<div class="newsletter-text">
				<?php if ( '' !== trim( (string) wookiee_get_setting( 'newsletter_eyebrow' ) ) ) : ?>
					<span class="newsletter-eyebrow"><?php echo esc_html( wookiee_get_setting( 'newsletter_eyebrow' ) ); ?></span>
				<?php endif; ?>
				<span class="newsletter-heading"><?php echo esc_html( wookiee_get_setting( 'newsletter_heading' ) ); ?></span>
				<span class="newsletter-sub"><?php echo esc_html( wookiee_get_setting( 'newsletter_sub' ) ); ?></span>
				<?php if ( $wookiee_nl_benefits ) : ?>
					<ul class="newsletter-benefits">
						<?php foreach ( $wookiee_nl_benefits as $wookiee_nl_benefit ) : ?>
							<li><span aria-hidden="true">&#10003;</span> <?php echo esc_html( $wookiee_nl_benefit ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<form class="newsletter-form">
				<span class="newsletter-form-icon" aria-hidden="true">&#9993;</span>
				<span class="newsletter-form-heading"><?php echo esc_html( wookiee_get_setting( 'newsletter_form_heading' ) ); ?></span>
				<span class="newsletter-form-sub">Enter your email address to join our mailing list.</span>
				<span class="newsletter-form-row">
				<input type="email" placeholder="Your email address" required>
				<button type="submit">Sign me up</button>
				</span>
				<?php
				/*
				 * Fixed wording, deliberately. This is the PECR consent
				 * statement, and it has to carry three things to be worth
				 * anything: what the address will be used for, that agreeing
				 * is optional and not a condition of buying, and how to
				 * withdraw. Generated copy would vary those per store, which
				 * is the one thing that must not happen.
				 */
				?>
				<span class="newsletter-consent">
					By entering your email address and selecting &ldquo;Sign me up&rdquo;, you consent to receive marketing emails from <?php echo esc_html( $wookiee_nl_brand ); ?>. Marketing is optional and is not required to make a purchase. You can withdraw your consent at any time using the unsubscribe link in any marketing email.
					<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Read our Privacy Policy</a>. <a href="<?php echo esc_url( home_url( '/cookie/' ) ); ?>">Read our Cookie Policy</a>.
				</span>
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
				<?php wookiee_render_payment_marks(); ?>
			</div>
		</div>
	</div>

</footer>

<?php wp_footer(); ?>
</body>
</html>
