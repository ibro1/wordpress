( function() {
	var COOKIE_NAME = 'wookiee_cookie_consent';
	var COOKIE_DAYS = 182;

	function readConsent() {
		var match = document.cookie.match( new RegExp( '(?:^|; )' + COOKIE_NAME + '=([^;]*)' ) );
		if ( ! match ) {
			return null;
		}
		try {
			return JSON.parse( decodeURIComponent( match[ 1 ] ) );
		} catch ( e ) {
			return null;
		}
	}

	function writeConsent( consent ) {
		var expires = new Date();
		expires.setTime( expires.getTime() + COOKIE_DAYS * 24 * 60 * 60 * 1000 );
		document.cookie = COOKIE_NAME + '=' + encodeURIComponent( JSON.stringify( consent ) ) + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
		document.dispatchEvent( new CustomEvent( 'wookiee:consent-updated', { detail: consent } ) );
	}

	var banner = document.getElementById( 'wookiee-cookie-banner' );
	var panel  = document.getElementById( 'wookiee-cookie-panel' );
	if ( ! banner || ! panel ) {
		return;
	}

	var analyticsBox = document.getElementById( 'wookiee-cookie-analytics' );
	var marketingBox = document.getElementById( 'wookiee-cookie-marketing' );
	var panelInner   = panel.querySelector( '.wookiee-cookie-panel-inner' );
	var lastFocused  = null;

	function hideBanner() { banner.hidden = true; }
	function showBanner() { banner.hidden = false; }

	function hidePanel() {
		panel.hidden = true;
		document.body.style.removeProperty( 'overflow' );
		// Send focus back where it came from, so closing the panel from a
		// footer link does not dump the visitor at the top of the document.
		if ( lastFocused && lastFocused.focus ) {
			lastFocused.focus();
		}
		lastFocused = null;
	}

	function showPanel() {
		var consent = readConsent() || { necessary: true, analytics: false, marketing: false };
		analyticsBox.checked = !! consent.analytics;
		marketingBox.checked = !! consent.marketing;
		lastFocused = document.activeElement;
		panel.hidden = false;
		// The panel is a modal over a scrollable page; without this the page
		// behind it scrolls instead of the dialog.
		document.body.style.overflow = 'hidden';
		var first = panel.querySelector( 'input:not([disabled]), button' );
		if ( first ) {
			first.focus();
		}
	}

	/** Applies a decision and closes everything - the shape all three answers share. */
	function decide( analytics, marketing ) {
		writeConsent( { necessary: true, analytics: analytics, marketing: marketing } );
		hidePanel();
		hideBanner();
	}

	document.getElementById( 'wookiee-cookie-accept' ).addEventListener( 'click', function() {
		writeConsent( { necessary: true, analytics: true, marketing: true } );
		hideBanner();
	} );
	document.getElementById( 'wookiee-cookie-reject' ).addEventListener( 'click', function() {
		writeConsent( { necessary: true, analytics: false, marketing: false } );
		hideBanner();
	} );
	document.getElementById( 'wookiee-cookie-manage' ).addEventListener( 'click', function() {
		showPanel();
	} );

	document.getElementById( 'wookiee-cookie-panel-close' ).addEventListener( 'click', hidePanel );
	document.getElementById( 'wookiee-cookie-panel-accept' ).addEventListener( 'click', function() {
		decide( true, true );
	} );
	document.getElementById( 'wookiee-cookie-panel-reject' ).addEventListener( 'click', function() {
		decide( false, false );
	} );
	document.getElementById( 'wookiee-cookie-panel-save' ).addEventListener( 'click', function() {
		decide( analyticsBox.checked, marketingBox.checked );
	} );

	// Clicking the dimmed area closes, the way every other modal on the web
	// does - but only the backdrop itself, never a click inside the dialog.
	panel.addEventListener( 'click', function( event ) {
		if ( event.target === panel ) {
			hidePanel();
		}
	} );

	document.addEventListener( 'keydown', function( event ) {
		if ( panel.hidden ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			hidePanel();
			return;
		}

		/*
		 * Keep Tab inside the dialog while it is open. Without this the focus
		 * ring walks off into the page behind the backdrop, where it is
		 * invisible and the controls it lands on are unreachable by mouse -
		 * which is exactly the state a keyboard user cannot get out of.
		 */
		if ( 'Tab' !== event.key ) {
			return;
		}

		var focusable = panelInner.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled])' );
		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last  = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	/*
	 * A "Cookie preferences" link should give you the toggles, not a page you
	 * then have to scroll to reach them. Every link pointing at /cookie-pref/
	 * - the footer, the Cookie Policy, anywhere else - now opens the panel in
	 * place instead of navigating.
	 *
	 * The href is left intact rather than replaced with a button: without JS
	 * the link still goes to the real page, which carries the same toggles and
	 * the explanation around them, so the control is never unreachable. Search
	 * engines and the compliance audit still see a real page at a real URL.
	 *
	 * Modified clicks are left alone - a middle-click or cmd-click is the
	 * visitor asking for a new tab, and hijacking that is worse than the
	 * extra page load it saves.
	 */
	document.addEventListener( 'click', function( event ) {
		if ( event.defaultPrevented || 0 !== event.button || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
			return;
		}

		var link = event.target && event.target.closest ? event.target.closest( 'a[href]' ) : null;
		if ( ! link || link.host !== window.location.host ) {
			return;
		}

		if ( ! /^\/cookie-pref\/?$/.test( link.pathname ) ) {
			return;
		}

		event.preventDefault();
		showPanel();
	} );

	if ( ! readConsent() ) {
		showBanner();
	}

	// Exposed so any future analytics/marketing script can check consent
	// before firing (none are wired into the theme yet) and so the
	// [wookiee_cookie_preferences_button] shortcode can reopen the panel
	// from the Cookie Preferences page or anywhere else.
	window.wookieeConsent = {
		get: readConsent,
		openPanel: showPanel
	};
} )();
