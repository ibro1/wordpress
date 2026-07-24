<?php
/**
 * Structured content for the four service pages (rendered by
 * template-service.php) and the two legal pages (rendered by
 * template-legal.php). Editing copy means editing this file for the
 * service pages; the legal pages are stored as normal editable
 * post_content in wp-admin once created.
 */

defined( 'ABSPATH' ) || exit;

function dbt_services() {
	return array(
		'software-development' => array(
			'nav_label' => 'Software',
			'title'     => 'Software Development',
			'lede'      => 'Web, mobile and desktop applications — built by engineers who own the project from first commit to production deploy.',
			'terminal'  => array(
				'$ npm run build',
				'==> Type-checking… done',
				'==> Bundling… done',
				'==> Running tests… PASS',
				'$ ./deploy.sh --env=production',
				'==> Zero-downtime swap… done',
				'==> Health check… 200 OK',
			),
			'points' => array(
				array( 'heading' => 'Web', 'body' => 'React, Next.js, Node and Python. Server-rendered where it matters, static where it doesn’t.' ),
				array( 'heading' => 'Mobile', 'body' => 'iOS and Android — native (Swift, Kotlin) or cross-platform (React Native), decided by what the product actually needs.' ),
				array( 'heading' => 'Desktop', 'body' => 'Electron and native tooling for internal dashboards, admin panels and installed software.' ),
				array( 'heading' => 'Integrations', 'body' => 'Payments, third-party APIs, and the unglamorous plumbing that makes an app actually work.' ),
			),
		),
		'devops' => array(
			'nav_label' => 'DevOps',
			'title'     => 'DevOps & Infrastructure',
			'lede'      => 'The part most agencies skip. We provision it, script it, and keep it running after launch.',
			'terminal'  => array(
				'$ ssh deploy@prod',
				'$ ./provision.sh --stack=lemp',
				'==> Installing cPanel/WHM… done',
				'==> Configuring SSL + DNS… done',
				'==> Registering monitoring agent… done',
				'PASS',
			),
			'points' => array(
				array( 'heading' => 'cPanel & WHM', 'body' => 'Server provisioning, domain and DNS setup, SSL and mail routing — configured once, documented, handed over.' ),
				array( 'heading' => 'Deployment scripts', 'body' => 'Zero-downtime deploy pipelines, staging environments, and rollbacks that actually roll back.' ),
				array( 'heading' => 'CI/CD', 'body' => 'GitHub Actions or GitLab CI wired to your repo, so a merge to main is a deploy, not a ticket.' ),
				array( 'heading' => 'Monitoring & hardening', 'body' => 'Uptime checks, log aggregation, firewall rules, and backup schedules that get tested, not just scheduled.' ),
			),
		),
		'advertising' => array(
			'nav_label' => 'Advertising',
			'title'     => 'Online Advertising',
			'lede'      => 'Getting the software found. Campaign structure, creative testing, and reporting you can actually read.',
			'terminal'  => array(
				'$ dbt-ads report --campaign=q3-search',
				'==> Pulling GA4 conversions… done',
				'==> Reconciling ad spend… done',
				'==> Attributing conversions… done',
				'PASS',
			),
			'points' => array(
				array( 'heading' => 'Meta Ads', 'body' => 'Campaign structure, audience testing, and creative rotation across Facebook and Instagram.' ),
				array( 'heading' => 'Google Ads', 'body' => 'Search, Shopping and Performance Max campaigns tied to real conversion tracking, not vanity clicks.' ),
				array( 'heading' => 'Analytics', 'body' => 'GA4 and conversion pixels wired in correctly, so the numbers you see are the numbers that are true.' ),
				array( 'heading' => 'Landing pages', 'body' => 'Pages built to convert the traffic we send, not just to look good in a portfolio.' ),
			),
		),
		'ai-agents' => array(
			'nav_label' => 'AI Agents',
			'title'     => 'AI Agents & Bots',
			'lede'      => 'LLM-backed agents wired into the systems you already run — not a chatbot bolted onto a homepage.',
			'terminal'  => array(
				'$ agent run support-bot --dry-run',
				'==> Loading policy + docs corpus… done',
				'==> Routing test query… resolved',
				'==> Escalation path… verified',
				'PASS',
			),
			'points' => array(
				array( 'heading' => 'Support agents', 'body' => 'Trained on your docs and policies, escalating to a human when it should, not when it fails.' ),
				array( 'heading' => 'Internal tools', 'body' => 'Agents that read your database, draft the report, and leave the decision to a person.' ),
				array( 'heading' => 'Workflow automation', 'body' => 'Connecting agents to the APIs you already use — CRM, helpdesk, inventory, billing.' ),
				array( 'heading' => 'Model choice', 'body' => 'Claude, GPT or open-weight models, picked per task on cost and accuracy, not brand loyalty.' ),
			),
		),
	);
}

function dbt_legal_pages() {
	$updated     = date( 'F j, Y' );
	$contact_row = sprintf( '<p>Questions: <a href="mailto:%1$s">%1$s</a>.</p>', esc_html( DBT_CONTACT_EMAIL ) );

	$privacy = array(
		'<p><em>Last updated: ' . $updated . '</em></p>',
		'<p>Dave Bukar Technologies ("we", "us") builds software, DevOps, advertising and AI-agent services for businesses. This policy explains what we collect through this website and why.</p>',
		'<h2>What we collect</h2>',
		'<p>Information you submit through contact or quote forms (name, email, company, project details); standard server logs (IP address, browser, pages visited); and analytics/advertising identifiers if you consent to cookies (see below).</p>',
		'<h2>Cookies and analytics</h2>',
		'<p>We may use analytics and advertising pixels (for example Google Analytics, Meta Pixel) to understand traffic and measure ad campaigns we run for ourselves or on behalf of clients. You can disable cookies in your browser at any time.</p>',
		'<h2>How we use it</h2>',
		'<p>To respond to enquiries, scope and deliver projects, and — only with consent — to measure marketing performance. We do not sell personal data.</p>',
		'<h2>Third parties</h2>',
		'<p>We share data with infrastructure and analytics providers strictly to operate this site and our services (hosting, email delivery, analytics). Each is bound to use it only for that purpose.</p>',
		'<h2>Your rights</h2>',
		'<p>You may request a copy of, correction to, or deletion of your data by emailing us.</p>',
		'<h2>Contact</h2>',
		$contact_row,
		'<p><em>This is a draft policy template — have it reviewed against your operating jurisdiction’s data-protection law (e.g. NDPR, GDPR) before publishing.</em></p>',
	);

	$terms = array(
		'<p><em>Last updated: ' . $updated . '</em></p>',
		'<p>These terms govern engagements with Dave Bukar Technologies for software development, DevOps, advertising and AI-agent services.</p>',
		'<h2>Scope of work</h2>',
		'<p>Each project is scoped in writing (proposal, statement of work, or quote) before work begins. Verbal agreements are not binding until confirmed in writing.</p>',
		'<h2>Payment</h2>',
		'<p>Payment terms (deposit, milestones, invoicing schedule) are set per engagement in the signed proposal. Late payment may pause active work.</p>',
		'<h2>Intellectual property</h2>',
		'<p>Unless otherwise agreed in writing, ownership of custom code and deliverables transfers to the client on final payment. We retain the right to reuse general-purpose components, libraries and know-how across other engagements.</p>',
		'<h2>Third-party services</h2>',
		'<p>Where a project uses third-party platforms (hosting, ad platforms, AI model providers), the client is responsible for that platform’s own terms and costs unless explicitly bundled into our quote.</p>',
		'<h2>Liability</h2>',
		'<p>We are not liable for indirect or consequential losses. Our total liability for any engagement is limited to the fees paid for that engagement.</p>',
		'<h2>Governing law</h2>',
		'<p>[Insert governing jurisdiction] — to be confirmed by Dave Bukar Technologies before publishing.</p>',
		'<h2>Contact</h2>',
		$contact_row,
		'<p><em>This is a draft terms template — have it reviewed by a lawyer before publishing.</em></p>',
	);

	return array(
		'privacy-policy'   => array(
			'title'   => 'Privacy Policy',
			'content' => implode( '', $privacy ),
		),
		'terms-of-service' => array(
			'title'   => 'Terms of Service',
			'content' => implode( '', $terms ),
		),
	);
}
