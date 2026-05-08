<?php
/**
 * WholesaleX Dynamic Rules - Tax Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule_Tax
 */
class Rule_Tax {

	/**
	 * Product page notices (tax-free flags).
	 *
	 * @var array
	 */
	public $product_page_notices = array();

	/**
	 * Handle Tax Related Rules and Other Stuffs.
	 * First will check For profile exemption, if found then do it
	 * otherwise will check for dynamic rules.
	 *
	 * @param array $data Data.
	 * Priority: Profile > Dynamic Rules.
	 */
	public function handle( $data ) {
		$current_user_id = get_current_user_id();
		$customer        = new \WC_Customer( $current_user_id );

		if ( null === WC()->customer ) {
			WC()->customer = $customer;
		}

		if ( is_admin() || null === WC()->customer ) {
			return;
		}

		$status                 = false;
		$is_customer_vat_exempt = false;

		if ( isset( $data['profile_exemption'] ) && 'yes' === $data['profile_exemption'] ) {
			$is_customer_vat_exempt = true;
			$status                 = true;
		}
		if ( isset( $data['profile_exemption'] ) && 'no' === $data['profile_exemption'] ) {
			$is_customer_vat_exempt = false;
			$status                 = true;
		}

		if ( ! $status && isset( $data['rules'] ) && is_array( $data['rules'] ) && ! empty( $data['rules'] ) ) {
			$is_customer_vat_exempt = '';

			foreach ( $data['rules'] as $rule ) {
				if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
					continue;
				}

				if ( isset( $rule['filter']['is_all_products'] ) && $rule['filter']['is_all_products'] ) {
					$is_tax_exempt          = isset( $rule['rule']['_tax_exempted'] ) ? $rule['rule']['_tax_exempted'] : '';
					$is_based_on_country    = isset( $rule['rule']['_exempted_country'] ) ? $rule['rule']['_exempted_country'] : false;
					$is_customer_vat_exempt = 'yes' === $is_tax_exempt ? true : false;
					if ( $is_based_on_country ) {
						$allowed_country = Dynamic_Rules_Condition_Engine::get_multiselect_values( $is_based_on_country );
						$user_country    = '';

						if ( is_a( WC()->customer, 'WC_Customer' ) ) {
							$tax_setting = get_option( 'woocommerce_tax_based_on' );
							if ( 'shipping' === $tax_setting ) {
								$user_country = WC()->customer->get_shipping_country();
							} else {
								$user_country = WC()->customer->get_billing_country();
							}
						} else {
							$user_country = 'NAC';
						}

						if ( ! in_array( $user_country, $allowed_country, true ) ) {
							$is_customer_vat_exempt = false;
						}
					}

					$this->add_tax_class_filter( 'woocommerce_product_get_tax_class', $rule );
					$this->add_tax_class_filter( 'woocommerce_product_variation_get_tax_class', $rule );

					wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
				} else {
					$this->add_tax_class_filter( 'woocommerce_product_get_tax_class', $rule );
					$this->add_tax_class_filter( 'woocommerce_product_variation_get_tax_class', $rule );
				}
			}

			if ( $is_customer_vat_exempt ) {
				add_filter(
					'woocommerce_package_rates',
					function ( $rates, $package ) {
						foreach ( $rates as $rate_key => $rate ) {
							$rates[ $rate_key ]->taxes = array_map(
								function () {
									return 0;
								},
								$rate->taxes
							);
						}
						return $rates;
					},
					20,
					2
				);
			}
		}
		if ( $is_customer_vat_exempt ) {
			add_filter(
				'woocommerce_product_get_tax_class',
				function () {
					return 'Zero Rate';
				}
			);
			add_filter(
				'woocommerce_product_variation_get_tax_class',
				function () {
					return 'Zero Rate';
				}
			);
		}
	}

	/**
	 * Add tax class filter for a specific hook.
	 *
	 * @param string $hook  WooCommerce filter hook name.
	 * @param array  $rule  Rule data.
	 */
	private function add_tax_class_filter( $hook, $rule ) {
		add_filter(
			$hook,
			function ( $tax_class, $product ) use ( $rule ) {
				$rule_tax_class = isset( $rule['rule']['_tax_class'] ) ? $rule['rule']['_tax_class'] : '';
				$is_tax_exempt  = isset( $rule['rule']['_tax_exempted'] ) ? $rule['rule']['_tax_exempted'] : '';
				if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product->get_id(), 0, $rule['filter'] ) ) {
					$is_based_on_country = isset( $rule['rule']['_exempted_country'] ) ? $rule['rule']['_exempted_country'] : false;
					if ( $is_based_on_country ) {
						$allowed_country = Dynamic_Rules_Condition_Engine::get_multiselect_values( $is_based_on_country );
						$user_country    = '';
						if ( is_a( WC()->customer, 'WC_Customer' ) ) {
							$tax_setting = get_option( 'woocommerce_tax_based_on' );
							if ( 'shipping' === $tax_setting ) {
								$user_country = WC()->customer->get_shipping_country();
							} else {
								$user_country = WC()->customer->get_billing_country();
							}
						} else {
							$user_country = 'NAC';
						}

						if ( ! in_array( $user_country, $allowed_country, true ) ) {
							return $tax_class;
						}
					}
					if ( 'yes' === $is_tax_exempt ) {
						$tax_class = 'Zero Rate';
						if ( ! isset( $this->product_page_notices[ $product->get_id() ] ) || ! is_array( $this->product_page_notices[ $product->get_id() ] ) ) {
							$this->product_page_notices[ $product->get_id() ] = array();
						}
						$this->product_page_notices[ $product->get_id() ]['tax_free'] = true;
					} else {
						$tax_class = $rule_tax_class;
					}
					wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
				}
				return $tax_class;
			},
			10,
			2
		);
	}
}
