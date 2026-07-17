<?php
/**
 * Static page content for Tatyli Fresh Rebuild.
 */

defined( 'ABSPATH' ) || exit;

function tatyli_fresh_starter_pages() {
	return array(
		'home'           => array( 'title' => 'Home', 'menu' => 'Home' ),
		'about'          => array( 'title' => 'About', 'menu' => 'About' ),
		'mission'        => array( 'title' => 'Mission', 'menu' => 'Mission' ),
		'activities'     => array( 'title' => 'Activities', 'menu' => 'Activities' ),
		'contact'        => array( 'title' => 'Contact', 'menu' => 'Contact' ),
		'privacy-policy' => array( 'title' => 'Privacy Policy', 'menu' => 'Privacy Policy' ),
	);
}

function tatyli_fresh_render_static_page( $slug = '' ) {
	$slug = $slug ? $slug : tatyli_fresh_current_slug();

	switch ( $slug ) {
		case 'home':
			tatyli_fresh_render_home();
			break;
		case 'about':
			tatyli_fresh_render_about();
			break;
		case 'mission':
			tatyli_fresh_render_mission();
			break;
		case 'activities':
			tatyli_fresh_render_activities();
			break;
		case 'contact':
			tatyli_fresh_render_contact();
			break;
		case 'privacy-policy':
			tatyli_fresh_render_privacy_policy();
			break;
		default:
			return false;
	}

	return true;
}

function tatyli_fresh_hero_style() {
	return 'style="--tat-hero-image:url(' . esc_url( TATYLI_FRESH_URI . 'assets/media/tatyli-hero.svg' ) . ')"';
}

function tatyli_fresh_page_hero( $title, $kicker = '' ) {
	?>
	<section class="tat-hero tat-hero-small" <?php echo tatyli_fresh_hero_style(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="tat-container tat-hero-content">
			<?php if ( $kicker ) : ?><p class="tat-kicker"><?php echo esc_html( $kicker ); ?></p><?php endif; ?>
			<h1><?php echo esc_html( $title ); ?></h1>
		</div>
	</section>
	<?php
}

function tatyli_fresh_art_panel( $title, $text = '' ) {
	?>
	<div class="tat-art-panel" aria-label="<?php echo esc_attr( $title ); ?>">
		<div class="tat-art-orbit tat-art-orbit-one"></div>
		<div class="tat-art-orbit tat-art-orbit-two"></div>
		<div class="tat-art-mark"><img src="<?php echo esc_url( TATYLI_FRESH_URI . 'assets/media/tatyli-symbol.svg' ); ?>" alt=""></div>
		<div class="tat-art-caption">
			<strong><?php echo esc_html( $title ); ?></strong>
			<?php if ( $text ) : ?><span><?php echo esc_html( $text ); ?></span><?php endif; ?>
		</div>
	</div>
	<?php
}

function tatyli_fresh_render_home() {
	?>
	<section class="tat-hero" <?php echo tatyli_fresh_hero_style(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="tat-container tat-hero-content">
			<p class="tat-kicker">Non-profit cultural organization</p>
			<h1>Tatyli</h1>
			<p>Tatyli is a cultural association based in Liège, Belgium. It brings people together through folk traditions, performing arts, learning, and community participation.</p>
			<div class="tat-hero-actions">
				<a class="tat-button tat-button-light" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About Tatyli</a>
				<a class="tat-button tat-button-outline" href="<?php echo esc_url( home_url( '/activities/' ) ); ?>">Activities</a>
			</div>
		</div>
	</section>

	<section class="tat-section">
		<div class="tat-container tat-split">
			<div class="tat-panel">
				<p class="tat-kicker">About</p>
				<h2>A space for cultural presence and shared understanding</h2>
				<p>Tatyli was formed from a shared wish to give cultural traditions a visible and respectful place in public life. The association works with the arts as a way to connect people, protect memory, and encourage dialogue between communities.</p>
				<p>Its work is rooted in cultural citizenship. Tatyli recognises that culture is not separate from daily life; it shapes how people meet, learn, remember, and take part in society.</p>
				<p>The association gives attention to folk traditions, artistic expression, public gatherings, educational moments, and community-led participation.</p>
				<p><a class="tat-button" href="<?php echo esc_url( home_url( '/mission/' ) ); ?>">Read the Mission</a></p>
			</div>
			<?php tatyli_fresh_art_panel( 'Culture, learning, and community', 'A simple symbol for shared roots, movement, and dialogue.' ); ?>
		</div>
	</section>

	<section class="tat-section tat-section-alt">
		<div class="tat-container">
			<div class="tat-section-head">
				<p class="tat-kicker">Focus</p>
				<h2>Work shaped by people, place, and memory</h2>
				<p>Tatyli approaches culture as something living: carried by people, renewed through participation, and strengthened when communities meet with respect.</p>
			</div>
			<div class="tat-grid">
				<div class="tat-card"><span class="tat-number">01</span><h3>Cultural exchange</h3><p>Creating moments where people can encounter different traditions with care, curiosity, and mutual respect.</p></div>
				<div class="tat-card"><span class="tat-number">02</span><h3>Living traditions</h3><p>Giving folk practices, music, dance, storytelling, and artistic forms a place in the present.</p></div>
				<div class="tat-card"><span class="tat-number">03</span><h3>Community learning</h3><p>Encouraging participation through workshops, conversations, exhibitions, and shared public activities.</p></div>
			</div>
		</div>
	</section>

	<section class="tat-section-tight">
		<div class="tat-container">
			<div class="tat-cta">
				<div>
					<h2>Activities</h2>
					<p>The association’s activities include cultural exchange, public performances, educational workshops, artistic collaborations, outreach, preservation work, digital projects, and heritage exhibitions.</p>
				</div>
				<a class="tat-button tat-button-light" href="<?php echo esc_url( home_url( '/activities/' ) ); ?>">View Activities</a>
			</div>
		</div>
	</section>
	<?php
}

function tatyli_fresh_render_about() {
	tatyli_fresh_page_hero( 'About Tatyli', 'About' );
	?>
	<section class="tat-section">
		<div class="tat-container tat-split">
			<div class="tat-panel">
				<h2>About the association</h2>
				<p>Tatyli is a non-profit cultural organization based in Liège, Belgium. It is dedicated to cultural exchange, performing arts, education, and the preservation of folk traditions and artistic expressions.</p>
				<p>The association was established in 2025 by people who shared a commitment to cultural dialogue and community participation. Its work grew from conversations about how traditions can be protected without being frozen, and how the arts can help people understand one another more deeply.</p>
				<p>Tatyli supports a multicultural society by creating space for expression, learning, and respectful encounter. It values heritage, but also recognises that culture continues to develop through the people who carry it forward.</p>
			</div>
			<?php tatyli_fresh_art_panel( 'Shared heritage', 'Traditions remain alive when they are remembered, practised, and passed on.' ); ?>
		</div>
	</section>

	<section class="tat-section tat-section-alt">
		<div class="tat-container">
			<div class="tat-panel tat-wide-panel">
				<h2>How Tatyli works</h2>
				<p>Tatyli’s approach is calm, community-centred, and respectful of the people and traditions involved. The association brings attention to cultural practices through activities that can be experienced, discussed, learned, and documented.</p>
				<p>Rather than treating culture as display alone, Tatyli understands it as a form of belonging. Its activities are intended to support participation, awareness, memory, and connection across communities.</p>
			</div>
		</div>
	</section>
	<?php
}

function tatyli_fresh_render_mission() {
	tatyli_fresh_page_hero( 'Mission', 'Purpose' );
	?>
	<section class="tat-section">
		<div class="tat-container tat-split">
			<div class="tat-panel">
				<h2>Mission</h2>
				<p>Tatyli’s mission is to support cultural understanding through the performing arts, folk traditions, education, and community participation.</p>
				<p>As a non-profit organization, Tatyli works to preserve and share cultural expressions from different backgrounds while encouraging dialogue between people. The association gives importance to accessibility, respect, and the right of communities to see their heritage recognised.</p>
				<p>The mission is not only to present culture, but to create conditions where people can learn from one another, take part with dignity, and recognise the value of diverse cultural histories.</p>
			</div>
			<?php tatyli_fresh_art_panel( 'Respectful participation', 'Culture becomes stronger when people can meet, listen, and take part.' ); ?>
		</div>
	</section>

	<section class="tat-section tat-section-alt">
		<div class="tat-container">
			<div class="tat-section-head">
				<p class="tat-kicker">Values</p>
				<h2>Principles behind the work</h2>
			</div>
			<div class="tat-grid">
				<div class="tat-card"><span class="tat-number">01</span><h3>Respect</h3><p>Each cultural expression is approached with care for the people, histories, and meanings connected to it.</p></div>
				<div class="tat-card"><span class="tat-number">02</span><h3>Access</h3><p>Arts and cultural learning should be open to people from different backgrounds and life situations.</p></div>
				<div class="tat-card"><span class="tat-number">03</span><h3>Dialogue</h3><p>Exchange is understood as listening as much as sharing, with attention to dignity and mutual recognition.</p></div>
			</div>
		</div>
	</section>
	<?php
}

function tatyli_fresh_activities() {
	return array(
		array(
			'title' => 'Cultural Exchange Programs',
			'text'  => 'Meetings and shared cultural moments that allow people from different backgrounds to learn about one another through practice, conversation, and artistic expression.',
		),
		array(
			'title' => 'Public Performances and Festivals',
			'text'  => 'Public presentations of music, dance, storytelling, and folk traditions, giving communities a respectful place to be seen and heard.',
		),
		array(
			'title' => 'Workshops and Educational Programs',
			'text'  => 'Learning sessions that introduce cultural practices, histories, and artistic forms in a clear and participatory way.',
		),
		array(
			'title' => 'Artistic Collaborations and Residencies',
			'text'  => 'Collaborative spaces where artists and cultural practitioners can exchange methods, develop ideas, and create work rooted in dialogue.',
		),
		array(
			'title' => 'Community Outreach Initiatives',
			'text'  => 'Activities that connect with local communities and encourage participation, awareness, and cultural presence in everyday settings.',
		),
		array(
			'title' => 'Cultural Preservation Projects',
			'text'  => 'Efforts to document, remember, and support traditions so they can continue to be understood by future generations.',
		),
		array(
			'title' => 'Collaborative Digital Projects',
			'text'  => 'Digital work that helps collect, share, and present cultural material with care, making it easier for people to access and remember.',
		),
		array(
			'title' => 'Cultural Heritage Exhibitions',
			'text'  => 'Exhibitions that bring attention to cultural heritage, objects, stories, and artistic practices in a thoughtful public setting.',
		),
	);
}

function tatyli_fresh_render_activities() {
	tatyli_fresh_page_hero( 'Activities', 'Tatyli' );
	?>
	<section class="tat-section">
		<div class="tat-container">
			<div class="tat-section-head">
				<p class="tat-kicker">Activities</p>
				<h2>Areas of work</h2>
				<p>These activities describe the areas through which Tatyli supports cultural exchange, learning, preservation, and community participation.</p>
			</div>
			<div class="tat-grid tat-activities">
				<?php foreach ( tatyli_fresh_activities() as $activity ) : ?>
					<article class="tat-card tat-activity">
						<h4><?php echo esc_html( $activity['title'] ); ?></h4>
						<p><?php echo esc_html( $activity['text'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

function tatyli_fresh_render_contact() {
	tatyli_fresh_page_hero( 'Contact', 'Tatyli' );
	$status = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : '';
	?>
	<section class="tat-section">
		<div class="tat-container tat-contact-wrap">
			<div class="tat-panel">
				<h2>Contact</h2>
				<p>For questions related to Tatyli, its activities, or general communication, the association can be contacted using the details below.</p>
				<div class="tat-contact-list">
					<div class="tat-contact-item"><span aria-hidden="true">📍</span><span><strong>Address</strong>Rue Hocheporte 20, 4000 Liège, Belgium</span></div>
					<div class="tat-contact-item"><span aria-hidden="true">✉️</span><span><strong>Email</strong><a href="mailto:info@tatyli.be">info@tatyli.be</a></span></div>
					<div class="tat-contact-item"><span aria-hidden="true">☎️</span><span><strong>Phone</strong><a href="tel:+32489249962">+32 489249962</a></span></div>
				</div>
			</div>
			<div class="tat-panel">
				<?php if ( 'sent' === $status ) : ?>
					<p class="tat-alert">Thank you. Your message has been sent.</p>
				<?php elseif ( 'mail-error' === $status ) : ?>
					<p class="tat-alert">The message could not be sent right now. Please email Tatyli directly at info@tatyli.be.</p>
				<?php elseif ( in_array( $status, array( 'missing', 'invalid' ), true ) ) : ?>
					<p class="tat-alert">Please check the form and try again.</p>
				<?php endif; ?>
				<form class="tat-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="tatyli_contact">
					<?php wp_nonce_field( 'tatyli_contact', 'tatyli_contact_nonce' ); ?>
					<label>Name<input type="text" name="tatyli_name" autocomplete="name" required></label>
					<label>Email<input type="email" name="tatyli_email" autocomplete="email" required></label>
					<label>Message<textarea name="tatyli_message" required></textarea></label>
					<label class="tat-honeypot" aria-hidden="true">Website<input type="text" name="tatyli_website" tabindex="-1" autocomplete="off"></label>
					<button class="tat-contact-submit" type="submit">Send Message</button>
				</form>
			</div>
		</div>
	</section>
	<?php
}

function tatyli_fresh_render_privacy_policy() {
	tatyli_fresh_page_hero( 'Privacy Policy', 'Tatyli' );
	?>
	<section class="tat-section">
		<div class="tat-container">
			<article class="tat-rich">
				<p><strong>Last updated: 19 April 2026</strong></p>

				<p>Tatyli respects your privacy. This Privacy Policy explains how Tatyli handles personal data when you visit this website or contact us through the contact form, email, or phone.</p>

				<h3>1. Who we are</h3>
				<p>Tatyli is a non-profit organization based in Belgium.</p>
				<p><strong>Tatyli</strong><br>Registered No.: 1021799582<br>Rue Hocheporte 20, 4000 Liège, Belgium<br>Email: <a href="mailto:info@tatyli.be">info@tatyli.be</a><br>Phone: <a href="tel:+32489249962">+32 489249962</a></p>
				<p>For any question about this Privacy Policy or how we handle personal data, you can contact us at <a href="mailto:info@tatyli.be">info@tatyli.be</a>.</p>

				<h3>2. What this Privacy Policy covers</h3>
				<p>This Privacy Policy applies to personal data handled through this website, mainly when a visitor uses the contact form or contacts Tatyli directly.</p>

				<h3>3. Personal data we collect</h3>
				<p>When you use the contact form, we collect the information needed to receive and respond to your message:</p>
				<ul>
					<li>your name;</li>
					<li>your email address;</li>
					<li>your message;</li>
					<li>any other personal information you choose to include in your message.</li>
				</ul>
				<p>Like most websites, the website or hosting provider may also process basic technical information, such as IP address, browser type, date and time of visit, pages requested, and security logs. This information is used only to keep the website working, secure, and reliable.</p>

				<h3>4. Why we use personal data</h3>
				<p>We use personal data for limited and practical purposes:</p>
				<ul>
					<li>to receive and respond to messages sent through the contact form;</li>
					<li>to communicate with people who contact Tatyli;</li>
					<li>to keep a reasonable record of communication where necessary;</li>
					<li>to maintain the security and functionality of the website;</li>
					<li>to comply with legal obligations, if required.</li>
				</ul>
				<p>We do not use contact form messages for advertising, automated marketing, or unrelated promotional mailing lists.</p>
					<h3>5. Legal basis for processing</h3>
					<p>We process personal data only where we have a lawful reason to do so. For this website, the relevant legal bases are mainly:</p>
					<ul>
						<li>our legitimate interest in receiving and responding to messages sent to Tatyli;</li>
						<li>our legitimate interest in keeping the website secure, reliable, and functional;</li>
						<li>compliance with a legal obligation, where applicable.</li>
					</ul>

					<h3>6. Contact form messages</h3>
				<p>When you submit the contact form, the information you provide is sent to Tatyli by email. The message may be stored in our email inbox and, where necessary, in website or server records connected with form delivery and security.</p>
				<p>Please avoid sending sensitive personal information through the contact form unless it is necessary for your message.</p>

				<h3>7. Cookies and tracking</h3>
				<p>This website does not use advertising scripts, marketing pixels, or analytics trackers.</p>
				<p>The website, WordPress, or the hosting provider may use strictly necessary cookies or technical logs required for normal website operation, security, spam prevention, or administration. These are not used by Tatyli to build advertising profiles.</p>
				<p>You can manage or disable cookies through your browser settings, although some website functions may not work properly if essential cookies are blocked.</p>

				<h3>8. Who we share personal data with</h3>
				<p>We do not sell personal data.</p>
				<p>We may share or make personal data available only where necessary to:</p>
				<ul>
					<li>website hosting and IT service providers;</li>
					<li>email service providers used to receive and respond to messages;</li>
					<li>professional advisers, if required;</li>
					<li>public authorities, if required by law.</li>
				</ul>
				<p>Where service providers process personal data on our behalf, we expect them to protect it and use it only for the relevant purpose.</p>

				<h3>9. International transfers</h3>
				<p>If personal data is processed outside the European Economic Area by a technical or email service provider, Tatyli relies on appropriate safeguards required by applicable data protection law.</p>

				<h3>10. How long we keep personal data</h3>
				<p>We keep personal data only for as long as necessary for the purpose for which it was collected.</p>
				<ul>
					<li>Contact form messages may be kept for up to 24 months after the last communication, unless a longer period is necessary for legal, administrative, or safety reasons.</li>
					<li>Technical and security logs are kept only for a limited period determined by website or hosting security needs.</li>
				</ul>

				<h3>11. Your rights</h3>
				<p>Under applicable data protection law, you may have the right to:</p>
				<ul>
					<li>access your personal data;</li>
					<li>ask us to correct inaccurate or incomplete personal data;</li>
					<li>ask us to delete your personal data;</li>
					<li>ask us to restrict certain processing;</li>
					<li>object to certain processing;</li>
					<li>lodge a complaint with a supervisory authority.</li>
				</ul>
				<p>To exercise your rights, please contact us at <a href="mailto:info@tatyli.be">info@tatyli.be</a>. We may need to verify your identity before responding to your request.</p>

				<h3>12. Security</h3>
				<p>We take reasonable technical and organizational measures to protect personal data against unauthorized access, loss, misuse, disclosure, alteration, or destruction.</p>
				<p>No method of online transmission or electronic storage is completely secure. We therefore cannot guarantee absolute security, but we take reasonable care to protect personal data.</p>

				<h3>13. Third-party links</h3>
				<p>This website may contain links to third-party websites or platforms. Tatyli is not responsible for the privacy practices of third parties. Please read their privacy policies before providing personal data to them.</p>

				<h3>14. Changes to this Privacy Policy</h3>
				<p>We may update this Privacy Policy from time to time. The latest version will be published on this website with the date of the last update.</p>
			</article>
		</div>
	</section>
	<?php
}
