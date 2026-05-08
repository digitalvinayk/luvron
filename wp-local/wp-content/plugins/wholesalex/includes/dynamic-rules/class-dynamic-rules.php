<?php
/**
 * WholesaleX Dynamic Rules - Main Orchestrator
 *
 * Contains fields, price engine, dispatch logic, and common functions.
 * Delegates to rule handlers, condition engine, REST API, and data provider.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Shipping_Zones;

/**
 * Dynamic_Rules - Main orchestrator class
 */
class Dynamic_Rules {

	// ─── Properties ──────────────────────────────────────────────

	// Core pricing and discount properties
	public $discount_src                     = '';
	public $active_tier_id                   = 0;
	public $first_sale_price_generator       = '';
	public $is_wholesalex_base_price_applied = false;
	public $price                            = '';

	// Internal state management
	private $valid_dynamic_rules       = array();
	private $active_tiers              = array();
	private $rule_data                 = array();
	private $current_shipping_zone     = '';
	private $cached_shipping_method_id = array();

	public static $cu_order_counts           = 0;
	public static $cu_total_spent            = 0;
	public static $total_cart_counts         = '';
	public static $total_unique_item_on_cart = '';

	// ─── Rule Handler Instances (Active Only) ────────────────────

	// Core rule handlers actually used by the orchestrator
	private $rule_tax;
	private $rule_shipping;
	private $rule_payment_gateway;
	private $rule_buy_x_get_one;
	private $rule_cart_discount;
	private $rule_payment_discount;
	private $rule_min_order_qty;

	// ─── Constructor ─────────────────────────────────────────────

	public function __construct() {
		$this->instantiate_handlers();

		// REST API is self-contained in Dynamic_Rules_Rest_Api class.
		// No hook registration needed here.

		// Order / Cart session hooks.
		// Classic checkout (shortcode-based) fires woocommerce_checkout_create_order.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'add_custom_meta_on_wholesale_order' ), 10 );
		// WooCommerce Blocks / Store API checkout never fires woocommerce_checkout_create_order;
		// it uses woocommerce_store_api_checkout_update_order_meta instead.
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'add_custom_meta_on_wholesale_order' ), 10 );
		add_action( 'woocommerce_update_cart_action_cart_updated', array( $this, 'update_discounted_product' ) );

		// PPOM compatibility.
		add_filter( 'ppom_product_price', array( $this, 'product_price' ), 10, 2 );
		add_filter( 'ppom_product_price_on_cart', array( $this, 'set_price_on_ppom' ), 10, 2 );

		// ProductX compatibility.
		add_filter( 'wopb_query_args', array( $this, 'modify_wopb_query_args' ) );

		// Main dispatch.
		add_action( 'wp_loaded', array( $this, 'get_valid_dynamic_rules' ) );

		// WooCommerce Blocks.
		add_action( 'woocommerce_blocks_loaded', array( $this, 'action_after_woo_block_loaded' ) );

		// Legacy Pro compatibility.
		add_action( 'plugins_loaded', array( $this, 'dynamic_rules_handler' ) );

		// BOGO badges - delegate to Rule_Buy_X_Get_One.
		add_filter( 'wopb_after_loop_image', array( $this->rule_buy_x_get_one, 'wopb_wholesalex_bogo_display_sale_badge' ), 10 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this->rule_buy_x_get_one, 'wholesalex_bogo_display_sale_badge' ), 10 );
		add_action( 'woocommerce_before_single_product', array( $this->rule_buy_x_get_one, 'wholesalex_bogo_single_page_display_sale_badge' ), 10 );
		add_action( 'wp_head', array( $this->rule_buy_x_get_one, 'wholesalex_bogo_badge_add_custom_css' ) );

		// Price table JS.
		add_action( 'wp_enqueue_scripts', array( $this, 'wholesalex_enqueue_price_table_js' ) );
	}

	/**
	 * Instantiate only the rule handlers that are actively used by the orchestrator.
	 * Unused handlers are removed to optimize memory and performance.
	 */
	private function instantiate_handlers() {
		// Core rule handlers used in main dispatch
		$this->rule_tax             = new Rule_Tax();
		$this->rule_shipping        = new Rule_Shipping();
		$this->rule_payment_gateway = new Rule_Payment_Gateway();
		$this->rule_min_order_qty   = new Rule_Min_Order_Qty();

		// Cart and discount handlers used in fee calculation.
		$this->rule_buy_x_get_one    = new Rule_Buy_X_Get_One();
		$this->rule_cart_discount    = new Rule_Cart_Discount();
		$this->rule_payment_discount = new Rule_Payment_Discount();

		// Note: REST API is self-contained and instantiates itself.
		// Note: Unused rule handlers (product_discount, max_order_qty, etc.)
		// are removed to optimize performance and reduce memory usage.
	}

	// ─── Pass-through to Condition Engine ─────────────────────────

	public static function check_rule_conditions( $conditions, $rule_filter = array() ) {
		return Dynamic_Rules_Condition_Engine::check_rule_conditions( $conditions, $rule_filter );
	}

	public static function is_eligible_for_rule( $product_id, $variation_id, $filter ) {
		return Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $filter );
	}

	public static function get_multiselect_values( $data, $type = 'value' ) {
		return Dynamic_Rules_Condition_Engine::get_multiselect_values( $data, $type );
	}

	public static function get_product_attributes( $product_id ) {
		return Dynamic_Rules_Condition_Engine::get_product_attributes( $product_id );
	}

	public function get_filtered_rules( $discount ) {
		return Dynamic_Rules_Condition_Engine::get_filtered_rules( $discount );
	}

	public function compare_by_priority( $a, $b ) {
		return Dynamic_Rules_Condition_Engine::compare_by_priority( $a, $b );
	}

	public function compare_by_priority_reverse( $a, $b ) {
		return Dynamic_Rules_Condition_Engine::compare_by_priority_reverse( $a, $b );
	}

	public static function has_limit( $__limits, $rule_id = 0 ) {
		return Dynamic_Rules_Condition_Engine::has_limit( $__limits, $rule_id );
	}

	public static function is_conditions_fullfiled( $conditions, $rule_filter = array() ) {
		return Dynamic_Rules_Condition_Engine::is_conditions_fullfiled( $conditions, $rule_filter );
	}

	public static function is_condition_passed( $condition, $rule_filter = array() ) {
		return Dynamic_Rules_Condition_Engine::is_condition_passed( $condition, $rule_filter );
	}

	public static function is_user_order_count_purchase_amount_condition_passed( $conditions ) {
		return Dynamic_Rules_Condition_Engine::is_user_order_count_purchase_amount_condition_passed( $conditions );
	}

	public function restore_smart_tags( $smart_tags, $new_string ) {
		return Dynamic_Rules_Condition_Engine::restore_smart_tags( $smart_tags, $new_string );
	}

	public function filter_empty_items( $item ) {
		return Dynamic_Rules_Condition_Engine::filter_empty_items( $item );
	}

	// ─── Pass-through to Data Provider ───────────────────────────

	public static function get_tax_classes() {
		return Dynamic_Rules_Data_Provider::get_tax_classes();
	}

	public static function get_shipping_zones() {
		return Dynamic_Rules_Data_Provider::get_shipping_zones();
	}

	// ─── Static API Methods ──────────────────────────────────────

	public static function dynamic_rules_get() {
		$__dynamic_rules = array_values( wholesalex()->get_dynamic_rules() );

		// Use is_admin() for true admin page loads, or current_user_can() for
		// REST API requests where is_admin() returns false even for admins.
		if ( is_admin() || current_user_can( 'manage_options' ) ) {
			$__dynamic_rules = wholesalex()->get_dynamic_rules();
		} else {
			$__dynamic_rules = wholesalex()->get_dynamic_rules_by_user_id( get_current_user_id() );
		}
		$__dynamic_rules = apply_filters( 'wholesalex_get_all_dynamic_rules', array_values( $__dynamic_rules ) );
		if ( empty( $__dynamic_rules ) ) {
			$__dynamic_rules = array();
		}
		return $__dynamic_rules;
	}

	// ─── Simple Utility Methods ──────────────────────────────────

	public function wholesalex_enqueue_price_table_js() {
		wp_enqueue_script( 'wholesalex_price_table' );
	}

	public function get_actual_discount_price( $product, $regular_price, $sale_price ) {
		$is_regular_price = wholesalex()->get_setting( '_is_sale_or_regular_Price', 'is_regular_price' );
		if ( 'is_regular_price' === $is_regular_price ) {
			return $regular_price;
		} else {
			$actual_sale_price = $sale_price ? $sale_price : get_post_meta( $product->get_id(), '_sale_price', true );
			if ( ! isset( $actual_sale_price ) || empty( $actual_sale_price ) || '' === $actual_sale_price ) {
				$actual_sale_price = $regular_price;
			}
			return $actual_sale_price;
		}
	}

	// ─── Legacy Pro Compatibility ────────────────────────────────

	public function dynamic_rules_handler() {
		if ( ! ( function_exists( 'wholesalex_pro' ) && version_compare( WHOLESALEX_PRO_VER, '1.3.1', '<=' ) ) ) {
			return;
		}
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		$__user_id          = apply_filters( 'wholesalex_dynamic_rule_user_id', get_current_user_id() );
		$__priorities       = wholesalex()->get_quantity_based_discount_priorities();
		$this->discount_src = $__priorities[0];
		$__priorities       = array_reverse( $__priorities );
		foreach ( $__priorities as $key => $priority ) {
			if ( 0 === $key ) {
				$this->first_sale_price_generator = $priority . '_discounts';
			}
			delete_transient( 'wholesalex_pricing_tiers_' . $priority . '_' . $__user_id );
			$discount_status = apply_filters( 'wholesalex_' . $priority . '_discounts_enabled', true );
			if ( $discount_status ) {
				$this->discounts_init( $priority . '_discounts' );
			}
		}
	}

	private function discounts_init( $sale_price_generator ) {
		add_filter( 'woocommerce_product_get_sale_price', array( $this, $sale_price_generator ), 9, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, $sale_price_generator ), 9, 2 );
		add_filter( 'woocommerce_variation_prices_sale_price', array( $this, $sale_price_generator ), 9, 2 );
		add_filter( 'woocommerce_variation_prices_price', array( $this, $sale_price_generator ), 9, 2 );
	}

	public function single_product_discounts( $sale_price, $product ) {
		$__product_id = $product->get_id();
		$this->set_initial_sale_price_to_session( __FUNCTION__, $__product_id, $sale_price );
		$__single_product_show_tier = wholesalex()->get_single_product_setting( $product->get_ID(), '_settings_show_tierd_pricing_table' );
		if ( 'yes' !== $__single_product_show_tier ) {
			remove_filter( 'wholesalex_single_product_quantity_based_table', array( $this, 'quantity_based_pricing_table' ), 10, 2 );
			remove_filter( 'wholesalex_variation_product_quantity_based_table', array( $this, 'quantity_based_pricing_table' ), 10, 2 );
		}
		$__discounts_result = apply_filters(
			'wholesalex_single_product_discount_action',
			array(
				'sale_price' => $sale_price,
				'product'    => $product,
			)
		);
		$sale_price         = $__discounts_result['sale_price'];
		if ( isset( $__discounts_result['discount_src'] ) ) {
			$this->discount_src = $__discounts_result['discount_src'];
		}
		if ( isset( $__discounts_result['active_tier_id'] ) ) {
			$this->active_tier_id = $__discounts_result['active_tier_id'];
		}
		if ( empty( $sale_price ) ) {
			return;
		} else {
			$this->set_discounted_product( $__product_id );
			$this->price = $sale_price;
			return $sale_price;
		}
	}

	public function profile_discounts( $sale_price, $product ) {
		$__user_id    = apply_filters( 'wholesalex_dynamic_rule_user_id', get_current_user_id() );
		$__product_id = $product->get_id();
		$this->set_initial_sale_price_to_session( __FUNCTION__, $__product_id, $sale_price );
		$plugins_status = wholesalex()->get_setting( '_settings_status', 'b2b' );
		if ( 'b2b' === $plugins_status ) {
			if ( ! ( 'active' === wholesalex()->get_user_status( $__user_id ) ) ) {
				if ( empty( $sale_price ) ) {
					return;
				} else {
					$this->price = $sale_price;
					return $sale_price;
				}
			}
		}
		$__discounts_result = apply_filters(
			'wholesalex_profile_discount_action',
			array(
				'sale_price' => $sale_price,
				'product'    => $product,
			)
		);
		$sale_price         = $__discounts_result['sale_price'];
		if ( isset( $__discounts_result['discount_src'] ) ) {
			$this->discount_src = $__discounts_result['discount_src'];
		}
		if ( isset( $__discounts_result['active_tier_id'] ) ) {
			$this->active_tier_id = $__discounts_result['active_tier_id'];
		}
		$__profile_settings = get_user_meta( $__user_id, '__wholesalex_profile_settings', true );
		if ( isset( $__profile_settings['_wholesalex_profile_override_tax_exemption'] ) && 'yes' === $__profile_settings['_wholesalex_profile_override_tax_exemption'] ) {
			set_transient( 'wholesalex_tax_exemption_' . $__user_id, true );
		}
		if ( isset( $__profile_settings['_wholesalex_profile_override_shipping_method'] ) && 'yes' === $__profile_settings['_wholesalex_profile_override_shipping_method'] ) {
			if ( isset( $__profile_settings['_wholesalex_profile_shipping_method_type'] ) ) {
				switch ( $__profile_settings['_wholesalex_profile_shipping_method_type'] ) {
					case 'force_free_shipping':
						set_transient( 'wholesalex_force_free_shipping_' . $__user_id, true );
						break;
					case 'specific_shipping_methods':
						if ( ! isset( $__profile_settings['_wholesalex_profile_shipping_zone'] ) || ! isset( $__profile_settings['_wholesalex_profile_shipping_zone_methods'] ) ) {
							break;
						}
						delete_transient( 'wholesalex_profile_shipping_methods_' . $__user_id );
						delete_transient( 'wholesalex_shipping_methods_' . $__user_id );
						$__zone_id               = $__profile_settings['_wholesalex_profile_shipping_zone'];
						$__shipping_zone_methods = $__profile_settings['_wholesalex_profile_shipping_zone_methods'];
						$__available_methods     = array();
						if ( ! empty( $__shipping_zone_methods ) && is_array( $__shipping_zone_methods ) ) {
							foreach ( $__shipping_zone_methods as $method ) {
								$__available_methods[ $method['value'] ] = true;
							}
						}
						$__shipping_method_transient = get_transient( 'wholesalex_profile_shipping_methods_' . $__user_id );
						if ( ! $__shipping_method_transient && ! empty( $__zone_id ) ) {
							$__temp_shipping_data                                = array();
							$__temp_shipping_data[ $__zone_id ][ $__product_id ] = $__available_methods;
							set_transient( 'wholesalex_profile_shipping_methods_' . $__user_id, $__temp_shipping_data );
						}
						break;
					default:
						break;
				}
			}
		}
		if ( empty( $sale_price ) ) {
			return;
		} else {
			$this->set_discounted_product( $__product_id );
			$this->price = $sale_price;
			return $sale_price;
		}
	}

	public function category_discounts( $sale_price, $product ) {
		$__product_id = $product->get_id();
		$this->set_initial_sale_price_to_session( __FUNCTION__, $__product_id, $sale_price );
		$__discounts_result = apply_filters(
			'wholesalex_category_discount_action',
			array(
				'sale_price' => $sale_price,
				'product'    => $product,
			)
		);
		$sale_price         = $__discounts_result['sale_price'];
		if ( isset( $__discounts_result['discount_src'] ) ) {
			$this->discount_src = $__discounts_result['discount_src'];
		}
		if ( isset( $__discounts_result['active_tier_id'] ) ) {
			$this->active_tier_id = $__discounts_result['active_tier_id'];
		}
		if ( empty( $sale_price ) ) {
			return;
		} else {
			$this->set_discounted_product( $__product_id );
			$this->price = $sale_price;
			return $sale_price;
		}
	}

	// ─── Session / Order Helpers ─────────────────────────────────

	public function set_discounted_product( $product_id ) {
		if ( is_admin() || null === WC()->session ) {
			return;
		}
		$__discounted_product = null !== WC()->session ? WC()->session->get( '__wholesalex_discounted_products' ) : '';
		if ( ! ( isset( $__discounted_product ) && is_array( $__discounted_product ) ) ) {
			$__discounted_product = array();
		}
		$__discounted_product[ $product_id ] = true;
		WC()->session->set( '__wholesalex_discounted_products', $__discounted_product );
	}

	public function add_custom_meta_on_wholesale_order( $order ) {
		if ( is_admin() || null === WC()->session ) {
			return;
		}

		// order_type is resolved from the current user's role and does not require the session.
		$__user_role = wholesalex()->get_current_user_role();
		$order_type  = in_array( $__user_role, array( '', 'wholesalex_guest', 'wholesalex_b2c_users' ), true ) ? 'b2c' : 'b2b';
		$order->update_meta_data( '__wholesalex_order_type', $order_type );

		// Everything below this point requires an active WC session (discount tracking).
		if ( null === WC()->session ) {
			return;
		}

		$__discounted_product = WC()->session->get( '__wholesalex_discounted_products' );
		$__dynamic_rule_id    = WC()->session->get( '__wholesalex_used_dynamic_rule' );
		if ( ! empty( $__dynamic_rule_id ) ) {
			$order->update_meta_data( '__wholesalex_dynamic_rule_ids', $__dynamic_rule_id );
			if ( is_array( $__dynamic_rule_id ) ) {
				foreach ( $__dynamic_rule_id as $key => $value ) {
					if ( 1 === $value ) {
						$__rule                          = wholesalex()->get_dynamic_rules( $key );
						$__rule['limit']['usages_count'] = isset( $__rule['limit']['usages_count'] ) ? (int) $__rule['limit']['usages_count'] + 1 : 1;
						wholesalex()->set_dynamic_rules( $key, $__rule );
					}
				}
			}
		}
		$__ordered_discounted_product = array();
		$items                        = $order->get_items();
		foreach ( $items as $item ) {
			$product_id           = $item->get_product_id();
			$product_variation_id = $item->get_variation_id();
			if ( isset( $__discounted_product[ $product_id ] ) ) {
				$__ordered_discounted_product[] = $product_id;
			}
			if ( isset( $__discounted_product[ $product_variation_id ] ) ) {
				$__ordered_discounted_product[] = $product_variation_id;
			}
		}
		if ( ! empty( $__ordered_discounted_product ) ) {
			$order->update_meta_data( '__wholesalex_discounted_products', array_unique( $__ordered_discounted_product ) );
		}
		$__user_role = wholesalex()->get_current_user_role();
		$order_type  = in_array( $__user_role, array( '', 'wholesalex_guest', 'wholesalex_b2c_users' ), true ) ? 'b2c' : 'b2b';
		$order->update_meta_data( '__wholesalex_order_type', $order_type );
		WC()->session->set( '__wholesalex_discounted_products', array() );
	}

	public function update_discounted_product( $cart_updated ) {
		if ( is_admin() || null === WC()->session ) {
			return $cart_updated;
		}
		WC()->session->set( '__wholesalex_discounted_products', array() );
		WC()->session->set( '__wholesalex_used_dynamic_rule', array() );
		return $cart_updated;
	}

	// ─── Currency Compatibility ──────────────────────────────────

	public function price_after_currency_changed( $price ) {
		$price = floatval( $price );
		if ( defined( 'WOPB_VER' ) && defined( 'WOPB_PRO_VER' ) && class_exists( 'WOPB_PRO\Currency_Switcher_Action' ) ) {
			$current_currency_code = wopb_function()->get_setting( 'wopb_current_currency' );
			$default_currency      = wopb_function()->get_setting( 'wopb_default_currency' );
			$current_currency      = \WOPB_PRO\Currency_Switcher_Action::get_currency( $current_currency_code );
			if ( ! $current_currency ) {
				$current_currency = $default_currency;
			}
			if ( $current_currency_code !== $default_currency ) {
				$wopb_current_currency_rate = floatval( ( isset( $current_currency['wopb_currency_rate'] ) && $current_currency['wopb_currency_rate'] > 0 && ! ( $current_currency['wopb_currency_rate'] == '' ) ) ? $current_currency['wopb_currency_rate'] : 1 );
				$wopb_current_exchange_fee  = floatval( ( isset( $current_currency['wopb_currency_exchange_fee'] ) && $current_currency['wopb_currency_exchange_fee'] >= 0 && ! ( $current_currency['wopb_currency_exchange_fee'] == '' ) ) ? $current_currency['wopb_currency_exchange_fee'] : 0 );
				$total_rate                 = ( $wopb_current_currency_rate + $wopb_current_exchange_fee );
				return $price / $total_rate;
			}
		}
		if ( defined( 'WOOMULTI_CURRENCY_F_VERSION' ) && function_exists( 'wmc_revert_price' ) ) {
			$curcy = \WOOMULTI_CURRENCY_F_Data::get_ins();
			if ( $curcy->get_enable() ) {
				$price = wmc_revert_price( $price );
			}
		}
		if ( defined( 'YAY_CURRENCY_VERSION' ) && function_exists( 'Yay_Currency\\plugin_init' ) ) {
			if ( method_exists( '\Yay_Currency\Helpers\Helper', 'default_currency_code' ) && method_exists( '\Yay_Currency\Helpers\YayCurrencyHelper', 'detect_current_currency' ) ) {
				$applied_currency    = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
				$is_default_currency = \Yay_Currency\Helpers\Helper::default_currency_code() == $applied_currency['currency'];
				if ( ! $is_default_currency ) {
					$total_rate = \Yay_Currency\Helpers\YayCurrencyHelper::get_rate_fee( $applied_currency );
					return ( floatval( $price / $total_rate ) );
				}
			}
		}
		if ( defined( 'WOOCS_VERSION' ) && class_exists( 'WOOCS' ) ) {
			global $WOOCS;
			if ( isset( $WOOCS ) && $WOOCS->is_multiple_allowed ) {
				$price = $WOOCS->woocs_back_convert_price( $price );
			}
		}
		return $price;
	}

	// ─── Plugin Compatibility Helpers ────────────────────────────

	public function is_enable_subscriptions_product_woo( $product ) {
		if ( in_array( 'woocommerce-all-products-for-subscriptions/woocommerce-all-products-for-subscriptions.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			if ( class_exists( 'WCS_ATT_Product_Schemes' ) ) {
				if ( \WCS_ATT_Product_Schemes::get_subscription_schemes( $product ) ) {
					return true;
				} else {
					return false;
				}
			}
		}
	}

	public function set_initial_sale_price_to_session( $__function_name, $id, $sale_price ) {
		if ( isset( WC()->session ) && ! is_admin() ) {
			if ( $__function_name === $this->first_sale_price_generator ) {
				$__wholesale_products        = WC()->session->get( '__wholesalex_wholesale_products' );
				$__wholesale_products[ $id ] = $this->price_after_currency_changed( $sale_price );
				WC()->session->set( '__wholesalex_wholesale_products', $__wholesale_products );
			}
		}
	}

	public function product_price( $price, $product ) {
		if ( ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) ) {
			if ( empty( $product->get_sale_price() ) ) {
				$price = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
			} else {
				$price = wc_get_price_to_display( $product, array( 'price' => $product->get_sale_price() ) );
			}
		}
		return $price;
	}

	public function set_price_on_ppom( $price, $cart_item ) {
		$__product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$__product    = wc_get_product( $__product_id );
		return $__product->get_sale_price();
	}

	public function modify_wopb_query_args( $query_args ) {
		$query_args['post__not_in'] = isset( $query_args['post__not_in'] ) ? array_merge( $query_args['post__not_in'], (array) wholesalex()->hidden_product_ids() ) : (array) wholesalex()->hidden_product_ids();
		return $query_args;
	}

	public function set_price_on_extra_product_addon_plugin( $data ) {
		if ( '' !== $this->price ) {
			$data['Product']['Price'] = $this->price;
		}
		return $data;
	}

	private function is_product_in_bundle( $product_id ) {
		if ( ! is_plugin_active( 'yith-woocommerce-product-bundles/init.php' ) ) {
			return false;
		}
		$bundle_data = get_post_meta( $product_id, '_yith_wcpb_bundle_data', true );
		if ( ! empty( $bundle_data ) ) {
			return true;
		}
		$args  = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_yith_wcpb_bundle_data',
					'value'   => '"' . $product_id . '"',
					'compare' => 'LIKE',
				),
			),
		);
		$query = new \WP_Query( $args );
		if ( $query->have_posts() ) {
			return true;
		}
		return false;
	}

	public function calculate_actual_sale_price( $product_id, $is_variable = false ) {
		$__current_role_id = wholesalex()->get_current_user_role();
		$price             = floatval( get_post_meta( $product_id, '_price', true ) );
		$regular_price     = floatval( get_post_meta( $product_id, '_regular_price', true ) );
		$sale_price        = floatval( get_post_meta( $product_id, '_sale_price', true ) );
		if ( $is_variable ) {
			return $price;
		}
		if ( isset( $__current_role_id ) ) {
			$_user_role_base_price = floatval( get_post_meta( $product_id, $__current_role_id . '_base_price', true ) );
			$_user_role_sale_price = floatval( get_post_meta( $product_id, $__current_role_id . '_sale_price', true ) );
			if ( ! empty( $_user_role_sale_price ) ) {
				return $_user_role_sale_price;
			} elseif ( ! empty( $_user_role_base_price ) ) {
				return $_user_role_base_price;
			}
		}
		$price = $sale_price && 0.0 !== $sale_price ? $sale_price : $regular_price;
		return $price;
	}

	public function is_wholesalex_topup_product( $product_id ) {
		$_topup_product_id = get_option( '__wholesalex_wallet_topup_product' );
		if ( $_topup_product_id && $_topup_product_id == $product_id ) {
			return true;
		}
		return false;
	}

	public function get_converted_sale_price_from_aelia( $product_id, $currency = null ) {
		if ( class_exists( 'WC_Aelia_CurrencyPrices_Manager' ) && method_exists( 'WC_Aelia_CurrencyPrices_Manager', 'Instance' ) && function_exists( 'aelia_get_object_aux_data' ) ) {
			$product = wc_get_product( $product_id );
			if ( ! $product instanceof \WC_Product ) {
				return false;
			}
			if ( ! $currency ) {
				$currency = get_woocommerce_currency();
			}
			$converted_product    = \WC_Aelia_CurrencyPrices_Manager::Instance()->convert_product_prices( $product, $currency );
			$converted_sale_price = aelia_get_object_aux_data( $converted_product, 'sale_price' );
			return ( $converted_sale_price !== null ) ? $converted_sale_price : $product->get_sale_price();
		}
		return false;
	}

	public function make_product_non_purchasable_and_remove_add_to_cart( $product = false ) {
		add_filter( 'woocommerce_is_purchasable', '__return_false' );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		if ( $product ) {
			remove_all_actions( 'woocommerce_' . $product->get_type() . '_add_to_cart' );
		}
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	}

	// ─── WooCommerce Blocks ──────────────────────────────────────

	public function action_after_woo_block_loaded() {
		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'wholesalex-payment-discount',
				'callback'  => function ( $data ) {
					if ( isset( $data['selected_gateway'] ) ) {
						WC()->session->set( 'chosen_payment_method', $data['selected_gateway'] );
					}
				},
			)
		);
	}

	// ─── Price Calculation Methods ───────────────────────────────

	public function get_role_base_sale_price( $product, $user_id = '' ) {
		return get_post_meta( $product->get_id(), $user_id . '_sale_price', true );
	}

	public function get_role_regular_price( $product, $user_id = '' ) {
		return get_post_meta( $product->get_id(), $user_id . '_base_price', true );
	}

	public function calculate_regular_price( $regular_price, $product, $data ) {
		if ( isset( $data['role_id'] ) && ! empty( $data['role_id'] ) && $data['eligible'] ) {
			$rrp           = get_post_meta( $product->get_id(), $data['role_id'] . '_base_price', true );
			$regular_price = $rrp ? floatval( $rrp ) : $regular_price;
			if ( $rrp ) {
				wholesalex()->set_wholesalex_regular_prices( $product->get_id(), $rrp );
			}
		}
		$is_regular_price = wholesalex()->get_setting( '_is_sale_or_regular_Price', 'is_regular_price' );
		if ( 'is_sale_price' === $is_regular_price ) {
			$role_sale_price    = floatval( $this->get_role_base_sale_price( $product, $data['role_id'] ) );
			$role_regular_price = get_post_meta( $product->get_id(), $data['role_id'] . '_base_price', true );
			if ( 0 == $role_sale_price && ! empty( $role_regular_price ) ) {
				return $regular_price;
			}
			$db_sale_price          = floatval( get_post_meta( $product->get_id(), '_sale_price', true ) );
			$get_session_sale_price = 0;
			if ( WC()->session && null !== WC()->session->get( 'wsx_sale_price' ) ) {
				$get_session_sale_price = floatval( WC()->session->get( 'wsx_sale_price' ) );
			}
			if ( $get_session_sale_price ) {
				if ( $role_sale_price === $get_session_sale_price ) {
					return $regular_price;
				} elseif ( $get_session_sale_price === $db_sale_price ) {
					return $regular_price;
				} else {
					return $this->get_actual_discount_price( $product, $regular_price, $role_sale_price );
				}
			} else {
				return $this->get_actual_discount_price( $product, $regular_price, $role_sale_price );
			}
		}
		return $regular_price;
	}

	public function calculate_sale_price( $sale_price, $product, $data ) {
		$parent_id     = $product->get_parent_id();
		$product_id    = $product->get_id();
		$regular_price = $product->get_regular_price();
		$sale_from     = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
		$current_time  = current_time( 'timestamp' );

		$current_role       = wholesalex()->get_current_user_role();
		$role_sale_price    = floatval( $this->get_role_base_sale_price( $product, $current_role ) );
		$role_regular_price = floatval( $this->get_role_regular_price( $product, $current_role ) );
		$has_rolewise_price = $role_sale_price || $role_regular_price;

		if ( $sale_from > $current_time && ! $has_rolewise_price ) {
			return $regular_price;
		}

		if ( ! $role_sale_price && $role_regular_price ) {
			$sale_price = false;
		}
		$base_price = $product->get_regular_price();
		if ( $has_rolewise_price ) {
			$base_price = $this->get_actual_discount_price( $product, $role_regular_price, $role_sale_price ? $role_sale_price : $role_regular_price );
		}

		$is_regular_price = wholesalex()->get_setting( '_is_sale_or_regular_Price', 'is_regular_price' );
		$base_price       = $this->price_after_currency_changed( $base_price );
		$rrs              = false;
		$previous_sp      = $sale_price;

		if ( $has_rolewise_price || 'is_regular_price' === $is_regular_price ) {
			$sale_price = false;
		}

		$used_rule_id = '';

		if ( $data['eligible'] ) {
			$priority         = wholesalex()->get_quantity_based_discount_priorities();
			$flipped_priority = array_flip( wholesalex()->get_quantity_based_discount_priorities() );
			if ( isset( $data['role_id'] ) && ! empty( $data['role_id'] ) ) {
				if ( 'is_regular_price' === $is_regular_price ) {
					if ( $role_sale_price ) {
						$rrs = $role_sale_price;
					} else {
						$rrs = get_post_meta( $product_id, $data['role_id'] . '_base_price', true );
					}
				} else {
					$rrs = get_post_meta( $product_id, $data['role_id'] . '_sale_price', true );
				}
			} else {
				$rrs = false;
			}

			$applied_discount_src = '';

			if ( $flipped_priority['dynamic_rule'] < $flipped_priority['single_product'] ) {
				if ( ! empty( $data['product_discount'] ) ) {
					foreach ( $data['product_discount'] as $pd ) {
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $product_id, $product_id, $pd['filter'] ) ) {
							if ( ! empty( $pd['conditions']['tiers'] ) ) {
								wholesalex()->set_rule_data(
									$pd['id'],
									$product_id,
									'product_discount',
									array(
										'value'        => $pd['rule']['_discount_amount'],
										'type'         => $pd['rule']['_discount_type'],
										'conditions'   => $pd['conditions'],
										'who_priority' => $pd['who_priority'],
										'applied_on_priority' => $pd['applied_on_priority'],
										'end_date'     => $pd['end_date'],
									)
								);
							}
							if ( isset( $pd['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $pd['conditions'], $pd['filter'] ) ) {
								continue;
							}
							$used_rule_id         = $pd['id'];
							$sale_price           = wholesalex()->calculate_sale_price( $pd['rule'], $base_price );
							$applied_discount_src = 'product_discount';
						}
					}
				}
				if ( '' === $applied_discount_src && $rrs ) {
					$sale_price = floatval( $rrs );
				}
			} elseif ( ! $rrs && ! empty( $data['product_discount'] ) ) {
				foreach ( $data['product_discount'] as $pd ) {
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $product_id, $product_id, $pd['filter'] ) ) {
						if ( ! empty( $pd['conditions']['tiers'] ) ) {
							wholesalex()->set_rule_data(
								$pd['id'],
								$product_id,
								'product_discount',
								array(
									'value'               => $pd['rule']['_discount_amount'],
									'type'                => $pd['rule']['_discount_type'],
									'conditions'          => $pd['conditions'],
									'who_priority'        => $pd['who_priority'],
									'applied_on_priority' => $pd['applied_on_priority'],
									'end_date'            => $pd['end_date'],
								)
							);
						}
						if ( isset( $pd['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $pd['conditions'], $pd['filter'] ) ) {
							continue;
						}
						$used_rule_id         = $pd['id'];
						$sale_price           = wholesalex()->calculate_sale_price( $pd['rule'], $base_price );
						$applied_discount_src = 'product_discount';
					}
				}
			} elseif ( $rrs ) {
				$sale_price = floatval( $rrs );
			}

			$__is_parent_rule_apply = wholesalex()->get_setting( '_settings_tier_table_discount_apply_on_variable', 'no' );
			if ( $product->is_type( 'variation' ) && 'yes' === $__is_parent_rule_apply ) {
				$cart_qty            = wholesalex()->cart_count( $parent_id );
				$total_variation_qty = 0;
				if ( WC()->cart ) {
					foreach ( WC()->cart->get_cart() as $cart_item ) {
						if ( $cart_item['product_id'] === $parent_id ) {
							$total_variation_qty += $cart_item['quantity'];
						}
					}
				}
				$cart_qty = $total_variation_qty;
			} else {
				$cart_qty = wholesalex()->cart_count( $product_id );
			}

			// NOTE:Product Discount and Quantity based discount Merged here. If a product_discount was already applied
			$tier_base_price = ( 'product_discount' === $applied_discount_src && $sale_price ) ? (float) $sale_price : $base_price;

			$tier_res = array();
			foreach ( $priority as $pr ) {
				$tier_res = $this->get_priority_wise_tier_price( $pr, $data, $product_id, $parent_id, $tier_base_price, $cart_qty, true );
				if ( ! empty( $tier_res['tiers'] ) && ! isset( $this->active_tiers[ $product_id ] ) ) {
					$tier_res['base_price']            = $tier_base_price;
					$this->active_tiers[ $product_id ] = $tier_res;
				}
				if ( $tier_res['price'] && $tier_res['src'] && 0.00 != $tier_res['price'] ) {
					$sale_price = $tier_res['price'];
					break;
				}
			}
			if ( isset( $tier_res['tiers'] ) && ! empty( $tier_res['tiers'] ) && $tier_res['price'] ) {
				$tier_res['base_price']            = $tier_base_price;
				$this->active_tiers[ $product_id ] = $tier_res;
			}
		}

		if ( $previous_sp !== $sale_price && $sale_price ) {
			wholesalex()->set_wholesalex_wholesale_prices( $product_id, $sale_price );
		}
		if ( wholesalex()->get_wholesalex_regular_prices( $product_id ) && ! wholesalex()->get_wholesalex_wholesale_prices( $product_id ) ) {
			$previous_sp = '';
		}
		if ( wholesalex()->get_wholesalex_wholesale_prices( $product_id ) ) {
			$this->set_discounted_product( $product_id );
		}
		if ( $sale_price && 0.00 !== $sale_price ) {
			if ( WC()->session ) {
				WC()->session->set( 'wsx_sale_price', $sale_price );
			}
		}
		return $sale_price && 0.00 !== (float) $sale_price ? $sale_price : $previous_sp;
	}

	// ─── Tier Pricing ────────────────────────────────────────────

	public function apply_individual_tier( $tiers = array(), $base_price = '', $cart_qty = '' ) {
		$res = array(
			'id'    => false,
			'price' => false,
		);
		foreach ( $tiers as $tier ) {
			if ( ! isset( $tier['_discount_type'], $tier['_discount_amount'], $tier['_min_quantity'] ) ) {
				continue;
			}
			if ( $cart_qty >= $tier['_min_quantity'] ) {
				$res['price'] = wholesalex()->calculate_sale_price( $tier, $base_price );
				$res['id']    = isset( $tier['_id'] ) ? $tier['_id'] : ( isset( $tier['id'] ) ? $tier['id'] : '' );
			}
		}
		return $res;
	}

	public function calculate_tier_pricing( $tiers = array(), $base_price = '', $cart_qty = '' ) {
		$res = false;
		if ( ! empty( $tiers ) ) {
			array_multisort( array_column( $tiers, '_min_quantity' ), SORT_ASC, $tiers );
			$res = $this->apply_individual_tier( $tiers, $base_price, $cart_qty );
		}
		return $res;
	}

	public function get_priority_wise_tier_price( $priority, $data, $product_id, $parent_id, $base_price, $cart_qty, $first_tier = false ) {
		$tier_res    = array(
			'src'   => false,
			'price' => false,
			'tiers' => array(),
		);
		$active_tier = array();
		switch ( $priority ) {
			case 'single_product':
				$single_product_tier = get_post_meta( $product_id, $data['role_id'] . '_tiers', true );
				if ( is_array( $single_product_tier ) && ! empty( $single_product_tier ) ) {
					$active_tier = $single_product_tier;
					$res         = $this->calculate_tier_pricing( $single_product_tier, $base_price, $cart_qty );
					if ( $res ) {
						$tier_res = array(
							'src'   => 'single_product_tier',
							'price' => $res['price'],
							'tiers' => $single_product_tier,
							'id'    => $res['id'],
						);
					}
				}
				break;
			case 'dynamic_rule':
				$dynamic_rule_tiers = array();
				$display_tiers      = array();
				if ( ! empty( $data['quantity_based'] ) ) {
					foreach ( $data['quantity_based'] as $qbd ) {
						$is_eligible = Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $product_id, $product_id, $qbd['filter'] );
						// Always collect tiers for table display from eligible products,
						// regardless of conditions (the table is informational).
						if ( $is_eligible ) {
							$display_tiers = array_merge( $display_tiers, $qbd['rule']['tiers'] );
						}
						if ( isset( $qbd['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $qbd['conditions'], $qbd['filter'] ) ) {
							continue;
						}
						if ( $is_eligible ) {
							$dynamic_rule_tiers = array_merge( $dynamic_rule_tiers, $qbd['rule']['tiers'] );
						}
					}
				}
				// Use display_tiers (all product-eligible tiers) so the tier table
				// always renders; price calculation still uses condition-gated tiers.
				$active_tier = ! empty( $display_tiers ) ? $display_tiers : $dynamic_rule_tiers;
				$res         = $this->calculate_tier_pricing( $dynamic_rule_tiers, $base_price, $cart_qty );
				if ( $res ) {
					$tier_res = array(
						'src'   => 'quantity_based_tier',
						'price' => $res['price'],
						'tiers' => $dynamic_rule_tiers,
						'id'    => $res['id'],
					);
				}
				break;
			case 'profile':
				if ( isset( $data['user_profile'] ) ) {
					$user_profile_tiers = array();
					foreach ( $data['user_profile_filter_map'] as $key => $upf ) {
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $product_id, $product_id, $upf ) ) {
							$user_profile_tiers = array_merge( $user_profile_tiers, $data['user_profile'][ $key ] );
						}
					}
					$active_tier = $user_profile_tiers;
					$res         = $this->calculate_tier_pricing( $user_profile_tiers, $base_price, $cart_qty );
					if ( $res ) {
						$tier_res = array(
							'src'   => 'user_profile_tier',
							'price' => $res['price'],
							'tiers' => $user_profile_tiers,
							'id'    => $res['id'],
						);
					}
				}
				break;
			case 'category':
				$cat_ids   = wc_get_product_term_ids( $parent_id ? $parent_id : $product_id, 'product_cat' );
				$cat_ids   = array_reverse( $cat_ids );
				$cat_tiers = array();
				$cart_qty  = 0;
				foreach ( $cat_ids as $cat_id ) {
					$cat_tier = get_term_meta( $cat_id, $data['role_id'] . '_tiers', true );
					if ( ! empty( $cat_tier ) && is_array( $cat_tier ) ) {
						$cat_tiers = array_merge( $cat_tiers, $cat_tier );
						$cart_qty += intval( wholesalex()->category_cart_count( $cat_id ) );
					}
				}
				$active_tier = $cat_tiers;
				$res         = $this->calculate_tier_pricing( $cat_tiers, $base_price, $cart_qty );
				if ( $res ) {
					$tier_res = array(
						'src'   => 'category_tier',
						'price' => $res['price'],
						'tiers' => $cat_tiers,
						'id'    => $res['id'],
					);
				}
				break;
			default:
				break;
		}
		if ( ! $tier_res['price'] && $first_tier ) {
			$tier_res['tiers'] = $active_tier;
		}
		return $tier_res;
	}

	private function filter_empty_tier( $tiers ) {
		$__tiers = array();
		if ( ! ( is_array( $tiers ) && ! empty( $tiers ) ) ) {
			return array();
		}
		foreach ( $tiers as $tier ) {
			if ( ! isset( $tier['_id'] ) ) {
				$tier['_id'] = wp_unique_id( 'wsx' );
			}
			if ( isset( $tier['_discount_type'] ) && ! empty( $tier['_discount_type'] ) && isset( $tier['_discount_amount'] ) && ! empty( $tier['_discount_amount'] ) && isset( $tier['_min_quantity'] ) && ! empty( $tier['_min_quantity'] ) ) {
				array_push( $__tiers, $tier );
			}
		}
		return $__tiers;
	}

	// ─── Price Display ───────────────────────────────────────────

	public function variation_price_hash( $hash, $product ) {
		$user_id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		$hash[]  = apply_filters( 'wholesalex_variation_prices_hash', strval( $user_id ) . strval( floor( time() / 60 ) ), $product );
		return $hash;
	}

	public function format_sale_price( $regular_price, $sale_price, $is_wholesalex_sale_price_applied ) {
		global $product;
		$sale_text = '';
		if ( class_exists( 'Aelia_Integration_Helper' ) && \Aelia_Integration_Helper::aelia_currency_switcher_active() ) {
			$active_currency = get_woocommerce_currency();
			$product_id      = $product->get_id();
			$base_currency   = \Aelia_Integration_Helper::get_product_base_currency( $product_id );
			$wholesale_price = \Aelia_Integration_Helper::convert( $sale_price, $active_currency, $base_currency );
			return wc_price( floatval( $wholesale_price ) );
		}
		if ( is_product() ) {
			$sale_text = wholesalex()->get_setting( '_settings_price_text', __( 'Wholesale Price:', 'wholesalex' ) );
		} else {
			$sale_text = wholesalex()->get_setting( '_settings_price_text_product_list_page', __( 'Wholesale Price:', 'wholesalex' ) );
		}
		$__hide_regular_price   = wholesalex()->get_setting( '_settings_hide_retail_price' ) ?? '';
		$__hide_wholesale_price = wholesalex()->get_setting( '_settings_hide_wholesalex_price' ) ?? '';
		if ( $this->is_enable_subscriptions_product_woo( $product ) ) {
			$sale_text = '';
		}
		if ( ! $is_wholesalex_sale_price_applied ) {
			$sale_text = '';
		}
		if ( ! empty( $sale_text ) ) {
			$sale_text = '<span class="wholesalex-sale-text">' . $sale_text . '</span> ';
		}
		if ( ! is_admin() ) {
			if ( 'yes' === (string) $__hide_wholesale_price && 'yes' === (string) $__hide_regular_price ) {
				return apply_filters( 'wholesalex_regular_sale_price_hidden_text', wholesalex()->get_language_n_text( '_language_price_is_hidden', 'Price is hidden!' ) );
			}
			if ( 'yes' === (string) $__hide_regular_price && ! empty( $sale_price ) ) {
				if ( is_string( $sale_price ) && $product && $product->get_type() === 'variable' ) {
					return $sale_text . $sale_price;
				}
				return $sale_text . wc_price( floatval( $sale_price ) );
			}
			if ( 'yes' === (string) $__hide_wholesale_price && ! empty( $regular_price ) && $is_wholesalex_sale_price_applied ) {
				return wc_price( floatval( $regular_price ) );
			}
		}
		if ( $sale_price === $regular_price ) {
			return '<ins>' . $sale_text . wc_price( floatval( $sale_price ) ) . '</ins>';
		}
		if ( ! empty( $sale_price ) && ! empty( $regular_price ) ) {
			return '<del aria-hidden="true">' . ( is_numeric( $regular_price ) ? wc_price( $regular_price ) : $regular_price ) . '</del> <ins>' . $sale_text . ( ( is_numeric( $sale_price ) ? wc_price( $sale_price ) : $sale_price ) ) . '</ins>';
		}
		if ( ! empty( $sale_price ) ) {
			return '<ins>' . $sale_text . wc_price( floatval( $sale_price ) ) . '</ins>';
		}
		if ( ! empty( $regular_price ) ) {
			return wc_price( floatval( $regular_price ) );
		}
	}

	public function get_product_quantity_in_cart( $product_id ) {
		$quantity_in_cart = 0;
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$cart_product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
				if ( $cart_product_id == $product_id ) {
					$quantity_in_cart += $cart_item['quantity'];
				}
			}
		}
		return $quantity_in_cart;
	}

	// ─── Main Dispatch: get_valid_dynamic_rules ──────────────────

	public function get_valid_dynamic_rules( $user_id = '' ) {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$user_id = ( isset( $user_id ) && ! empty( $user_id ) ) ? $user_id : get_current_user_id();
		$user_id = apply_filters( 'wholesalex_set_current_user', $user_id );

		if ( ! isset( $GLOBALS['wholesalex_rule_data'] ) ) {
			$GLOBALS['wholesalex_rule_data'] = array();
		}

		if ( is_user_logged_in() ) {
			self::$cu_order_counts = wc_get_customer_order_count( $user_id );
			self::$cu_total_spent  = wc_get_customer_total_spent( $user_id );
			// Sync to facade for backward compatibility.
			WHOLESALEX_Dynamic_Rules::$cu_order_counts = self::$cu_order_counts;
			WHOLESALEX_Dynamic_Rules::$cu_total_spent  = self::$cu_total_spent;
		}

		self::$total_cart_counts                     = false;
		WHOLESALEX_Dynamic_Rules::$total_cart_counts = false;

		do_action( 'wholesalex_before_dynamic_rules_loaded', $user_id );
		$plugins_status = wholesalex()->get_setting( '_settings_status', 'b2b' );
		$is_eligible    = true;

		if ( 'b2b' === $plugins_status && 'active' !== wholesalex()->get_user_status( $user_id ) ) {
			$is_eligible = false;
		}

		$__discounts        = wholesalex()->get_dynamic_rules();
		$__role             = wholesalex()->get_user_role( $user_id );
		$__discounts_for_me = array();

		foreach ( $__discounts as $discount ) {
			if ( isset( $discount['_rule_status'] ) && $discount['_rule_status'] && ! empty( $discount['_rule_status'] ) && isset( $discount['_product_filter'] ) ) {
				if ( isset( $discount['conditions']['tiers'] ) && ! empty( $discount['conditions']['tiers'] ) && ! Dynamic_Rules_Condition_Engine::is_user_order_count_purchase_amount_condition_passed( $discount['conditions']['tiers'] ) ) {
					continue;
				}

				$__role_for       = $discount['_rule_for'];
				$__for_me         = false;
				$who_priority     = 10;
				$product_priority = 10;
				switch ( $__role_for ) {
					case 'specific_roles':
						foreach ( $discount['specific_roles'] as $role ) {
							if ( (string) $role['value'] === (string) $__role || 'role_' . $__role === $role['value'] ) {
								array_push( $__discounts_for_me, $discount );
								$__for_me     = true;
								$who_priority = 20;
								break;
							}
						}
						break;
					case 'specific_users':
						foreach ( $discount['specific_users'] as $user ) {
							if ( ( is_numeric( $user['value'] ) && (int) $user['value'] === (int) $user_id ) || ( 'user_' . $user_id === $user['value'] ) ) {
								array_push( $__discounts_for_me, $discount );
								$__for_me     = true;
								$who_priority = 10;
								break;
							}
						}
						break;
					case 'all_roles':
						if ( empty( $__role ) ) {
							break;
						}
						$__exclude_roles = apply_filters( 'wholesalex_dynamic_rules_exclude_roles', array( 'wholesalex_guest', 'wholesalex_b2c_users' ) );
						if ( is_array( $__exclude_roles ) && ! empty( $__exclude_roles ) ) {
							if ( ! in_array( $__role, $__exclude_roles ) ) {
								array_push( $__discounts_for_me, $discount );
								$__for_me     = true;
								$who_priority = 30;
								break;
							}
						} else {
							array_push( $__discounts_for_me, $discount );
							$__for_me     = true;
							$who_priority = 30;
						}
						break;
					case 'all_users':
						$__exclude_users = apply_filters( 'wholesalex_dynamic_rules_exclude_users', array() );
						if ( is_array( $__exclude_users ) && ! empty( $__exclude_users ) ) {
							if ( ! in_array( $user_id, $__exclude_users ) ) {
								array_push( $__discounts_for_me, $discount );
								$__for_me     = true;
								$who_priority = 40;
								break;
							}
						} elseif ( 0 !== $user_id ) {
							array_push( $__discounts_for_me, $discount );
							$__for_me     = true;
							$who_priority = 40;
						}
						break;
					case 'all':
						array_push( $__discounts_for_me, $discount );
						$__for_me     = true;
						$who_priority = 50;
						break;
				}
				if ( ! $__for_me ) {
					continue;
				}

				$include_products   = array();
				$include_cats       = array();
				$include_brands     = array();
				$include_variations = array();
				$include_attributes = array();
				$exclude_products   = array();
				$exclude_cats       = array();
				$exclude_brands     = array();
				$exclude_variations = array();
				$exclude_attributes = array();
				$is_all_products    = false;

				$is_dynamic_rules_apply_in_backend = apply_filters( 'is_dynamic_rules_work_in_backend', true );
				if ( ! is_admin() && '' !== $is_dynamic_rules_apply_in_backend ) {
					extract( Dynamic_Rules_Condition_Engine::get_filtered_rules( $discount ) );
				} elseif ( is_admin() && $is_dynamic_rules_apply_in_backend ) {
					extract( Dynamic_Rules_Condition_Engine::get_filtered_rules( $discount ) );
				}
			} else {
				continue;
			}

			if ( isset( $discount['limit'] ) && ! empty( $discount['limit'] ) ) {
				if ( ! Dynamic_Rules_Condition_Engine::has_limit( $discount['limit'], $discount['id'] ) ) {
					continue;
				}
			}

			if ( ! isset( $discount['_rule_for'] ) ) {
				continue;
			}
			if ( ! isset( $discount['_rule_type'] ) || ( 'restrict_product_visibility' !== $discount['_rule_type'] && ! isset( $discount[ $discount['_rule_type'] ] ) ) ) {
				continue;
			}

			if ( ! ( isset( $this->valid_dynamic_rules[ $discount['_rule_type'] ] ) && is_array( $this->valid_dynamic_rules[ $discount['_rule_type'] ] ) ) ) {
				$this->valid_dynamic_rules[ $discount['_rule_type'] ] = array();
			}

			$frule = array();
			switch ( $discount['_rule_type'] ) {
				case 'quantity_based':
					$frule = array( 'tiers' => $this->filter_empty_tier( $discount[ $discount['_rule_type'] ]['tiers'] ) );
					break;
				case 'min_order_qty':
					if ( ! empty( $discount['min_order_qty']['_min_order_qty'] ) ) {
						$frule = $discount['min_order_qty'];
					}
					break;
				case 'max_order_qty':
					if ( ! empty( $discount['max_order_qty']['_max_order_qty'] ) ) {
						$frule = $discount['max_order_qty'];
					}
					break;
				case 'hidden_price':
					if ( ! empty( $discount['hidden_price'] ) ) {
						$frule = $discount['hidden_price'];
					}
					break;
				default:
					$frule = $discount[ $discount['_rule_type'] ];
					break;
			}

			if ( ! empty( $frule ) || in_array( $discount['_rule_type'], array( 'hidden_price', 'non_purchasable', 'restrict_product_visibility', 'restrict_checkout' ), true ) ) {
				$this->valid_dynamic_rules[ $discount['_rule_type'] ][] = array(
					'id'                  => $discount['id'],
					'filter'              => array(
						'include_products'   => $include_products,
						'include_attributes' => $include_attributes,
						'include_brands'     => $include_brands,
						'include_cats'       => $include_cats,
						'include_variations' => $include_variations,
						'exclude_products'   => $exclude_products,
						'exclude_attributes' => $exclude_attributes,
						'exclude_brands'     => $exclude_brands,
						'exclude_cats'       => $exclude_cats,
						'exclude_variations' => $exclude_variations,
						'is_all_products'    => $is_all_products,
					),
					'rule'                => $frule,
					'conditions'          => array( 'tiers' => isset( $discount['conditions']['tiers'] ) ? wholesalex()->filter_empty_conditions( $discount['conditions']['tiers'] ) : array() ),
					'who_priority'        => $who_priority,
					'applied_on_priority' => $product_priority,
					'end_date'            => isset( $discount['limit']['_end_date'] ) ? $discount['limit']['_end_date'] : false,
				);
			}
		}

		foreach ( $this->valid_dynamic_rules as $key => $value ) {
			usort( $this->valid_dynamic_rules[ $key ], array( $this, 'compare_by_priority' ) );
		}

		do_action( 'wholesalex_valid_dynamic_rules', $this->valid_dynamic_rules );

		// ── User Profile ──
		$profile_settings        = get_user_meta( $user_id, '__wholesalex_profile_settings', true );
		$user_profile_tiers      = get_user_meta( $user_id, '__wholesalex_profile_discounts', true );
		$user_profile_data       = array();
		$user_profile_filter_map = array();

		if ( isset( $user_profile_tiers['_profile_discounts']['tiers'] ) && ! empty( $user_profile_tiers['_profile_discounts']['tiers'] ) ) {
			$user_profile_tiers = $user_profile_tiers['_profile_discounts']['tiers'];
			foreach ( $user_profile_tiers as $upt ) {
				extract( Dynamic_Rules_Condition_Engine::get_filtered_rules( $upt ) );
				$user_profile_filter = array(
					'include_products'   => $include_products,
					'include_cats'       => $include_cats,
					'include_brands'     => $include_brands,
					'include_attributes' => $include_attributes,
					'include_variations' => $include_variations,
					'exclude_products'   => $exclude_products,
					'exclude_cats'       => $exclude_cats,
					'exclude_brands'     => $exclude_brands,
					'exclude_attributes' => $exclude_attributes,
					'exclude_variations' => $exclude_variations,
					'is_all_products'    => $is_all_products,
				);
				$idx                 = md5( serialize( $user_profile_filter ) );
				if ( ! isset( $user_profile_data[ $idx ] ) ) {
					$user_profile_data[ $idx ] = array();
				}
				$user_profile_data[ $idx ][]     = array(
					'_id'                 => $upt['_id'],
					'_discount_type'      => $upt['_discount_type'],
					'_discount_amount'    => $upt['_discount_amount'],
					'_min_quantity'       => $upt['_min_quantity'],
					'applied_on_priority' => $product_priority,
				);
				$user_profile_filter_map[ $idx ] = $user_profile_filter;
			}
		}

		// ── Tax Rules → delegate to Rule_Tax ──
		$is_tax_exempt = '';
		if ( isset( $profile_settings['_wholesalex_profile_override_tax_exemption'] ) ) {
			$is_tax_exempt = $profile_settings['_wholesalex_profile_override_tax_exemption'];
		}
		if ( isset( $this->valid_dynamic_rules['tax_rule'] ) && ! empty( $this->valid_dynamic_rules['tax_rule'] ) ) {
			usort( $this->valid_dynamic_rules['tax_rule'], array( $this, 'compare_by_priority' ) );
		}
		$this->valid_dynamic_rules['tax_rule'] = isset( $this->valid_dynamic_rules['tax_rule'] ) ? $this->valid_dynamic_rules['tax_rule'] : array();
		if ( ! empty( $this->valid_dynamic_rules['tax_rule'] ) || $is_tax_exempt ) {
			$this->rule_tax->handle(
				array(
					'profile_exemption' => $is_tax_exempt,
					'rules'             => $this->valid_dynamic_rules['tax_rule'],
				)
			);
		}

		// ── Shipping Rules → delegate to Rule_Shipping ──
		if ( isset( $this->valid_dynamic_rules['shipping_rule'] ) && ! empty( $this->valid_dynamic_rules['shipping_rule'] ) ) {
			usort( $this->valid_dynamic_rules['shipping_rule'], array( $this, 'compare_by_priority' ) );
		}
		$profile_shipping_data = array();
		if ( isset( $profile_settings['_wholesalex_profile_override_shipping_method'] ) && 'yes' === $profile_settings['_wholesalex_profile_override_shipping_method'] ) {
			$profile_shipping_data['method_type'] = isset( $profile_settings['_wholesalex_profile_shipping_method_type'] ) ? $profile_settings['_wholesalex_profile_shipping_method_type'] : '';
			$profile_shipping_data['zone']        = isset( $profile_settings['_wholesalex_profile_shipping_zone'] ) ? $profile_settings['_wholesalex_profile_shipping_zone'] : '';
			$profile_shipping_data['methods']     = isset( $profile_settings['_wholesalex_profile_shipping_zone_methods'] ) ? $profile_settings['_wholesalex_profile_shipping_zone_methods'] : array();
		}
		$__role_content     = wholesalex()->get_roles( 'by_id', $__role );
		$__shipping_methods = array();
		if ( isset( $__role_content['_shipping_methods'] ) && ! empty( $__role_content['_shipping_methods'] ) ) {
			$__shipping_methods = $__role_content['_shipping_methods'];
		}
		$__shipping_methods                         = array_filter( $__shipping_methods );
		$this->valid_dynamic_rules['shipping_rule'] = isset( $this->valid_dynamic_rules['shipping_rule'] ) ? $this->valid_dynamic_rules['shipping_rule'] : array();
		if ( ! empty( $profile_shipping_data ) || ! empty( $__shipping_methods ) || ! empty( $this->valid_dynamic_rules['shipping_rule'] ) ) {
			$this->rule_shipping->handle(
				array(
					'profile' => $profile_shipping_data,
					'roles'   => $__shipping_methods,
					'rules'   => $this->valid_dynamic_rules['shipping_rule'],
				)
			);
		}

		// ── Payment Gateway Rules → delegate to Rule_Payment_Gateway ──
		$profile_gateway_data = array();
		if ( isset( $profile_settings['_wholesalex_profile_override_payment_gateway'] ) && 'yes' === $profile_settings['_wholesalex_profile_override_payment_gateway'] ) {
			if ( isset( $profile_settings['_wholesalex_profile_payment_gateways'] ) && ! empty( $profile_settings['_wholesalex_profile_payment_gateways'] ) ) {
				$profile_gateway_data = $profile_settings['_wholesalex_profile_payment_gateways'];
			}
		}
		$payment_related_rules = array();
		if ( isset( $this->valid_dynamic_rules['payment_order_qty'] ) && ! empty( $this->valid_dynamic_rules['payment_order_qty'] ) ) {
			usort( $this->valid_dynamic_rules['payment_order_qty'], array( $this, 'compare_by_priority' ) );
			$payment_related_rules = $this->valid_dynamic_rules['payment_order_qty'];
		}
		$role_payment_methods = array();
		if ( isset( $__role_content['_payment_methods'] ) && ! empty( $__role_content['_payment_methods'] ) ) {
			$role_payment_methods = $__role_content['_payment_methods'];
			$role_payment_methods = array_filter( $role_payment_methods );
		}
		$this->rule_payment_gateway->handle(
			array(
				'profile' => $profile_gateway_data,
				'rules'   => $payment_related_rules,
				'roles'   => $role_payment_methods,
			)
		);

		// ── Cart Fees → aggregate from rule handlers ──
		$cart_related_data = array();
		if ( isset( $this->valid_dynamic_rules['buy_x_get_one'] ) && ! empty( $this->valid_dynamic_rules['buy_x_get_one'] ) ) {
			usort( $this->valid_dynamic_rules['buy_x_get_one'], array( $this, 'compare_by_priority' ) );
			$cart_related_data['buy_x_get_one'] = $this->valid_dynamic_rules['buy_x_get_one'];
		}
		if ( isset( $this->valid_dynamic_rules['cart_discount'] ) && ! empty( $this->valid_dynamic_rules['cart_discount'] ) ) {
			usort( $this->valid_dynamic_rules['cart_discount'], array( $this, 'compare_by_priority' ) );
			$cart_related_data['cart_discount'] = $this->valid_dynamic_rules['cart_discount'];
		}
		if ( isset( $this->valid_dynamic_rules['payment_discount'] ) && ! empty( $this->valid_dynamic_rules['payment_discount'] ) ) {
			usort( $this->valid_dynamic_rules['payment_discount'], array( $this, 'compare_by_priority' ) );
			$cart_related_data['payment_discount'] = $this->valid_dynamic_rules['payment_discount'];
		}
		$cart_related_data = apply_filters( 'wholesalex_dr_cart_related_data', $cart_related_data );
		if ( ! empty( $cart_related_data ) ) {
			$this->handle_cart( $cart_related_data );
		}

		// Pass all valid dynamic rules to BOGO badge handler (needs buy_x_get_one and buy_x_get_y keys).
		$this->rule_buy_x_get_one->set_valid_rules( $this->valid_dynamic_rules );

		// ── Min/Max Order Quantity → delegate to Rule_Min_Order_Qty ──
		$min_max_data = array(
			'min_order_qty' => array(),
			'max_order_qty' => array(),
		);
		if ( isset( $this->valid_dynamic_rules['min_order_qty'] ) && ! empty( $this->valid_dynamic_rules['min_order_qty'] ) ) {
			usort( $this->valid_dynamic_rules['min_order_qty'], array( $this, 'compare_by_priority' ) );
			$min_max_data['min_order_qty'] = $this->valid_dynamic_rules['min_order_qty'];
		}
		$min_max_data = apply_filters( 'wholesalex_dr_min_max_rules', $min_max_data, $this->valid_dynamic_rules );
		if ( ! is_plugin_active( 'woocommerce-min-max-quantities/woocommerce-min-max-quantities.php' ) ) {
			$this->rule_min_order_qty->handle( $min_max_data );
		}

		// ── Discounts (price filters) ──
		$discounts_releated_data = array(
			'user_id'                 => $user_id,
			'role_id'                 => $__role,
			'plugin_status'           => $plugins_status,
			'eligible'                => $is_eligible,
			'product_discount'        => array(),
			'quantity_based'          => array(),
			'user_profile'            => array(),
			'user_profile_filter_map' => array(),
		);
		if ( isset( $this->valid_dynamic_rules['product_discount'] ) && ! empty( $this->valid_dynamic_rules['product_discount'] ) ) {
			usort( $this->valid_dynamic_rules['product_discount'], array( $this, 'compare_by_priority' ) );
			$discounts_releated_data['product_discount'] = $this->valid_dynamic_rules['product_discount'];
		}
		if ( ! empty( $user_profile_data ) ) {
			$discounts_releated_data['user_profile']            = $user_profile_data;
			$discounts_releated_data['user_profile_filter_map'] = $user_profile_filter_map;
		}
		$discounts_releated_data = apply_filters( 'wholesalex_dr_discounts', $discounts_releated_data );
		$this->handle_discounts( $discounts_releated_data );

		// ── Shipping zone detection ──
		if ( ! empty( $this->valid_dynamic_rules['shipping_rule'] ) && ! is_null( WC()->cart ) ) {
			$shipping_packages = WC()->cart->get_shipping_packages();
			if ( ! empty( $shipping_packages ) && is_array( $shipping_packages ) ) {
				$first_package = reset( $shipping_packages );
				if ( is_array( $first_package ) && ! empty( $first_package ) ) {
					$shipping_zone = wc_get_shipping_zone( $first_package );
					if ( is_object( $shipping_zone ) ) {
						$this->current_shipping_zone = $shipping_zone->get_id();
					}
				}
			}
		}

		// ── Single product page promo hooks ──
		add_action(
			'woocommerce_before_add_to_cart_form',
			function () use ( $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt ) {
				global $product;
				if ( $product->is_type( 'simple' ) ) {
					$this->handle_single_product_page_promo( $product, $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt, true );
				}
			}
		);

		add_action(
			'woocommerce_available_variation',
			function ( $variation_array, $product, $variation ) use ( $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt ) {
				$variation_array['availability_html'] .= $this->handle_single_product_page_promo( $variation, $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt, false );
				return $variation_array;
			},
			10,
			3
		);

		if ( 'yes' === wholesalex()->get_setting( '_settings_hide_retail_price' ) && 'yes' === wholesalex()->get_setting( '_settings_hide_wholesalex_price' ) ) {
			$this->make_product_non_purchasable_and_remove_add_to_cart();
		}

		// ── Pro rule dispatching ──
		do_action( 'wholesalex_dr_after_valid_rules_dispatch', $this->valid_dynamic_rules, $discounts_releated_data, $min_max_data, $cart_related_data );
	}

	// ─── Handle Cart Discount and charges calculation for Buy X Get Y, Cart Discount and Payment Discount rules ────────────

	public function handle_cart( $rules ) {
		$rule_buy_x     = $this->rule_buy_x_get_one;
		$rule_cart_disc = $this->rule_cart_discount;
		$rule_pay_disc  = $this->rule_payment_discount;

		add_action(
			'woocommerce_cart_calculate_fees',
			function ( $cart ) use ( $rules, $rule_buy_x, $rule_cart_disc, $rule_pay_disc ) {
				if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
					return;
				}

				$cart_fees = array();

				if ( isset( $rules['buy_x_get_one'] ) && ! empty( $rules['buy_x_get_one'] ) ) {
					$cart_fees['buy_x_get_one'] = $rule_buy_x->calculate( $rules['buy_x_get_one'] );
				}
				if ( isset( $rules['cart_discount'] ) && ! empty( $rules['cart_discount'] ) ) {
					$cart_fees['cart_discount'] = $rule_cart_disc->calculate( $rules['cart_discount'] );
				}
				if ( isset( $rules['payment_discount'] ) && ! empty( $rules['payment_discount'] ) ) {
					$cart_fees['payment_discount'] = $rule_pay_disc->calculate( $rules['payment_discount'] );
				}

				// Pro extra_charge and other fees via filter.
				$cart_fees = apply_filters( 'wholesalex_dr_cart_fees', $cart_fees, $rules, WC()->session->get( 'chosen_payment_method' ) );

				if ( ! empty( $cart_fees ) ) {
					$coupon_names = array();
					foreach ( $cart_fees as $fees ) {
						foreach ( $fees as $discount ) {
							if ( isset( $discount['discount'] ) && 0 !== $discount['discount'] ) {
								$__is_taxable = apply_filters( 'wholesalex_payment_gateway_discount_is_taxable', false );
								if ( ! isset( $coupon_names[ $discount['name'] ] ) ) {
									$coupon_names[ $discount['name'] ] = true;
								} else {
									$discount['name']                  = wp_unique_id( $discount['name'] );
									$coupon_names[ $discount['name'] ] = true;
								}
								$cart->add_fee( $discount['name'], -1 * floatval( $discount['discount'] ), $__is_taxable );
							} elseif ( isset( $discount['charge'] ) && 0 !== $discount['charge'] ) {
								$__is_taxable = apply_filters( 'wholesalex_extra_charge_is_taxable', true );
								if ( ! isset( $coupon_names[ $discount['name'] ] ) ) {
									$coupon_names[ $discount['name'] ] = true;
								} else {
									$discount['name']                  = wp_unique_id( $discount['name'] );
									$coupon_names[ $discount['name'] ] = true;
								}
								$cart->add_fee( $discount['name'], floatval( $discount['charge'] ), $__is_taxable );
							}
						}
					}
					if ( isset( $rules['payment_discount'] ) && ( ! empty( $rules['payment_discount'] ) || isset( $rules['extra_charge'] ) ) && ! empty( $rules['extra_charge'] ) ) {
						add_action(
							'woocommerce_review_order_before_payment',
							function () {
								if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) :
									?>
							<script type="text/javascript">
								jQuery(function($) {
									$('form.woocommerce-checkout').on('change', 'input[name="payment_method"]', function() {
										$('body').trigger('update_checkout');
									});
								})
							</script>
									<?php
								endif;
							}
						);
					}
				}
			}
		);
	}

	// ─── Handle Discounts (Price Filter Registration) ────────────

	public function handle_discounts( $data ) {
		$global_show_tier_table = wholesalex()->get_setting( '_settings_show_table', 'yes' );

		if ( 'yes' === $global_show_tier_table ) {
			$tier_position = 'before';
			add_action(
				'woocommerce_' . $tier_position . '_add_to_cart_form',
				function () use ( $data ) {
					global $post;
					$product_id = $post->ID;
					$product    = wc_get_product( $post->ID );
					if ( ! $product ) {
						return;
					}
					if ( ! $product->is_type( 'simple' ) ) {
						return;
					}
					if ( ! ( $product_id && 'yes' === wholesalex()->get_single_product_setting( $product_id, '_settings_show_tierd_pricing_table' ) ) ) {
						return;
					}
					// Ensure active_tiers is populated even if price filters haven't fired yet
					// (e.g. themes/page-builders that render the form before the price).
					if ( ! isset( $this->active_tiers[ $product_id ] ) ) {
						$product->get_sale_price();
					}
					$tiers          = isset( $this->active_tiers[ $product_id ] ) ? $this->active_tiers[ $product_id ] : array( 'tiers' => array() );
					$tiers['tiers'] = $this->filter_empty_tier( $tiers['tiers'] );
					$table_data     = false;
					if ( ! empty( $tiers['tiers'] ) ) {
						$table_data = $this->quantity_based_pricing_table( '', $product_id, $data );
					}
					if ( ( function_exists( 'wholesalex_pro' ) && version_compare( WHOLESALEX_PRO_VER, '1.3.1', '<=' ) ) && ! $table_data ) {
						$table_data = $this->quantity_based_pricing_table( '', $product_id, $data );
					}
					do_action( 'wholesalex_tier_pricing_table', $product );
					if ( $table_data ) {
						echo $table_data;
					}
				},
				10
			);
			add_filter(
				'woocommerce_available_variation',
				function ( $variation_data, $product, $variation ) use ( $data ) {
					$variation_id = $variation->get_id();
					$product_id   = $product->get_id();
					if ( ! ( $product_id && 'yes' === wholesalex()->get_single_product_setting( $product_id, '_settings_show_tierd_pricing_table' ) ) ) {
						return $variation_data;
					}
					// Ensure active_tiers is populated even if price filters haven't fired yet.
					if ( ! isset( $this->active_tiers[ $variation_id ] ) ) {
						$variation->get_sale_price();
					}
					$tiers = isset( $this->active_tiers[ $variation_id ] ) ? $this->active_tiers[ $variation_id ] : array();
					if ( isset( $tiers['tiers'] ) ) {
						$tiers['tiers'] = $this->filter_empty_tier( $tiers['tiers'] );
					}
					if ( ! empty( $tiers ) ) {
						$tier_table                           = $this->quantity_based_pricing_table( '', $variation_id, $data );
						$variation_data['availability_html'] .= $tier_table;
					}
					if ( ( function_exists( 'wholesalex_pro' ) && version_compare( WHOLESALEX_PRO_VER, '1.3.1', '<=' ) ) && empty( $tiers ) ) {
						$tier_table                           = $this->quantity_based_pricing_table( '', $variation_id, $data );
						$variation_data['availability_html'] .= $tier_table;
					}
					return $variation_data;
				},
				10,
				3
			);
		}

		add_filter(
			'woocommerce_product_get_regular_price',
			function ( $regular_price, $product ) use ( $data ) {
				$product_id = $product->get_id();
				if ( $this->is_wholesalex_topup_product( $product_id ) ) {
					return $regular_price; }
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'regular_price' ) ) {
					return $regular_price; }
				if ( $this->is_product_in_bundle( $product_id ) ) {
					return $regular_price; }
				$regular_price = $this->calculate_regular_price( $regular_price, $product, $data );
				return ( ! empty( $regular_price ) ) ? (float) $regular_price : $regular_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_product_variation_get_regular_price',
			function ( $regular_price, $product ) use ( $data ) {
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'regular_price' ) ) {
					return $regular_price; }
				$regular_price = $this->calculate_regular_price( $regular_price, $product, $data );
				return ( ! empty( $regular_price ) ) ? (float) $regular_price : $regular_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_variation_prices_regular_price',
			function ( $regular_price, $product ) use ( $data ) {
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'regular_price' ) ) {
					return $regular_price; }
				$regular_price = $this->calculate_regular_price( $regular_price, $product, $data );
				return ( ! empty( $regular_price ) ) ? (float) $regular_price : $regular_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_product_get_sale_price',
			function ( $sale_price, $product ) use ( $data ) {
				$product_id = $product->get_id();
				if ( $this->is_wholesalex_topup_product( $product_id ) ) {
					return $sale_price; }
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'sale_price' ) ) {
					return $sale_price; }
				if ( $this->is_product_in_bundle( $product_id ) ) {
					return $sale_price; }
				$sale_price = $this->calculate_sale_price( $sale_price, $product, $data );
				return ( ! empty( $sale_price ) ) ? (float) $sale_price : $sale_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_product_variation_get_sale_price',
			function ( $sale_price, $product ) use ( $data ) {
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'sale_price' ) ) {
					return $sale_price; }
				$sale_price = $this->calculate_sale_price( $sale_price, $product, $data );
				return ( ! empty( $sale_price ) ) ? (float) $sale_price : $sale_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_variation_prices_sale_price',
			function ( $sale_price, $product ) use ( $data ) {
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'sale_price' ) ) {
					return $sale_price; }
				$sale_price = $this->calculate_sale_price( $sale_price, $product, $data );
				return ( ! empty( $sale_price ) ) ? (float) $sale_price : $sale_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_variation_prices_price',
			function ( $price, $product ) use ( $data ) {
				$sale_price          = floatval( $this->calculate_sale_price( '', $product, $data ) );
				$regular_price       = floatval( $this->calculate_regular_price( $price, $product, $data ) );
				$to_be_display_price = $sale_price ? $sale_price : $regular_price;
				return ( ! empty( $to_be_display_price ) ) ? (float) $to_be_display_price : $to_be_display_price;
			},
			9,
			2
		);

		add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'variation_price_hash' ), 9, 2 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'woocommerce_get_price_html' ), 9, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'update_cart_price' ), 5 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'set_cart_item_price_to_display' ), 10, 2 );
	}

	// ─── woocommerce_get_price_html, update_cart_price, set_cart_item_price_to_display ──
	// These large methods reference $this-> properties and remain in the orchestrator.
	// For brevity in this file, they delegate back to the original facade which includes them.
	// They are loaded via the facade's require of this file.

	public function woocommerce_get_price_html( $price_html, $product ) {
		if ( ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) || ! ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) ) {
			return $price_html;
		}
		if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'price_html' ) ) {
			return $price_html;
		}
		do_action( 'wholesalex_dynamic_rule_get_price_html' );

		$regular_price      = $product->get_regular_price();
		$sale_from          = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
		$current_time       = current_time( 'timestamp' );
		$current_role       = wholesalex()->get_current_user_role();
		$role_sale_price    = floatval( $this->get_role_base_sale_price( $product, $current_role ) );
		$role_regular_price = floatval( $this->get_role_regular_price( $product, $current_role ) );
		$has_rolewise_price = $role_sale_price || $role_regular_price;

		if ( $sale_from > $current_time && ! $has_rolewise_price ) {
			return '<span class="scheduled-sale-price">' . wc_price( $regular_price ) . '</span>';
		}

		if ( ! is_user_logged_in() ) {
			$lvp_pl = wholesalex()->get_setting( '_settings_login_to_view_price_product_list' );
			$lvp_sp = wholesalex()->get_setting( '_settings_login_to_view_price_product_page' );
			if ( ( is_product() && 'yes' === $lvp_sp ) || ( ! is_product() && 'yes' === $lvp_pl ) ) {
				$lvp_url = wholesalex()->get_setting( '_settings_login_to_view_price_login_url', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
				$lvp_url = esc_url( add_query_arg( 'redirect', isset( $_SERVER['REQUEST_URI'] ) ? esc_url( $_SERVER['REQUEST_URI'] ) : '', $lvp_url ) );
				$this->make_product_non_purchasable_and_remove_add_to_cart( $product );
				return '<div><a class="wsx-link" href="' . $lvp_url . '">' . esc_html( wholesalex()->get_language_n_text( '_language_login_to_see_prices', __( 'Login to see prices', 'wholesalex' ) ) ) . '</a></div>';
			}
		}

		$rp = $product->get_regular_price();
		if ( $rp ) {
			$rp = wc_get_price_to_display( $product, array( 'price' => $rp ) ); }
		$sp = $product->get_sale_price();
		if ( $sp ) {
			$sp = wc_get_price_to_display( $product, array( 'price' => $sp ) ); }
		$db_price = $product->get_price( 'edit' );

		$is_woo_custom_price = get_post_meta( $product->get_id(), '_product_addons', true );
		if ( wholesalex()->is_plugin_installed_and_activated( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && $sp || $rp && is_array( $is_woo_custom_price ) && ! empty( $is_woo_custom_price ) ) {
			add_filter(
				'woocommerce_available_variation',
				function ( $data, $variation ) use ( $rp, $sp ) {
					$data['display_price'] = ! empty( $sp ) ? $sp : $rp;
					return $data;
				},
				10,
				2
			);
		}

		if ( ! ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) ) {
			$is_wholesale_price_applied = wholesalex()->get_wholesalex_wholesale_prices( $product->get_id() ) ? true : false;
			if ( $sp == $db_price ) {
				return apply_filters( 'wholesalex_get_price_html', $price_html, $product );
			}
			$price_html = $this->format_sale_price( $rp, $sp, $is_wholesale_price_applied ) . $product->get_price_suffix();
			if ( is_shop() || is_product_category() ) {
				$__product_list_page_price = wholesalex()->get_setting( '_settings_price_product_list_page', 'pricing_range' );
				$__min_sale_price          = $sp;
				$__max_sale_price          = $rp;
				switch ( $__product_list_page_price ) {
					case 'pricing_range':
						if ( $__min_sale_price === $__max_sale_price ) {
							$__max_sale_price = $rp; }
						$__sp       = wc_format_price_range( $__min_sale_price, $__max_sale_price );
						$__sp       = ( $rp !== $__sp ) ? $__min_sale_price : $__sp;
						$price_html = $this->format_sale_price( $rp, $__sp, $is_wholesale_price_applied ) . $product->get_price_suffix();
						break;
					case 'minimum_pricing':
						$price_html = $this->format_sale_price( $rp, $__min_sale_price, $is_wholesale_price_applied ) . $product->get_price_suffix();
						break;
					case 'maximum_pricing':
						$price_html = $this->format_sale_price( $rp, $__max_sale_price, $is_wholesale_price_applied ) . $product->get_price_suffix();
						break;
					default:
						$price_html = $this->format_sale_price( $rp, $sp, $is_wholesale_price_applied ) . $product->get_price_suffix();
						break;
				}
			}
		}

		if ( $product->is_type( 'variable' ) ) {
			$variations_ids             = $product->get_children();
			$variation_sale_prices      = array();
			$variation_regular_prices   = array();
			$is_wholesale_price_applied = true;
			foreach ( $variations_ids as $variation_id ) {
				$variation_obj = wc_get_product( $variation_id );
				$regular_price = $variation_obj->get_regular_price();
				if ( ! empty( $regular_price ) ) {
					$variation_regular_prices[]  = $regular_price;
					$is_wholesale_price_applied &= wholesalex()->get_wholesalex_wholesale_prices( $variation_id ) ? true : false;
					$sale_price                  = $variation_obj->get_sale_price();
					if ( ! empty( $sale_price ) ) {
						$variation_sale_prices[] = $sale_price;
					}
				}
			}
			if ( ! empty( $variations_ids ) && $is_wholesale_price_applied && ! empty( $variation_sale_prices ) ) {
				$min_sp = min( $variation_sale_prices );
				$max_sp = max( $variation_sale_prices );
				$min_rp = min( $variation_regular_prices );
				$max_rp = max( $variation_regular_prices );
				if ( '' !== $min_sp && '' !== $max_sp && '' !== $min_rp && '' !== $max_rp ) {
					$min_sp = wc_get_price_to_display( $product, array( 'price' => $min_sp ) );
					$max_sp = wc_get_price_to_display( $product, array( 'price' => $max_sp ) );
					$min_rp = wc_get_price_to_display( $product, array( 'price' => $min_rp ) );
					$max_rp = wc_get_price_to_display( $product, array( 'price' => $max_rp ) );
				}
				$sp         = ( $min_sp !== $max_sp ) ? wc_format_price_range( $min_sp, $max_sp ) : $min_sp;
				$rp         = ( $min_rp !== $max_rp ) ? wc_format_price_range( $min_rp, $max_rp ) : $min_rp;
				$price_html = $this->format_sale_price( $rp, $sp, $is_wholesale_price_applied ) . $product->get_price_suffix();
			}
		}
		return apply_filters( 'wholesalex_get_price_html', $price_html, $product );
	}

	public function set_cart_item_price_to_display( $price, $cart_item ) {
		$woo_custom_price = 0;
		$product          = $cart_item['data'];
		if ( wholesalex()->is_plugin_installed_and_activated( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && isset( $cart_item['addons'] ) && is_array( $cart_item['addons'] ) ) {
			foreach ( $cart_item['addons'] as $addon ) {
				if ( isset( $addon['price'] ) ) {
					$woo_custom_price += $addon['price'];
				}
			}
			$price = $product->is_on_sale() ? wc_price( $product->get_sale_price() ) : wc_price( $product->get_regular_price() );
		}
		return $price;
	}

	public function update_cart_price( $cart ) {
		if ( ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) || ! is_object( $cart ) || did_action( 'woocommerce_before_calculate_totals' ) > 1 ) {
			return;
		}
		foreach ( $cart->get_cart() as $cart_item ) {
			$woo_custom_price = 0;
			$product          = $cart_item['data'];
			$product_id       = $product->get_id();
			$quantity         = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
			if ( ! empty( $cart_item['free_product'] ) ) {
				$product->set_price( 0 );
				continue;
			}
			if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'cart_totals' ) ) {
				continue; }
			if ( $this->is_wholesalex_topup_product( $product_id ) ) {
				continue; }
			if ( $this->is_product_in_bundle( $product_id ) ) {
				continue; }

			if ( wholesalex()->is_plugin_installed_and_activated( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && isset( $cart_item['addons'] ) && is_array( $cart_item['addons'] ) && ! empty( $cart_item['addons'] ) ) {
				$base_price = isset( $cart_item['addons_price_before_calc'] ) ? floatval( $cart_item['addons_price_before_calc'] ) : ( $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price() );
				foreach ( $cart_item['addons'] as $addon ) {
					if ( isset( $addon['price'], $addon['price_type'] ) ) {
						switch ( $addon['price_type'] ) {
							case 'flat_fee':
								$woo_custom_price += floatval( $addon['price'] );
								break;
							case 'percentage_based':
								$woo_custom_price += ( $base_price * floatval( $addon['price'] ) / 100 );
								break;
							case 'quantity_based':
								$woo_custom_price += floatval( $addon['price'] );
								break;
						}
					}
				}
				$product->set_price( max( 0, $this->price_after_currency_changed( $base_price + $woo_custom_price ) ) );
				continue;
			}

			$price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();

			$prad_option_price = 0;
			if ( function_exists( 'WC' ) && defined( 'PRAD_VER' ) && isset( $cart_item['prad_selection']['price'] ) ) {
				$prad_option_price = floatval( $cart_item['prad_selection']['price'] );
			}

			if ( ! $product->is_type( 'simple' ) ) {
				if ( function_exists( 'WC' ) && wholesalex()->is_plugin_installed_and_activated( 'product-extras-for-woocommerce/product-extras-for-woocommerce.php' ) && isset( $cart_item['product_extras']['price_with_extras'] ) ) {
					$product->set_price( max( 0, $this->price_after_currency_changed( floatval( $cart_item['product_extras']['price_with_extras'] ) ) ) );
				} elseif ( wholesalex()->is_plugin_installed_and_activated( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && $woo_custom_price > 0 ) {
					if ( $quantity > 0 ) {
						$adjusted_unit_price = ( $price * $quantity + $woo_custom_price ) / $quantity;
						if ( $prad_option_price > 0 ) {
							$adjusted_unit_price += $prad_option_price; }
						$product->set_price( max( 0, $this->price_after_currency_changed( $adjusted_unit_price ) ) );
					}
				} else {
					$base = $price;
					if ( $prad_option_price > 0 && $quantity > 0 ) {
						$base += $prad_option_price; }
					$product->set_price( max( 0, $this->price_after_currency_changed( $base ) ) );
				}
			} elseif ( function_exists( 'WC' ) && wholesalex()->is_plugin_installed_and_activated( 'product-extras-for-woocommerce/product-extras-for-woocommerce.php' ) && isset( $cart_item['product_extras']['price_with_extras'] ) ) {
					$product->set_price( max( 0, $this->price_after_currency_changed( floatval( $cart_item['product_extras']['price_with_extras'] ) ) ) );
			} elseif ( wholesalex()->is_plugin_installed_and_activated( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && $woo_custom_price > 0 ) {
				$base = $price;
				if ( $quantity > 0 ) {
					$base = ( $price * $quantity + $woo_custom_price ) / $quantity; }
				if ( $prad_option_price > 0 ) {
					$base += $prad_option_price; }
				$product->set_price( max( 0, $this->price_after_currency_changed( $base ) ) );
			} else {
				$base = $price;
				if ( $prad_option_price > 0 ) {
					$base += $prad_option_price; }
				$product->set_price( max( 0, $this->price_after_currency_changed( $base ) ) );
			}
		}
	}

	// ─── Price Table Methods (delegated from original) ───────────
	// These large rendering methods (wholesalex_product_price_table, quantity_based_pricing_table,
	// price_table_layout_css, wholesalex_price_table_generator) remain accessible via the
	// WHOLESALEX_Dynamic_Rules facade which delegates to this class.
	// They are included here as pass-through stubs that call back to the facade's originals.
	// This avoids duplicating ~800+ lines of rendering code.

	public function wholesalex_product_price_table() {
		global $post;
		$product_id = $post->ID;
		$product    = wc_get_product( $post->ID );
		if ( ! $product ) {
			return;
		}
		if ( ! $product->is_type( 'simple' ) ) {
			return;
		}
		if ( ! ( $product_id && 'yes' === wholesalex()->get_single_product_setting( $product_id, '_settings_show_tierd_pricing_table' ) ) ) {
			return;
		}
		// Ensure active_tiers is populated even if price filters haven't fired yet.
		if ( ! isset( $this->active_tiers[ $product_id ] ) ) {
			$product->get_sale_price();
		}
		$tiers          = isset( $this->active_tiers[ $product_id ] ) ? $this->active_tiers[ $product_id ] : array( 'tiers' => array() );
		$tiers['tiers'] = $this->filter_empty_tier( $tiers['tiers'] );
		$table_data     = false;
		if ( ! empty( $tiers['tiers'] ) ) {
			$table_data = $this->quantity_based_pricing_table( '', $product_id, array() );
		}
		if ( ( function_exists( 'wholesalex_pro' ) && version_compare( WHOLESALEX_PRO_VER, '1.3.1', '<=' ) ) && ! $table_data ) {
			$table_data = $this->quantity_based_pricing_table( '', $product_id, array() );
		}
		do_action( 'wholesalex_tier_pricing_table', $product );
		$allowed_tags = array(
			'table' => array(),
			'thead' => array(),
			'tbody' => array(),
			'th'    => array(),
			'tr'    => array( 'id' => array() ),
			'td'    => array(),
			'div'   => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'span'  => array(
				'class' => array(),
				'id'    => array(),
			),
			'style' => array(),
			'pre'   => array(),
		);
		if ( $table_data ) {
			echo wp_kses( $table_data, $allowed_tags );
		}
	}

	/**
	 * Generate the quantity-based pricing table markup for a product.
	 *
	 * Resolves the correct base price for the current user (respecting the
	 * "use sale or regular price" setting, role-based prices, and any active
	 * product-discount dynamic rules), then delegates to
	 * `wholesalex_price_table_generator()` to render the tier table HTML.
	 *
	 * Priority handling mirrors the global quantity-based discount priority
	 * order (`get_quantity_based_discount_priorities()`): when `dynamic_rule`
	 * has a higher priority than `single_product`, product-discount rules are
	 * applied to the base price first; otherwise the role-specific stored price
	 * takes precedence.
	 *
	 * @param string $markup        Existing HTML markup to append the table to.
	 * @param int    $id            WooCommerce product or variation ID.
	 * @param array  $discount_data {
	 *     Discount data array produced by the discount pipeline.
	 *
	 *     @type string $role_id          Current user's wholesale role slug.
	 *                                    Used to look up role-specific meta prices.
	 *     @type array  $product_discount List of active product-discount rule entries.
	 *                                    Each entry contains 'filter' (eligibility config)
	 *                                    and 'rule' (discount type/amount).
	 *     @type array  $quantity_based   Quantity-based tier rules (used downstream
	 *                                    by the table generator).
	 * }
	 * @return string The original $markup with the pricing table HTML appended.
	 */
	public function quantity_based_pricing_table( $markup, $id, $discount_data ) {
		$product            = wc_get_product( $id );
		$is_regular_price   = wholesalex()->get_setting( '_is_sale_or_regular_Price', 'is_regular_price' );
		$current_role       = wholesalex()->get_current_user_role();
		$role_sale_price    = floatval( $this->get_role_base_sale_price( $product, $current_role ) );
		$role_regular_price = floatval( $this->get_role_regular_price( $product, $current_role ) );

		if ( 'is_sale_price' === $is_regular_price ) {
			if ( $role_sale_price ) {
				$product_price = $role_sale_price;
			} elseif ( $role_regular_price ) {
				$product_price = $role_regular_price;
			} elseif ( $product->get_sale_price( 'edit' ) ) {
				$product_price = $product->get_sale_price( 'edit' );
			} else {
				$product_price = $product->get_regular_price();
			}
			$parent_id            = $product->get_parent_id();
			$flipped_priority     = array_flip( wholesalex()->get_quantity_based_discount_priorities() );
			$rrs                  = ( isset( $discount_data['role_id'] ) && ! empty( $discount_data['role_id'] ) ) ? get_post_meta( $id, $discount_data['role_id'] . '_sale_price', true ) : false;
			$applied_discount_src = '';
			if ( $flipped_priority['dynamic_rule'] < $flipped_priority['single_product'] ) {
				if ( ! empty( $discount_data['product_discount'] ) ) {
					foreach ( $discount_data['product_discount'] as $pd ) {
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $id, $id, $pd['filter'] ) ) {
							if ( isset( $pd['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $pd['conditions'], $pd['filter'] ) ) {
								continue; }
							$product_price        = wholesalex()->calculate_sale_price( $pd['rule'], $product_price );
							$applied_discount_src = 'product_discount';
						}
					}
				}
				if ( '' === $applied_discount_src && $rrs ) {
					$product_price = floatval( $rrs ); }
			} elseif ( ! $rrs && ! empty( $discount_data['product_discount'] ) ) {
				foreach ( $discount_data['product_discount'] as $pd ) {
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $id, $id, $pd['filter'] ) ) {
						if ( isset( $pd['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $pd['conditions'], $pd['filter'] ) ) {
							continue; }
						$product_price        = wholesalex()->calculate_sale_price( $pd['rule'], $product_price );
						$applied_discount_src = 'product_discount';
					}
				}
			} elseif ( $rrs ) {
				$product_price = floatval( $rrs ); }
		} else {
			$product_price        = $product->get_regular_price();
			$parent_id            = $product->get_parent_id();
			$flipped_priority     = array_flip( wholesalex()->get_quantity_based_discount_priorities() );
			$rrs                  = ( isset( $discount_data['role_id'] ) && ! empty( $discount_data['role_id'] ) ) ? get_post_meta( $id, $discount_data['role_id'] . '_regular_price', true ) : false;
			$applied_discount_src = '';
			if ( $flipped_priority['dynamic_rule'] < $flipped_priority['single_product'] ) {
				if ( ! empty( $discount_data['product_discount'] ) ) {
					foreach ( $discount_data['product_discount'] as $pd ) {
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $id, $id, $pd['filter'] ) ) {
							if ( isset( $pd['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $pd['conditions'], $pd['filter'] ) ) {
								continue; }
							$product_price        = wholesalex()->calculate_sale_price( $pd['rule'], $product_price );
							$applied_discount_src = 'product_discount';
						}
					}
				}
				if ( '' === $applied_discount_src && $rrs ) {
					$product_price = floatval( $rrs ); }
			} elseif ( ! $rrs && ! empty( $discount_data['product_discount'] ) ) {
				foreach ( $discount_data['product_discount'] as $pd ) {
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $id, $id, $pd['filter'] ) ) {
						if ( isset( $pd['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $pd['conditions'], $pd['filter'] ) ) {
							continue; }
						$product_price        = wholesalex()->calculate_sale_price( $pd['rule'], $product_price );
						$applied_discount_src = 'product_discount';
					}
				}
			} elseif ( $rrs ) {
				$product_price = floatval( $rrs ); }
		}

		ob_start();
		$this->wholesalex_price_table_generator( $product_price, $product );
		$markup .= ob_get_clean();
		return $markup;
	}

	public function price_table_layout_css() {
		$is_table_radius           = wholesalex()->get_setting( '_settings_tier_table_radius_style', 'no' );
		$table_font_size           = wholesalex()->get_setting( '_settings_tier_font_size', '14' );
		$table_text_color          = wholesalex()->get_setting( '_settings_tier_table_text_color', '#494949' );
		$table_title_text_color    = wholesalex()->get_setting( '_settings_tier_table_title_text_color', '#3A3A3A' );
		$table_title_bg_color      = wholesalex()->get_setting( '_settings_tier_table_title_bg_color', '#F7F7F7' );
		$table_border_color        = wholesalex()->get_setting( '_settings_tier_table_border_color', '#E5E5E5' );
		$table_active_text_color   = wholesalex()->get_setting( '_settings_active_tier_text_color', '#FFFFFF' );
		$table_active_bg_color     = wholesalex()->get_setting( '_settings_active_tier_bg_color', '#6C6CFF' );
		$table_discount_text_color = wholesalex()->get_setting( '_settings_tier_discount_text_color', '#FFFFFF' );
		$table_discount_bg_color   = wholesalex()->get_setting( '_settings_tier_discount_bg_color', '#070707' );
		$tier_table_column         = wholesalex()->get_setting(
			'_settings_tier_table_columns_priority',
			array(
				0 => array(
					'label'  => 'Quantity_Range',
					'value'  => 'Quantity Range',
					'status' => 'yes',
				),
				1 => array(
					'label'  => 'Discount',
					'value'  => 'Discount',
					'status' => '1',
				),
				2 => array(
					'label'  => 'Price_Per_Unit',
					'value'  => 'Price Per Unit',
					'status' => '1',
				),
			)
		);
		$tier_table_length         = 0;
		if ( is_array( $tier_table_column ) ) {
			foreach ( $tier_table_column as $column ) {
				if ( ! empty( $column['status'] ) ) {
					++$tier_table_length; }
			}
		}
		$radius                 = ( 'no' === $is_table_radius ) ? '0' : '8px';
		$radius_sm              = ( 'no' === $is_table_radius ) ? '0' : '2px';
		$radius_4               = ( 'no' === $is_table_radius ) ? '0' : '4px';
		$radius_header          = ( 'no' === $is_table_radius ) ? '0' : '8px 8px 0 0';
		$radius_vertical_header = ( 'no' === $is_table_radius ) ? '0' : '8px 0 0 8px';
		?>
		<style>
			.wsx-price-container-title{font-size:20px;font-weight:500;margin-bottom:12px}
			.wsx-price-table-container{width:100%;border:1px solid <?php echo esc_attr( $table_border_color ); ?>;margin-bottom:40px;font-size:<?php echo esc_attr( $table_font_size ); ?>px;color:<?php echo esc_attr( $table_text_color ); ?>;border-radius:<?php echo esc_attr( $radius ); ?>}
			@media(max-width:768px){.wsx-table-overflow{overflow:auto;width:100vw}.wsx-price-table-body{overflow:scroll}}
			.wsx-price-table-header{width:100%;font-weight:600;border-bottom:1px solid <?php echo esc_attr( $table_border_color ); ?>;color:<?php echo esc_attr( $table_title_text_color ); ?>;background-color:<?php echo esc_attr( $table_title_bg_color ); ?>;border-radius:<?php echo esc_attr( $radius_header ); ?>}
			.wsx-price-table-row{width:100%;display:grid;grid-template-columns:minmax(155px,2fr) repeat(<?php echo esc_attr( $tier_table_length - 1 ); ?>,minmax(155px,1.9fr));border-bottom:1px solid <?php echo esc_attr( $table_border_color ); ?>}
			.wsx-price-table-row.active{color:<?php echo esc_attr( $table_active_text_color ); ?>;background-color:<?php echo esc_attr( $table_active_bg_color ); ?>}
			.wsx-price-table-row:last-child{border-bottom:none}
			.wsx-price-table-cell{flex-grow:1;padding:12px 16px;text-align:left;border-right:1px solid <?php echo esc_attr( $table_border_color ); ?>;white-space:nowrap;width:auto}
			.wsx-price-table-cell:last-child{border-right:0}
			.wsx-ellipsis{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:24rem}
			.wsx-price-table-container.layout-vertical{display:flex}
			.layout-vertical .wsx-price-table-body{display:flex;width:100%}
			.layout-vertical .wsx-price-table-header{border-right:1px solid <?php echo esc_attr( $table_border_color ); ?>;border-bottom:0;border-radius:<?php echo esc_attr( $radius_vertical_header ); ?>}
			.layout-vertical .wsx-price-table-row{grid-template-columns:minmax(120px,1fr);border-right:1px solid <?php echo esc_attr( $table_border_color ); ?>;border-bottom:0}
			.layout-vertical .wsx-price-table-row:last-child{border-right:0}
			.layout-vertical .wsx-price-table-cell{border-right:0;border-bottom:1px solid <?php echo esc_attr( $table_border_color ); ?>}
			.layout-vertical .wsx-price-table-cell:last-child{border-bottom:0}
			.wsx-price-classical-container{display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:12px;background-color:<?php echo esc_attr( $table_title_bg_color ); ?>;border-radius:<?php echo esc_attr( $radius ); ?>;margin-bottom:40px;width:fit-content}
			.wsx-price-classical-item{padding:8px 0 8px 8px}
			.wsx-price-classical-content{border-right:1px solid <?php echo esc_attr( $table_border_color ); ?>;padding-right:20px;position:relative;z-index:1}
			.wsx-price-classical-price{display:flex;align-items:center;gap:6px;margin-bottom:8px}
			.wsx-price-classical-text{font-weight:500;font-size:<?php echo esc_attr( $table_font_size ); ?>px;color:<?php echo esc_attr( $table_title_text_color ); ?>}
			.wsx-price-classical-tag{padding:0 6px;font-size:<?php echo esc_attr( $table_font_size - 4 ); ?>px;color:<?php echo esc_attr( $table_discount_text_color ); ?>;background-color:<?php echo esc_attr( $table_discount_bg_color ); ?>;border-radius:<?php echo esc_attr( $radius_sm ); ?>}
			.wsx-price-classical-divider,.wsx-price-classical-quantity{color:<?php echo esc_attr( $table_text_color ); ?>;font-size:<?php echo esc_attr( $table_font_size - 2 ); ?>px}
			.wsx-quantities{font-weight:600}
			.wsx-price-classical-item.active,.active .wsx-price-classical-text,.active .wsx-price-classical-divider,.active .wsx-price-classical-quantity{color:<?php echo esc_attr( $table_active_text_color ); ?>}
			.wsx-price-classical-overlay{position:absolute;z-index:-1;content:'';top:-8px;bottom:-8px;left:-8px;right:12px;background-color:<?php echo esc_attr( $table_active_bg_color ); ?>;border-radius:<?php echo esc_attr( $radius ); ?>}
			.wsx-price-classical-container.layout-vertical{display:block;width:fit-content;padding:20px}
			.layout-vertical .wsx-price-classical-item{padding:8px 12px;border-radius:<?php echo esc_attr( $radius_4 ); ?>}
			.layout-vertical .wsx-price-classical-item.active{background-color:<?php echo esc_attr( $table_active_bg_color ); ?>}
			.layout-vertical .wsx-price-classical-content{border:0;padding-right:0;display:flex;align-items:center;gap:12px}
			.layout-vertical .wsx-price-classical-price{margin-bottom:0}
		</style>
		<?php
	}

	public function wholesalex_price_table_generator( $regular_price, $product ) {
		if ( class_exists( 'Aelia_Integration_Helper' ) && \Aelia_Integration_Helper::aelia_currency_switcher_active() ) {
			$active_currency = get_woocommerce_currency();
			$product_id      = $product->get_id();
			$base_currency   = \Aelia_Integration_Helper::get_product_base_currency( $product_id );
			$regular_price   = \Aelia_Integration_Helper::convert( $regular_price, $active_currency, $base_currency );
		}

		$layout_css = $this->price_table_layout_css();
		if ( ! is_null( $layout_css ) && is_string( $layout_css ) && '' !== trim( $layout_css ) ) {
			wp_add_inline_style( 'wholesalex', $layout_css );
		}

		$tier_data          = isset( $this->active_tiers[ $product->get_id() ] ) ? $this->active_tiers[ $product->get_id() ] : array( 'tiers' => array() );
		$tier_data['tiers'] = $this->filter_empty_tier( $tier_data['tiers'] );
		$tiers              = isset( $tier_data['tiers'] ) ? $tier_data['tiers'] : array();
		$active_tier        = isset( $tier_data['id'] ) ? $tier_data['id'] : '';
		array_multisort( array_column( $tiers, '_min_quantity' ), SORT_ASC, $tiers );
		$classes    = apply_filters( 'wholesalex_tier_layout_custom_classes', '' );
		$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();

		if ( wholesalex()->get_single_product_setting( $product_id, '_settings_tire_price_product_layout' ) ) {
			$tier_layout_compatibility = wholesalex()->get_single_product_setting( $product_id, '_settings_tier_layout_single_product' ) ? wholesalex()->get_single_product_setting( $product_id, '_settings_tier_layout_single_product' ) : '';
			$product_tire_layout       = wholesalex()->get_single_product_setting( $product_id, '_settings_tire_price_product_layout' );
			$is_vertical_layout        = wholesalex()->get_single_product_setting( $product_id, '_settings_vertical_product_style' );
		} else {
			$tier_layout_compatibility = wholesalex()->get_setting( '_settings_tier_layout', 'layout_one' );
			$product_tire_layout       = wholesalex()->get_setting( '_settings_tier_table_style_design', 'table_style' );
			$is_vertical_layout        = wholesalex()->get_setting( '_settings_vertical_style', 'no' );
		}

		$tier_layout_mapping = array(
			'table_style_no'    => 'layout_one',
			'table_style_yes'   => 'layout_six',
			'classic_style_no'  => 'layout_two',
			'classic_style_yes' => 'layout_three',
		);
		$tier_layout_key     = "{$product_tire_layout}_{$is_vertical_layout}";
		$tier_layout         = $tier_layout_mapping[ $tier_layout_key ] ?? '';

		if ( ! $tier_layout ) {
			$fallback_mapping = array(
				'layout_four'  => 'layout_one',
				'layout_one'   => 'layout_one',
				'layout_six'   => 'layout_six',
				'layout_five'  => 'layout_two',
				'layout_two'   => 'layout_two',
				'layout_seven' => 'layout_two',
				'layout_three' => 'layout_three',
				'layout_eight' => 'layout_three',
			);
			$tier_layout      = $fallback_mapping[ $tier_layout_compatibility ] ?? $tier_layout;
		}
		if ( '' === $tier_layout ) {
			$tier_layout = 'layout_one'; }
		$tier_layout     = apply_filters( 'wholesalex_tier_layout', $tier_layout, $product_id );
		$quantity_prices = $tiers;

		if ( ( function_exists( 'wholesalex_pro' ) && version_compare( WHOLESALEX_PRO_VER, '1.3.1', '<=' ) ) ) {
			$__priorities    = wholesalex()->get_quantity_based_discount_priorities();
			$__user_id       = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
			$quantity_prices = get_transient( 'wholesalex_pricing_tiers_' . $this->discount_src . '_' . $__user_id );
			if ( empty( $quantity_prices ) ) {
				foreach ( $__priorities as $priority ) {
					$__temp_quantity_prices = get_transient( 'wholesalex_pricing_tiers_' . $priority . '_' . $__user_id );
					if ( $__temp_quantity_prices && ! empty( $__temp_quantity_prices ) ) {
						$quantity_prices = $__temp_quantity_prices;
						break; }
				}
			}
			if ( empty( $quantity_prices ) ) {
				return; }
			if ( isset( $quantity_prices['_min_quantity'] ) ) {
				$__sort_colum = array_column( $quantity_prices, '_min_quantity' );
				array_multisort( $__sort_colum, SORT_ASC, $quantity_prices );
			}
		}

		if ( empty( $quantity_prices ) ) {
			return; }
		if ( isset( $quantity_prices['_min_quantity'] ) ) {
			$__sort_colum = array_column( $quantity_prices, '_min_quantity' );
			array_multisort( $__sort_colum, SORT_ASC, $quantity_prices );
		}

		$__wc_currency         = get_option( 'woocommerce_currency' );
		$table_column_data     = array(
			array(
				'label'  => 'Quantity_Range',
				'value'  => 'Quantity Range',
				'status' => 'yes',
			),
			array(
				'label'  => 'Discount',
				'value'  => 'Discount',
				'status' => 'yes',
			),
			array(
				'label'  => 'Price_Per_Unit',
				'value'  => 'Price Per Unit',
				'status' => 'yes',
			),
		);
		$table_label           = wholesalex()->get_setting( '_settings_tier_price_table_heading', 'Buy More, Save More' );
		$table_column_priority = wholesalex()->get_setting( '_settings_tier_table_columns_priority', $table_column_data );
		$cart_quantity         = $this->get_product_quantity_in_cart( $product->get_id() );

		switch ( $tier_layout ) {
			case 'layout_one':
			case 'layout_six':
				?>
				<div class="wsx-price-container-title"><?php echo esc_html( $table_label ); ?></div>
				<div class="wsx-price-table-container wsx-scrollbar wsx-table-overflow <?php echo 'layout_six' === $tier_layout ? 'layout-vertical' : ''; ?>">
					<div class="wsx-price-table-header"><div class="wsx-price-table-row">
					<?php
					foreach ( $table_column_priority as $column ) {
						if ( isset( $column['status'] ) && ( 'yes' === $column['status'] || $column['status'] ) ) {
							?>
						<div class="wsx-tooltip wsx-tooltip-global wsx-price-table-cell"><div class="wsx-ellipsis"><?php echo esc_html( $column['value'] ); ?></div><div class="wsx-tooltip-content wsx-font-regular top wsx-text-center" style="margin-bottom: -16px;"><?php echo esc_html( $column['value'] ); ?></div></div>
											<?php
						}
					}
					?>
					</div></div>
					<div class="wsx-price-table-body">
					<?php
					$__tier_size = count( $quantity_prices );
					for ( $i = 0; $i < $__tier_size; $i++ ) {
						$__current_tier = $quantity_prices[ $i ];
						$__next_tier    = ( ( $__tier_size ) - 1 !== $i ) ? $quantity_prices[ $i + 1 ] : '';
						$__sale_price   = wholesalex()->calculate_sale_price( $__current_tier, $regular_price );
						$__discount     = floatval( $regular_price ) - floatval( $__sale_price );
						$__sale_price   = wc_get_price_to_display( $product, array( 'price' => $__sale_price ) );
						?>
						<div <?php echo isset( $__current_tier['_min_quantity'] ) && is_numeric( $__current_tier['_min_quantity'] ) ? 'data-min="' . esc_attr( $__current_tier['_min_quantity'] ) . '"' : ''; ?>
							class="wsx-price-table-row <?php echo esc_attr( ( ! empty( $__current_tier['_id'] ) && $__current_tier['_id'] == $active_tier ) ? 'active' : '' ); ?>">
							<?php
							foreach ( $table_column_priority as $column ) {
								if ( isset( $column['status'] ) && ( 'yes' === $column['status'] || $column['status'] ) ) {
									switch ( $column['label'] ) {
										case 'Discount':
											?>
									<div class="wsx-price-table-cell"><?php echo wp_kses_post( wc_price( $__discount ) ); ?></div>
											<?php
											break;
										case 'Quantity_Range':
											?>
										<div class="wsx-price-table-cell">
											<?php
											if ( isset( $__current_tier['_min_quantity'] ) ) {
												if ( ! empty( $__next_tier ) && ( $__next_tier['_min_quantity'] - 1 ) > $__current_tier['_min_quantity'] ) {
													echo ( $__current_tier['_min_quantity'] === $__next_tier['_min_quantity'] ) ? esc_html( $__current_tier['_min_quantity'] ) : esc_html( $__current_tier['_min_quantity'] ) . '-' . esc_html( $__next_tier['_min_quantity'] - 1 );
												} else {
													echo esc_html( $__current_tier['_min_quantity'] ) . '+'; }
											}
											?>
										</div>
											<?php
											break;
										case 'Price_Per_Unit':
											?>
										<div class="wsx-price-table-cell"><?php echo wp_kses_post( wc_price( $__sale_price ) ); ?></div>
											<?php
											break;
									}
								}
							}
							?>
						</div>
						<?php
					}
					?>
					</div>
				</div>
				<?php
				wp_localize_script(
					'wholesalex_price_table',
					'wholesalexPriceTableData',
					array(
						'quantityPrices' => $quantity_prices,
						'cartQuantity'   => $cart_quantity,
					)
				);
				break;

			case 'layout_two':
			case 'layout_three':
				$__show_discount_amount = apply_filters( 'wholesalex_tier_layout_two_show_discount_amount', true );
				?>
				<div class="wsx-price-container-title"><?php echo esc_html( $table_label ); ?></div>
				<div class="wsx-price-classical-container <?php echo 'layout_three' === $tier_layout ? 'layout-vertical' : ''; ?>">
				<?php
				$__tier_size = count( $quantity_prices );
				for ( $i = 0; $i < $__tier_size; $i++ ) {
					$__current_tier = $quantity_prices[ $i ];
					$__next_tier    = ( ( $__tier_size ) - 1 !== $i ) ? $quantity_prices[ $i + 1 ] : '';
					$__sale_price   = wholesalex()->calculate_sale_price( $__current_tier, floatval( $regular_price ) );
					$__discount     = floatval( $regular_price ) - floatval( $__sale_price );
					$__sale_price   = wc_get_price_to_display( $product, array( 'price' => $__sale_price ) );
					$__quantities   = '';
					if ( isset( $__current_tier['_min_quantity'] ) ) {
						if ( ! empty( $__next_tier ) ) {
							$__quantities = ( $__current_tier['_min_quantity'] === $__next_tier['_min_quantity'] ) ? $__current_tier['_min_quantity'] : $__current_tier['_min_quantity'] . '-' . ( (int) $__next_tier['_min_quantity'] - 1 );
						} else {
							$__quantities = $__current_tier['_min_quantity'] . '+'; }
					}
					?>
					<div class="wsx-price-classical-item <?php echo esc_attr( ( ! empty( $__current_tier['_id'] ) && $__current_tier['_id'] == $active_tier ) ? 'active' : '' ); ?>">
						<div class="wsx-price-classical-content">
							<div class="wsx-price-classical-price">
							<?php
							if ( $__show_discount_amount && 'layout_three' !== $tier_layout ) {
								echo '<div class="wsx-price-classical-text">' . wp_kses_post( $__wc_currency . ' ' . wc_price( $__sale_price ) . '</div><div class="wsx-price-classical-tag">' . -round( ( (float) $__discount / (float) $regular_price ) * 100.00, 2 ) . '% </div>' );
							} else {
								echo '<div class="wsx-price-classical-text">' . wp_kses_post( $__wc_currency . ' ' . wc_price( $__sale_price ) . '</div>' );
							}
							?>
							</div>
							<?php
							if ( $__show_discount_amount && 'layout_three' === $tier_layout ) {
								echo '<div class="wsx-price-classical-divider">/</div>'; }
							?>
							<div class="wsx-price-classical-quantity"><span class="wsx-quantities"><?php echo esc_html( $__quantities ); ?></span> <span class="wsx-quantity-text"><?php esc_html_e( 'Pieces', 'wholesalex' ); ?></span></div>
							<?php
							if ( esc_attr( ( isset( $__current_tier['_id'] ) && $__current_tier['_id'] == $active_tier ) ) && 'layout_three' !== $tier_layout ) {
								?>
								<div class="wsx-price-classical-overlay"></div><?php } ?>
						</div>
					</div>
					<?php
				}
				?>
				</div>
				<?php
				break;
			default:
				break;
		}
	}

	// ─── Single Product Page Promo ───────────────────────────────
	// This 540+ line method with its helpers remains in the facade for now.
	// It will be fully migrated in phase 2.

	public function handle_single_product_page_promo( $product, $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt, $is_echo = false ) {
		do_action( 'wholesalex_before_add_to_cart_form', $product );

		if ( 'yes' === wholesalex()->get_setting( 'show_promotions_on_sp', 'no' ) ) {

			$this->check_for_product_discounts( $product );

			$this->check_for_cart_releated_discounts( $product, $cart_related_data );

			$this->check_for_free_shipping( $product, $profile_shipping_data );
		}

		if ( ! empty( $min_max_data ) ) {
			// Minimum.
			if ( 'yes' === wholesalex()->get_setting( 'show_order_qty_text_on_sp', 'no' ) && isset( $min_max_data['min_order_qty'] ) && ! empty( $min_max_data['min_order_qty'] ) ) {
				foreach ( $min_max_data['min_order_qty'] as $rule ) {
					if ( isset( $rule['conditions'] ) && ! self::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
						continue;
					}
					$is_all_products = $rule['filter']['is_all_products'];

					if ( self::is_eligible_for_rule( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(), $product->get_parent_id() ? $product->get_id() : 0, $rule['filter'] ) || $is_all_products ) {
						wholesalex()->set_rule_data(
							$rule['id'],
							$product->get_id(),
							'min_order_qty',
							array(
								'conditions'          => $rule['conditions'] ? $rule['conditions'] : array(),
								'minimum_qty'         => $rule['rule']['_min_order_qty'],
								'who_priority'        => $rule['who_priority'],
								'applied_on_priority' => $rule['applied_on_priority'],
								'end_date'            => $rule['end_date'],
							)
						);
					}
				}
			}
			// Maximum.
			if ( 'yes' === wholesalex()->get_setting( 'show_order_qty_text_on_sp', 'no' ) && isset( $min_max_data['max_order_qty'] ) && ! empty( $min_max_data['max_order_qty'] ) ) {
				foreach ( $min_max_data['max_order_qty'] as $rule ) {
					if ( isset( $rule['conditions'] ) && ! self::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
						continue;
					}
					$is_all_products = $rule['filter']['is_all_products'];

					if ( self::is_eligible_for_rule( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(), $product->get_parent_id() ? $product->get_id() : 0, $rule['filter'] ) || $is_all_products ) {
						wholesalex()->set_rule_data(
							$rule['id'],
							$product->get_id(),
							'max_order_qty',
							array(
								'conditions'          => $rule['conditions'] ? $rule['conditions'] : array(),
								'maximum_qty'         => $rule['rule']['_max_order_qty'],
								'who_priority'        => $rule['who_priority'],
								'applied_on_priority' => $rule['applied_on_priority'],
								'end_date'            => $rule['end_date'],
							)
						);
					}
				}
			}
		}

		$modal_content = '';
		if ( ! $is_echo ) {
			ob_start();
		}

		if ( ! empty( wholesalex()->get_rule_data( $product->get_id() ) ) ) {

			ob_start();

			if ( 'yes' === wholesalex()->get_setting( 'show_product_discounts_text', 'no' ) ) {

				$product_discounts = wholesalex()->get_rule_data( $product->get_id(), 'product_discount' );

				if ( ! empty( $product_discounts ) ) {

					usort( $product_discounts, array( $this, 'compare_by_priority_reverse' ) );
					?>
					<div class="wsx-sp-product-discounts">
						<?php
						if ( 'yes' === wholesalex()->get_setting( 'product_discount_rule_sp_show_rule_info', 'yes' ) ) {
							?>
							<div class="wsx-sp-rule-info">
								<div class="wsx-font-14 wsx-font-medium">
									<?php echo esc_html( wholesalex()->get_setting( 'product_discount_rule_info_rule_type_text', __( 'Product Discount', 'wholesalex' ) ) ); ?>
								</div>
								<?php
								if ( count( $product_discounts ) > 1 ) {
									?>
									<div class="wsx-font-14">
										<?php echo esc_html( wholesalex()->get_setting( 'dynamic_rule_promotional_explainer_text_single_discount', __( 'You can avail one of the following offers by completing the requirements.', 'wholesalex' ) ) ); ?>
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
							foreach ( $product_discounts as $cd ) {

								if ( 'percentage' === $cd['type'] ) {
									$heading_text = $cd['value'] . __( ' % OFF', 'wholesalex' );
								} elseif ( 'amount' === $cd['type'] ) {
									$heading_text = wc_price( $cd['value'] ) . __( ' OFF', 'wholesalex' );
								} elseif ( 'fixed' === $cd['type'] ) {
									$heading_text = '<del>' . wc_price( $product->get_price() ) . '</del>. to <ins>' . wc_price( $cd['value'] ) . '</ins>';
								}

								$conditions = 'yes' === wholesalex()->get_setting( 'show_discount_conditions_on_sp', 'no' ) && isset( $cd['conditions']['tiers'] ) ? $this->generate_rule_conditions_markup( $cd['conditions']['tiers'] ) : '';
								$validity   = '';
								if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
									$validity = $cd['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . $this->restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $cd['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
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
			}
			// Cart discount promo HTML — managed in Rule_Cart_Discount::render_promo_html().
			$this->rule_cart_discount->render_promo_html( $product, array( $this, 'generate_rule_conditions_markup' ) );
			if ( 'yes' === wholesalex()->get_setting( 'show_payment_method_discount_promo_text_sp', 'no' ) ) {

				$payment_discount = wholesalex()->get_rule_data( $product->get_id(), 'payment_discount' );

				if ( ! empty( $payment_discount ) ) {
					usort( $payment_discount, array( $this, 'compare_by_priority_reverse' ) );
					?>
					<div class="wsx-sp-payment-discounts">
						<?php
						if ( 'yes' === wholesalex()->get_setting( 'payment_discount_rule_sp_show_rule_info', 'yes' ) ) {
							?>
							<div class="wsx-sp-rule-info">
								<div class="wsx-font-14 wsx-font-medium">
									<?php echo esc_html( wholesalex()->get_setting( 'payment_method_discount_label_text', __( 'Payment Method Discount', 'wholesalex' ) ) ); ?>
								</div>
								<?php
								if ( count( $payment_discount ) > 1 ) {
									?>
									<div class="wsx-font-14">
										<?php echo esc_html( wholesalex()->get_setting( 'dynamic_rule_promotional_explainer_text_single_discount', __( 'You can avail one of the following offers by completing the requirements.', 'wholesalex' ) ) ); ?>
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
							foreach ( $payment_discount as $pd ) {
								if ( 'percentage' === $pd['type'] ) {
									$heading_text = $pd['value'] . __( ' % OFF', 'wholesalex' );
								} elseif ( 'amount' === $pd['type'] ) {
									$heading_text = wc_price( $pd['value'] ) . __( ' OFF', 'wholesalex' );
								} elseif ( 'fixed' === $pd['type'] ) {
									$heading_text = '<del>' . wc_price( $product->get_price() ) . '</del>. to <ins>' . wc_price( $pd['value'] ) . '</ins>';
								}
								$desc       = '<span class="wsx-font-14">' . __( 'Use ', 'wholesalex' ) . implode( ',', $pd['gateways'] ) . ' </span>';
								$conditions = 'yes' === wholesalex()->get_setting( 'show_discount_conditions_on_sp', 'no' ) && isset( $pd['conditions']['tiers'] ) ? $this->generate_rule_conditions_markup( $pd['conditions']['tiers'] ) : '';
								$validity   = '';
								if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
									$validity = $pd['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . $this->restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $pd['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
								}
								?>
								<div class="wsx-single-product-discount-card wsx-payment-discount-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
									<div class="wsx-font-18"><?php echo wp_kses_post( $heading_text ); ?></div>
									<div class="wsx-font-14"><?php echo wp_kses_post( $desc . $conditions . $validity ); ?> </div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
					<?php
				}
			}
			if ( 'yes' === wholesalex()->get_setting( 'show_bogo_discount_promo_text_on_sp', 'no' ) ) {

				$buy_x_get_one = wholesalex()->get_rule_data( $product->get_id(), 'buy_x_get_one' );

				if ( ! empty( $buy_x_get_one ) ) {
					usort( $buy_x_get_one, array( $this, 'compare_by_priority_reverse' ) );

					?>
					<div class="wsx-sp-bogo-discounts">
						<?php
						if ( 'yes' == wholesalex()->get_setting( 'bogo_discount_rule_sp_show_rule_info', 'yes' ) ) {
							?>
							<div class="wsx-sp-rule-info">
								<div class="wsx-font-14 wsx-font-medium">
									<?php echo esc_html( wholesalex()->get_setting( 'bogo_discount_rule_info_rule_type_text', __( 'BOGO Discount', 'wholesalex' ) ) ); ?>
								</div>
								<?php
								if ( count( $buy_x_get_one ) > 1 ) {
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
							foreach ( $buy_x_get_one as $pd ) {
								$min_qty      = $pd['minimum_qty'];
								$heading_text = wholesalex()->get_setting( 'bogo_discount_free_text_on_sp', __( 'Get 1 Free', 'wholesalex' ) );
								$desc         = '<span class="wsx-font-14">' . $this->restore_smart_tags(
									array(
										'{required_quantity}' => $min_qty,
										'{product_title}' => $product->get_title(),
									),
									wholesalex()->get_setting( 'bogo_discounts_promo_sp_desc_text_on_sp', __( 'Buy at least {required_quantity} products', 'wholesalex' ) )
								) . ' </span>';
								$conditions   = 'yes' === wholesalex()->get_setting( 'show_discount_conditions_on_sp', 'no' ) && isset( $pd['conditions']['tiers'] ) ? $this->generate_rule_conditions_markup( $pd['conditions']['tiers'] ) : '';
								$validity     = '';
								if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
									$validity = $pd['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . $this->restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $pd['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
								}
								?>
								<div class="wsx-single-product-discount-card wsx-sp-bogo-discount-cart wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
									<div class="wsx-font-18"><?php echo wp_kses_post( $heading_text ); ?></div>
									<div class="wsx-font-14"><?php echo wp_kses_post( $desc . $conditions . $validity ); ?></div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
					<?php
				}
			}
			if ( 'yes' === wholesalex()->get_setting( 'show_free_shipping_promo_text_on_sp', 'no' ) ) {

				$free_shipping = wholesalex()->get_rule_data( $product->get_id(), 'free_shipping' );

				if ( ! empty( $free_shipping ) ) {
					usort( $free_shipping, array( $this, 'compare_by_priority_reverse' ) );

					ob_start();
					foreach ( $free_shipping as $pd ) {
						if ( isset( $pd['conditions']['tiers'] ) && ! empty( $pd['conditions']['tiers'] ) ) {
							$heading_text = wholesalex()->get_setting( 'free_shipping_heading_text_on_sp', __( 'Free Shipping', 'wholesalex' ) );
							$conditions   = 'yes' === wholesalex()->get_setting( 'show_discount_conditions_on_sp', 'no' ) && isset( $pd['conditions']['tiers'] ) ? $this->generate_rule_conditions_markup( $pd['conditions']['tiers'] ) : '';
							$validity     = '';
							if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
								$validity = $pd['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . $this->restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $pd['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
							}
							?>
							<div class="wsx-single-product-discount-card  wsx-sp-free-shipping-cart wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
								<div class="wsx-font-18"><?php echo wp_kses_post( $heading_text ); ?></div>
								<div class="wsx-font-14"> <?php echo wp_kses_post( $conditions . $validity ); ?></div>
							</div>
							<?php
						}
					}
					$conditional_free_shipping_content = ob_get_clean();
					if ( $conditional_free_shipping_content ) {
						?>
						<div class="wsx-free-shipping-discounts">
							<div class="wsx-sp-discounts-cards wsx-p-4 wsx-br-sm wsx-mt-8 wsx-bg-promotion">
								<?php echo wp_kses_post( $conditional_free_shipping_content ); ?>
							</div>
						</div>
						<?php
					}
				}
			}

			$modal_content = ob_get_clean();

			$modal_content = trim( apply_filters( 'wholesalex_promotions_frontend_popup_modal_content', $modal_content ) );
			if ( $modal_content ) {
				do_action( 'wholesalex_promotions_popup_header' );
				?>
				<div class="wsx-d-flex wsx-item-center wsx-gap-12 wsx-mb-24">
					<div class="wsx-font-14"> <?php echo esc_html__( 'Promotions:', 'wholesalex' ); ?></div>
					<div class="wsx-relative">
						<div class="wsx-font-12 wsx-bg-secondary wsx-br-md wsx-pt-4 wsx-pb-6 wsx-plr-10 wsx-color-text-reverse wsx-curser-pointer wsx-btn-icon"
							id="wsx-sp-dr-view-more" data-product-id="<?php esc_attr( $product->get_id() ); ?>">
							<?php echo esc_html( wholesalex()->get_setting( 'promo_button_text_on_sp', __( 'Get exclusive offers', 'wholesalex' ) ) ); ?>
							<div class="wsx-icon" id="wsx-icon-angle-down"
								style="margin-bottom: -4px; transition: all 0.3s;"><svg xmlns="http://www.w3.org/2000/svg"
									width="20" height="20" fill="none">
									<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
										d="m5 7.5 5 5 5-5" />
								</svg></div>
						</div>

						<div class="wsx-dr-single-product-discounts-modal wsx-absolute wsx-down-4 wsx-z-999 wsx-card wsx-plr-8 wsx-pt-10 wsx-pb-10 wsx-width-70v wsx-width-250 wsx-shadow-primary"
							id="wsx-dr-single-product-discounts-modal-<?php esc_attr( $product->get_id() ); ?>" style="display:none;">
							<div class="wsx-font-14 wsx-font-bold wsx-text-center wsx-color-text-medium wsx-mb-8">
								<?php echo esc_html__( 'Conditional Discount Offers', 'wholesalex' ); ?></div>
							<?php echo wp_kses_post( $modal_content ); ?>
						</div>
					</div>
				</div>
				<?php
				do_action( 'wholesalex_promotions_popup_footer' );
			}
			if ( 'yes' == wholesalex()->get_setting( 'show_order_qty_text_on_sp', 'no' ) && ( ! empty( wholesalex()->get_rule_data( $product->get_id() )['min_order_qty'] ) || ! empty( wholesalex()->get_rule_data( $product->get_id() )['max_order_qty'] ) ) ) {
				$min_qty = '';
				if ( isset( wholesalex()->get_rule_data( $product->get_id() )['min_order_qty'] ) ) {
					foreach ( wholesalex()->get_rule_data( $product->get_id() )['min_order_qty'] as $rule ) {
						$min_qty = $rule['minimum_qty'];
					}
				}
				$max_qty = '';
				if ( isset( wholesalex()->get_rule_data( $product->get_id() )['max_order_qty'] ) ) {
					foreach ( wholesalex()->get_rule_data( $product->get_id() )['max_order_qty'] as $rule ) {
						$max_qty = $rule['maximum_qty'];
					}
				}

				if ( $max_qty && $min_qty ) {
					$message = $this->restore_smart_tags(
						array(
							'{minimum_qty}'   => $min_qty,
							'{maximum_qty}'   => $max_qty,
							'{product_title}' => $product->get_title(),
						),
						wholesalex()->get_setting( 'min_max_both_order_qty_promo_text', __( 'You can add minimum {minimum_qty} and maximum {maximum_qty} quantity of this product', 'wholesalex' ) )
					);
				} elseif ( $min_qty ) {
					$message = $this->restore_smart_tags(
						array(
							'{minimum_qty}'   => $min_qty,
							'{product_title}' => $product->get_title(),
						),
						wholesalex()->get_setting( 'only_minimum_order_qty_promo_text', __( 'You have to add minimun {minimum_qty} quantity', 'wholesalex' ) )
					);
				} elseif ( $max_qty ) {
					$message = $this->restore_smart_tags(
						array(
							'{maximum_qty}'   => $max_qty,
							'{product_title}' => $product->get_title(),
						),
						wholesalex()->get_setting( 'only_maximum_order_qty_promo_text', __( 'You can add maximum {maximum_qty} quantity', 'wholesalex' ) )
					);
				}
				?>
				<div class="wsx-single-product-discount-card wsx-mt-10 wsx-min-max-sp-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion"><?php echo esc_html( $message ); ?> </div>
				<?php
			}

			if ( 'yes' === wholesalex()->get_setting( 'show_free_shipping_promo_text_on_sp', 'no' ) ) {

				$free_shipping    = wholesalex()->get_rule_data( $product->get_id(), 'free_shipping' );
				$is_free_shipping = false;

				if ( ! empty( $free_shipping ) ) {
					foreach ( $free_shipping as $pd ) {
						if ( ! isset( $pd['conditions']['tiers'] ) || empty( $pd['conditions']['tiers'] ) ) {
							$is_free_shipping = true;
							break;
						}
					}
				}

				if ( $is_free_shipping ) {
					$shipping_text = wholesalex()->get_setting( 'free_shipping_text_on_sp', __( 'Free Shipping', 'wholesalex' ) );
					?>
					<div class="wsx-single-product-discount-card wsx-mt-10 wsx-free-shipping-sp-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
						<?php echo esc_html( $shipping_text ); ?> </div>
					<?php
				}
			}

			if ( 'yes' === wholesalex()->get_setting( '_settings_show_bxgy_free_products_on_single_product_page', 'no' ) ) {
				$bxgy_rules = wholesalex()->get_rule_data( $product->get_id(), 'buy_x_get_y' );

				foreach ( $bxgy_rules as $rule ) {

					if ( ! isset( $rule['conditions']['tiers'] ) || empty( $rule['conditions']['tiers'] ) ) {
						$validity = '';
						if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
							$validity = $rule['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . $this->restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $rule['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
						}

						$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
						$variation_id = $product->get_parent_id() ? $product->get_id() : 0;

						extract( $rule['filter'] );

						if ( ! empty( $include_cats ) || ! empty( $exclude_cats ) ) {
							$cats = wc_get_product_term_ids( $product_id, 'product_cat' );
						}

						$heading_text = false;

						if ( ! empty( $include_products ) && in_array( $product_id, $include_products, true ) ) {
							$heading_text = sprintf( 'Buy %s Quantity to Get These Item Free!', $rule['min_purchase_count'] );
						} elseif ( ! empty( $exclude_products ) && ! in_array( $product_id, $exclude_products, true ) ) {
							$heading_text = sprintf( 'Buy %s Quantity to Get These Item Free!', $rule['min_purchase_count'] );
						} elseif ( ! empty( $include_cats ) && array_intersect( $cats, $include_cats ) ) {
							$cat_names = array();
							foreach ( $include_cats as $cat_id ) {
								$term        = get_term_by( 'id', $cat_id, 'product_cat' );
								$cat_names[] = $term->name;
							}
							$heading_text = sprintf( 'Buy %s Quantity From %s Categories to Get These Item Free!', $rule['min_purchase_count'], implode( ',', $cat_names ) );
						} elseif ( ! empty( $exclude_cats ) && ! array_intersect( $cats, $exclude_cats ) ) {
							$cat_names = array();
							foreach ( $exclude_cats as $cat_id ) {
								$term        = get_term_by( 'id', $cat_id, 'product_cat' );
								$cat_names[] = $term->name;
							}
							$heading_text = sprintf( 'Buy %s Quantity Excluding These %s Categories to Get These Item Free!', $rule['min_purchase_count'], implode( ',', $cat_names ) );
						} elseif ( ! empty( $include_variations ) && in_array( $variation_id, $include_variations, true ) ) {
							$heading_text = sprintf( 'Buy %s Quantity to Get These Item Free!', $rule['min_purchase_count'] );
						} elseif ( ! empty( $exclude_variations ) && ! in_array( $variation_id, $exclude_variations, true ) ) {
							$heading_text = sprintf( 'Buy %s Quantity to Get These Item Free!', $rule['min_purchase_count'] );
						} elseif ( $is_all_products ) {
							$heading_text = sprintf( 'Buy %s Quantity From All Products to Get These Item Free!', $rule['min_purchase_count'] );
						}

						if ( $heading_text ) {
							$this->bxgy_free_items_template( $heading_text, $rule['free_items'], $rule['free_item_quantity'] );
						}
					}
				}
			}

			if ( $modal_content ) {
				?>
				<script type="text/javascript">
					(function($) {
						'use strict';
						let view_more = $("#wsx-sp-dr-view-more");
						view_more.on('click', function(e) {
							const product_id = view_more.data('product-id');
							$("#wsx-dr-single-product-discounts-modal-" + product_id).slideToggle(100);
							const icon = $("#wsx-icon-angle-down");
							if (icon.hasClass('rotated')) {
								icon.removeClass('rotated').css('transform', 'rotate(0deg)');
							} else {
								icon.addClass('rotated').css('transform', 'rotate(180deg)');
							}
						});

						$(document).click(function(e) {
							if ($(e.target).closest('.wsx-dr-single-product-discounts-modal').length != 0) return false;
							if ($(e.target).closest('#wsx-sp-dr-view-more').length != 0) return false;
							$('.wsx-dr-single-product-discounts-modal').hide(100);
							$("#wsx-icon-angle-down").removeClass('rotated').css('transform', 'rotate(0deg)');
						});

					})(jQuery);
				</script>
				<?php
			}
		}

		if ( ! $is_echo ) {
			return ob_get_clean();
		}
	}

	public function bxgy_free_items_template( $min_purchase_text, $free_items, $free_item_quantity ) {
		?>
		<div class="wholesalex_free_items wsx-single-product-discount-card">
			<div class="wsx-bxgy-min-purchase-text"> <?php echo esc_html( $min_purchase_text ); ?> </div>
			<?php
			foreach ( $free_items as $item ) {
				$free_item_id = $item['value'];
				$product      = wc_get_product( $free_item_id );
				$image        = wp_get_attachment_image_src( get_post_thumbnail_id( $free_item_id ), 'thumbnail' );
				?>
				<div class="wsx-bxgy-free-promo-card">
					<img src="<?php echo esc_url( $image[0] ); ?>">
					<div class="wsx-bxgy-free-item-meta">
						<div class="wsx-bxgy-free-item-title"><?php echo esc_html( $product->get_title() ); ?> </div>
						<?php if ( $free_item_quantity > 1 ) { ?>
							<div class="wsx-bxgy-free-item-qty">
								<?php printf( __( 'Quantity: %s', 'wholesalex' ), $free_item_quantity ); ?>
							</div>
						<?php } ?>
						<div class="wsx-bxgy-free-item-price">
							<?php echo wc_price( $product->get_price( 'edit' ) * esc_html( $free_item_quantity ) ); ?>
						</div>
					</div>
					<div class="wsx-free-item-tag"><?php echo esc_html__( 'FREE', 'wholesalex' ); ?> </div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	public function generate_rule_conditions_markup( $conditions ) {
		$data   = array();
		$markup = '<div>';
		foreach ( $conditions as $condition ) {
			if ( isset( $condition['_conditions_for'], $condition['_conditions_operator'], $condition['_conditions_value'] ) ) {
				$con_value = floatval( $condition['_conditions_value'] );
				if ( 'less_equal' === $condition['_conditions_operator'] || 'less' === $condition['_conditions_operator'] ) {
					if ( ! isset( $data[ $condition['_conditions_for'] ]['less'] ) ) {
						$data[ $condition['_conditions_for'] ] = array( 'less' => $con_value );
					}
					$data[ $condition['_conditions_for'] ]['less'] = min( $data[ $condition['_conditions_for'] ]['less'], $con_value );
				}
				if ( 'greater_equal' === $condition['_conditions_operator'] || 'greater' === $condition['_conditions_operator'] ) {
					if ( 'greater' === $condition['_conditions_operator'] ) {
						++$con_value;
					}
					if ( ! isset( $data[ $condition['_conditions_for'] ]['greater'] ) ) {
						$data[ $condition['_conditions_for'] ] = array( 'greater' => $con_value );
					}
					$data[ $condition['_conditions_for'] ]['greater'] = max( $data[ $condition['_conditions_for'] ]['greater'], $con_value );
				}
			}
		}

		if ( isset( $data['cart_total_weight'] ) ) {
			$weight_unit = get_option( 'woocommerce_weight_unit' );
		}
		foreach ( $data as $con_name => $cons ) {
			switch ( $con_name ) {
				case 'cart_total_value':
					if ( isset( $cons['greater'], $cons['less'] ) ) {
						$cons['greater'] = wc_price( $cons['greater'] );
						$cons['less']    = wc_price( $cons['less'] );
						$markup         .= '<div>' . $this->restore_smart_tags(
							array(
								'{min_value}' => $cons['less'],
								'{max_value}' => $cons['greater'],
							),
							wholesalex()->get_setting( 'cart_total_value_min_max_conditions_text', __( 'Spend {min_value} to {max_value}', 'wholesalex' ) )
						) . '</div>';
					} elseif ( isset( $cons['greater'] ) ) {
						$cons['greater'] = wc_price( $cons['greater'] );
						$markup         .= '<div>' . $this->restore_smart_tags( array( '{max_value}' => $cons['greater'] ), wholesalex()->get_setting( 'cart_total_value_min_conditions_text', __( 'Spend min {max_value}', 'wholesalex' ) ) ) . '</div>';
					} elseif ( isset( $cons['less'] ) ) {
						$cons['less'] = wc_price( $cons['less'] );
						$markup      .= '<div>' . $this->restore_smart_tags( array( '{min_value}' => $cons['less'] ), wholesalex()->get_setting( 'cart_total_value_max_conditions_text', __( 'Spend upto {min_value}', 'wholesalex' ) ) ) . '</div>';
					}
					break;
				case 'cart_total_qty':
					if ( isset( $cons['greater'] ) && isset( $cons['less'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags(
							array(
								'{min_value}' => $cons['less'],
								'{max_value}' => $cons['greater'],
							),
							wholesalex()->get_setting( 'cart_total_qty_min_max_conditions_text', __( 'Add {min_value} to {max_value} product(s) to cart', 'wholesalex' ) )
						) . '</div>';
					} elseif ( isset( $cons['greater'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags( array( '{max_value}' => $cons['greater'] ), wholesalex()->get_setting( 'cart_total_qty_min_conditions_text', __( 'Buy minimum {max_value} product(s) to get the discount', 'wholesalex' ) ) ) . '</div>';
					} elseif ( isset( $cons['less'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags( array( '{min_value}' => $cons['less'] ), wholesalex()->get_setting( 'cart_total_qty_max_conditions_text', __( 'Don&apos;t buy more than {min_value} product(s) to get the discount', 'wholesalex' ) ) ) . '</div>';
					}
					break;
				case 'cart_total_weight':
					if ( isset( $cons['greater'], $cons['less'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags(
							array(
								'{min_value}' => $cons['less'],
								'{max_value}' => $cons['greater'],
								'{unit}'      => $weight_unit,
							),
							wholesalex()->get_setting( 'cart_total_weight_min_max_conditions_text', __( 'Add {min_value} to {max_value} {unit} to cart', 'wholesalex' ) )
						) . '</div>';
					} elseif ( isset( $cons['greater'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags(
							array(
								'{max_value}' => $cons['greater'],
								'{unit}'      => $weight_unit,
							),
							wholesalex()->get_setting( 'cart_total_weight_min_conditions_text', __( 'Add min {max_value} {unit} to cart', 'wholesalex' ) )
						) . '</div>';
					} elseif ( isset( $cons['less'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags(
							array(
								'{min_value}' => $cons['less'],
								'{unit}'      => $weight_unit,
							),
							wholesalex()->get_setting( 'cart_total_weight_max_conditions_text', __( 'Add up to {min_value} {unit} to cart', 'wholesalex' ) )
						) . '</div>';
					}
					break;
				default:
					break;
			}
		}
		$markup .= '</div>';
		return $markup;
	}

	public function check_for_product_discounts( $product ) {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;

		if ( ! isset( $this->valid_dynamic_rules['product_discount'] ) || empty( $this->valid_dynamic_rules['product_discount'] ) ) {
			return;
		}

		if ( 'yes' !== wholesalex()->get_setting( 'show_product_discounts_text', 'no' ) ) {
			return;
		}

		foreach ( $this->valid_dynamic_rules['product_discount'] as $pd ) {
			if ( ! empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'product_discount', $pd['id'] ) ) ) {
				continue;
			}

			if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $pd['filter'] ) ) {
				wholesalex()->set_rule_data(
					$pd['id'],
					$variation_id ? $variation_id : $product_id,
					'product_discount',
					array(
						'value'               => $pd['rule']['_discount_amount'],
						'type'                => $pd['rule']['_discount_type'],
						'conditions'          => $pd['conditions'],
						'who_priority'        => $pd['who_priority'],
						'applied_on_priority' => $pd['applied_on_priority'],
						'end_date'            => $pd['end_date'],
					)
				);
			}
		}
	}

	public function check_for_free_shipping( $product, $profile_shipping_data ) {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;

		$is_profile_free_shipping = false;
		if ( ! empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'free_shipping', 'profile' ) ) ) {
			$is_profile_free_shipping = true;
		}

		if ( ! empty( $profile_shipping_data ) ) {
			if ( isset( $profile_shipping_data['method_type'] ) && 'force_free_shipping' === $profile_shipping_data['method_type'] ) {
				$is_profile_free_shipping = true;
			}

			if ( ! $is_profile_free_shipping && isset( $profile_shipping_data['method_type'] ) && 'specific_shipping_methods' === $profile_shipping_data['method_type'] ) {
				foreach ( $profile_shipping_data['methods'] as $method ) {
					if ( ! isset( $this->cached_shipping_method_id[ $method['value'] ] ) ) {
						$zone = WC_Shipping_Zones::get_shipping_method( $method['value'] );
						$this->cached_shipping_method_id[ $method['value'] ] = $zone->id;
					}
					if ( 'free_shipping' === $this->cached_shipping_method_id[ $method['value'] ] ) {
						$is_profile_free_shipping = true;
						break;
					}
				}
			}
		}

		if ( $is_profile_free_shipping ) {
			wholesalex()->set_rule_data(
				'profile',
				$variation_id ? $variation_id : $product_id,
				'free_shipping',
				array(
					'conditions'          => array(),
					'end_date'            => false,
					'who_priority'        => 10,
					'applied_on_priority' => 10,
				),
			);
		} elseif ( ! empty( $this->valid_dynamic_rules['shipping_rule'] ) ) {
			foreach ( $this->valid_dynamic_rules['shipping_rule'] as $sr ) {
				if ( empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'free_shipping', $sr['id'] ) ) ) {
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $sr['filter'] ) ) {
						if ( $sr['rule']['__shipping_zone'] === $this->current_shipping_zone && ! $is_profile_free_shipping ) {
							$methods = $sr['rule']['_shipping_zone_methods'];
							foreach ( $methods as $method ) {
								if ( ! isset( $this->cached_shipping_method_id[ $method['value'] ] ) ) {
									$zone = WC_Shipping_Zones::get_shipping_method( $method['value'] );
									$this->cached_shipping_method_id[ $method['value'] ] = $zone->id;
								}
								if ( 'free_shipping' === $this->cached_shipping_method_id[ $method['value'] ] ) {
									wholesalex()->set_rule_data(
										$sr['id'],
										$variation_id ? $variation_id : $product_id,
										'free_shipping',
										array(
											'conditions'   => $sr['conditions'] ? $sr['conditions'] : array(),
											'who_priority' => $sr['who_priority'],
											'applied_on_priority' => $sr['applied_on_priority'],
											'end_date'     => $sr['end_date'],
										),
									);
									break;
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Register eligible cart-related discount rules for a product on the single product page.
	 *
	 * Iterates over active cart_discount, payment_discount, and buy_x_get_one rules
	 *
	 * The raw `$cart_related_data` is passed through the
	 * `wholesalex_dr_cart_related_data` filter before processing, allowing third-party
	 * plugins or Pro add-ons to inject or modify rules at runtime.
	 *
	 * Called by:
	 *   - `handle_single_product_page_promo()` (line 2406) — triggered on the single
	 *     product page when `show_promotions_on_sp` setting is enabled.
	 *
	 * Settings that gate each rule type:
	 *   - `show_cart_discount_text`                  → cart_discount rules
	 *   - `show_payment_method_discount_promo_text_sp` → payment_discount rules
	 *   - `show_bogo_discount_promo_text_on_sp`        → buy_x_get_one rules
	 *
	 * @param WC_Product $product          The current product being rendered on the single product page.
	 * @param array      $cart_related_data Associative array of cart-related rule sets, keyed by rule type
	 *                                      (e.g. 'cart_discount', 'payment_discount', 'buy_x_get_one').
	 *
	 * @return void
	 */
	public function check_for_cart_releated_discounts( $product, $cart_related_data ) {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;
		$rules        = apply_filters( 'wholesalex_dr_cart_related_data', $cart_related_data );

		if ( ! empty( $rules ) ) {
			// Cart discount promo data registration — managed in Rule_Cart_Discount::register_promo_data().
			if ( isset( $rules['cart_discount'] ) && ! empty( $rules['cart_discount'] ) ) {
				$this->rule_cart_discount->register_promo_data( $product, $rules['cart_discount'] );
			}
			if ( 'yes' === wholesalex()->get_setting( 'show_payment_method_discount_promo_text_sp', 'no' ) && isset( $rules['payment_discount'] ) && ! empty( $rules['payment_discount'] ) ) {
				foreach ( $rules['payment_discount'] as $rule ) {
					if ( empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'payment_discount', $rule['id'] ) ) ) {
						$gateways_name = Dynamic_Rules_Condition_Engine::get_multiselect_values( $rule['rule']['_payment_gateways'], 'name' );
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $rule['filter'] ) ) {
							wholesalex()->set_rule_data(
								$rule['id'],
								$variation_id ? $variation_id : $product_id,
								'payment_discount',
								array(
									'type'                => $rule['rule']['_discount_type'],
									'value'               => $rule['rule']['_discount_amount'],
									'conditions'          => $rule['conditions'],
									'gateways'            => $gateways_name,
									'who_priority'        => $rule['who_priority'],
									'applied_on_priority' => $rule['applied_on_priority'],
									'end_date'            => $rule['end_date'],
								)
							);
						}
					}
				}
			}
			if ( 'yes' === wholesalex()->get_setting( 'show_bogo_discount_promo_text_on_sp', 'no' ) && isset( $rules['buy_x_get_one'] ) && ! empty( $rules['buy_x_get_one'] ) ) {
				foreach ( $rules['buy_x_get_one'] as $rule ) {
					if ( empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'buy_x_get_one', $rule['id'] ) ) ) {
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $rule['filter'] ) ) {
							wholesalex()->set_rule_data(
								$rule['id'],
								$variation_id ? $variation_id : $product_id,
								'buy_x_get_one',
								array(
									'minimum_qty'         => $rule['rule']['_minimum_purchase_count'],
									'conditions'          => $rule['conditions'] ? $rule['conditions'] : array(),
									'who_priority'        => $rule['who_priority'],
									'applied_on_priority' => $rule['applied_on_priority'],
									'end_date'            => $rule['end_date'],
								)
							);
						}
					}
				}
			}
		}
	}
}
