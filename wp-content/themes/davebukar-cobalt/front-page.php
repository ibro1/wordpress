<?php
/**
 * Homepage — Bento Grid macrostructure, Cobalt theme.
 * Hero (title-left / terminal-right) → services bento → one dark
 * graphite band ("how we ship") → CTA → Ft5 statement footer (footer.php).
 */

defined( 'ABSPATH' ) || exit;

get_header();

$services = dbt_services();
$home_terminal = array(
	'$ dbt ship --project=client-app',
	'==> Build… done',
	'==> Tests… PASS',
	'==> Deploy (zero-downtime)… done',
	'==> Health check… 200 OK',
	'$ dbt agents check support-bot',
	'==> Escalation path… verified',
);

$svc_url = function ( $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
};
?>

<section class="hero">
	<div class="hero__inner">
		<div class="hero__copy reveal">
			<h1 class="hero__title">We build the software behind your business — and keep it running.</h1>
			<p class="hero__lede">Custom web, mobile and desktop apps. DevOps that ships without downtime. Advertising that gets found. AI agents that handle the repetitive part.</p>
			<div class="hero__actions">
				<a class="btn btn--primary" href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>">Book a call</a>
				<a class="btn btn--outline" href="#services">See what we build</a>
			</div>
		</div>

		<div class="hero__demo reveal">
			<div class="code-card" data-typein>
				<div class="code-card__bar">
					<span class="code-card__file">deploy.sh</span>
					<span class="status status--ok">200 OK</span>
				</div>
				<pre class="code-card__body" aria-hidden="false"><code><?php
					foreach ( $home_terminal as $i => $line ) {
						$cls = ( 0 === strpos( $line, '$' ) ) ? 'tok-cmd' : ( ( false !== strpos( $line, 'PASS' ) || false !== strpos( $line, '200 OK' ) ) ? 'tok-key' : 'tok-muted' );
						echo '<span class="code-line ' . esc_attr( $cls ) . '" data-line="' . (int) $i . '">' . esc_html( $line ) . '</span>' . "\n";
					}
				?></code></pre>
			</div>
		</div>
	</div>
</section>

<section class="bento reveal" id="services" aria-label="Services">
	<article class="cell span-2x2">
		<p class="cell__label">Software</p>
		<h3 class="cell__title">Web, mobile &amp; desktop apps</h3>
		<p class="cell__body">React, Next.js and Node on the web. Swift, Kotlin and React Native on mobile. Electron for desktop tools. One team, the whole stack.</p>
		<a class="cell__link" href="<?php echo esc_url( $svc_url( 'software-development' ) ); ?>">Software development →</a>
	</article>

	<article class="cell span-2x1">
		<p class="cell__label">DevOps</p>
		<h3 class="cell__title">DevOps &amp; Infrastructure</h3>
		<p class="cell__body">cPanel and WHM provisioning, deploy scripts, CI/CD pipelines, and monitoring that pages a person before a customer notices.</p>
		<a class="cell__link" href="<?php echo esc_url( $svc_url( 'devops' ) ); ?>">Explore DevOps →</a>
	</article>

	<article class="cell span-1x1">
		<p class="cell__label">Advertising</p>
		<h3 class="cell__title">Online Advertising</h3>
		<p class="cell__body">Meta and Google Ads campaigns tied to analytics that are actually wired up correctly.</p>
		<a class="cell__link" href="<?php echo esc_url( $svc_url( 'advertising' ) ); ?>">Explore advertising →</a>
	</article>

	<article class="cell span-1x2">
		<p class="cell__label">AI Agents</p>
		<h3 class="cell__title">AI Agents &amp; Bots</h3>
		<p class="cell__body">Support agents, internal tools and workflow automation — wired into the systems you already run, not bolted on.</p>
		<a class="cell__link" href="<?php echo esc_url( $svc_url( 'ai-agents' ) ); ?>">Explore AI agents →</a>
	</article>

	<article class="cell span-1x1 cell--accent">
		<h3 class="cell__title">Not sure where to start?</h3>
		<p class="cell__body">Tell us what you’re building. We’ll scope it on a call — no deck, no pitch.</p>
		<a class="cell__link" href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>">Book a call →</a>
	</article>

	<article class="cell span-1x1">
		<h3 class="cell__title">How we ship</h3>
		<p class="cell__body">Scope, build, ship — with a written plan and a staging environment at every step.</p>
		<a class="cell__link" href="#how-we-work">See our process →</a>
	</article>
</section>

<section class="band reveal" id="how-we-work" aria-label="How we ship">
	<div class="band__inner">
		<h2 class="band__title">How we ship</h2>
		<ol class="band__steps">
			<li class="band__step">
				<span class="band__step-no">01</span>
				<h3 class="band__step-title">Scope</h3>
				<p class="band__step-body">A real call, a written plan, and — where possible — fixed pricing before a line of code is written.</p>
			</li>
			<li class="band__step">
				<span class="band__step-no">02</span>
				<h3 class="band__step-title">Build</h3>
				<p class="band__step-body">Weekly check-ins against a staging environment. No black box, no surprise invoice.</p>
			</li>
			<li class="band__step">
				<span class="band__step-no">03</span>
				<h3 class="band__step-title">Ship</h3>
				<p class="band__step-body">Deployed through our own DevOps pipeline: zero-downtime, monitored, documented.</p>
			</li>
		</ol>
	</div>
</section>

<section class="cta reveal">
	<div class="cta__inner">
		<h2 class="cta__title">Tell us what you’re building.</h2>
		<div class="cta__actions">
			<a class="btn btn--primary" href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>">Book a call</a>
			<a class="cta__email" href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>"><?php echo esc_html( DBT_CONTACT_EMAIL ); ?></a>
		</div>
	</div>
</section>

<?php get_footer(); ?>
