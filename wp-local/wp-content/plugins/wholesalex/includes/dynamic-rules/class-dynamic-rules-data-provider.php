<?php
/**
 * WholesaleX Dynamic Rules - Data Provider
 *
 * Provides AJAX data methods for the dynamic rules admin UI:
 * users, products, categories, brands, attributes, variations,
 * payment gateways, tax classes, roles, shipping zones/methods/countries.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Shipping_Zone;
use WC_Shipping_Zones;
use WC_Tax;
use WP_User_Query;

/**
 * Dynamic Rules Data Provider
 */
class Dynamic_Rules_Data_Provider {

	/**
	 * Get Users.
	 *
	 * @param string $search Search Keyword.
	 * @return array
	 */
	public function get_users( $search = '' ) {
		$__user_roles = wholesalex()->get_roles( 'roles_option' );
		$__final      = array();
		$args         = array(
			'role__in' => array_column( $__user_roles, 'value' ),
			'number'   => 20,
		);

		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$__users = new WP_User_Query( $args );

		foreach ( $__users->get_results() as $user ) {
			$__final[] = array(
				'value' => $user->ID,
				'name'  => $user->display_name . '(' . $user->user_email . ')',
			);
		}

		return $__final;
	}

	/**
	 * Get Products.
	 *
	 * @param string $search Search Keyword.
	 * @return array
	 */
	public function get_products( $search = '' ) {
		$__final               = array();
		$__search              = $search;
		$is_search_has_numeric = preg_match( '/\d+/', $__search );

		global $wpdb;
		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT ID, post_title FROM $wpdb->posts
                WHERE post_type = %s
                AND post_status = %s
                AND (post_title LIKE %s" . ( $is_search_has_numeric ? ' OR ID = %d' : '' ) . ')
                LIMIT 20',
				...array_filter(
					array(
						'product',
						'publish',
						'%' . $wpdb->esc_like( $__search ) . '%',
						$is_search_has_numeric ? (int) $__search : null,
					),
					function ( $v ) {
						return null !== $v;
					}
				)
			)
		);

		foreach ( $results as $result ) {
			$title     = $result->post_title;
			$__final[] = array(
				'value' => (int) $result->ID,
				'name'  => $title . ' (' . $result->ID . ')',
			);
		}

		return $__final;
	}

	/**
	 * Get WooCommerce Categories.
	 *
	 * @param string $search Search Keyword.
	 * @return array
	 */
	public function get_categories( $search = '' ) {
		$__final = array();
		$args    = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => 20,
		);
		if ( '' !== $search ) {
			$args['search'] = $search;
		}
		$__categories = get_terms( $args );
		foreach ( $__categories as $category ) {
			$__final[] = array(
				'value' => $category->term_id,
				'name'  => $category->name,
			);
		}
		return $__final;
	}

	/**
	 * Get WooCommerce Brands.
	 *
	 * @param string $search Search Keyword.
	 * @return array
	 */
	public function get_brands( $search = '' ) {
		$__final    = array();
		$taxonomies = array( 'product_brand', 'pwb-brand', 'yith_product_brand' );
		$taxonomy   = '';
		foreach ( $taxonomies as $tax ) {
			if ( taxonomy_exists( $tax ) ) {
				$taxonomy = $tax;
				break;
			}
		}
		if ( '' === $taxonomy ) {
			return $__final;
		}
		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => 20,
		);
		if ( '' !== $search ) {
			$args['search'] = $search;
		}
		$__brands = get_terms( $args );
		foreach ( $__brands as $brand ) {
			$__final[] = array(
				'value' => $brand->term_id,
				'name'  => $brand->name,
			);
		}
		return $__final;
	}

	/**
	 * Get WooCommerce Attributes.
	 *
	 * @param string $search Search Keyword.
	 * @return array
	 */
	public function get_attributes( $search = '' ) {
		$__final      = array();
		$__attributes = wc_get_attribute_taxonomies();
		foreach ( $__attributes as $attribute ) {
			if ( '' !== $search ) {
				if ( false !== strpos( $attribute->attribute_label, $search ) || false !== strpos( $attribute->attribute_name, $search ) ) {
					$__final[] = array(
						'value' => (int) $attribute->attribute_id,
						'name'  => $attribute->attribute_label,
					);
				}
			} else {
				$__final[] = array(
					'value' => (int) $attribute->attribute_id,
					'name'  => $attribute->attribute_label,
				);
			}
		}
		return $__final;
	}

	/**
	 * Get Variation Products.
	 *
	 * @param string      $search Search Keyword.
	 * @param int|boolean $product_id Product ID.
	 * @return array
	 */
	public function get_variation_products( $search = '', $product_id = false ) {
		$__final = array();

		$args = array(
			'post_type'   => 'product_variation',
			'post_status' => 'publish',
			'numberposts' => 20,
		);

		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		if ( $product_id ) {
			$args['post_parent'] = $product_id;
		}

		$__variations = get_posts( $args );

		foreach ( $__variations as $variation ) {
			$__final[] = array(
				'value' => $variation->ID,
				'name'  => $variation->post_title . ' (' . $variation->ID . ')',
			);
		}

		return $__final;
	}

	/**
	 * Get Products with Variations.
	 *
	 * @param string $search Search Keyword.
	 * @return array
	 */
	public function get_products_with_variations( $search = '' ) {
		$products = $this->get_products( $search );
		if ( empty( $products ) ) {
			return $this->get_variation_products( $search );
		}
		return $products;
	}

	/**
	 * Get Payment Gateways.
	 *
	 * Returns all enabled payment gateways for use in admin rule configuration.
	 *
	 * The WC_Payment_Gateways singleton can be initialised before payment extension
	 * plugins (e.g. Mollie, Stripe) have had a chance to register their gateways via
	 * the `woocommerce_payment_gateways` filter.  This is particularly common in the
	 * WP REST API context where certain admin hooks do not fire.  To work around this,
	 * we re-apply the filter at call-time and merge any newly-discovered gateways with
	 * those already cached in the singleton, so that extension gateways are always
	 * present in the list regardless of initialisation order.
	 *
	 * @return array
	 */
	public function get_payment_gateways() {
		$__final = array();

		if ( ! function_exists( 'WC' ) ) {
			return $__final;
		}

		// Pull the already-instantiated gateways from the WC singleton.
		$gateways = WC()->payment_gateways->payment_gateways();

		// Build a quick lookup of class names already present in the singleton so we
		// can avoid creating duplicate instances.
		$existing_by_class = array();
		foreach ( $gateways as $gw ) {
			$existing_by_class[ get_class( $gw ) ] = true;
		}

		// Re-apply the filter with an empty seed array.  At this point in a REST API
		// request all plugin hooks are guaranteed to be registered, so the result will
		// contain any extension gateways that were not present when the singleton was
		// first created.
		$registered = apply_filters( 'woocommerce_payment_gateways', array() );

		foreach ( $registered as $entry ) {
			if ( is_string( $entry ) ) {
				// Skip classes already in the singleton or that do not exist.
				if ( isset( $existing_by_class[ $entry ] ) || ! class_exists( $entry ) ) {
					continue;
				}
				$instance = new $entry();
				if ( is_a( $instance, 'WC_Payment_Gateway' ) && ! isset( $gateways[ $instance->id ] ) ) {
					$gateways[ $instance->id ] = $instance;
				}
			} elseif ( is_object( $entry ) && is_a( $entry, 'WC_Payment_Gateway' ) ) {
				$class_name = get_class( $entry );
				if ( ! isset( $existing_by_class[ $class_name ] ) && ! isset( $gateways[ $entry->id ] ) ) {
					$gateways[ $entry->id ] = $entry;
				}
			}
		}

		foreach ( $gateways as $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				$__final[] = array(
					'value' => $gateway->id,
					'name'  => $gateway->title,
				);
			}
		}

		return $__final;
	}

	/**
	 * Get Tax Classes.
	 *
	 * @return array
	 */
	public static function get_tax_classes() {
		$__final       = array();
		$__tax_classes = WC_Tax::get_tax_classes();
		foreach ( $__tax_classes as $tax_class ) {
			$__final[] = array(
				'value' => $tax_class,
				'name'  => $tax_class,
			);
		}
		return $__final;
	}

	/**
	 * Get WholesaleX Roles.
	 *
	 * @return array
	 */
	public function get_roles() {
		return wholesalex()->get_roles( 'roles_option' );
	}

	/**
	 * Get Shipping Zones.
	 *
	 * @return array
	 */
	public static function get_shipping_zones() {
		$__final = array();
		$__zones = WC_Shipping_Zones::get_zones();
		foreach ( $__zones as $zone ) {
			$__final[] = array(
				'value' => $zone['zone_id'],
				'name'  => $zone['zone_name'],
			);
		}
		return $__final;
	}

	/**
	 * Get Shipping Methods.
	 *
	 * @param string $zone_id Zone ID.
	 * @return array
	 */
	public function get_shipping_methods( $zone_id = '' ) {
		$__final = array();
		if ( '' !== $zone_id ) {
			$__zone    = new WC_Shipping_Zone( $zone_id );
			$__methods = $__zone->get_shipping_methods();
			foreach ( $__methods as $method ) {
				$__final[] = array(
					'value' => $method->instance_id,
					'name'  => $method->title,
				);
			}
		}
		return $__final;
	}

	/**
	 * Get Shipping Country.
	 *
	 * @param string $search Search Keyword.
	 * @return array
	 */
	public function get_shipping_country( $search = '' ) {
		$__final     = array();
		$__countries = WC()->countries->get_shipping_countries();
		foreach ( $__countries as $code => $name ) {
			if ( '' !== $search ) {
				if ( false !== strpos( strtolower( $name ), strtolower( $search ) ) || false !== strpos( strtolower( $code ), strtolower( $search ) ) ) {
					$__final[] = array(
						'value' => $code,
						'name'  => $name,
					);
				}
			} else {
				$__final[] = array(
					'value' => $code,
					'name'  => $name,
				);
			}
		}
		return $__final;
	}

	/**
	 * Get Payment Gateway Options.
	 *
	 * Called from get_dynamic_rules_field() which is invoked during PHP page
	 * rendering (wp_localize_script) where WP_ADMIN is set and every payment
	 * plugin hook is active.  This avoids the REST-API context issue where
	 * is_admin() returns false and some gateways are never registered.
	 *
	 * @return array  Array of {value, name} objects for MultiSelect.
	 */
	public static function get_payment_gateway_options() {
		$options = array();
		if ( ! function_exists( 'WC' ) ) {
			return $options;
		}
		$available = WC()->payment_gateways->payment_gateways();
		foreach ( $available as $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				$options[] = array(
					'value' => $gateway->id,
					'name'  => $gateway->get_title(),
				);
			}
		}
		return $options;
	}

	/**
	 * Get Dynamic Rules Field Definitions.
	 *
	 * Returns the full field schema used to render the dynamic rules admin UI.
	 *
	 * @return array
	 */
	public static function get_dynamic_rules_field() {
		// Load payment gateways once here, matching the User Roles approach.
		$payment_gateway_options = self::get_payment_gateway_options();

		return apply_filters(
			'wholesalex_dynamic_rules_field',
			array(
				'create_n_save_btn' => array(
					'type' => 'buttons',
					'attr' => array(
						'create' => array(
							'type'  => 'button',
							'label' => __( 'Create Dynamic Rule', 'wholesalex' ),
						),
					),
				),
				'_new_rule'         => array(
					'label' => __( 'New Dynamic Rule', 'wholesalex' ),
					'type'  => 'rule',
					'attr'  => array(
						'_rule_title_n_status_section'  => array(
							'label' => '',
							'type'  => 'title_n_status',
							'_id'   => 1,
							'attr'  => array(
								'_rule_title' => array(
									'type'        => 'text',
									'label'       => __( 'Rule Title', 'wholesalex' ),
									'placeholder' => __( 'Rule Title', 'wholesalex' ),
									'default'     => '',
									'help'        => '',
								),
								'_rule_type'  => array(
									'type'    => 'select',
									'label'   => __( 'Rule Type', 'wholesalex' ),
									'options' => apply_filters(
										'wholesalex_dynamic_rules_rule_type_options',
										array(
											''         => __( 'Choose Rule...', 'wholesalex' ),
											'product_discount' => __( 'Product Discount', 'wholesalex' ),
											'cart_discount' => __( 'Cart Discount ', 'wholesalex' ),
											'payment_discount' => __( 'Payment Method Discount', 'wholesalex' ),
											'payment_order_qty' => __( 'Required Quantity for Payment Method', 'wholesalex' ),
											'buy_x_get_one' => __( 'BOGO Discounts (Buy X Get One Free)', 'wholesalex' ),
											'shipping_rule' => __( 'Shipping Rule', 'wholesalex' ),
											'min_order_qty' => __( 'Minimum Order Quantity', 'wholesalex' ),
											'tax_rule' => __( 'Tax Rule', 'wholesalex' ),
											'pro_restrict_checkout' => __( 'Checkout Restriction (Pro)', 'wholesalex' ),
											'pro_quantity_based' => __( 'Quantity Based Discount (Pro)', 'wholesalex' ),
											'pro_extra_charge' => __( 'Extra Charge (Pro)', 'wholesalex' ),
											'pro_buy_x_get_y' => __( 'Buy X Get Y Free (Pro)', 'wholesalex' ),
											'pro_max_order_qty' => __( 'Maximum Order Quantity (Pro)', 'wholesalex' ),
											'pro_restrict_product_visibility' => __( 'Restrict Product Visibility (Pro)', 'wholesalex' ),
											'pro_hidden_price' => __( 'Hidden Price (Pro)', 'wholesalex' ),
											'pro_non_purchasable' => __( 'Non Purchasable (Pro)', 'wholesalex' ),
										),
										'rule_type'
									),
									'default' => '',
									'help'    => '',
								),
								'save_rule'   => array(
									'type'  => 'button',
									'label' => __( 'Save', 'wholesalex' ),
								),
							),
						),

						'_rule_section'                 => array(
							'label' => '',
							'type'  => 'rules',
							'attr'  => array(

								'_rule_for'      => array(
									'type'    => 'select',
									'label'   => __( 'Select User/Role', 'wholesalex' ),
									'options' => apply_filters(
										'wholesalex_dynamic_rules_rule_for_options',
										array(
											''          => __( 'Select Users/Role...', 'wholesalex' ),
											'all'       => __( 'All (Registered and Guest Users)', 'wholesalex' ),
											'all_users' => __( 'All Registered Users', 'wholesalex' ),
											'all_roles' => __( 'All B2B Roles', 'wholesalex' ),
											'specific_users' => __( 'Specific Users', 'wholesalex' ),
											'specific_roles' => __( 'Specific Roles', 'wholesalex' ),
										),
										'rule_for'
									),
									'default' => '',
									'help'    => '',
								),
								'specific_users' => array(
									'label'       => __( 'Select Users', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_rule_for',
											'value' => 'specific_users',
										),
									),
									'options'     => array(),
									'placeholder' => __( 'Choose Users...', 'wholesalex' ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_users',
									'ajax_search' => true,
								),
								'specific_roles' => array(
									'label'       => __( 'Select Roles', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_rule_for',
											'value' => 'specific_roles',
										),
									),
									'options'     => array(),
									'placeholder' => __( 'Choose Roles...', 'wholesalex' ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_roles',
									'ajax_search' => false,
								),
							),
						),
						'_rule_product_filters_section' => array(
							'label' => '',
							'type'  => 'rules',
							'_id'   => 1,
							'attr'  => array(
								'_product_filter'       => array(
									'type'    => 'select',
									'label'   => __( 'Product Filter', 'wholesalex' ),
									'options' => apply_filters(
										'wholesalex_dynamic_rules_product_filter_options',
										array(
											''             => __( 'Choose Filter...', 'wholesalex' ),
											'all_products' => __( 'All Products', 'wholesalex' ),
											'products_in_list' => __( 'Product in list', 'wholesalex' ),
											'products_not_in_list' => __( 'Product not in list', 'wholesalex' ),
											'cat_in_list'  => __( 'Categories in list', 'wholesalex' ),
											'cat_not_in_list' => __( 'Categories not in list', 'wholesalex' ),
											'attribute_in_list' => __( 'Variations in list', 'wholesalex' ),
											'attribute_not_in_list' => __( 'Variations not in list', 'wholesalex' ),
											'pro_brand_in_list' => __( 'Brand in list (Pro)', 'wholesalex' ),
											'pro_brand_not_in_list' => __( 'Brand not in list (Pro)', 'wholesalex' ),
											'pro_att_in_list' => __( 'Attribute in list (Pro)', 'wholesalex' ),
											'pro_att_not_in_list' => __( 'Attribute not in list (Pro)', 'wholesalex' ),
										),
										'product_filter'
									),
									'default' => '',
								),
								'products_in_list'      => array(
									'label'       => __( 'Select Multiple Products', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'products_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_product_in_list_placeholder', __( 'Choose Products to apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_products',
									'ajax_search' => true,
								),
								'products_not_in_list'  => array(
									'label'       => __( 'Select Multiple Products', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'products_not_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_product_not_in_list_placeholder', __( 'Choose Products that wont apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_products',
									'ajax_search' => true,
								),
								'cat_in_list'           => array(
									'label'       => __( 'Select Multiple Categories', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'cat_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_cat_in_list_placeholder', __( 'Choose Categories to apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_categories',
									'ajax_search' => true,
								),
								'cat_not_in_list'       => array(
									'label'       => __( 'Select Multiple Categories', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'cat_not_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_cat_not_in_list_placeholder', __( 'Choose Categories that wont apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_categories',
									'ajax_search' => true,
								),
								'attribute_in_list'     => array(
									'label'       => __( 'Select Multiple Variations', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'attribute_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_attribute_in_list_placeholder', __( 'Choose Product Variations to apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_variation_products',
									'ajax_search' => true,
								),
								'attribute_not_in_list' => array(
									'label'       => __( 'Select Multiple Variations', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'attribute_not_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_attribute_not_in_list_placeholder', __( 'Choose Product Variations that wont apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_variation_products',
									'ajax_search' => true,
								),
								'brand_in_list'         => array(
									'label'       => __( 'Select Multiple Brands', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'brand_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_brand_in_list_placeholder', __( 'Choose Brands to apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_brands',
									'ajax_search' => true,
								),
								'brand_not_in_list'     => array(
									'label'       => __( 'Select Multiple Brands', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'brand_not_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_brand_not_in_list_placeholder', __( 'Choose Brands that wont apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_brands',
									'ajax_search' => true,
								),
								'att_in_list'           => array(
									'label'       => __( 'Select Multiple Attributes', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'att_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_att_in_list_placeholder', __( 'Choose Attributes to apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_attributes',
									'ajax_search' => true,
								),
								'att_not_in_list'       => array(
									'label'       => __( 'Select Multiple Attributes', 'wholesalex' ),
									'type'        => 'multiselect',
									'depends_on'  => array(
										array(
											'key'   => '_product_filter',
											'value' => 'att_not_in_list',
										),
									),
									'options'     => array(),
									'placeholder' => apply_filters( 'wholesalex_dynamic_rules_att_not_in_list_placeholder', __( 'Choose Attributes that wont apply discounts', 'wholesalex' ) ),
									'default'     => array(),
									'is_ajax'     => true,
									'ajax_action' => 'get_attributes',
									'ajax_search' => true,
								),
							),
						),
						'product_discount'              => array(
							'label'      => __( 'Manage Discount', 'wholesalex' ),
							'type'       => 'manage_discount',
							'depends_on' => array(
								array(
									'key'   => '_rule_type',
									'value' => 'product_discount',
								),
							),
							'attr'       => array(
								'_discount_type'   => array(
									'type'    => 'select',
									'label'   => __( 'Discount Type', 'wholesalex' ),
									'options' => array(
										'percentage' => __( 'Percentage', 'wholesalex' ),
										'amount'     => __( 'Amount', 'wholesalex' ),
										'fixed'      => __( 'Fixed Price', 'wholesalex' ),
									),
									'default' => 'percentage',
								),
								'_discount_amount' => array(
									'type'        => 'number',
									'label'       => __( 'Amount', 'wholesalex' ),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
								),
								'_discount_name'   => array(
									'type'        => 'text',
									'label'       => __( 'Discount name(optional)', 'wholesalex' ),
									'default'     => '',
									'placeholder' => __( 'Add discount name here', 'wholesalex' ),
									'help'        => '',
								),
							),
						),
						'payment_discount'              => array(
							'label'      => __( 'Payment Discount', 'wholesalex' ),
							'type'       => 'payment_discount',
							'depends_on' => array(
								array(
									'key'   => '_rule_type',
									'value' => 'payment_discount',
								),
							),
							'attr'       => array(
								'_payment_gateways' => array(
									'type'        => 'multiselect',
									'label'       => __( 'Payment Gateways', 'wholesalex' ),
									'options'     => $payment_gateway_options,
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
									'is_ajax'     => false,
									'ajax_action' => 'get_payment_gateways',
									'ajax_search' => false,
								),
								'_discount_type'    => array(
									'type'    => 'select',
									'label'   => __( 'Discount Type', 'wholesalex' ),
									'options' => array(
										'percentage' => __( 'Percentage', 'wholesalex' ),
										'amount'     => __( 'Amount', 'wholesalex' ),
										'fixed'      => __( 'Fixed Price', 'wholesalex' ),
									),
									'default' => 'percentage',
								),
								'_discount_amount'  => array(
									'type'        => 'number',
									'label'       => __( 'Amount', 'wholesalex' ),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
								),
								'_discount_name'    => array(
									'type'        => 'text',
									'label'       => __( 'Discount name(optional)', 'wholesalex' ),
									'default'     => '',
									'placeholder' => __( 'Add discount name here', 'wholesalex' ),
									'help'        => '',
								),
							),
						),
						'payment_order_qty'             => array(
							'label'      => __( 'Payment Order Quantity', 'wholesalex' ),
							'type'       => 'payment_qty_discount',
							'depends_on' => array(
								array(
									'key'   => '_rule_type',
									'value' => 'payment_order_qty',
								),
							),
							'attr'       => array(
								'_payment_gateways' => array(
									'type'        => 'multiselect',
									'label'       => __( 'Payment Gateways', 'wholesalex' ),
									'options'     => $payment_gateway_options,
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
									'is_ajax'     => false,
									'ajax_action' => 'get_payment_gateways',
									'ajax_search' => false,
								),
								'_order_quantity'   => array(
									'type'        => 'number',
									'label'       => __( 'Order Quantity', 'wholesalex' ),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
								),
							),
						),
						'tax_rule'                      => array(
							'label'      => __( 'Tax Rule', 'wholesalex' ),
							'type'       => 'tax_rule',
							'depends_on' => array(
								array(
									'key'   => '_rule_type',
									'value' => 'tax_rule',
								),
							),
							'attr'       => array(
								'_tax_exempted'     => array(
									'type'    => 'select',
									'label'   => __( 'Tax Exempted?', 'wholesalex' ),
									'options' => array(
										''    => __( 'Choose Tax Exempted Status...', 'wholesalex' ),
										'yes' => __( 'Yes', 'wholesalex' ),
										'no'  => __( 'No', 'wholesalex' ),
									),
									'default' => '',
									'help'    => '',
								),
								'_tax_class'        => array(
									'type'       => 'select',
									'depends_on' => array(
										array(
											'key'   => '_tax_exempted',
											'value' => 'no',
										),
									),
									'label'      => __( 'Tax Class Mapping', 'wholesalex' ),
									'options'    => self::get_tax_classes(),
									'default'    => '',
									'help'       => '',
								),
								'_exempted_country' => array(
									'type'        => 'multiselect',
									'label'       => __( 'Country(optional)', 'wholesalex' ),
									'depends_on'  => array(
										array(
											'key'   => '_tax_exempted',
											'value' => 'yes',
										),
									),
									'options'     => array(),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
									'is_ajax'     => true,
									'ajax_action' => 'get_shipping_country',
									'ajax_search' => false,
								),
							),
						),
						'shipping_rule'                 => array(
							'label'      => __( 'Shipping Rule', 'wholesalex' ),
							'type'       => 'shipping_rule',
							'depends_on' => array(
								array(
									'key'   => '_rule_type',
									'value' => 'shipping_rule',
								),
							),
							'attr'       => array(
								'__shipping_zone'        => array(
									'type'        => 'select',
									'label'       => __( 'Shipping Zone', 'wholesalex' ),
									'options'     => self::get_shipping_zones(),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
									'is_ajax'     => true,
									'ajax_action' => 'get_shipping_zones',
									'ajax_search' => false,
								),
								'_shipping_zone_methods' => array(
									'type'                 => 'multiselect',
									'label'                => __( 'Shipping Zone Methods', 'wholesalex' ),
									'options_dependent_on' => '__shipping_zone',
									'options'              => array(),
									'default'              => '',
									'placeholder'          => '',
									'help'                 => '',
									'is_ajax'              => true,
									'ajax_action'          => 'get_shipping_methods',
									'ajax_search'          => false,
								),
							),
						),
						'cart_discount'                 => array(
							'label'      => __( 'Cart Discount', 'wholesalex' ),
							'type'       => 'manage_discount',
							'depends_on' => array(
								array(
									'key'   => '_rule_type',
									'value' => 'cart_discount',
								),
							),
							'attr'       => array(
								'_discount_type'   => array(
									'type'    => 'select',
									'label'   => __( 'Discount Type', 'wholesalex' ),
									'options' => array(
										'percentage' => __( 'Percentage', 'wholesalex' ),
										'amount'     => __( 'Amount', 'wholesalex' ),
										'fixed'      => __( 'Fixed Price', 'wholesalex' ),
									),
									'default' => 'percentage',
								),
								'_discount_amount' => array(
									'type'        => 'number',
									'label'       => __( 'Amount', 'wholesalex' ),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
								),
								'_discount_name'   => array(
									'type'        => 'text',
									'label'       => __( 'Discount name(optional)', 'wholesalex' ),
									'default'     => '',
									'placeholder' => __( 'Add discount name here', 'wholesalex' ),
									'help'        => '',
								),
							),
						),
						'buy_x_get_one'                 => array(
							'label'      => __( 'BOGO Discounts (Buy X Get One Free)', 'wholesalex' ),
							'type'       => 'buy_x_get_one',
							'depends_on' => array(
								array(
									'key'   => '_rule_type',
									'value' => 'buy_x_get_one',
								),
							),
							'attr'       => array(
								'_minimum_purchase_count' => array(
									'type'        => 'number',
									'label'       => __( 'Product Quantity (X)', 'wholesalex' ),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
								),
								'_per_cart_once'          => array(
									'type'        => 'slider',
									'label'       => __( 'Restrict Free Quantity', 'wholesalex' ),
									'desc'        => __( 'Restrict to one free product per order', 'wholesalex' ),
									'default'     => 'no',
									'placeholder' => '',
									'descTooltip' => __( 'Enabling this option will restrict shoppers from availing of more than one product by adding the required number of products more than one time to the cart.', 'wholesalex' ),
								),
								'_buy_x_get_product_badge_enable' => array(
									'type'        => 'slider',
									'label'       => __( 'Enable Discount Badge', 'wholesalex' ),
									'desc'        => __( 'Enable Discount Badge (Both Shop & Product Page)', 'wholesalex' ),
									'default'     => 'no',
									'placeholder' => '',
									'descTooltip' => __( 'Enable "Offer Badge" on Product Image for both the shop page and the single product page', 'wholesalex' ),
								),
								'_product_badge_label'    => array(
									'type'        => 'text',
									'label'       => __( 'Badge Label Text', 'wholesalex' ),
									'default'     => 'BOGO Free',
									'placeholder' => '',
									'help'        => '',
								),
								'_product_badge_bg_color' => array(
									'type'      => 'color',
									'label'     => __( 'Badge Background Color', 'wholesalex' ),
									'desc'      => '#5a40e8',
									'default'   => '#5a40e8',
									'flexColor' => 'yes',
								),
								'_product_badge_text_color' => array(
									'type'      => 'color',
									'label'     => __( 'Badge Text Color', 'wholesalex' ),
									'desc'      => '#ffffff',
									'default'   => '#ffffff',
									'flexColor' => 'yes',
								),
								'_product_badge_position' => array(
									'type'        => 'select',
									'label'       => 'Badge Position (on image)',
									'options'     => array(
										''      => __( 'Choose Badge Position...', 'wholesalex' ),
										'left'  => __( 'Left', 'wholesalex' ),
										'right' => __( 'Right', 'wholesalex' ),
									),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
									'flexBadge'   => 'yes',
								),
								'_product_badge_styles'   => array(
									'type'      => 'choosebox',
									'label'     => __( 'Badge Style', 'wholesalex' ),
									'options'   => wholesalex()->Badge_image_display(),
									'default'   => 'style_one',
									'flexBadge' => 'yes',
								),
							),
						),
						'min_order_qty'                 => array(
							'label'      => __( 'Minimum Order Quantity', 'wholesalex' ),
							'type'       => 'min_order_qty',
							'depends_on' => array(
								array(
									'key'   => '_rule_type',
									'value' => 'min_order_qty',
								),
							),
							'attr'       => array(
								'_min_order_qty'         => array(
									'type'        => 'number',
									'label'       => __( 'Minimum Product Quantity', 'wholesalex' ),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
								),
								'_min_order_qty_disable' => array(
									'type'        => 'slider',
									'label'       => __( 'Disable Quantity in Shop & Product Page', 'wholesalex' ),
									'desc'        => __( 'Disable Minimum Product Limit in Shop & Product Page', 'wholesalex' ),
									'default'     => 'no',
									'placeholder' => '',
									'descTooltip' => __( 'Enabling it will disable the minimum order restriction from shop and product pages. So the buyers can add any number of products to the cart. However, they will be restricted in the checkout process.', 'wholesalex' ),
								),
								'_min_order_qty_step'    => array(
									'type'        => 'number',
									'label'       => __( 'Step', 'wholesalex' ),
									'default'     => '1',
									'placeholder' => '',
									'help'        => '',
								),
							),
						),
						'conditions'                    => array(
							'label' => __( 'Conditions: (optional)', 'wholesalex' ),
							'type'  => 'tiers',
							'attr'  =>
							array(
								'_quantity_based_tier' => array(
									'type'   => 'tier',
									'_tiers' => array(
										'data' => array(
											'_conditions_for' => array(
												'type'    => 'select',
												'label'   => 'Condition',
												'options' => apply_filters(
													'wholesalex_dynamic_rules_condition_options',
													array(
														'' => __( 'Choose Conditions...', 'wholesalex' ),
														'cart_total_qty' => __( 'Cart - Total Quantity', 'wholesalex' ),
														'cart_total_value' => __( 'Cart - Total Value', 'wholesalex' ),
														'cart_total_weight' => __( 'Cart - Total Weight', 'wholesalex' ),
														'pro_order_count' => __( 'Lifetime Order Count (Pro)', 'wholesalex' ),
														'pro_total_purchase' => __( 'Lifetime Purchase (Pro)', 'wholesalex' ),
													),
													'conditions'
												),
												'default' => '',
												'placeholder' => '',
												'help'    => '',
											),
											'_conditions_operator' => array(
												'type'    => 'select',
												'label'   => 'Operator',
												'options' => array(
													''     => __( 'Choose Operators...', 'wholesalex' ),
													'less' => __( 'Less than (<)', 'wholesalex' ),
													'less_equal' => __( 'Less than or equal (<=)', 'wholesalex' ),
													'greater_equal' => __( 'Greater than or equal (>=)', 'wholesalex' ),
													'greater' => __( 'Greater than (>)', 'wholesalex' ),
												),
												'default' => '',
												'placeholder' => '',
												'help'    => '',
											),
											'_conditions_value' => array(
												'type'    => 'number',
												'label'   => 'Amount',
												'default' => '',
												'placeholder' => __( 'Amount', 'wholesalex' ),
												'help'    => '',
											),
										),
									),
								),
							),
						),
						'limit'                         => array(
							'label' => __( 'Date & Limit Rule', 'wholesalex' ),
							'type'  => 'date_n_usages_limit',
							'attr'  => array(
								'_start_date' => array(
									'type'        => 'date',
									'label'       => __( 'Start Date', 'wholesalex' ),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
									'flexDate'    => 'yes',
								),
								'_end_date'   => array(
									'type'        => 'date',
									'label'       => __( 'End Date', 'wholesalex' ),
									'default'     => '',
									'placeholder' => '',
									'help'        => '',
									'flexDate'    => 'yes',
								),
							),
						),
					),
				),
			)
		);
	}
}
