<?php
/**
 * WholesaleX Dynamic Rules - Payment Gateway Rule Handler
 *
 * Handles payment_order_qty (Required Qty for Payment Method) rules
 * and profile/role-level payment gateway filtering.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule_Payment_Gateway
 */
class Rule_Payment_Gateway {

	/**
	 * Handle Payment Gateway.
	 * Priority: User Profile >> Role > Dynamic Rule.
	 *
	 * @param array $data Data.
	 */
	public function handle( $data ) {
		add_filter(
			'woocommerce_available_payment_gateways',
			function ( $gateways ) use ( $data ) {
				if ( ! is_array( $gateways ) ) {
					return $gateways;
				}
				$allowed_gateways = array();
				if ( ! empty( $data['profile'] ) ) {
					$allowed_profile_gateways = Dynamic_Rules_Condition_Engine::get_multiselect_values( $data['profile'] );

					foreach ( $gateways as $gateway_id => $gateway ) {
						if ( in_array( $gateway_id, $allowed_profile_gateways, true ) ) {
							$allowed_gateways[ $gateway_id ] = $gateway;
						}
					}

					if ( ! empty( $allowed_gateways ) ) {
						return $allowed_gateways;
					}
				}

				if ( empty( $data['roles'] ) ) {
					$data['roles'] = array_keys( $gateways );
				}

				if ( ! empty( $data['rules'] ) && WC() && null !== WC()->cart ) {
					$cart_contents         = WC()->cart->cart_contents;
					$rule_allowed_gateways = $data['roles'];

					foreach ( $data['rules'] as $rule ) {
						$rule_gateway = isset( $rule['rule']['_payment_gateways'] ) ? Dynamic_Rules_Condition_Engine::get_multiselect_values( $rule['rule']['_payment_gateways'] ) : array();
						if ( ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) || empty( $rule_gateway ) ) {
							continue;
						}

						foreach ( $cart_contents as $item ) {
							$required_qty = isset( $rule['rule']['_order_quantity'] ) ? (int) $rule['rule']['_order_quantity'] : 999999999;
							if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $item['product_id'], $item['variation_id'], $rule['filter'] ) ) {
								$item_qty_in_cart = (int) wholesalex()->cart_count( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
								if ( $item_qty_in_cart < $required_qty ) {
									$rule_allowed_gateways = array_diff( $rule_allowed_gateways, $rule_gateway );
								}
							}
						}

						if ( empty( $rule_allowed_gateways ) ) {
							$rule_allowed_gateways = $data['roles'];
						}
					}

					foreach ( $gateways as $gateway_id => $gateway ) {
						if ( in_array( $gateway_id, $rule_allowed_gateways, true ) ) {
							$allowed_gateways[ $gateway_id ] = $gateway;
						}
					}

					return $allowed_gateways;
				}

				$allowed_gateways = array();

				foreach ( $gateways as $gateway_id => $gateway ) {
					if ( in_array( $gateway_id, $data['roles'], true ) ) {
						$allowed_gateways[ $gateway_id ] = $gateway;
					}
				}

				return $allowed_gateways;
			}
		);
	}
}
