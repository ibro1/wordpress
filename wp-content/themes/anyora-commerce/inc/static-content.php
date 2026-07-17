<?php
/**
 * Static content and dummy data generation for Anyora Commerce.
 */

defined( 'ABSPATH' ) || exit;

function anyora_starter_pages() {
	return array(
		'home'    => array( 'title' => 'Home', 'menu' => 'Home' ),
		'shop'    => array( 'title' => 'Shop', 'menu' => 'Shop' ),
		'about'   => array( 'title' => 'About', 'menu' => 'About' ),
		'contact' => array( 'title' => 'Contact', 'menu' => 'Contact' ),
	);
}

function anyora_create_dummy_products() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    $dummy_categories = array(
        'Indoor Storage' => 'indoor-storage',
        'Home Accessories' => 'home-accessories',
        'Bathroom' => 'bathroom',
        'Office & Desk' => 'office-desk',
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
