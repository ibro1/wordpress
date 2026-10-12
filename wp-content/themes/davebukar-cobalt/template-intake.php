<?php
/**
 * Template Name: Start a project
 *
 * Intake form on /register/. It replaces the old Ultimate Multisite checkout
 * (the shortcode and its data stay in the page and the database, unrendered).
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="hero hero--lite">
	<div class="hero__inner">
		<div class="hero__copy reveal">
			<p class="mono-label">START A PROJECT</p>
			<h1 class="hero__title">Start a project</h1>
			<p class="hero__lede">Tell us what you need. We reply within 2 working hours on WhatsApp, and we quote after a short scoping call.</p>
		</div>
	</div>
</section>

<section class="intake reveal">
	<form id="intake-form" class="intake__form" novalidate>
		<div class="field field--honeypot" aria-hidden="true"><label for="in-website">Website</label><input type="text" id="in-website" name="website" tabindex="-1" autocomplete="off"></div>
		<div class="field"><label for="in-name">Your name</label><input type="text" id="in-name" name="name" required autocomplete="name"></div>
		<div class="field"><label for="in-whatsapp">WhatsApp number</label><input type="tel" id="in-whatsapp" name="whatsapp" required autocomplete="tel" placeholder="+234 …"></div>
		<div class="field"><label for="in-email">Email</label><input type="email" id="in-email" name="email" required autocomplete="email"></div>
		<div class="field"><label for="in-need">What do you need?</label>
			<select id="in-need" name="need">
				<?php foreach ( array( 'Software Development', 'DevOps & Infrastructure', 'SEO & Online Advertising', 'AI Agents & Bots', 'Short clips', 'Not sure yet' ) as $opt ) : ?>
					<option><?php echo esc_html( $opt ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field"><label for="in-message">Tell us about it</label><textarea id="in-message" name="message" required rows="5"></textarea></div>
		<div class="field"><label for="in-budget">Budget range (optional)</label><input type="text" id="in-budget" name="budget" placeholder="In ₦ or £, or “not sure yet”" maxlength="80"></div>
		<div class="field"><label for="in-timeline">Timeline</label>
			<select id="in-timeline" name="timeline">
				<option value="">Choose one</option>
				<?php foreach ( array( 'As soon as possible', 'Within a month', 'In 1 to 3 months', 'Just exploring' ) as $opt ) : ?>
					<option><?php echo esc_html( $opt ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit" class="btn btn--primary intake__submit">Send</button>
		<p class="intake__status" id="intake-status" role="status" aria-live="polite"></p>
		<p class="intake__note">Prefer WhatsApp? <a href="<?php echo esc_url( dbt_wa_link( 'Hi Linkfa, I would like to start a project.' ) ); ?>">Message us directly</a>. See also <a href="<?php echo esc_url( dbt_page_url( 'pricing-faq' ) ); ?>">Pricing &amp; FAQ</a>.</p>
	</form>
</section>

<script>
(function () {
	var form = document.getElementById('intake-form');
	if (!form) return;
	var status = document.getElementById('intake-status');
	var btn = form.querySelector('button[type="submit"]');
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var data = new FormData(form);
		data.append('action', 'dbt_intake');
		data.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'dbt_intake' ) ); ?>);
		btn.disabled = true;
		status.textContent = 'Sending…';
		fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method: 'POST', credentials: 'same-origin', body: data })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				status.textContent = (res && res.data && res.data.message) || 'Something went wrong. Please message us on WhatsApp.';
				if (res && res.success) { form.reset(); } else { btn.disabled = false; }
			})
			.catch(function () {
				status.textContent = 'Something went wrong. Please message us on WhatsApp.';
				btn.disabled = false;
			});
	});
})();
</script>

<?php get_footer(); ?>
