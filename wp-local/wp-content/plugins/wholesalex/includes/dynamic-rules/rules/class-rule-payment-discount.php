<?php
/**
 * WholesaleX Dynamic Rules - Payment Discount Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule_Payment_Discount
 */
class Rule_Payment_Discount {

	/**
	 * Calculate payment discount fees.
	 *
	 * @param array $rules Payment discount rules.
	 * @return array Hash of fee data.
	 */
	public function calculate( $rules ) {
		$hash             = array();
		$selected_gateway = WC()->session->get( 'chosen_payment_method' );

		foreach ( $rules as $rule ) {
			if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
				continue;
			}

			$allowed_gateways = Dynamic_Rules_Condition_Engine::get_multiselect_values( $rule['rule']['_payment_gateways'] );
			if ( ! in_array( $selected_gateway, $allowed_gateways, true ) ) {
				continue;
			}

			$discount_amount = 0;
			$discount_name   = '';
			$is_all_products = $rule['filter']['is_all_products'];
			$discount_type   = $rule['rule']['_discount_type'];
			$hash_key        = md5( serialize( array( $rule['id'], $rule['filter'] ) ) );

			if ( $is_all_products ) {
				$total_value     = wholesalex()->get_cart_total();
				$discount_amount = ( 'percentage' == $discount_type ) ? ( $total_value * floatval( $rule['rule']['_discount_amount'] ) ) / 100 : floatval( $rule['rule']['_discount_amount'] );
				$discount_name   = apply_filters( 'wholesalex_payment_gateway_default_discount_name', isset( $rule['rule']['_discount_name'] ) ? $rule['rule']['_discount_name'] : __( 'Payment Discount!', 'wholesalex' ) );
				if ( isset( $hash[ $hash_key ] ) && is_array( $hash[ $hash_key ] ) ) {
					if ( $discount_amount >= $hash[ $hash_key ]['discount'] ) {
						$hash[ md5( serialize( $rule['filter'] ) ) ] = array(
							'discount' => $discount_amount,
							'name'     => $discount_name,
						);
						wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
					}
				} else {
					$hash[ $hash_key ] = array(
						'discount' => $discount_amount,
						'name'     => $discount_name,
					);
					wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
				}
			} else {
				if ( ! isset( WC()->cart ) || null === WC()->cart->get_cart() ) {
					return $hash;
				}
				$rule_total_amount = 0;
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $cart_item['product_id'], $cart_item['variation_id'], $rule['filter'] ) ) {
						$rule_total_amount += $cart_item['line_total'];
						if ( apply_filters( 'wholesalex_dr_payment_discount_on_tax', false ) ) {
							$rule_total_amount += $cart_item['line_tax'];
						}
					}
				}
				$discount_amount = ( 'percentage' === $discount_type ) ? ( $rule_total_amount * floatval( $rule['rule']['_discount_amount'] ) ) / 100 : floatval( $rule['rule']['_discount_amount'] );
				$discount_name   = apply_filters( 'wholesalex_payment_gateway_default_discount_name', isset( $rule['rule']['_discount_name'] ) ? $rule['rule']['_discount_name'] : __( 'Payment Discount!', 'wholesalex' ) );
				if ( isset( $hash[ $hash_key ] ) && is_array( $hash[ $hash_key ] ) ) {
					if ( $discount_amount >= $hash[ $hash_key ]['discount'] ) {
						$hash[ $hash_key ] = array(
							'discount' => $discount_amount,
							'name'     => $discount_name,
						);
						wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
					}
				} else {
					$hash[ $hash_key ] = array(
						'discount' => $discount_amount,
						'name'     => $discount_name,
					);
					wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
				}
			}
		}
		return $hash;
	}
}
