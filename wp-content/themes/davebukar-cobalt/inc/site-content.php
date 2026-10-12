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
			'nav_label' => 'SEO & Ads',
			'title'     => 'SEO & Online Advertising',
			'lede'      => 'Getting your business found. We do SEO to get your website ranking on Google, and we manage your Google and Meta ads, with reporting you can actually read.',
			'terminal'  => array(
				'$ dbt-ads report --campaign=q3-search',
				'==> Pulling GA4 conversions… done',
				'==> Reconciling ad spend… done',
				'==> Attributing conversions… done',
				'PASS',
			),
			'points' => array(
				array( 'heading' => 'SEO', 'body' => 'Getting your website to rank on Google: the pages, the structure and the content that search engines and your customers look for.' ),
				array( 'heading' => 'Google Ads management', 'body' => 'We run your Google Ads campaigns for you: Search, Shopping and Performance Max, tied to real conversion tracking, not vanity clicks.' ),
				array( 'heading' => 'Meta Ads', 'body' => 'Campaign structure, audience testing, and creative rotation across Facebook and Instagram.' ),
				array( 'heading' => 'Analytics & landing pages', 'body' => 'GA4 and conversion pixels wired in correctly, and pages built to convert the traffic we send.' ),
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
	// Fixed on purpose: change this date only when the legal text itself changes.
	$updated     = 'October 5, 2026';
	$contact_row = sprintf( '<p>Questions: <a href="mailto:%1$s">%1$s</a>.</p>', esc_html( DBT_CONTACT_EMAIL ) );

	$privacy = array(
		'<p><em>Last updated: ' . $updated . '</em></p>',
		'<p>Linkfa is operated by DaveBukar Technologies ("we", "us"). We build software, DevOps, advertising and AI-agent services. This policy covers this website (linkfa.de) and the automation software we run for our own business and channels at harness.linkfa.de, including the parts that connect to Google, YouTube and Meta accounts.</p>',
		'<h2>What we collect on this website</h2>',
		'<p>Information you submit through contact or quote forms (name, email, company, project details) and standard server logs (IP address, browser, pages visited). If you accept cookies, analytics or advertising identifiers may also be collected (see "Cookies and analytics").</p>',
		'<h2>Our automation software and Google user data</h2>',
		'<p>Our automation software signs in to accounts that we own or that an account holder has explicitly connected, using Google OAuth. Only the account holder can grant this access, and they choose which account or YouTube channel to connect on Google’s own consent screen. Depending on the feature, it requests:</p>',
		'<ul>',
		'<li><strong>YouTube Data API</strong> (youtube.upload, youtube.readonly, youtube.force-ssl): to read the connected channel’s name and ID, upload videos to that channel with their title, description, tags, thumbnail, captions, publishing time and AI-content disclosure, and confirm what YouTube did with each upload.</li>',
		'<li><strong>Google Search Console</strong> (webmasters): to read search performance (queries, pages, clicks, impressions, positions) for websites the account holder owns, so the software can suggest and improve articles and tools for those sites.</li>',
		'<li><strong>Google Ads</strong> (adwords): to read keyword search-volume estimates from Keyword Planner for the account holder’s Google Ads account. It does not create, change or pay for campaigns.</li>',
		'</ul>',
		'<p>We use this data only to provide those features to the account holder. We do not sell it, use it for advertising, or share it with anyone else, except where the account holder asks the software to perform a task (for example writing a video title or choosing an article topic from Search Console data), in which case the relevant data may be processed by the AI service that performs that task. Our use and transfer of information received from Google APIs adheres to the <a href="https://developers.google.com/terms/api-services-user-data-policy">Google API Services User Data Policy</a>, including the Limited Use requirements.</p>',
		'<h2>YouTube API Services</h2>',
		'<p>Our software uses YouTube API Services. By connecting a YouTube channel you agree to be bound by the <a href="https://www.youtube.com/t/terms">YouTube Terms of Service</a>. Google’s handling of your data is described in the <a href="https://policies.google.com/privacy">Google Privacy Policy</a>.</p>',
		'<h2>Storage, retention and deletion</h2>',
		'<p>OAuth access and refresh tokens, the connected channel’s ID and name, and records of uploads (video ID, title, time, status) are stored on our own server, which only we can access. Tokens are kept until the account holder disconnects the account in the software or revokes access; disconnecting deletes the stored token. Upload records are kept while the channel is managed by us and deleted on request. Data read from Search Console and Keyword Planner is cached for a limited time (30 days by default) and then read again or discarded.</p>',
		'<p>You can revoke our access at any time from your Google Account’s <a href="https://myaccount.google.com/permissions">Third-party apps &amp; services</a> page. To have any data we hold about you deleted, email us at the address below; we will do so within 30 days.</p>',
		'<h2>Meta (Facebook) accounts</h2>',
		'<p>Where a Facebook Page is connected, the software uses Meta’s APIs to publish videos to that Page and read the Page’s name and ID. Access can be removed in Facebook’s Business Integrations settings, and the same storage and deletion terms apply.</p>',
		'<h2>Cookies and analytics</h2>',
		'<p>We may use analytics and advertising pixels (for example Google Analytics, Meta Pixel) to understand traffic and measure our own ad campaigns. You can disable cookies in your browser at any time.</p>',
		'<h2>How we use website data</h2>',
		'<p>To respond to enquiries, scope and deliver projects, and, only with consent, to measure marketing performance. We do not sell personal data.</p>',
		'<h2>Third parties</h2>',
		'<p>We share data with infrastructure providers (hosting, email delivery, analytics) strictly to operate this site and our services. Each may use it only for that purpose.</p>',
		'<h2>Your rights</h2>',
		'<p>You may request a copy of, correction to, or deletion of your personal data, or object to its use, by emailing us. We handle requests in line with applicable data-protection law, including the Nigeria Data Protection Act 2023.</p>',
		'<h2>Changes</h2>',
		'<p>We will update the date above when this policy changes.</p>',
		'<h2>Contact</h2>',
		$contact_row,
	);

	$terms = array(
		'<p><em>Last updated: ' . $updated . '</em></p>',
		'<p>These terms govern engagements with Linkfa, operated by DaveBukar Technologies, for software development, DevOps, advertising and AI-agent services.</p>',
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
		// Held back (not published, not linked) until the owner supplies the legal facts: governing jurisdiction and company details.
		'terms-of-service' => array(
			'hidden'  => true,
			'title'   => 'Terms of Service',
			'content' => implode( '', $terms ),
		),
	);
}
