<?php
/**
 * WholesaleX Dynamic Rules - Cart Discount Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule_Cart_Discount
 */
class Rule_Cart_Discount {

	/**
	 * Register cart discount rule metadata for the single product page promo display.
	 *
	 * Iterates the supplied cart discount rules and stores each eligible rule's
	 * data via wholesalex()->set_rule_data() so it can be fetched later by
	 * render_promo_html() during single product page rendering.
	 *
	 * Skips rules whose data is already stored (idempotent). Only runs when the
	 * `show_cart_discount_text` setting is enabled.
	 *
	 * Called by:
	 *   - Dynamic_Rules::check_for_cart_releated_discounts()
	 *
	 * @param WC_Product $product Current product being displayed.
	 * @param array      $rules   Cart discount rule entries to evaluate.
	 * @return void
	 */
	public function register_promo_data( $product, array $rules ): void {
		if ( 'yes' !== wholesalex()->get_setting( 'show_cart_discount_text', 'no' ) ) {
			return;
		}

		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;
		$pid          = $variation_id ? $variation_id : $product_id;

		foreach ( $rules as $rule ) {
			if ( ! empty( wholesalex()->get_rule_data( $pid, 'cart_discount', $rule['id'] ) ) ) {
				continue;
			}
			if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $rule['filter'] ) ) {
				wholesalex()->set_rule_data(
					$rule['id'],
					$pid,
					'cart_discount',
					array(
						'type'                => $rule['rule']['_discount_type'],
						'value'               => $rule['rule']['_discount_amount'],
						'conditions'          => $rule['conditions'],
						'who_priority'        => $rule['who_priority'],
						'applied_on_priority' => $rule['applied_on_priority'],
						'end_date'            => $rule['end_date'],
					)
				);
			}
		}
	}

	/**
	 * Render the cart discount promo HTML block on the single product page.
	 *
	 * Reads rule data stored by register_promo_data(), sorts it by priority
	 * (highest first), and outputs the discount info cards. Each card shows the
	 * discount heading, optional conditions text, and optional validity date.
	 *
	 * Gated by the `show_cart_discount_text` setting. Outputs nothing when no
	 * cart discount data exists for the product.
	 *
	 * Called by:
	 *   - Dynamic_Rules::handle_single_product_page_promo()
	 *
	 * @param WC_Product $product       Current product being displayed.
	 * @param callable   $conditions_cb Callback to generate conditions markup from
	 *                                  a tiers array. Pass [$dynamic_rules, 'generate_rule_conditions_markup'].
	 * @return void
	 */
	public function render_promo_html( $product, callable $conditions_cb ): void {
		if ( 'yes' !== wholesalex()->get_setting( 'show_cart_discount_text', 'no' ) ) {
			return;
		}

		$cart_discounts = wholesalex()->get_rule_data( $product->get_id(), 'cart_discount' );

		if ( empty( $cart_discounts ) ) {
			return;
		}

		usort( $cart_discounts, array( 'WHOLESALEX\Dynamic_Rules_Condition_Engine', 'compare_by_priority_reverse' ) );

		?>
		<div class="wsx-sp-cart-discounts">
			<?php
			if ( 'yes' === wholesalex()->get_setting( 'cart_discount_rule_sp_show_rule_info', 'yes' ) ) {
				?>
				<div class="wsx-sp-rule-info">
					<div class="wsx-font-14 wsx-font-medium">
						<?php echo esc_html( wholesalex()->get_setting( 'cart_discount_rule_info_rule_type_text', __( 'Cart Discount', 'wholesalex' ) ) ); ?>
					</div>
					<?php
					if ( count( $cart_discounts ) > 1 ) {
						?>
						<div class="wsx-font-14">
							<?php echo esc_html( wholesalex()->get_setting( 'dynamic_rule_promotional_explainer_text_multiple_discount', __( 'You can avail following offers by completing the requirements.', 'wholesalex' ) ) ); ?>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>

			<div class="wsx-sp-discounts-cards wsx-p-4 wsx-br-sm wsx-mt-8 wsx-bg-promotion">
				<?php
				foreach ( $cart_discounts as $cd ) {
					if ( 'percentage' === $cd['type'] ) {
						$heading_text = $cd['value'] . __( ' % OFF', 'wholesalex' );
					} elseif ( 'amount' === $cd['type'] ) {
						$heading_text = wc_price( $cd['value'] ) . __( ' OFF', 'wholesalex' );
					} elseif ( 'fixed' === $cd['type'] ) {
						$heading_text = '<del>' . wc_price( $product->get_price() ) . '</del>. to <ins>' . wc_price( $cd['value'] ) . '</ins>';
					}

					$conditions = 'yes' === wholesalex()->get_setting( 'show_discount_conditions_on_sp', 'no' ) && isset( $cd['conditions']['tiers'] ) ? call_user_func( $conditions_cb, $cd['conditions']['tiers'] ) : '';
					$validity   = '';
					if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
						$validity = $cd['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . Dynamic_Rules_Condition_Engine::restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $cd['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
					}
					?>
					<div class="wsx-single-product-discount-card wsx-cart-discount-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
						<div class="wsx-font-18 wsx-font-medium" style="color: var(--color-notice)"> <?php echo wp_kses_post( $heading_text ); ?></div>
						<div class="wsx-font-14"><?php echo wp_kses_post( $conditions . $validity ); ?></div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Calculate cart discount fees.
	 *
	 * Called by Dynamic_Rules::handle_cart() inside the woocommerce_cart_calculate_fees hook.
	 * Receives pre-filtered, pre-sorted rules for the current user and returns a hash of
	 * fee entries that the orchestrator then applies as negative WooCommerce cart fees.
	 *
	 * @param array $rules Cart discount rules (already filtered for the current user/role).
	 * @return array Hash map of [ md5_key => [ 'discount' => float, 'name' => string ] ].
	 */
	public function calculate( $rules ) {
		$hash = array();
		foreach ( $rules as $rule ) {
			if ( ! isset( WC()->cart ) || null === WC()->cart->get_cart() ) {
				return $hash;
			}
			if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
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
				$discount_name   = apply_filters( 'wholesalex_cart_discount_title', isset( $rule['rule']['_discount_name'] ) ? $rule['rule']['_discount_name'] : __( 'Cart Discounts', 'wholesalex' ) );
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
			} else {
				if ( ! isset( WC()->cart ) || null === WC()->cart->get_cart() ) {
					return $hash;
				}
				$rule_total_amount = 0;
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $cart_item['product_id'], $cart_item['variation_id'], $rule['filter'] ) ) {
						$rule_total_amount += $cart_item['line_total'];
						if ( apply_filters( 'wholesalex_dr_cart_discount_on_tax', false ) ) {
							$rule_total_amount += $cart_item['line_tax'];
						}
					}
				}

				$discount_amount = ( 'percentage' === $discount_type ) ? ( $rule_total_amount * floatval( $rule['rule']['_discount_amount'] ) ) / 100 : floatval( $rule['rule']['_discount_amount'] );
				$discount_name   = apply_filters( 'wholesalex_cart_discount_title', isset( $rule['rule']['_discount_name'] ) ? $rule['rule']['_discount_name'] : __( 'Cart Discounts', 'wholesalex' ) );
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
