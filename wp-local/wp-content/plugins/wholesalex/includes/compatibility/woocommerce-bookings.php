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
class WHOLESALEX_Woocommerce_Bookings {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_filter( 'wholesalex_ignore_dynamic_price', array( $this, 'is_booking_product' ), 10, 2 );
	}

	/**
	 * Check if the given product is a booking product of type 'mwb_booking'.
	 *
	 * This function checks whether the passed product object is of the custom type 'mwb_booking'.
	 *
	 * @param float      $price   The price of the product (not used in this check, but included for compatibility).
	 * @param WC_Product $product An instance of the WooCommerce product object.
	 *
	 * @return bool Returns true if the product is of type 'mwb_booking', false otherwise.
	 */
	public function is_booking_product( $price, $product ) {
		if ( $product->is_type( 'mwb_booking' ) || $product->is_type( 'booking' ) ) {
			return true;
		}
		return false;
	}
}
