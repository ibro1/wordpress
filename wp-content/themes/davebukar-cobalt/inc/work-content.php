<?php
/**
 * Structured content for the Work page (template-work.php).
 *
 * Client systems are described by sector, not by client name: most were
 * delivered as a subcontractor, so the client relationship is not ours to
 * advertise. Swap in a name only once that client has agreed to it.
 * Our own products are named and linked.
 */

defined( 'ABSPATH' ) || exit;

function dbt_case_studies() {
	return array(
		array(
			'sector' => 'Logistics',
			'title'  => 'Operations platform for a multi-branch logistics company',
			'lede'   => 'Parcel delivery, heavy haulage, warehousing and accounting for a company running branches across several Nigerian states — one system for customers, branch staff, drivers and executives.',
			'points' => array(
				'Live driver tracking: GPS cached in Redis, pushed over WebSockets, written to Postgres in batches.',
				'Proof of delivery with an SMS PIN, GPS tag, signature and photo.',
				'Haulage priced per km and per ton between states; monthly corporate statements settled through Paystack.',
				'Branch-scoped roles: a Lagos manager sees Lagos, and nothing else.',
			),
			'stack'  => array( 'Next.js', 'Express', 'PostgreSQL', 'BullMQ', 'Socket.io', 'Expo', 'Paystack' ),
			'scale'  => 'Web portal + driver app + customer app · ~10 roles',
		),
		array(
			'sector' => 'Government · Education',
			'title'  => 'Learning platform for a federal civil-service academy',
			'lede'   => 'An LMS where federal civil servants take courses, attend live classes and earn certificates that anyone can verify.',
			'points' => array(
				'SCORM course packages uploaded, parsed and played in the browser.',
				'Live classes with recordings that expire and delete on schedule.',
				'PDF certificates with QR codes and a public verification page.',
				'Resumable chunked uploads for large course media.',
			),
			'stack'  => array( 'SvelteKit', 'PostgreSQL', 'Drizzle', 'S3', 'Jitsi', 'Docker' ),
			'scale'  => 'Four portals: learner, instructor, organisation, super-admin · in acceptance testing',
		),
		array(
			'sector' => 'Transport',
			'title'  => 'Ride-hailing platform built around rider safety',
			'lede'   => 'Rider app, driver app and an operations console for a Nigerian ride-hailing service.',
			'points' => array(
				'Route-deviation alerts that escalate at 100 m, 300 m and 500 m — the last one raises an SOS.',
				'Automatic driver matching, fare pricing, refunds and admin-approved driver payouts.',
				'Trusted contacts and a public live-trip link families can follow.',
				'Play Store compliance: background-location disclosure and scheduled account deletion.',
			),
			'stack'  => array( 'Next.js', 'Socket.io', 'PostgreSQL', 'Redis', 'Expo', 'Google Maps', 'Paystack' ),
			'scale'  => 'Three apps · ~20 admin sections including SOS and disputes',
		),
		array(
			'sector' => 'Healthcare',
			'title'  => 'Care-management platform for a home-care provider in Ireland',
			'lede'   => 'Clinical records, visit documentation and calling for carers in the field, and a family portal at home.',
			'points' => array(
				'In-app voice calling bridged over Telnyx, so a carer’s personal number is never exposed.',
				'Clinical assessments and risk scoring built to the Director of Care’s field feedback.',
				'Mobile app for carers with over-the-air updates between store releases.',
			),
			'stack'  => array( 'TanStack Start', 'Expo', 'PostgreSQL', 'Telnyx', 'WebRTC' ),
			'scale'  => 'Web portal + carer app · in active development',
		),
		array(
			'sector' => 'AI · SaaS',
			'title'  => 'AI customer-service employees for small businesses',
			'lede'   => 'A multi-tenant platform where a business trains an assistant on its own website and documents, then puts it on its site, on WhatsApp and on the phone.',
			'points' => array(
				'Retrieval over each business’s own data with pgvector, plus long-term memory.',
				'Website crawling that imports products and courses automatically.',
				'Voice and phone channels with speech-to-text and text-to-speech.',
				'Prepaid credit billing with volume tiers and VAT.',
			),
			'stack'  => array( 'TanStack Start', 'PostgreSQL', 'pgvector', 'OpenRouter', 'Deepgram', 'Twilio' ),
			'scale'  => 'Live · onboarding businesses on prepaid credits',
		),
		array(
			'sector' => 'Video · Advertising',
			'title'  => '30-second video advert for a Quran school',
			'lede'   => 'Script, the teacher’s own voice, real class footage and a paid-ads plan for a Quran-reading class taught over WhatsApp.',
			'points' => array(
				'Cut in 9:16, 1:1 and 16:9 from one master, loudness-normalised for phone speakers.',
				'Held to 30 seconds on purpose — a WhatsApp status splits anything longer.',
				'Ad budget sized to the class’s real capacity, not to reach for its own sake.',
			),
			'stack'  => array( 'ffmpeg', 'Scripting', 'Voiceover', 'Meta Ads' ),
			'scale'  => 'Delivered in three formats',
		),
	);
}

/**
 * Products we build and run ourselves. Filled in from each repo's own
 * README and deploy config; 'url' is left empty for anything not public.
 */
function dbt_own_products() {
	return array(
		array(
			'name' => 'Klipara',
			'kind' => 'Long video → short clips',
			'body' => 'Paste a YouTube link or upload a lecture, stream or podcast; get back captioned vertical clips with a hook on screen. Speech and caption language are set separately, so a Hausa lecture can carry English captions.',
			'tags' => array( 'TanStack Start', 'Expo', 'ffmpeg', 'Paystack', 'MCP' ),
			'url'  => 'https://klipara.linkfa.de',
		),
		array(
			'name' => 'Framevane',
			'kind' => 'Live streaming with AI overlays',
			'body' => 'Stream once from a phone to YouTube, Facebook and TikTok at the same time, with titles, tickers and live captions changed mid-broadcast — no restart. Includes our own Android broadcaster app.',
			'tags' => array( 'MediaMTX', 'ffmpeg', 'Expo', 'Whisper' ),
			'url'  => 'https://framevane.linkfa.de',
		),
		array(
			'name' => 'Rainmaker',
			'kind' => 'Marketing campaigns from one sentence',
			'body' => 'A business owner describes what they sell; Rainmaker drafts a landing page, social posts, an email sequence, artwork and a captioned video ad. Nothing is published until the owner approves it.',
			'tags' => array( 'TanStack Start', 'PostgreSQL', 'Meta', 'LinkedIn' ),
			'url'  => 'https://rainmaker.linkfa.de',
		),
		array(
			'name' => 'Harness',
			'kind' => 'Private AI agent workspace',
			'body' => 'Our hardened deployment of an open-source agent framework, extended with plugins for DNS, deploys, WhatsApp and databases. It is the AI backend our other products call.',
			'tags' => array( 'TypeScript', 'Docker', 'Traefik' ),
			'url'  => '',
		),
	);
}
