document.addEventListener('DOMContentLoaded', function() {
    // Search toggle interactions
    const toggleSearch = document.getElementById('toggle-search');
    const closeSearch = document.getElementById('close-search');
    const searchBar = document.getElementById('header-search-bar');
    const searchInput = document.getElementById('search-input');

    if (toggleSearch && closeSearch && searchBar) {
        toggleSearch.addEventListener('click', function(e) {
            e.preventDefault();
            if (searchBar.style.display === 'none' || searchBar.style.display === '') {
                searchBar.style.display = 'block';
                if (searchInput) {
                    searchInput.focus();
                }
            } else {
                searchBar.style.display = 'none';
            }
        });

        closeSearch.addEventListener('click', function(e) {
            e.preventDefault();
            searchBar.style.display = 'none';
        });
        
        // Close search on Esc key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchBar.style.display = 'none';
            }
        });
    }

    // Mobile nav toggle
    const navToggle = document.getElementById('toggle-mobile-nav');
    const mainNav = document.getElementById('main-navigation');

    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function() {
            const isOpen = mainNav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                mainNav.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
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
