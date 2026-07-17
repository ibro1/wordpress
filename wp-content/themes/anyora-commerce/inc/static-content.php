<?php
/**
 * Static content and dummy data generation for Anyora Commerce.
 */

defined( 'ABSPATH' ) || exit;

function anyora_starter_pages() {
	return array(
		'home'    => array( 'title' => 'Home', 'menu' => 'Home', 'content' => '' ),
		'shop'    => array( 'title' => 'Shop', 'menu' => 'Shop', 'content' => '' ),
		'about'   => array( 'title' => 'About', 'menu' => 'About', 'content' => "<h2>Who We Are</h2>\n<p>Anyora is a UK private-label home-storage brand and online retailer operated by Anyora Limited. We believe that a tidy home leads to a clearer mind. That's why we design products that are not only functional but also beautiful, helping you create spaces you love to spend time in without the stress of clutter.</p>\n<p>Our team is dedicated to sourcing the most sustainable, durable materials to bring you storage solutions that last a lifetime.</p>" ),
		'contact' => array( 'title' => 'Contact', 'menu' => 'Contact', 'content' => "<h2>Get in Touch</h2>\n<p>We'd love to hear from you. Our customer support team is available Monday to Friday, 9:00am to 5:00pm UK local time, excluding public holidays.</p>\n<ul>\n<li><strong>Email:</strong> support@anyora.uk</li>\n<li><strong>Phone:</strong> +44 1902 382162</li>\n</ul>\n<p><strong>Registered office:</strong><br>72 Ambergate Road, Bilston, WV14 0SR, United Kingdom</p>" ),
		'mission' => array( 'title' => 'Mission', 'menu' => 'Mission', 'content' => "<h2>Our Mission</h2>\n<p>To provide practical storage products selected to help make everyday spaces tidier and easier to use. We aim to reduce clutter and bring harmony to modern living spaces.</p>" ),
		'terms'   => array( 'title' => 'Terms and conditions', 'menu' => '', 'content' => "<h2>Terms and Conditions</h2>\n<p>These terms and conditions outline the rules and regulations for the use of Anyora's Website.</p>" ),
		'shipping'=> array( 'title' => 'Shipping policy', 'menu' => '', 'content' => "<h2>Shipping Policy</h2>\n<p>We offer free delivery on all orders over £50. Standard shipping takes 3-5 business days.</p>" ),
		'returns' => array( 'title' => 'Returns, refunds and cancellations', 'menu' => '', 'content' => "<h2>Returns & Refunds</h2>\n<p>We offer a 30-day return policy for all unused items in their original packaging.</p>" ),
		'payment' => array( 'title' => 'Payment policy', 'menu' => '', 'content' => "<h2>Payment Policy</h2>\n<p>We accept all major credit cards, PayPal, Apple Pay, and Google Pay. All transactions are securely processed.</p>" ),
		'privacy' => array( 'title' => 'Privacy policy', 'menu' => '', 'content' => "<h2>Privacy Policy</h2>\n<p>Your privacy is important to us. We only collect the necessary information to process your order and provide you with a great experience.</p>" ),
		'cookie'  => array( 'title' => 'Cookie policy', 'menu' => '', 'content' => "<h2>Cookie Policy</h2>\n<p>We use cookies to improve your browsing experience and analyze site traffic.</p>" ),
		'cookie-pref' => array( 'title' => 'Cookie preferences', 'menu' => '', 'content' => "<h2>Cookie Preferences</h2>\n<p>Manage your cookie settings here.</p>" ),
		'my-account' => array( 'title' => 'My account', 'menu' => '', 'content' => "[woocommerce_my_account]" ),
	);
}

function anyora_create_dummy_products() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    $dummy_categories = array(
        'Kitchen storage' => 'kitchen-storage',
        'Bathroom storage' => 'bathroom-storage',
        'Drawer organisers' => 'drawer-organisers',
        'Shoe storage' => 'shoe-storage',
    );

    foreach ( $dummy_categories as $name => $slug ) {
        if ( ! term_exists( $slug, 'product_cat' ) ) {
            wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
        }
    }

    $dummy_products = array(
        array(
            'title' => 'Compact Mobility Scooter (Foldable)',
            'price' => '899.00',
            'cat'   => 'indoor-storage',
        ),
        array(
            'title' => 'Bamboo 3-Tier Shelving Unit',
            'price' => '45.00',
            'cat'   => 'indoor-storage',
        ),
        array(
            'title' => 'Minimalist Desk Organizer',
            'price' => '24.99',
            'cat'   => 'office-desk',
        ),
        array(
            'title' => 'Ceramic Bathroom Set',
            'price' => '35.00',
            'cat'   => 'bathroom',
        ),
    );

    foreach ( $dummy_products as $prod ) {
        $existing = get_page_by_title( $prod['title'], OBJECT, 'product' );
        if ( ! $existing ) {
            $post_id = wp_insert_post( array(
                'post_title'   => $prod['title'],
                'post_content' => 'High quality organization product for your modern home.',
                'post_status'  => 'publish',
                'post_type'    => 'product',
            ) );

            if ( $post_id ) {
                wp_set_object_terms( $post_id, 'simple', 'product_type' );
                update_post_meta( $post_id, '_visibility', 'visible' );
                update_post_meta( $post_id, '_stock_status', 'instock' );
                update_post_meta( $post_id, '_regular_price', $prod['price'] );
                update_post_meta( $post_id, '_price', $prod['price'] );
                
                $term = get_term_by( 'slug', $prod['cat'], 'product_cat' );
                if ( $term ) {
                    wp_set_object_terms( $post_id, $term->term_id, 'product_cat' );
                }
            }
        }
    }
}
