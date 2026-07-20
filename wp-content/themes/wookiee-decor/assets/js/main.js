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
