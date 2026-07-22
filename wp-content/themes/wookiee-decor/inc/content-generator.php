<?php
/**
 * Niche-agnostic page content generator (v2 spec §2b, phase 3 of the
 * roadmap). Generates brand-voice copy (About, homepage hero/philosophy)
 * and UK-compliant policy pages (Terms, Privacy, Shipping, Returns,
 * Cookies) from the store's niche brief and real business details already
 * held in Wookiee Settings.
 *
 * Everything lands as a WordPress page titled "<Thing> (AI Draft)" in
 * Draft status - never overwriting the theme's existing live pages,
 * never auto-published. An admin reviews, edits, and republishes (or
 * copies content across) manually. The policy prompt below is adapted
 * from docs/policy writing law.txt (not shipped to the live server, since
 * deployment only copies the theme folder - so the same instructions are
 * reproduced here rather than read from that file at runtime).
 */

defined( 'ABSPATH' ) || exit;

function wookiee_content_generator_pieces() {
	return array(
		'about'          => array( 'label' => 'About page narrative', 'title' => 'About (AI Draft)' ),
		'homepage_copy'  => array( 'label' => 'Homepage hero & philosophy copy', 'title' => 'Homepage Copy (AI Draft)' ),
		'terms'          => array( 'label' => 'Terms & Conditions', 'title' => 'Terms & Conditions (AI Draft)' ),
		'privacy'        => array( 'label' => 'Privacy Policy', 'title' => 'Privacy Policy (AI Draft)' ),
		'shipping'       => array( 'label' => 'Shipping Policy', 'title' => 'Shipping Policy (AI Draft)' ),
		'returns'        => array( 'label' => 'Returns & Refunds Policy', 'title' => 'Returns & Refunds Policy (AI Draft)' ),
		'cookies'        => array( 'label' => 'Cookie Policy', 'title' => 'Cookie Policy (AI Draft)' ),
	);
}

function wookiee_render_content_generator_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$has_key     = '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	$saved_brief = get_option( 'wookiee_niche_brief', '' );
	?>
	<div class="wrap">
		<h1>Wookiee Content Generator</h1>
		<p>Generates on-brand page copy and UK policy pages from the store's niche and the business details already saved in Wookiee Settings. Every result is created as a new page titled "<em>(AI Draft)</em>" in <strong>Draft</strong> status — it never touches or replaces an existing live page. Review each draft, edit as needed, then either copy its content into the real page or publish it and update the live page to match.</p>

		<?php if ( ! $has_key ) : ?>
			<div class="notice notice-warning"><p>No LLM API key set. Add one on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> page first.</p></div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="wookiee-niche-brief-2">Niche brief</label></th>
				<td>
					<textarea id="wookiee-niche-brief-2" rows="3" class="large-text" placeholder="e.g. UK home-storage and organisation products - baskets, shelving, drawer organisers, aimed at small flats"><?php echo esc_textarea( $saved_brief ); ?></textarea>
					<p class="description">Shared with the Product Generator's niche brief.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Content to generate</th>
				<td>
					<?php foreach ( wookiee_content_generator_pieces() as $key => $piece ) : ?>
						<label style="display:block;margin-bottom:6px;">
							<input type="checkbox" class="wookiee-content-piece" value="<?php echo esc_attr( $key ); ?>" checked>
							<?php echo esc_html( $piece['label'] ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>

		<p>
			<button type="button" class="button button-primary" id="wookiee-content-generate-btn" <?php disabled( ! $has_key ); ?>>Generate selected drafts</button>
			<span id="wookiee-content-generate-status" style="margin-left:8px;"></span>
		</p>

		<div id="wookiee-content-generate-results"></div>

		<p style="margin-top:16px;">
			<button type="button" class="button" id="wookiee-apply-homepage-btn">Apply reviewed homepage copy to live site</button>
			<span id="wookiee-apply-homepage-status" style="margin-left:8px;"></span>
		</p>
		<p class="description">Only click this after you've generated the homepage hero &amp; philosophy copy above and reviewed it on the "Homepage Copy (AI Draft)" page — this writes straight into the live homepage's hero and philosophy section.</p>

		<hr>
		<h2>Policy compliance audit</h2>
		<p>Runs a UK compliance QA pass (Google Merchant Center risk, UK consumer/privacy law, quality) over an already-generated policy draft — adapted from <code>docs/policy audit new.txt</code>'s US/GMC audit format for a UK-only store. This only produces a report for you to act on; it does not edit the page.</p>
		<p>
			<select id="wookiee-audit-page-select">
				<option value="">Select a policy draft to audit…</option>
				<?php
				foreach ( array( 'terms', 'privacy', 'shipping', 'returns', 'cookies' ) as $policy_key ) {
					$piece = wookiee_content_generator_pieces()[ $policy_key ];
					$page  = get_page_by_title( $piece['title'], OBJECT, 'page' );
					if ( $page ) {
						echo '<option value="' . esc_attr( $page->ID ) . '">' . esc_html( $piece['title'] ) . '</option>';
					}
				}
				?>
			</select>
			<button type="button" class="button button-primary" id="wookiee-audit-btn">Run compliance audit</button>
			<span id="wookiee-audit-status" style="margin-left:8px;"></span>
		</p>
		<div id="wookiee-audit-results" style="white-space:pre-wrap;max-width:900px;"></div>
		<p id="wookiee-audit-fix-row" style="display:none;">
			<button type="button" class="button button-primary" id="wookiee-audit-fix-btn">Apply these fixes to the draft</button>
			<span id="wookiee-audit-fix-status" style="margin-left:8px;"></span>
		</p>
		<p class="description">Rewrites the draft to resolve everything the report above lists, using the same real business details as generation - it still only updates the Draft page, and you can re-run the audit afterwards to check.</p>
	</div>
	<script>
	( function() {
		var btn = document.getElementById( 'wookiee-content-generate-btn' );
		if ( ! btn ) {
			return;
		}
		btn.addEventListener( 'click', function() {
			var status  = document.getElementById( 'wookiee-content-generate-status' );
			var results = document.getElementById( 'wookiee-content-generate-results' );
			var brief   = document.getElementById( 'wookiee-niche-brief-2' ).value.trim();
			var checked = Array.prototype.slice.call( document.querySelectorAll( '.wookiee-content-piece:checked' ) ).map( function( el ) { return el.value; } );

			if ( ! brief ) {
				status.textContent = 'Describe the niche first.';
				return;
			}
			if ( ! checked.length ) {
				status.textContent = 'Select at least one item to generate.';
				return;
			}

			btn.disabled = true;
			results.innerHTML = '';
			status.textContent = 'Generating ' + checked.length + ' item(s) with the LLM… this can take a minute or two.';

			var data = new FormData();
			data.append( 'action', 'wookiee_generate_content' );
			data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'wookiee_generate_content' ) ); ?>' );
			data.append( 'brief', brief );
			checked.forEach( function( key ) { data.append( 'pieces[]', key ); } );

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					btn.disabled = false;
					if ( ! res.success ) {
						status.textContent = res.data && res.data.message ? res.data.message : 'Generation failed.';
						return;
					}
					status.textContent = 'Done. ' + res.data.pages.length + ' draft page(s) ready for review.';
					var html = '<table class="widefat"><thead><tr><th>Draft</th><th>Status</th><th></th></tr></thead><tbody>';
					res.data.pages.forEach( function( p ) {
						html += '<tr><td>' + p.title + '</td><td>' + ( p.error ? p.error : 'Created' ) + '</td><td>' + ( p.edit_link ? '<a href="' + p.edit_link + '" class="button">Review draft</a>' : '' ) + '</td></tr>';
					} );
					html += '</tbody></table>';
					results.innerHTML = html;
				} )
				.catch( function() {
					btn.disabled = false;
					status.textContent = 'Generation failed — could not reach the server.';
				} );
		} );

		var applyBtn = document.getElementById( 'wookiee-apply-homepage-btn' );
		applyBtn.addEventListener( 'click', function() {
			var applyStatus = document.getElementById( 'wookiee-apply-homepage-status' );
			applyBtn.disabled = true;
			applyStatus.textContent = 'Applying…';
			var data = new FormData();
			data.append( 'action', 'wookiee_apply_homepage_copy' );
			data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'wookiee_generate_content' ) ); ?>' );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					applyBtn.disabled = false;
					applyStatus.textContent = res.success ? res.data.message : ( res.data && res.data.message ? res.data.message : 'Failed to apply.' );
				} )
				.catch( function() {
					applyBtn.disabled = false;
					applyStatus.textContent = 'Failed — could not reach the server.';
				} );
		} );

		var auditBtn = document.getElementById( 'wookiee-audit-btn' );
		var fixRow   = document.getElementById( 'wookiee-audit-fix-row' );
		var fixBtn   = document.getElementById( 'wookiee-audit-fix-btn' );

		auditBtn.addEventListener( 'click', function() {
			var auditStatus  = document.getElementById( 'wookiee-audit-status' );
			var auditResults = document.getElementById( 'wookiee-audit-results' );
			var postId       = document.getElementById( 'wookiee-audit-page-select' ).value;
			if ( ! postId ) {
				auditStatus.textContent = 'Select a policy draft first.';
				return;
			}
			auditBtn.disabled = true;
			auditResults.textContent = '';
			fixRow.style.display = 'none';
			auditStatus.textContent = 'Auditing… this can take a minute.';
			var data = new FormData();
			data.append( 'action', 'wookiee_audit_policy_page' );
			data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'wookiee_generate_content' ) ); ?>' );
			data.append( 'post_id', postId );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					auditBtn.disabled = false;
					if ( ! res.success ) {
						auditStatus.textContent = res.data && res.data.message ? res.data.message : 'Audit failed.';
						return;
					}
					auditStatus.textContent = 'Done.';
					auditResults.textContent = res.data.report;
					fixRow.style.display = '';
				} )
				.catch( function() {
					auditBtn.disabled = false;
					auditStatus.textContent = 'Audit failed — could not reach the server.';
				} );
		} );

		fixBtn.addEventListener( 'click', function() {
			var fixStatus    = document.getElementById( 'wookiee-audit-fix-status' );
			var auditResults = document.getElementById( 'wookiee-audit-results' );
			var postId       = document.getElementById( 'wookiee-audit-page-select' ).value;
			if ( ! postId || ! auditResults.textContent ) {
				fixStatus.textContent = 'Run the audit first.';
				return;
			}
			fixBtn.disabled = true;
			fixStatus.textContent = 'Rewriting the draft… this can take a minute.';
			var data = new FormData();
			data.append( 'action', 'wookiee_apply_audit_fixes' );
			data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'wookiee_generate_content' ) ); ?>' );
			data.append( 'post_id', postId );
			data.append( 'audit_report', auditResults.textContent );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					fixBtn.disabled = false;
					if ( ! res.success ) {
						fixStatus.textContent = res.data && res.data.message ? res.data.message : 'Failed to apply fixes.';
						return;
					}
					fixStatus.innerHTML = 'Draft updated. <a href="' + res.data.edit_link + '">Review it</a>, then re-run the audit to check.';
				} )
				.catch( function() {
					fixBtn.disabled = false;
					fixStatus.textContent = 'Failed — could not reach the server.';
				} );
		} );
	} )();
	</script>
	<?php
}

add_action( 'wp_ajax_wookiee_generate_content', 'wookiee_generate_content_handler' );
function wookiee_generate_content_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_content', 'nonce' );

	$brief  = isset( $_POST['brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['brief'] ) ) : '';
	$pieces = isset( $_POST['pieces'] ) && is_array( $_POST['pieces'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['pieces'] ) ) : array();

	if ( '' === trim( $brief ) ) {
		wp_send_json_error( array( 'message' => 'Describe the niche first.' ) );
	}
	if ( empty( $pieces ) ) {
		wp_send_json_error( array( 'message' => 'Select at least one item to generate.' ) );
	}
	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the Wookiee Settings page first.' ) );
	}

	update_option( 'wookiee_niche_brief', $brief );

	$available = wookiee_content_generator_pieces();
	$results   = array();

	foreach ( $pieces as $key ) {
		if ( ! isset( $available[ $key ] ) ) {
			continue;
		}
		$piece  = $available[ $key ];
		$prompt = wookiee_build_content_prompt( $key, $brief );
		$text   = wookiee_call_llm( $prompt, wookiee_content_piece_max_tokens( $key ) );

		if ( is_wp_error( $text ) ) {
			$results[] = array(
				'title'     => esc_html( $piece['title'] ),
				'error'     => esc_html( $text->get_error_message() ),
				'edit_link' => '',
			);
			continue;
		}

		if ( 'homepage_copy' === $key ) {
			update_option( 'wookiee_homepage_copy_parsed', wookiee_parse_homepage_copy( $text ) );
		}

		$post_id = wookiee_insert_or_update_ai_draft_page( $piece['title'], $text );
		$results[] = array(
			'title'     => esc_html( $piece['title'] ),
			'error'     => '',
			'edit_link' => $post_id ? get_edit_post_link( $post_id, 'raw' ) : '',
		);
	}

	wp_send_json_success( array( 'pages' => $results ) );
}

/**
 * Pulls the five labelled sections out of the homepage_copy response.
 * Each label is expected on its own line per the prompt above, so this
 * is a simple per-line match rather than a general-purpose parser.
 */
function wookiee_parse_homepage_copy( $text ) {
	$map = array(
		'EYEBROW'            => 'eyebrow',
		'HEADLINE'           => 'headline',
		'SUBHEADLINE'        => 'subheadline',
		'PHILOSOPHY_HEADING' => 'philosophy_heading',
		'PHILOSOPHY'         => 'philosophy',
	);
	$fields = array_fill_keys( array_values( $map ), '' );

	foreach ( explode( "\n", $text ) as $line ) {
		$line = trim( $line );
		foreach ( $map as $label => $field_key ) {
			if ( 0 === strpos( $line, $label . ':' ) ) {
				$fields[ $field_key ] = trim( substr( $line, strlen( $label ) + 1 ) );
			}
		}
	}

	return $fields;
}

/**
 * Copies the parsed homepage_copy fields into the live settings that
 * front-page.php actually reads - a deliberate, explicit action the admin
 * takes after reviewing the generated copy, not something that happens
 * automatically at generation time.
 */
add_action( 'wp_ajax_wookiee_apply_homepage_copy', 'wookiee_apply_homepage_copy_handler' );
function wookiee_apply_homepage_copy_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_content', 'nonce' );

	$parsed = get_option( 'wookiee_homepage_copy_parsed', array() );
	if ( empty( array_filter( $parsed ) ) ) {
		wp_send_json_error( array( 'message' => 'Generate the homepage hero & philosophy copy first.' ) );
	}

	$setting_map = array(
		'eyebrow'            => 'hero_eyebrow',
		'headline'           => 'hero_headline',
		'subheadline'        => 'hero_subheadline',
		'philosophy_heading' => 'homepage_philosophy_heading',
		'philosophy'         => 'homepage_philosophy',
	);

	foreach ( $setting_map as $parsed_key => $setting_key ) {
		if ( ! empty( $parsed[ $parsed_key ] ) ) {
			update_option( 'wookiee_setting_' . $setting_key, sanitize_text_field( $parsed[ $parsed_key ] ) );
		}
	}

	wp_send_json_success( array( 'message' => 'Live homepage copy updated.' ) );
}

/**
 * A single block of real business details, shared by every prompt below
 * so the model has the same facts to draw from and nothing to invent.
 */
function wookiee_business_details_block() {
	$lines = array(
		'Business/trading name: ' . wookiee_get_setting( 'business_name' ),
		'Registered address: ' . str_replace( "\n", ', ', wookiee_get_setting( 'registered_address' ) ),
		'Company number: ' . wookiee_get_setting( 'company_number' ),
		'Contact email: ' . wookiee_get_setting( 'contact_email' ),
		'Countries served: ' . wookiee_get_setting( 'countries_served' ),
		'Typical delivery time: ' . wookiee_get_setting( 'shipping_dispatch' ),
		'Flat shipping rate: £' . wookiee_get_setting( 'shipping_rate' ),
		'Returns period: ' . wookiee_get_setting( 'returns_period_days' ) . ' days',
		'Returns address: ' . str_replace( "\n", ', ', wookiee_get_returns_address() ),
		'Website: ' . home_url( '/' ),
	);
	return implode( "\n", $lines );
}

/**
 * Policy pages need more room than brand-voice copy - a cookie or terms
 * page covering every required section can run well past 2048 tokens
 * and get cut off mid-sentence, which is exactly what a compliance audit
 * will (correctly) flag as an "incomplete" issue.
 */
function wookiee_content_piece_max_tokens( $key ) {
	$policy_keys = array( 'terms', 'privacy', 'shipping', 'returns', 'cookies' );
	return in_array( $key, $policy_keys, true ) ? 4096 : 2048;
}

function wookiee_build_content_prompt( $key, $brief ) {
	$policy_labels = array(
		'terms'    => 'Terms & Conditions',
		'privacy'  => 'Privacy Policy',
		'shipping' => 'Shipping Policy',
		'returns'  => 'Returns & Refunds Policy',
		'cookies'  => 'Cookie Policy',
	);

	if ( isset( $policy_labels[ $key ] ) ) {
		$prompt = "Act as a UK e-commerce legal policy writer. Write a complete, ready-to-publish {$policy_labels[ $key ]} page for a UK online store.\n\n"
			. "Store niche, in the owner's own words: \"{$brief}\"\n\n"
			. "Real business details to use (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
			. "Rules:\n"
			. "- Check against UK GDPR, the Data Protection Act 2018, the Consumer Rights Act 2015, the Consumer Contracts Regulations, the Electronic Commerce Regulations, and PECR, wherever relevant to this specific policy.\n"
			. "- Do not invent any business fact beyond the details given above. If something relevant is missing, write a clear inline placeholder like \"[Business input required: X]\" instead of guessing.\n"
			. "- Do not copy another company's policy text.\n"
			. "- Write in plain, professional, customer-friendly English - not robotic or generic-sounding boilerplate.\n"
			. "- Include a clearly labelled section near the end on how customers can contact the business about this policy, using the contact email given above.\n"
			. "- Include a brief note that this policy may be updated from time to time and customers should check this page periodically.\n"
			. "- Where genuinely relevant, refer to the store's other policies by name (e.g. mention the Privacy Policy when discussing personal data, the Returns Policy when discussing refunds) rather than repeating their content.\n"
			. "- End with a short note that this policy should be reviewed by a qualified UK solicitor before being relied on, since it is not legal advice.\n"
			. "- Output ONLY the finished policy text as plain paragraphs separated by a blank line, starting with a single plain-text heading line. No markdown, no HTML, no commentary, no A/B/C-style breakdown - just the finished, publishable page.";

		if ( 'cookies' === $key ) {
			$prompt .= "\n\nThis store's actual cookie consent mechanism, describe it accurately using these facts (do not describe any other mechanism, and do not omit it): " . wookiee_cookie_consent_mechanism_description();
		}

		return $prompt;
	}

	if ( 'about' === $key ) {
		return "Write the About page narrative for a UK single-niche ecommerce store.\n\n"
			. "Store niche, in the owner's own words: \"{$brief}\"\n"
			. 'Business/trading name: ' . wookiee_get_setting( 'business_name' ) . "\n\n"
			. "Rules:\n"
			. "- Natural, human, on-brand voice for this niche - not generic AI-sounding filler, not overly formal.\n"
			. "- Do not invent specific facts (founding year, headcount, awards, press mentions) that weren't given above.\n"
			. "- Do not reference or imitate any specific real-world competitor brand.\n"
			. "- About 150-250 words, plain paragraphs separated by a blank line, no markdown, no headings.\n"
			. "- Output ONLY the finished copy - no preamble, no commentary.";
	}

	if ( 'homepage_copy' === $key ) {
		return "Write homepage marketing copy for a UK single-niche ecommerce store.\n\n"
			. "Store niche, in the owner's own words: \"{$brief}\"\n"
			. 'Business/trading name: ' . wookiee_get_setting( 'business_name' ) . "\n\n"
			. "Provide exactly these five labelled sections, each starting on its own line with the label shown (including the colon), and nothing else before or after them:\n"
			. "EYEBROW: a very short tag line above the headline (2-5 words)\n"
			. "HEADLINE: a short, punchy hero headline (under 10 words)\n"
			. "SUBHEADLINE: one supporting sentence under the headline\n"
			. "PHILOSOPHY_HEADING: a short heading for the store's values/approach section (under 8 words)\n"
			. "PHILOSOPHY: a 100-150 word paragraph about the store's approach and values for this niche\n\n"
			. "Rules: natural, human, on-brand voice; do not invent specific facts; do not reference or imitate a real competitor brand; no markdown.";
	}

	return '';
}

/**
 * Runs an already-generated policy draft through a compliance audit and
 * returns a plain-text report - it never edits the page. Adapted from
 * docs/policy audit new.txt: that prompt is written for US law (FTC,
 * CCPA/CPRA) which doesn't apply here, so this version swaps in the same
 * UK frameworks used by the generation prompt above, keeping the audit's
 * rigor (scored risk, itemised issues, missing-information callouts)
 * rather than reserving the US original for a future non-UK site.
 */
add_action( 'wp_ajax_wookiee_audit_policy_page', 'wookiee_audit_policy_page_handler' );
function wookiee_audit_policy_page_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_content', 'nonce' );

	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the Wookiee Settings page first.' ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'page' !== $post->post_type ) {
		wp_send_json_error( array( 'message' => 'Select a valid policy draft first.' ) );
	}

	$prompt = wookiee_build_policy_audit_prompt( $post->post_title, wp_strip_all_tags( $post->post_content ) );
	$report = wookiee_call_llm( $prompt, 3000 );

	if ( is_wp_error( $report ) ) {
		wp_send_json_error( array( 'message' => $report->get_error_message() ) );
	}

	wp_send_json_success( array( 'report' => $report ) );
}

function wookiee_build_policy_audit_prompt( $title, $policy_text ) {
	return "Act as a senior UK e-commerce compliance reviewer: a Google Merchant Center (GMC) policy reviewer, a UK solicitor specialising in consumer protection and e-commerce law, and a professional legal copywriter. Perform a compliance audit of the following policy page - do not just proofread it.\n\n"
		. "Policy page: {$title}\n\n"
		. "--- POLICY TEXT ---\n{$policy_text}\n--- END POLICY TEXT ---\n\n"
		. "Review against:\n"
		. "- Google Merchant Center requirements: misrepresentation, missing business information, unclear refund/shipping disclosures, trustworthiness, account suspension risk.\n"
		. "- UK law: the Consumer Rights Act 2015, the Consumer Contracts Regulations, the Electronic Commerce Regulations, UK GDPR, the Data Protection Act 2018, and PECR, wherever relevant.\n"
		. "- Quality: weak, confusing, or contradictory wording; generic boilerplate; AI-sounding text; missing sections; poor formatting.\n\n"
		. "Do not invent legal obligations that don't apply, and do not assume any business fact that isn't present in the text above - flag missing information instead of guessing.\n\n"
		. "Output in plain text, no markdown, using exactly this structure:\n"
		. "OVERALL SCORE: a number from 1 to 10\n"
		. "GMC RISK: Low, Medium, or High, with a one-sentence reason\n"
		. "LEGAL RISK: a short paragraph on UK legal concerns, if any\n"
		. "ISSUES FOUND: a numbered list - what's wrong, how serious, how to fix it\n"
		. "MISSING INFORMATION: anything the business needs to supply that isn't in the text\n"
		. "RECOMMENDATION: a short closing paragraph\n\n"
		. "Be critical and specific. This is a QA report for a human to act on - do not rewrite the policy, only assess it.";
}

/**
 * Rewrites a policy draft to resolve everything a compliance audit
 * flagged, instead of the admin manually retyping fixes an AI report
 * already itemised. Still lands back on the same Draft page (via
 * wookiee_insert_or_update_ai_draft_page()), so nothing goes live
 * without the usual manual Publish step - only the tedious "now go make
 * these exact edits by hand" part is what this removes.
 */
add_action( 'wp_ajax_wookiee_apply_audit_fixes', 'wookiee_apply_audit_fixes_handler' );
function wookiee_apply_audit_fixes_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_content', 'nonce' );

	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the Wookiee Settings page first.' ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'page' !== $post->post_type ) {
		wp_send_json_error( array( 'message' => 'Select a valid policy draft first.' ) );
	}

	$audit_report = isset( $_POST['audit_report'] ) ? sanitize_textarea_field( wp_unslash( $_POST['audit_report'] ) ) : '';
	if ( '' === trim( $audit_report ) ) {
		wp_send_json_error( array( 'message' => 'Run the compliance audit first.' ) );
	}

	$prompt = wookiee_build_policy_fix_prompt( $post->post_title, wp_strip_all_tags( $post->post_content ), $audit_report );
	$text   = wookiee_call_llm( $prompt, 4096 );

	if ( is_wp_error( $text ) ) {
		wp_send_json_error( array( 'message' => $text->get_error_message() ) );
	}

	wookiee_insert_or_update_ai_draft_page( $post->post_title, $text );

	wp_send_json_success( array( 'edit_link' => get_edit_post_link( $post->ID, 'raw' ) ) );
}

function wookiee_build_policy_fix_prompt( $title, $current_text, $audit_report ) {
	$prompt = "You previously drafted a UK ecommerce policy page. Below is its CURRENT text, followed by a compliance audit report listing problems with it. Rewrite the complete policy to resolve every issue in the audit report while keeping everything that was already correct.\n\n"
		. "Policy page: {$title}\n\n"
		. "--- CURRENT POLICY TEXT ---\n{$current_text}\n--- END CURRENT POLICY TEXT ---\n\n"
		. "--- AUDIT REPORT ---\n{$audit_report}\n--- END AUDIT REPORT ---\n\n"
		. "Real business details (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
		. "Rules:\n"
		. "- Resolve every item under \"ISSUES FOUND\".\n"
		. "- For anything under \"MISSING INFORMATION\", either fill it in from the business details above, or if it's genuinely not available, use a clear \"[Business input required: X]\" placeholder - do not invent it.\n"
		. "- Do not claim any feature, mechanism, or business practice exists unless it's in the business details above or already accurately stated in the current text - if the audit flagged something missing that isn't something this business actually has, use a placeholder rather than inventing it.\n"
		. "- Write in plain, professional, customer-friendly English - not robotic or generic-sounding boilerplate.\n"
		. "- End with a short note that this policy should be reviewed by a qualified UK solicitor before being relied on, since it is not legal advice.\n"
		. "- Output ONLY the finished, complete policy text as plain paragraphs separated by a blank line, starting with a single plain-text heading line. No markdown, no HTML, no commentary, no changelog of what you fixed - just the finished, publishable page.";

	if ( false !== stripos( $title, 'cookie' ) ) {
		$prompt .= "\n\nThis store's actual cookie consent mechanism, describe it accurately using these facts: " . wookiee_cookie_consent_mechanism_description();
	}

	return $prompt;
}

/**
 * Creates or refreshes a draft page by exact title. Refreshing (rather
 * than skipping) on a repeat run is deliberate - unlike products, these
 * are meant to be iterated on with a refined brief, not accumulated as
 * separate candidates.
 */
function wookiee_insert_or_update_ai_draft_page( $title, $raw_text ) {
	$content  = wpautop( esc_html( $raw_text ) );
	$existing = get_page_by_title( $title, OBJECT, 'page' );

	if ( $existing ) {
		wp_update_post( array(
			'ID'           => $existing->ID,
			'post_content' => $content,
			'post_status'  => 'draft',
		) );
		return $existing->ID;
	}

	$post_id = wp_insert_post( array(
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => 'draft',
		'post_type'    => 'page',
	) );

	return ( $post_id && ! is_wp_error( $post_id ) ) ? $post_id : 0;
}
