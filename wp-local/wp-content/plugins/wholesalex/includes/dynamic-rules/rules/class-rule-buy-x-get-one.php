<?php
/**
 * WholesaleX Dynamic Rules - BOGO (Buy X Get One Free) Rule Handler
 *
 * Calculates cart fees for buy_x_get_one rules.
 * Also handles BOGO badge display on shop and single product pages.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule_Buy_X_Get_One
 */
class Rule_Buy_X_Get_One {

	/**
	 * Valid dynamic rules (for badge display).
	 *
	 * @var array
	 */
	private $valid_dynamic_rules = array();

	/**
	 * Set valid dynamic rules reference for badge display.
	 *
	 * @param array $rules All validated dynamic rules.
	 */
	public function set_valid_rules( $rules ) {
		$this->valid_dynamic_rules = $rules;
	}

	/**
	 * Calculate BOGO cart fees.
	 *
	 * @param array $rules BOGO rules.
	 * @return array Hash of fee data.
	 */
	public function calculate( $rules ) {
		$hash = array();
		foreach ( $rules as $rule ) {
			if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
				continue;
			}

			if ( ! isset( WC()->cart ) || null === WC()->cart->get_cart() ) {
				return $hash;
			}

			$min_qty    = isset( $rule['rule']['_minimum_purchase_count'] ) ? (int) $rule['rule']['_minimum_purchase_count'] : 99999999;
			$apply_once = isset( $rule['rule']['_per_cart_once'] ) && 'yes' === $rule['rule']['_per_cart_once'] ? true : false;

			$cart_item_count = 0;
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $cart_item['product_id'], $cart_item['variation_id'], $rule['filter'] ) ) {
					$cart_item_count = wholesalex()->cart_count( $cart_item['product_id'] );
					if ( $min_qty > 1 && $cart_item_count >= $min_qty ) {
						$free_quantity = (int) ( $cart_item_count / $min_qty ) * 1;
						if ( $apply_once ) {
							$free_quantity = 1;
						}
						$free_quantity = apply_filters( 'wholesalex_dr_buy_x_get_one_free_quantity', $free_quantity );

						$price = '';
						if ( $cart_item['data']->get_sale_price() ) {
							$price = $cart_item['data']->get_sale_price();
						} else {
							$price = $cart_item['data']->get_regular_price();
						}

						$cart = WC()->cart;
						if ( 'incl' === $cart->get_tax_price_display_mode() ) {
							$price = wc_get_price_excluding_tax(
								$cart_item['data'],
								array(
									'qty'   => $free_quantity,
									'price' => $price,
								)
							);
						} else {
							$price = wc_get_price_including_tax(
								$cart_item['data'],
								array(
									'qty'   => $free_quantity,
									'price' => $price,
								)
							);
						}
						$smart_tags         = array(
							'{product_title}' => $cart_item['data']->get_title(),
							'{x}'             => $min_qty,
							'{y}'             => $free_quantity,
						);
						$bogo_discount_text = wholesalex()->get_setting( '_settings_bogo_discount_text', '{product_title} (BOGO Discounts)' );
						if ( '' === $bogo_discount_text ) {
							$bogo_discount_text = apply_filters( 'wholesalex_dynamic_rules_bogo_discount_text', $cart_item['data']->get_title() . __( '(BOGO Discounts)', 'wholesalex' ) );
						}
						foreach ( $smart_tags as $key => $value ) {
							$bogo_discount_text = str_replace( $key, $value, $bogo_discount_text );
						}

						$hash[ wp_unique_id( md5( serialize( $rule['filter'] ) ) ) ] = array(
							'discount' => $price,
							'name'     => $bogo_discount_text,
						);
					}
				}
			}
		}
		return $hash;
	}

	/**
	 * Register badge hooks.
	 */
	public function register_badge_hooks() {
		add_filter( 'wopb_after_loop_image', array( $this, 'wopb_wholesalex_bogo_display_sale_badge' ), 10 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'wholesalex_bogo_display_sale_badge' ), 10 );
		add_action( 'woocommerce_before_single_product', array( $this, 'wholesalex_bogo_single_page_display_sale_badge' ), 10 );
		add_action( 'wp_head', array( $this, 'wholesalex_bogo_badge_add_custom_css' ) );
	}

	/**
	 * Add custom CSS for BOGO badges.
	 */
	public function wholesalex_bogo_badge_add_custom_css() {
		if ( is_shop() || is_product() ) {
			$bogo_css = $this->bogo_badge_css();
			if ( ! is_null( $bogo_css ) && is_string( $bogo_css ) && '' !== trim( $bogo_css ) ) {
				wp_add_inline_style( 'wholesalex', $bogo_css );
			}
		}
	}

	/**
	 * Badge CSS generation.
	 */
	public function bogo_badge_css() {
		if ( isset( $this->valid_dynamic_rules['buy_x_get_one'] ) ) {
			$this->wholesalex_bogo_display_markup_css_generate( $this->valid_dynamic_rules['buy_x_get_one'] );
		}
		if ( isset( $this->valid_dynamic_rules['buy_x_get_y'] ) ) {
			$this->wholesalex_bogo_display_markup_css_generate( $this->valid_dynamic_rules['buy_x_get_y'] );
		}
	}

	/**
	 * Generate Dynamic CSS For Badge.
	 *
	 * @param array $bogo_badge_dynamic_rule Badge rules.
	 */
	public function wholesalex_bogo_display_markup_css_generate( $bogo_badge_dynamic_rule ) {
		foreach ( $bogo_badge_dynamic_rule as $badge_dynamic_rule ) {
			$badge_roles            = $badge_dynamic_rule['rule'];
			$badge_label_text_color = isset( $badge_roles['_product_badge_text_color'] ) ? $badge_roles['_product_badge_text_color'] : '';
			$badge_style            = isset( $badge_roles['_product_badge_styles'] ) ? $badge_roles['_product_badge_styles'] : '';
			$badge_label_bg_color   = isset( $badge_roles['_product_badge_bg_color'] ) ? $badge_roles['_product_badge_bg_color'] : '';
			$badge_position         = isset( $badge_roles['_product_badge_position'] ) ? $badge_roles['_product_badge_position'] : 'right';

			?>
			<style>
				<?php
				if ( 'style_one' === $badge_style || '' === $badge_style || empty( $badge_style ) ) {
					?>
					.wholesalex-bogo-badge-<?php echo esc_attr( $badge_dynamic_rule['id'] ); ?>::before {
						content: "";
						position: absolute;
						left: -14px;
						transform: translateY(-1px);
						border-radius: 3px;
						border-top: 16px solid transparent;
						border-bottom: 16px solid transparent;
						border-right: 15px solid <?php echo esc_attr( $badge_label_bg_color ); ?>;
					}

					.wholesalex-bogo-badge-<?php echo esc_attr( $badge_dynamic_rule['id'] ); ?>::after {
						content: "";
						width: 7px;
						height: 7px;
						position: absolute;
						left: 0px;
						top: calc(100% / 2 - 4px);
						border-radius: 50%;
						background-color: <?php echo esc_attr( $badge_label_text_color ); ?>;
					}

					.wholesalex-bogo-badge-<?php echo esc_attr( $badge_dynamic_rule['id'] ); ?> {
						right: <?php echo 'left' === $badge_position ? 'auto' : '0px'; ?>;
						left: <?php echo 'left' === $badge_position ? '0px' : 'auto'; ?>;
						margin-left: <?php echo 'left' === $badge_position ? '14px' : '0'; ?>;
					}

					<?php
				} elseif ( 'style_two' === $badge_style ) {
					?>
					.wholesalex-bogo-badge-<?php echo esc_attr( $badge_dynamic_rule['id'] ); ?> {
						right: <?php echo 'left' === $badge_position ? 'auto' : '0px'; ?>;
						left: <?php echo 'left' === $badge_position ? '0px' : 'auto'; ?>;
						border-radius: 4px;
					}

					<?php
				} elseif ( 'style_three' === $badge_style ) {
					?>
					.wholesalex-bogo-badge-<?php echo esc_attr( $badge_dynamic_rule['id'] ); ?> {
						right: <?php echo 'left' === $badge_position ? 'auto' : '0px'; ?>;
						left: <?php echo 'left' === $badge_position ? '0px' : 'auto'; ?>;
						border-top-left-radius: 40px;
						border-bottom-left-radius: 5px;
					}

					<?php
				} elseif ( 'style_four' === $badge_style ) {
					?>
					.wholesalex-bogo-badge-<?php echo esc_attr( $badge_dynamic_rule['id'] ); ?> {
						right: <?php echo 'left' === $badge_position ? 'auto' : '0px'; ?>;
						left: <?php echo 'left' === $badge_position ? '0px' : 'auto'; ?>;
						border-top-left-radius: 26px;
						border-bottom-right-radius: 26px;
					}

					<?php
				} elseif ( 'style_five' === $badge_style ) {
					?>
					.wholesalex-bogo-badge-<?php echo esc_attr( $badge_dynamic_rule['id'] ); ?> {
						right: <?php echo 'left' === $badge_position ? 'auto' : '0px'; ?>;
						left: <?php echo 'left' === $badge_position ? '0px' : 'auto'; ?>;
						border-top-right-radius: 26px;
						border-bottom-left-radius: 26px;
					}

				<?php } ?>
			</style>
			<?php
		}
	}

	/**
	 * Generate HTML Markup For Showing Badge.
	 *
	 * @param array $bogo_badge_dynamic_rule The dynamic rule for the BOGO badge.
	 * @param bool  $is_single Whether it is a single product page.
	 * @return string|void
	 */
	public function wholesalex_bogo_display_markup( $bogo_badge_dynamic_rule, $is_single ) {
		global $product;
		if ( ! isset( $product ) && ! is_a( $product, 'WC_Product' ) ) {
			return;
		}
		ob_start();
		foreach ( $bogo_badge_dynamic_rule as $badge_dynamic_rule ) {
			$bogo_badge_type   = $badge_dynamic_rule['rule'];
			$bogo_badge_filter = $badge_dynamic_rule['filter'];
			$enable_bogo_badge = isset( $bogo_badge_type['_buy_x_get_product_badge_enable'] ) ? $bogo_badge_type['_buy_x_get_product_badge_enable'] : '';
			if ( 'yes' === $enable_bogo_badge ) {
				$badge_label            = $bogo_badge_type['_product_badge_label'];
				$badge_label_bg_color   = $bogo_badge_type['_product_badge_bg_color'];
				$badge_label_text_color = $bogo_badge_type['_product_badge_text_color'];
				if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(), $product->get_id(), $bogo_badge_filter ) ) {
					if ( isset( $badge_label ) ) :
						?>
						<div
							class="wholesalex-bogo-badge-container wholesalex-bogo-badge-<?php echo $is_single ? 'single' : 'shop'; ?>">
							<div class="wholesalex-bogo-badge wholesalex-bogo-badge-style-<?php echo esc_attr( $is_single ? 'single-product-' . $badge_dynamic_rule['id'] : $badge_dynamic_rule['id'] ); ?> wholesalex-bogo-badge-<?php echo esc_attr( $badge_dynamic_rule['id'] ); ?>"
								style="background-color: <?php echo esc_attr( ! empty( $badge_label_bg_color ) ? $badge_label_bg_color : '#5a40e8' ); ?>; color: <?php echo esc_attr( ! empty( $badge_label_text_color ) ? $badge_label_text_color : '#FFFFFF' ); ?>;">
								<?php echo esc_html( $badge_label ); ?>
							</div>
						</div>
					<?php endif; ?>
					<?php
				}
			}
		}
		return ob_get_clean();
	}

	/**
	 * Dynamic Bogo Badge For Shop Product.
	 */
	public function wholesalex_bogo_display_sale_badge() {
		if ( wholesalex()->get_setting( 'bogo_discount_bogo_badge_enable', 'yes' ) === 'yes' && isset( $this->valid_dynamic_rules['buy_x_get_one'] ) && ! empty( $this->valid_dynamic_rules['buy_x_get_one'] ) ) {
			echo $this->wholesalex_bogo_display_markup( $this->valid_dynamic_rules['buy_x_get_one'], false ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( wholesalex()->is_pro_active() && wholesalex()->get_setting( '_settings_show_bxgy_free_products_badge', 'yes' ) === 'yes' && isset( $this->valid_dynamic_rules['buy_x_get_y'] ) && ! empty( $this->valid_dynamic_rules['buy_x_get_y'] ) ) {
			echo $this->wholesalex_bogo_display_markup( $this->valid_dynamic_rules['buy_x_get_y'], false ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Dynamic Bogo Badge For Shop Product -- WowStore Compatibility.
	 *
	 * @return string|void
	 */
	public function wopb_wholesalex_bogo_display_sale_badge() {
		if ( wholesalex()->get_setting( 'bogo_discount_bogo_badge_enable', 'yes' ) === 'yes' && isset( $this->valid_dynamic_rules['buy_x_get_one'] ) && ! empty( $this->valid_dynamic_rules['buy_x_get_one'] ) ) {
			return $this->wholesalex_bogo_display_markup( $this->valid_dynamic_rules['buy_x_get_one'], false );
		}
		if ( wholesalex()->is_pro_active() && wholesalex()->get_setting( '_settings_show_bxgy_free_products_badge', 'yes' ) === 'yes' && isset( $this->valid_dynamic_rules['buy_x_get_y'] ) && ! empty( $this->valid_dynamic_rules['buy_x_get_y'] ) ) {
			return $this->wholesalex_bogo_display_markup( $this->valid_dynamic_rules['buy_x_get_y'], false );
		}
	}

	/**
	 * Dynamic Bogo Badge For Single Product.
	 */
	public function wholesalex_bogo_single_page_display_sale_badge() {
		$localized_content = array();
		if ( wholesalex()->get_setting( 'bogo_discount_bogo_badge_enable', 'yes' ) === 'yes' && isset( $this->valid_dynamic_rules['buy_x_get_one'] ) && ! empty( $this->valid_dynamic_rules['buy_x_get_one'] ) ) {
			$localized_content['buy_x_get_one'] = $this->wholesalex_bogo_display_markup( $this->valid_dynamic_rules['buy_x_get_one'], true );
		}
		if ( wholesalex()->is_pro_active() && wholesalex()->get_setting( '_settings_show_bxgy_free_products_badge', 'yes' ) === 'yes' && isset( $this->valid_dynamic_rules['buy_x_get_y'] ) && ! empty( $this->valid_dynamic_rules['buy_x_get_y'] ) ) {
			$localized_content['buy_x_get_y'] = $this->wholesalex_bogo_display_markup( $this->valid_dynamic_rules['buy_x_get_y'], true );
		}
		wp_localize_script( 'wholesalex', 'wholesalex_bogo_single', array( 'content' => $localized_content ) );
	}
}
