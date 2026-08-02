document.addEventListener('DOMContentLoaded', function() {
    // Search toggle interactions
    const toggleSearch = document.getElementById('toggle-search');
    const closeSearch = document.getElementById('close-search');
    const searchBar = document.getElementById('header-search-bar');
    const searchInput = document.getElementById('search-input');

    function openSearch() {
        searchBar.classList.add('is-open');
        if (searchInput) {
            searchInput.focus();
        }
    }
    function closeSearchBar() {
        searchBar.classList.remove('is-open');
    }

    if (toggleSearch && closeSearch && searchBar) {
        toggleSearch.addEventListener('click', function(e) {
            e.preventDefault();
            if (searchBar.classList.contains('is-open')) {
                closeSearchBar();
            } else {
                openSearch();
            }
        });

        closeSearch.addEventListener('click', function(e) {
            e.preventDefault();
            closeSearchBar();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSearchBar();
            }
        });
    }

    // Mobile off-canvas nav toggle
    const navToggle = document.getElementById('toggle-mobile-nav');
    const navClose = document.getElementById('close-mobile-nav');
    const mainNav = document.getElementById('main-navigation');
    const navOverlay = document.getElementById('mobile-nav-overlay');

    function openNav() {
        mainNav.classList.add('is-open');
        navOverlay.classList.add('is-open');
        navToggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('mobile-nav-active');
    }
    function closeNav() {
        mainNav.classList.remove('is-open');
        navOverlay.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mobile-nav-active');
    }

    if (navToggle && mainNav && navOverlay) {
        navToggle.addEventListener('click', function() {
            if (mainNav.classList.contains('is-open')) {
                closeNav();
            } else {
                openNav();
            }
        });

        if (navClose) {
            navClose.addEventListener('click', closeNav);
        }
        navOverlay.addEventListener('click', closeNav);

        // Close the mobile nav when a link is clicked
        mainNav.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', closeNav);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeNav();
                closeSearchBar();
            }
        });
    }
});

// Single Product AJAX Add to Cart (jQuery)
jQuery(function($) {
    $(document).on('submit', 'form.cart', function(e) {
        var $form = $(this);
        
        // Skip for external/grouped products
        if ($form.closest('.product').hasClass('product-type-external') || $form.closest('.product').hasClass('product-type-grouped')) {
            return;
        }
        
        var $button = $form.find('.single_add_to_cart_button');
        if (!$button.length) return;
        
        e.preventDefault();
        
        $button.addClass('loading').attr('disabled', 'disabled').text('Adding...');
        
        var product_id = $form.find('[name="add-to-cart"]').val() || $button.val();
        if (!product_id) {
            $form.off('submit').submit();
            return;
        }
        
        var data = $form.serialize() + '&action=woocommerce_ajax_add_to_cart&product_id=' + product_id;
        
        $.ajax({
            type: 'POST',
            url: wc_add_to_cart_params.ajax_url,
            data: data,
            success: function(response) {
                if (response.error) {
                    if (response.product_url) {
                        window.location = response.product_url;
                    }
                    return;
                }
                
                // Trigger fragment refresh for WooCommerce
                $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                
                $button.removeClass('loading').removeAttr('disabled').text('Added!');
                
                setTimeout(function() {
                    $button.text('Add to Cart');
                }, 2000);
            },
            error: function() {
                $button.removeClass('loading').removeAttr('disabled').text('Add to Cart');
            }
        });
    });
});

/**
 * Contact form result banner.
 *
 * This lives here rather than in the Contact page's post_content, where it
 * used to sit inside a <script> tag. Baked-in scripts do not survive the
 * content filters: KSES strips the <script> wrapper, wpautop then wraps the
 * bare code in <p>/<br />, and wptexturize turns every " into a curly quote
 * and the -- in a CSS class name into an en dash. The result was the whole
 * function rendering as visible text at the bottom of the Contact page.
 *
 * main.js is enqueued on every page, so this guards on the banner element
 * and does nothing anywhere else.
 */
(function () {
    var banner = document.getElementById('wookiee-contact-banner');
    if (!banner) { return; }

    var status = new URLSearchParams(window.location.search).get('contact');
    if (!status) { return; }

    var messages = {
        'sent': ['success', 'Thank you! Your message has been sent successfully.'],
        'missing': ['error', 'Please fill in all required fields with a valid email address.'],
        'invalid': ['error', 'Something went wrong. Please try again.'],
        'mail-error': ['error', 'Sorry, we could not send your message right now. Please email us directly.']
    };

    var entry = messages[status];
    if (!entry) { return; }

    banner.className = 'contact-banner contact-banner--' + entry[0] + ' is-visible';
    banner.textContent = entry[1];
    banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
}());

/*
 * Prev/next controls for the product gallery thumbnail strip.
 *
 * The strip scrolls and shows a cut-off tile plus a scrollbar to say so, but
 * on a desktop with no touch and on a phone where the scrollbar is a few
 * pixels tall, dragging it is fiddly. A pair of buttons makes paging through
 * a six-image gallery a click rather than a swipe-and-hope.
 *
 * Built here rather than in PHP because flexslider creates the strip itself,
 * after the markup is rendered - there is nothing to attach to server-side.
 */
(function () {
    var STRIP_SELECTOR = '.woocommerce div.product div.images .flex-control-thumbs';

    function makeButton( direction, label, glyph ) {
        var btn = document.createElement( 'button' );
        btn.type = 'button';
        btn.className = 'wookiee-thumb-nav wookiee-thumb-nav--' + direction;
        btn.setAttribute( 'aria-label', label );
        btn.textContent = glyph;
        return btn;
    }

    function setup( strip ) {
        // flexslider can rebuild the strip; the flag stops a second pass
        // wrapping an already-wrapped one.
        if ( strip.dataset.wookieeThumbNav ) {
            return;
        }
        strip.dataset.wookieeThumbNav = '1';

        var wrap = document.createElement( 'div' );
        wrap.className = 'wookiee-thumb-nav-wrap';
        strip.parentNode.insertBefore( wrap, strip );
        wrap.appendChild( strip );

        var prev = makeButton( 'prev', 'Scroll thumbnails left', '‹' );
        var next = makeButton( 'next', 'Scroll thumbnails right', '›' );
        wrap.appendChild( prev );
        wrap.appendChild( next );

        // Just under a full frame, so the tile you were looking at stays
        // partly in view and the strip does not feel like it jumped.
        function step() {
            return Math.max( strip.clientWidth * 0.8, 80 );
        }

        prev.addEventListener( 'click', function () {
            strip.scrollBy( { left: -step(), behavior: 'smooth' } );
        } );
        next.addEventListener( 'click', function () {
            strip.scrollBy( { left: step(), behavior: 'smooth' } );
        } );

        function update() {
            var overflow = strip.scrollWidth - strip.clientWidth;

            // No buttons at all when every thumbnail already fits - a control
            // that cannot do anything is worse than no control.
            wrap.classList.toggle( 'has-overflow', overflow > 4 );
            prev.disabled = strip.scrollLeft <= 2;
            next.disabled = strip.scrollLeft >= overflow - 2;
        }

        strip.addEventListener( 'scroll', update, { passive: true } );
        window.addEventListener( 'resize', update );

        /*
         * Thumbnails are still downloading when this first runs, so scrollWidth
         * is not final yet and the buttons would be hidden on a strip that does
         * overflow. ResizeObserver catches the reflow when they land; the
         * timeouts are the fallback where it is unavailable.
         */
        if ( 'ResizeObserver' in window ) {
            new ResizeObserver( update ).observe( strip );
        }
        window.setTimeout( update, 400 );
        window.setTimeout( update, 1500 );

        update();
    }

    function scan() {
        var strips = document.querySelectorAll( STRIP_SELECTOR );
        for ( var i = 0; i < strips.length; i++ ) {
            setup( strips[ i ] );
        }
        return strips.length > 0;
    }

    function start() {
        if ( scan() ) {
            return;
        }

        // The strip appears only once flexslider has initialised, which is
        // after this file runs. Watch for it instead of polling forever, and
        // stop as soon as it is found.
        if ( ! ( 'MutationObserver' in window ) ) {
            return;
        }

        var gallery = document.querySelector( '.woocommerce div.product div.images' );
        if ( ! gallery ) {
            return;
        }

        var observer = new MutationObserver( function () {
            if ( scan() ) {
                observer.disconnect();
            }
        } );
        observer.observe( gallery, { childList: true, subtree: true } );

        // Nothing to watch forever: if the strip has not appeared by now the
        // product has one image and never will have a strip.
        window.setTimeout( function () {
            observer.disconnect();
        }, 10000 );
    }

    if ( 'loading' === document.readyState ) {
        document.addEventListener( 'DOMContentLoaded', start );
    } else {
        start();
    }
}());
