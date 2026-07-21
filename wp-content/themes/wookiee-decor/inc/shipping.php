<?php
/**
 * Wires the flat shipping rate from Wookiee Settings into an actual
 * WooCommerce shipping zone/method, so checkout genuinely charges what
 * the site's messaging (announcement bar, hero stat, shipping policy
 * page) has been promising all along. Previously the shipping_rate
 * setting only drove copy - this closes the gap so there's a single
 * source of truth for the number itself.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Finds the existing shipping zone covering the United Kingdom (GB), if
 * any, so setup doesn't create a duplicate zone on every request.
 */
function wookiee_find_uk_shipping_zone() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return null;
	}
	foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
		$zone = new WC_Shipping_Zone( $zone_data['id'] );
		foreach ( $zone->get_zone_locations() as $location ) {
			if ( 'country' === $location->type && 'GB' === $location->code ) {
				return $zone;
			}
		}
	}
	return null;
}

function wookiee_find_flat_rate_instance( WC_Shipping_Zone $zone ) {
	foreach ( $zone->get_shipping_methods() as $method ) {
		if ( 'flat_rate' === $method->id ) {
			return (int) $method->instance_id;
		}
	}
	return 0;
}

/**
 * Creates the "United Kingdom" shipping zone + a flat rate method if
 * neither exists yet. Self-healing on every init like the theme's other
 * starter-content setup, rather than a one-time version-gated run, so it
 * repairs itself if the zone is ever deleted.
 */
add_action( 'init', 'wookiee_ensure_shipping_method', 24 );
function wookiee_ensure_shipping_method() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return;
	}

	$zone = wookiee_find_uk_shipping_zone();
	if ( ! $zone ) {
		$new_zone = new WC_Shipping_Zone();
		$new_zone->set_zone_name( 'United Kingdom' );
		$new_zone->set_zone_order( 0 );
		$zone_id = $new_zone->save();

		$zone = new WC_Shipping_Zone( $zone_id );
		$zone->add_location( 'GB', 'country' );
		$zone->save();
	}

	$instance_id = wookiee_find_flat_rate_instance( $zone );
	if ( ! $instance_id ) {
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
	}

	if ( $instance_id ) {
		wookiee_sync_shipping_method_cost( (int) $instance_id );
	}
}

/**
 * Writes the current shipping_rate setting into the flat rate method's
 * own settings option - this is the actual price WooCommerce charges at
 * checkout, stored separately from wookiee_setting_shipping_rate.
 */
function wookiee_sync_shipping_method_cost( $instance_id ) {
	$option_key = 'woocommerce_flat_rate_' . $instance_id . '_settings';
	$settings   = get_option( $option_key, array() );

	$settings['title']      = ! empty( $settings['title'] ) ? $settings['title'] : 'Standard UK Delivery';
	$settings['tax_status'] = isset( $settings['tax_status'] ) ? $settings['tax_status'] : 'taxable';
	$settings['cost']       = (string) wookiee_get_setting( 'shipping_rate' );

	update_option( $option_key, $settings );
}

/**
 * Keeps checkout in sync the moment an admin changes the flat shipping
 * rate on the Wookiee Settings page, rather than only at the next init.
 */
add_action( 'update_option_wookiee_setting_shipping_rate', 'wookiee_on_shipping_rate_changed' );
function wookiee_on_shipping_rate_changed() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return;
	}
	$zone = wookiee_find_uk_shipping_zone();
	if ( ! $zone ) {
		return;
	}
	$instance_id = wookiee_find_flat_rate_instance( $zone );
	if ( $instance_id ) {
		wookiee_sync_shipping_method_cost( $instance_id );
	}
}
