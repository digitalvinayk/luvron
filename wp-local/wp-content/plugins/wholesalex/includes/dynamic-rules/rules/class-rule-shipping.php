<?php
/**
 * WholesaleX Dynamic Rules - Shipping Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Shipping_Free_Shipping;

/**
 * Rule_Shipping
 */
class Rule_Shipping {

	/**
	 * Handle Shipping related all kind of rules and options.
	 *
	 * @param array $data Data.
	 * Priority: Profile > User Role > Dynamic Rules.
	 */
	public function handle( $data ) {
		add_filter(
			'woocommerce_package_rates',
			function ( $package_rates, $package ) use ( $data ) {
				// Force Free Shipping For Specific.
				if ( isset( $data['profile']['method_type'] ) && 'force_free_shipping' === $data['profile']['method_type'] ) {
					$free_shipping = new WC_Shipping_Free_Shipping( 'wholesalex_free_shipping' );
					/* translators: %s: Plugin Name */
					$free_shipping->title = apply_filters( 'wholesalex_free_shipping_title', sprintf( __( '%s Free Shipping', 'wholesalex' ), wholesalex()->get_plugin_name() ) );
					$free_shipping->calculate_shipping( $package );
					return apply_filters( 'wholesalex_available_shipping_methods', $free_shipping->rates, $package_rates, $package, $data );
				}

				if ( isset( $data['profile']['method_type'] ) && 'specific_shipping_methods' === $data['profile']['method_type'] ) {
					$all_methods     = $package_rates;
					$allowed_methods = array();

					foreach ( $data['profile']['methods'] as $method ) {
						$allowed_methods[] = $method['value'];
					}

					foreach ( $package_rates as $rate_key => $rate ) {
						if ( ! in_array( $rate->instance_id, $allowed_methods, true ) ) {
							unset( $package_rates[ $rate_key ] );
						}
					}
					return apply_filters( 'wholesalex_available_shipping_methods', $package_rates, $all_methods, $package, $data );
				}

				if ( empty( $data['roles'] ) ) {
					foreach ( $package_rates as $rate_key => $rate ) {
						$data['roles'][] = $rate->instance_id;
					}
				}

				if ( ! empty( $data['rules'] ) ) {
					$allowed_rates = array();
					foreach ( $data['rules'] as $rule ) {
						if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
							continue;
						}

						$available_methods = array();

						$temp_available_methods = $data['roles'];

						if ( ! empty( $rule['rule']['_shipping_zone_methods'] ) ) {
							foreach ( $rule['rule']['_shipping_zone_methods'] as $method ) {
								$available_methods[] = $method['value'];
							}
						}

						if ( isset( $rule['filter']['is_all_products'] ) && $rule['filter']['is_all_products'] ) {
							$temp_available_methods = array_intersect( $temp_available_methods, $available_methods );
							wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
						} elseif ( isset( $package['contents'] ) ) {
							foreach ( $package['contents'] as $item ) {
								if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $item['product_id'], $item['variation_id'], $rule['filter'] ) ) {
									$temp_available_methods = array_intersect( $temp_available_methods, $available_methods );
									wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
								} elseif ( apply_filters( 'wholesalex_shipping_rule_merged_all_methods', false ) ) {
										$temp_available_methods = array_merge( $temp_available_methods, $data['roles'] );
								} else {
									$temp_available_methods = array_diff( $data['roles'], $temp_available_methods );
								}
							}
						}

						foreach ( $package_rates as $rate_key => $rate ) {
							if ( in_array( (string) $rate->instance_id, $temp_available_methods, true ) ) {
								$allowed_rates[ $rate_key ] = $package_rates[ $rate_key ];
							}
						}
					}

					if ( ! empty( $allowed_rates ) ) {
						return apply_filters( 'wholesalex_available_shipping_methods', $allowed_rates, $package_rates, $package, $data );
					}
				}

				$allowed_rates = array();
				foreach ( $package_rates as $rate_key => $rate ) {
					if ( in_array( (string) $rate->instance_id, $data['roles'], true ) ) {
						$allowed_rates[ $rate_key ] = $package_rates[ $rate_key ];
					}
				}
				return apply_filters( 'wholesalex_available_shipping_methods', $allowed_rates, $package_rates, $package, $data );
			},
			10,
			2
		);
	}
}
