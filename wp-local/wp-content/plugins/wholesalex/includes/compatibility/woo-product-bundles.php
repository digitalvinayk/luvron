<?php
/**
 * Compatibility with woocommerce Booking product and woocommerce booking.
 *
 * @package WHOLESALEX
 * @since 2.0.14
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WholesaleX Dynamic Rules Class
 */
class WHOLESALEX_WooProduct_Bundles {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Use the same filter name the main plugin applies: wholesalex_ignore_dynamic_price
		// It passes ( $ignore, $p_product, $context ) so we accept three args and
		// detect bundle cart items so the dynamic pricing engine can skip them.
		add_filter( 'wholesalex_ignore_dynamic_price', array( $this, 'is_product_bundle' ), 10, 3 );
	}

	/**
	 * Check if the given product is a booking product of type 'mwb_booking'.
	 *
	 * This function checks whether the passed product object is of the custom type 'mwb_booking'.
	 *
	 * @param float      $price     The price of the product (not used in this check, but included for compatibility).
	 * @param WC_Product $cart_item An instance of the WooCommerce product object.
	 *
	 * @return bool Returns true if the product is of type 'mwb_booking', false otherwise.
	 */
	/**
	 * Determine whether we should ignore dynamic pricing for a product because
	 * it is part of a WPC Product Bundle.
	 *
	 * @param bool       $ignore  Current ignore flag.
	 * @param WC_Product $p_product Product object.
	 * @param string     $context Context string where filter is applied.
	 * @return bool True to ignore dynamic pricing for this product.
	 */
	public function is_product_bundle( $ignore, $p_product, $context = '' ) {
		// Only proceed if WPC Product Bundles is active.
		if ( ! function_exists( 'is_plugin_active' ) || ! is_plugin_active( 'woo-product-bundle/wpc-product-bundles.php' ) ) {
			return $ignore;
		}
		global $post;

		// Be defensive: if product is not a WC_Product, return original value.
		if ( ! is_object( $p_product ) || ! method_exists( $p_product, 'get_id' ) ) {
			return $ignore;
		}

		$p_product_id = (int) $p_product->get_id();

		// Check if bundle info is stored on current product (when it's the bundle parent).
		if ( isset( $post->ID ) && $post->ID && is_product() ) {
			$bundle_ids = get_post_meta( $post->ID, 'woosb_ids', true );
			if ( ! empty( $bundle_ids ) && is_array( $bundle_ids ) ) {
				// Check if this is the bundle parent page, ignore dynamic pricing.
				// Also check if current product is a child in this bundle.
				foreach ( $bundle_ids as $bundled_item ) {
					if ( isset( $bundled_item['id'] ) && (int) $bundled_item['id'] === $p_product_id ) {
						return true;
					}
				}
			}
		}

		// Check if this is a bundled child product in cart context.
		if ( function_exists( 'WC' ) && WC()->cart ) {
			$variation_id = isset( $p_product->variation_id ) ? (int) $p_product->variation_id : 0;

			foreach ( WC()->cart->get_cart() as $cart_item ) {
				// Check if cart item is part of a bundle (has parent or is parent).
				if ( isset( $cart_item['woosb_ids'] ) || isset( $cart_item['woosb_parent_id'] ) ) {
					$item_product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
					$item_variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;

					if ( $item_product_id === $p_product_id || $item_variation_id === $p_product_id || ( $variation_id && $item_variation_id === $variation_id ) ) {
						return true;
					}
				}
			}
		}

		return $ignore;
	}
}
