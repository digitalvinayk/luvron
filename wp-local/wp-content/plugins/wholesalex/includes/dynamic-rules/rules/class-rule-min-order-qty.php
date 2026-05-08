<?php
/**
 * WholesaleX Dynamic Rules - Min Order Qty Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule_Min_Order_Qty
 */
class Rule_Min_Order_Qty {

	/**
	 * Handle min order qty rules.
	 *
	 * @param array $data All categorized rules data.
	 */
	public function handle( $data ) {
		// Quantity input args for simple products.
		add_filter(
			'woocommerce_quantity_input_args',
			function ( $args, $product ) use ( $data ) {
				if ( ! empty( $data['min_order_qty'] ) ) {
					foreach ( $data['min_order_qty'] as $rule ) {
						if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
							continue;
						}
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(), $product->get_id(), $rule['filter'] ) ) {
							if ( isset( $rule['rule']['_min_order_qty_disable'] ) && 'no' === $rule['rule']['_min_order_qty_disable'] ) {
								$args['min_value'] = $rule['rule']['_min_order_qty'];
							}
							if ( isset( $rule['rule']['_min_order_qty_step'] ) && $rule['rule']['_min_order_qty_step'] ) {
								$args['step'] = $rule['rule']['_min_order_qty_step'];
							}
						}
					}
				}
				$args = apply_filters( 'wholesalex_dr_min_max_qty_input_args', $args, $data, $product );
				return $args;
			},
			10,
			2
		);

		// Variation quantity step control.
		add_filter(
			'woocommerce_available_variation',
			function ( $variation_data, $product, $variation ) use ( $data ) {
				if ( ! empty( $data['min_order_qty'] ) ) {
					foreach ( $data['min_order_qty'] as $rule ) {
						if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
							continue;
						}
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $variation->get_parent_id(), $variation->get_id(), $rule['filter'] ) ) {
							if ( isset( $rule['rule']['_min_order_qty_disable'] ) && 'no' === $rule['rule']['_min_order_qty_disable'] ) {
								$variation_data['min_qty'] = $rule['rule']['_min_order_qty'];
							}
							if ( isset( $rule['rule']['_min_order_qty_step'] ) && $rule['rule']['_min_order_qty_step'] ) {
								$variation_data['step'] = $rule['rule']['_min_order_qty_step'];
							}
						}
					}
				}
				return $variation_data;
			},
			10,
			3
		);

		// Loop add-to-cart default quantity.
		add_filter(
			'woocommerce_loop_add_to_cart_args',
			function ( $args, $product ) use ( $data ) {
				if ( ! $product->is_type( 'simple' ) ) {
					return $args;
				}
				foreach ( $data['min_order_qty'] as $rule ) {
					if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
						continue;
					}
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product->get_id(), $product->get_id(), $rule['filter'] ) ) {
						if ( isset( $rule['rule']['_min_order_qty_disable'] ) && 'no' === $rule['rule']['_min_order_qty_disable'] ) {
							$args['quantity'] = $rule['rule']['_min_order_qty'];
						}
					}
				}
				return $args;
			},
			10,
			2
		);

		// Add-to-cart validation.
		add_filter(
			'woocommerce_add_to_cart_validation',
			function ( $status, $product_id, $quantity ) use ( $data ) {
				$variation_id = 0;
				$product      = wc_get_product( $product_id );
				$variation    = false;
				if ( ! $product->is_type( 'simple' ) ) {
					$args         = func_get_args();
					$variation_id = isset( $args[3] ) ? $args[3] : 0;
					if ( $variation_id ) {
						$variation = wc_get_product( $variation_id );
					}
				}

				$min = '';
				foreach ( $data['min_order_qty'] as $rule ) {
					if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
						continue;
					}
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $rule['filter'] ) ) {
						if ( $variation_id ) {
							$existing_qty = wholesalex()->cart_count( $variation_id );
							$new_qty      = $existing_qty + $quantity;
							if ( $new_qty < $rule['rule']['_min_order_qty'] ) {
								if ( isset( $rule['rule']['_min_order_qty_disable'] ) && 'no' === $rule['rule']['_min_order_qty_disable'] ) {
									$status = false;
								}
								if ( ! $min ) {
									$min = $rule['rule']['_min_order_qty'];
								}
								$min = min( $rule['rule']['_min_order_qty'], $min );
							}
						}

						if ( $status ) {
							$existing_qty = wholesalex()->cart_count( $product_id );
							$new_qty      = $existing_qty + $quantity;
							if ( $new_qty < $rule['rule']['_min_order_qty'] ) {
								if ( isset( $rule['rule']['_min_order_qty_disable'] ) && 'no' === $rule['rule']['_min_order_qty_disable'] ) {
									$status = false;
								}
								if ( ! $min ) {
									$min = $rule['rule']['_min_order_qty'];
								}
								$min = min( $rule['rule']['_min_order_qty'], $min );
							}
						}
					}
				}
				if ( ! $status ) {
					wc_add_notice(
						apply_filters(
							'wholesalex_dynamic_rules_min_quantity_error_message',
							Dynamic_Rules_Condition_Engine::restore_smart_tags(
								array(
									'{minimum_qty}'   => $min,
									'{product_title}' => $variation_id ? $variation->get_name() : $product->get_name(),
								),
								wholesalex()->get_setting( 'only_minimum_order_qty_promo_text', __( 'You have to add minimun {minimum_qty} quantity', 'wholesalex' ) )
							)
						),
						'error'
					);
					return $status;
				}
				$status = apply_filters( 'wholesalex_dr_min_max_add_to_cart_validation', $status, $data, $product_id, $variation_id, $quantity, $product, $variation );
				return $status;
			},
			10,
			5
		);

		// Cart update validation.
		add_filter(
			'woocommerce_update_cart_validation',
			function ( $status, $cart_item_key, $values, $quantity ) use ( $data ) {
				$product_id   = $values['variation_id'] ? $values['variation_id'] : $values['product_id'];
				$variation_id = $values['variation_id'];
				$product      = wc_get_product( $product_id );

				$min_data = array(
					'variation' => '',
					'product'   => '',
				);
				foreach ( $data['min_order_qty'] as $rule ) {
					if ( isset( $rule['conditions']['tiers'] ) && ! empty( $rule['conditions']['tiers'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
						continue;
					}
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $rule['filter'] ) ) {
						if ( $variation_id ) {
							if ( ! $min_data['variation'] ) {
								if ( isset( $rule['rule']['_min_order_qty_disable'] ) && 'no' === $rule['rule']['_min_order_qty_disable'] ) {
									$min_data['variation'] = $rule['rule']['_min_order_qty'];
								}
							}
							$min_data['variation'] = min( $rule['rule']['_min_order_qty'], $min_data['variation'] );
						}
						if ( ! $min_data['variation'] ) {
							if ( ! $min_data['product'] ) {
								if ( isset( $rule['rule']['_min_order_qty_disable'] ) && 'no' === $rule['rule']['_min_order_qty_disable'] ) {
									$min_data['product'] = $rule['rule']['_min_order_qty'];
								}
							}
							$min_data['product'] = min( $rule['rule']['_min_order_qty'], $min_data['product'] );
						}
					}
				}
				$min = $min_data['variation'] ? $min_data['variation'] : $min_data['product'];
				if ( $min && $quantity < $min ) {
					$status = false;
				}

				if ( ! $status ) {
					wc_add_notice(
						apply_filters(
							'wholesalex_dynamic_rules_min_quantity_error_message',
							Dynamic_Rules_Condition_Engine::restore_smart_tags(
								array(
									'{minimum_qty}'   => $min,
									'{product_title}' => $product->get_title(),
								),
								wholesalex()->get_setting( 'only_minimum_order_qty_promo_text', __( 'You have to add minimun {minimum_qty} quantity', 'wholesalex' ) )
							)
						),
						'error'
					);
					return $status;
				}
				$status = apply_filters( 'wholesalex_dr_min_max_update_cart_validation', $status, $data, $product_id, $variation_id, $quantity, $product );
				return $status;
			},
			10,
			4
		);

		// Checkout cart items check.
		add_action(
			'woocommerce_check_cart_items',
			function () use ( $data ) {
				if ( ! ( isset( WC()->cart ) && ! empty( WC()->cart ) ) ) {
					return;
				}
				foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
					if ( isset( $cart_item['free_product'] ) && $cart_item['free_product'] ) {
						continue;
					}
					$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
					$product       = wc_get_product( $product_id );
					$product_title = $product->get_name();

					$min_data = array(
						'variation' => '',
						'product'   => '',
					);
					foreach ( $data['min_order_qty'] as $rule ) {
						if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
							continue;
						}
						$min = $rule['rule']['_min_order_qty'];
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $cart_item['variation_id'] ? 0 : $cart_item['product_id'], $cart_item['variation_id'], $rule['filter'] ) ) {
							if ( ! $min_data['variation'] ) {
								$min_data['variation'] = $rule['rule']['_min_order_qty'];
							}
							$min_data['variation'] = min( $rule['rule']['_min_order_qty'], $min_data['variation'] );
						}
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $cart_item['product_id'], 0, $rule['filter'] ) && ! $min_data['variation'] ) {
							if ( ! $min_data['product'] ) {
								$min_data['product'] = $rule['rule']['_min_order_qty'];
							}
							$min_data['product'] = min( $rule['rule']['_min_order_qty'], $min_data['product'] );
						}
					}

					$min = $min_data['variation'] ? $min_data['variation'] : $min_data['product'];
					if ( $min && $cart_item['quantity'] < $min ) {
						wc_add_notice(
							apply_filters(
								'wholesalex_dynamic_rules_min_quantity_error_message',
								Dynamic_Rules_Condition_Engine::restore_smart_tags(
									array(
										'{minimum_qty}'   => $min,
										'{product_title}' => $product_title,
									),
									wholesalex()->get_setting( 'only_minimum_order_qty_promo_text', __( 'You have to add minimun {minimum_qty} quantity', 'wholesalex' ) )
								)
							),
							'error'
						);
					}

					do_action( 'wholesalex_dr_min_max_check_cart_items', true, $data, $cart_item, $product_title );
				}
			}
		);

		// WooCommerce Store API minimum quantity.
		add_action(
			'woocommerce_store_api_product_quantity_minimum',
			function ( $value, $product ) use ( $data ) {
				if ( ! empty( $data['min_order_qty'] ) ) {
					foreach ( $data['min_order_qty'] as $rule ) {
						if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
							continue;
						}
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(), $product->get_id(), $rule['filter'] ) ) {
							$value = $rule['rule']['_min_order_qty'];
						}
					}
				}
				return $value;
			},
			10,
			2
		);
	}
}
