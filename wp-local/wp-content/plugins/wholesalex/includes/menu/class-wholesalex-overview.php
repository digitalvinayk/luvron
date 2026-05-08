<?php

/**
 * WholesaleX Overview
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

use WHOLESALEX\WholesaleX_CommonUtils;

use DateTime;
use WP_Query;
use WP_User_Query;

/**
 * WholesaleX Overview Class
 */
class WHOLESALEX_Overview {

	/**
	 * Migration Tool Active
	 *
	 * @var bool
	 */
	public $is_migration_tool_active = false;

	/**
	 * Overview Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'overview_submenu_page_callback' ), 1 );
		add_action( 'rest_api_init', array( $this, 'overview_callback' ) );
		add_action( 'admin_menu', array( $this, 'go_pro_menu_page' ), 99999 );

		add_filter( 'wholesalex_capability_access', array( $this, 'wholesalex_menus_access' ) );
		global $wpdb;
		add_action( 'wp_dashboard_setup', array( $this, 'my_custom_dashboard_widgets' ) );
		add_action( 'wp_ajax_wholesalex_migration_tool_install', array( $this, 'install_callback' ) );
		$this->init_migrations();
		add_action( 'init', array( $this, 'wsx_translations_script_load' ) );
	}

	/**
	 * Translations Script Load
	 */
	public function wsx_translations_script_load() {
		$scripts = array(
			'wholesalex_overview',
			'wholesalex_category',
			'wholesalex_product',
			'wholesalex_profile',
			'wholesalex-builder',
			'whx_integration',
		);

		foreach ( $scripts as $script ) {
			wp_set_script_translations( $script, 'wholesalex', WHOLESALEX_PATH . 'languages/' );
		}
	}
	/**
	 * Check b2bking or wholesale Suite is Exist or Not
	 *
	 * @return void
	 */
	public function init_migrations() {
		$status = false;
		if ( function_exists( 'b2bkingcore_run' ) ) {
			// Ensure the is_plugin_active() function is available.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( is_plugin_active( 'b2bking-wholesale-for-woocommerce/b2bking.php' ) ) {
				$status = true;
			}
		}
		if ( file_exists( WP_PLUGIN_DIR . '/woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php' ) ) {
			// Ensure the is_plugin_active() function is available.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( is_plugin_active( 'woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php' ) ) {
				$status = true;
			}
		}
		$this->is_migration_tool_active = $status;
	}

	/**
	 * Add Dashboard Widgets
	 *
	 * @return void
	 */
	public function migration_tools_content() {
		if ( method_exists( '\WholesaleXMigrationTool', 'get_tool_fields' ) && is_plugin_active( 'wholesalex-migration-tool/wholesalex-migration-tool.php' ) ) {
			wp_enqueue_script( 'whx_migration_tools' );
			wp_localize_script(
				'whx_migration_tools',
				'wholesalex_migration',
				array(
					'nonce'                           => wp_create_nonce( 'wholesalex-migration' ),
					'fields'                          => \WholesaleXMigrationTool::get_tool_fields(),
					'allow_wholesale_suite_migration' => ! wholesalex()->get_setting( 'wholesale_suite_migration_complete', false ),
					'allow_b2bking_migration'         => ! wholesalex()->get_setting( 'b2bking_migration_complete', false ),
					'migration_status'                => array(
						'b2bking_migration'         => get_transient( 'wholesalex_b2bking_migrating' ) ? 'running' : ( wholesalex()->get_setting( 'b2bking_migration_complete' ) ? 'complete' : false ),
						'wholesale_suite_migration' => get_transient( 'wholesalex_wholesalex_suite_migrating' ) ? 'running' : ( wholesalex()->get_setting( 'wholesale_suite_migration_complete' ) ? 'complete' : false ),
					),
					'stats'                           => array(
						'b2bking_migration'         => get_transient( 'wholesalex_b2bking_migration_stats' ),
						'wholesale_suite_migration' => get_transient( 'wholesalex_wholesale_suite_migration_stats' ),
					),
				)
			);
			?>
			<div id="wholesalex_migration_tools_root"></div>
			<?php
		} else {
			wp_enqueue_script( 'whx_migration_tools' );
			wp_enqueue_style( 'whx_migration_tools' );
			wp_localize_script(
				'whx_migration_tools',
				'wholesalex',
				array(
					'url'           => WHOLESALEX_URL,
					'version'       => WHOLESALEX_VER,
					'is_pro_active' => wholesalex()->is_pro_active(),
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
				)
			);
			?>
			<div id="wsx-migration-header"></div>
			<div id="wsx-migration-body"></div>
			<?php
		}
	}

	/** Install Migration Tool Callback */
	public function install_callback() {
		wholesalex()->wsx_migration_install_callback();
	}

	/**
	 * Add Dashboard Widgets
	 *
	 * @return string
	 */
	public function wholesalex_menus_access() {
		$menu_access = wholesalex()->get_setting( '_settings_access_shop_manager_with_wxs_menu', '' );
		return ( 'yes' === $menu_access ? 'manage_woocommerce' : 'manage_options' );
	}

	/**
	 * Overview Menu callback
	 *
	 * @return void
	 */
	public function overview_submenu_page_callback() {

		$wholesalex_menu_icon = plugins_url( 'wholesalex/assets/icons/icon-sm.svg' );
		$wholesalex_menu_icon = apply_filters( 'wholesalex_menu_icon', $wholesalex_menu_icon );

		$menu_name = apply_filters( 'wholesalex_plugin_menu_name', __( 'WholesaleX', 'wholesalex' ) );
		$menu_slug = apply_filters( 'wholesalex_plugin_menu_slug', 'wholesalex' );
		add_menu_page(
			$menu_name,
			$menu_name,
			apply_filters( 'wholesalex_capability_access', 'manage_options' ),
			$menu_slug,
			array( $this, 'output' ),
			$wholesalex_menu_icon,
			59
		);
		add_submenu_page(
			$menu_slug,
			__( 'Dashboard', 'wholesalex' ),
			__( 'Dashboard', 'wholesalex' ),
			apply_filters( 'wholesalex_capability_access', 'manage_options' ),
			$menu_slug,
			array( $this, 'output' ),
		);

		// Sub Menu List Start.
		$manage_options_cap             = apply_filters( 'wholesalex_capability_access', 'manage_options' );
		$is_white_label_enabled         = wholesalex()->get_setting( 'wsx_addon_whitelabel' );
		$is_role_switcher_option_enable = wholesalex()->get_setting( '_settings_role_switcher_option', '' );
		$submenus                       = array(
			array(
				'title'      => __( 'Dynamic Rules', 'wholesalex' ),
				'menu_title' => __( 'Dynamic Rules', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/dynamic-rules',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'dynamic_rules',
			),
			array(
				'title'      => __( 'User Roles', 'wholesalex' ),
				'menu_title' => __( 'User Roles', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/user-role',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'user_roles',
			),
			array(
				'title'      => __( 'Registration Form', 'wholesalex' ),
				'menu_title' => __( 'Registration Form', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/registration',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'registration',
			),
			array(
				'title'      => __( 'Addons', 'wholesalex' ),
				'menu_title' => __( 'Addons', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/addons',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'addons',
			),
			array(
				'title'      => __( 'Users', 'wholesalex' ),
				'menu_title' => __( 'Users', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/users',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'users',
			),
			array(
				'title'      => __( 'Emails', 'wholesalex' ),
				'menu_title' => __( 'Emails', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/emails',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'emails',
			),
			array(
				'title'      => __( 'Settings', 'wholesalex' ),
				'menu_title' => __( 'Settings', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/settings',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'settings',
			),
		);

		if ( 'yes' === $is_role_switcher_option_enable ) {
			$submenus[] = array(
				'title'      => __( 'User Role Requests', 'wholesalex' ),
				'menu_title' => __( 'User Role Requests', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/user_role_change_requests',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'user-role-request',
			);
		}

		if ( 'yes' !== $is_white_label_enabled ) {
			$pro_submenu = array(
				'title'      => __( 'Features', 'wholesalex' ),
				'menu_title' => __( 'Features', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/features',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'features',
			);
			array_splice( $submenus, 8, 0, array( $pro_submenu ) );
		}

		// If Pro plugin is active, add the Pro submenu in the second position.
		if ( function_exists( 'wholesalex_pro' ) && ( version_compare( WHOLESALEX_PRO_VER, '1.5.7', '>' ) ) && wholesalex()->is_pro_active() && 'yes' === wholesalex()->get_setting( 'wsx_addon_conversation' ) ) {
			$menu_title = apply_filters( 'wholesalex_addon_conversation_title', __( 'Conversations', 'wholesalex' ) );
			if ( class_exists( '\WHOLESALEX_PRO\Conversation' ) ) {
				$__pending_conversation = \WHOLESALEX_PRO\Conversation::get_open_conversation_count();
			} else {
				$__pending_conversation = 0;
			}

			$pro_submenu = array(
				'title'      => __( 'Conversations', 'wholesalex' ),
				// translators: %1$s: Menu title, %2$d: Pending conversation count.
				'menu_title' => $__pending_conversation ? sprintf( __( '%1$s <span class="menu-counter"><span>%2$d</span></span>', 'wholesalex' ), $menu_title, $__pending_conversation ) : $menu_title,
				'capability' => $manage_options_cap,
				'slug'       => '/conversation',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'conversation',
			);
			array_splice( $submenus, 3, 0, array( $pro_submenu ) );
		}
		if ( file_exists( WP_PLUGIN_DIR . '/wholesalex-pro/wholesalex-pro.php' ) ) {
			$menu_title = apply_filters( 'wholesalex_addon_conversation_title', __( 'Conversations', 'wholesalex' ) );
			$status     = apply_filters( 'wholesalex_show_license_page', true );
			if ( $status && 'yes' !== $is_white_label_enabled ) {
				$pro_submenu = array(
					'title'      => __( 'License', 'wholesalex' ),
					'menu_title' => __( 'License', 'wholesalex' ),
					'capability' => $manage_options_cap,
					'slug'       => '/license',
					'callback'   => array( $this, 'render_submenu_page' ),
					'identifier' => 'license',
				);
				array_splice( $submenus, 10, 0, array( $pro_submenu ) );
			}
		}
		if ( $this->is_migration_tool_active ) {
			$slug        = apply_filters( 'wholesalex_support_submenu_slug', 'wholesalex-migration' );
			$pro_submenu = array(
				'title'      => __( 'Migration Tool', 'wholesalex' ),
				'menu_title' => __( 'Migration Tool', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => $slug,
				'callback'   => array( $this, 'migration_tools_content' ),
				'identifier' => 'migration_tool',
			);
			array_splice( $submenus, 11, 0, array( $pro_submenu ) );
		}

		foreach ( $submenus as $submenu ) {
			add_submenu_page(
				wholesalex()->get_menu_slug(),
				$submenu['title'],
				$submenu['menu_title'],
				$submenu['capability'],
				'wholesalex-migration' !== $submenu['slug'] ? wholesalex()->get_menu_slug() . '#' . $submenu['slug'] : $submenu['slug'],
				$submenu['callback']
			);
		}
	}

	/**
	 * Overview Actions
	 *
	 * @since 1.0.0
	 */
	public function overview_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/overview_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'overview_action_callback' ),
					'permission_callback' => function () {
						return current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Overview Actions Callback
	 *
	 * @param object $server Rest Server Object.
	 * @return array
	 */
	public function overview_action_callback( $server ) {
		$post = $server->get_params();
		if ( ! ( isset( $post['nonce'] ) && wp_verify_nonce( sanitize_key( $post['nonce'] ), 'wholesalex-registration' ) ) ) {
			return;
		}

		$response = array(
			'status' => false,
			'data'   => array(),
		);

		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';
		if ( 'post' === $type ) {
			wp_send_json( $response );
		} elseif ( 'get' === $type ) {
			$request_for = isset( $post['request_for'] ) ? sanitize_text_field( $post['request_for'] ) : '';
			$start_date  = isset( $post['start_date'] ) ? sanitize_text_field( $post['start_date'] ) : '';
			$end_date    = isset( $post['end_date'] ) ? sanitize_text_field( $post['end_date'] ) : '';
			$period      = isset( $post['period'] ) ? sanitize_text_field( $post['period'] ) : 'day';
			switch ( $request_for ) {
				case 'overview_init':
					// Batch endpoint: returns all non-date-dependent dashboard data in a single call.
					$response['status'] = true;
					$response['data']   = array(
						'new_messages_count'      => $this->get_new_messages_count(),
						'new_registrations_count' => $this->get_new_registrations_count(),
						'recent_orders'           => $this->get_b2b_recent_orders( 10 ),
						'pending_registrations'   => $this->get_pending_users( 10 ),
						'top_customers'           => $this->get_top_customers( 10 ),
						'b2b_customer_count'      => $this->get_b2b_customer_count( $start_date, $end_date ),
					);
					break;
				case 'top_customers':
					$response['status'] = true;
					$response['data']   = $this->get_top_customers( 10 );
					break;
				case 'recent_orders':
					$response['status'] = true;
					$response['data']   = $this->get_b2b_recent_orders( 10 );
					break;
				case 'pending_registrations':
					$response['status'] = true;
					$response['data']   = $this->get_pending_users( 10 );
					break;
				case 'new_registrations_count':
					$response['status'] = true;
					$response['data']   = $this->get_new_registrations_count();
					break;
				case 'new_messages_count':
					$response['status'] = true;
					$response['data']   = $this->get_new_messages_count();
					break;
				case 'b2b_customer_count':
					$response['status'] = true;
					$response['data']   = $this->get_b2b_customer_count( $start_date, $end_date );
					break;
				case 'b2b_order_data':
					$response['status'] = true;
					$response['data']   = $this->get_b2b_order_data( $start_date, $end_date, $period );
					break;
				default:
					// code...
					break;
			}
		}

		wp_send_json( $response );
	}

	/**
	 * Custom Dashboard Widgets
	 *
	 * @return void
	 */
	public function my_custom_dashboard_widgets() {
		wp_add_dashboard_widget(
			'custom_help_widget',          // Widget slug.
			sprintf( '%s - Last Month Insights', wholesalex()->get_plugin_name() ),              // Title.
			array( $this, 'wholesalex_wp_dashboard_callback' ) // Display function.
		);
	}

	/**
	 * WholesaleX WordPress Dashboard Widget
	 *
	 * @return void
	 */
	public function wholesalex_wp_dashboard_callback() {
		// Calculate date range for last month.
		$current_date            = new DateTime();
		$first_day_of_last_month = $current_date->modify( 'first day of last month' )->format( 'Y-m-d' );
		$last_day_of_last_month  = $current_date->modify( 'last day of this month' )->format( 'Y-m-d' );

		// Get B2B statistics.
		$wsx_sales_summary      = $this->get_b2b_order_data( $first_day_of_last_month, $last_day_of_last_month );
		$wsx_b2b_customer_count = $this->get_b2b_customer_count( $first_day_of_last_month, $last_day_of_last_month );

		// Enqueue React dashboard widget script and styles.
		wp_enqueue_script( 'wholesalex_dashboard_widget' );
		wp_enqueue_style( 'wholesalex_dashboard_widget' );

		// Prepare data for React component.
		$widget_data = array(
			'data'    => array(
				'salesSummary'  => array(
					'total_orders'        => isset( $wsx_sales_summary['total_orders'] ) ? (int) $wsx_sales_summary['total_orders'] : 0,
					'total_sales'         => isset( $wsx_sales_summary['total_sales'] ) ? $wsx_sales_summary['total_sales'] : '0',
					'net_revenue'         => isset( $wsx_sales_summary['net_revenue'] ) ? $wsx_sales_summary['net_revenue'] : '0',
					'gross_sales'         => isset( $wsx_sales_summary['gross_sales'] ) ? $wsx_sales_summary['gross_sales'] : '0',
					'average_order_value' => isset( $wsx_sales_summary['average_order_value'] ) ? $wsx_sales_summary['average_order_value'] : '0',
				),
				'customerCount' => (int) $wsx_b2b_customer_count,
				'translations'  => array(
					'customerLabel'     => __( 'Customer No. (B2B)', 'wholesalex' ),
					'totalOrderLabel'   => __( 'Total Order (B2B)', 'wholesalex' ),
					'totalSaleLabel'    => __( 'Total Sale (B2B)', 'wholesalex' ),
					'netRevenueLabel'   => __( 'Net Revenue (B2B)', 'wholesalex' ),
					'grossSaleLabel'    => __( 'Gross Sale (B2B)', 'wholesalex' ),
					'averageOrderLabel' => __( 'Average Order (B2B)', 'wholesalex' ),
				),
			),
			'baseUrl' => WHOLESALEX_URL,
			'nonce'   => wp_create_nonce( 'wholesalex_dashboard_widget' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		);

		// Localize script with data.
		wp_localize_script(
			'wholesalex_dashboard_widget',
			'wholesalexDashboardWidget',
			$widget_data
		);

		// Render the container for React component.
		?>
		<div id="wholesalex-wp-dashboard-root"></div>
		<?php
	}

	/**
	 * Get b2b customer count.
	 *
	 * @param string $start_date Start Date.
	 * @param string $end_date End Date.
	 * @return int
	 */
	public function get_b2b_customer_count( $start_date = '', $end_date = '' ) {
		$start_date = gmdate( 'Y-m-d H:i:s', strtotime( $start_date ) );
		$end_date   = gmdate( 'Y-m-d H:i:s', strtotime( '+1 DAY', strtotime( $end_date ) ) );

		// Meta key and excluded values.
		$meta_key       = '__wholesalex_role';
		$exclude_values = array( '', 'wholesalex_guest', 'wholesalex_b2c_users' );

		// Meta query to exclude non-B2B users.
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => $meta_key,
				'value'   => $exclude_values,
				'compare' => 'NOT IN',
			),
		);

		// Date query if both start and end dates are provided.
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$date_query   = array(
				array(
					'column'    => 'user_registered',
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
					'compare'   => 'BETWEEN',
					'type'      => 'DATETIME',
				),
			);
			$meta_query[] = $date_query; // Adding date query to existing meta query.
		}

		// User query arguments.
		$args = array(
			'meta_query'  => $meta_query,
			'count_total' => true, // Only retrieve the count.
		);

		// Run the user query.
		$user_query = new WP_User_Query( $args );

		// Get the total count of users.
		$user_count = $user_query->get_total();

		return $user_count;
	}

	/**
	 * Get All Addons
	 *
	 * @return array
	 */
	public function get_addons() {
		$addons_data = apply_filters( 'wholesalex_addons_config', array() );

		return $addons_data;
	}

	/**
	 * Get Top Customers
	 *
	 * @param mixed $dependency Dependency.
	 * @return array
	 */
	public static function get_dynamic_rules_localize_data( $dependency ) {
		return array(
			'whx_dr_fields'   => \WHOLESALEX\WHOLESALEX_Dynamic_Rules::get_dynamic_rules_field(),
			'whx_dr_rule'     => $dependency == 'integration' ? array_values( wholesalex()->get_dynamic_rules_by_user_id() ) : \WHOLESALEX\WHOLESALEX_Dynamic_Rules::dynamic_rules_get(),
			'whx_dr_nonce'    => wp_create_nonce( 'whx-export-dynamic-rules' ),
			'whx_dr_currency' => get_woocommerce_currency_symbol(),
		);
	}


	/**
	 * New Output for Integration For Dokan and WXFM
	 *
	 * @param mixed $dependency Dependency.
	 * @return void
	 */
	public static function new_output( $dependency ) {

		wp_enqueue_script( 'wp-api-fetch' );
		wp_enqueue_script( 'wp-components' );
		wp_enqueue_script( 'whx_integration' );
		wp_enqueue_style( 'whx_integration' );
		// Styles for @wordpress/components used by DataViews and Buttons.
		wp_enqueue_style( 'wp-components' );
		// DataViews package styles (vendored from node_modules into assets/css via Grunt).
		if ( wp_style_is( 'wholesalex_dataviews', 'registered' ) ) {
			wp_enqueue_style( 'wholesalex_dataviews' );
		}

		wp_localize_script(
			'whx_integration',
			'wholesalex_overview',
			apply_filters(
				'wholesalex_overview_localize_integration_data',
				apply_filters(
					'wholesalex_overview_localize_integration_data',
					array_merge(
						self::get_dynamic_rules_localize_data( $dependency ),
						array()
					)
				)
			)
		);
		wp_enqueue_style( 'wholesalex' );
		wp_enqueue_style( 'wholesalex_public' );
		?>
		<div id="wholesalex-overview"></div>
		<?php
	}

	/**
	 * Retrieves the email address of the currently logged-in user.
	 *
	 * This method uses WordPress's wp_get_current_user() function to fetch
	 * the current user object. If a user is logged in, their email address is returned.
	 * If no user is logged in, the method returns null.
	 */
	public function get_current_user_info() {
		$user_info = get_userdata( get_current_user_id() );
		return array(
			'name'  => $user_info->first_name ? $user_info->first_name . ( $user_info->last_name ? ' ' . $user_info->last_name : '' ) : $user_info->user_login,
			'email' => $user_info->user_email,
		);
	}

	/**
	 * Output Function
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function output() {
		/**
		 * Enqueue Script
		 *
		 * @since 1.1.0 Enqueue Script (Reconfigure Build File)
		 */
		wp_enqueue_script( 'wholesalex_overview' );
		wp_enqueue_style( 'wholesalex_overview' );
		// Base styles for @wordpress/components used by DataViews.
		wp_enqueue_style( 'wp-components' );
		// DataViews package styles (vendored from node_modules into assets/css via Grunt).
		if ( wp_style_is( 'wholesalex_dataviews', 'registered' ) ) {
			wp_enqueue_style( 'wholesalex_dataviews' );
		}
		$user_heading_data = array();

		// Prepare as heading data.
		foreach ( \WHOLESALEX\WHOLESALEX_Users::get_wholesalex_users_columns() as $key => $value ) {
			$data               = array();
			$data['all_select'] = '';
			$data['name']       = $key;
			$data['title']      = $value;
			if ( 'action' === $key ) {
				$data['type'] = '3dot';
			} elseif ( 'wallet_balance' === $key || 'account_type' === $key ) {
				$data['type'] = 'html';
			} elseif ( 'transaction' === $key ) {
				$data['type'] = 'btn';
			} else {
				$data['type'] = 'text';
			}

			$user_heading_data[ $key ] = $data;
		}

		$user_heading_data['user_id']['status']           = 'yes';
		$user_heading_data['username']['status']          = 'yes';
		$user_heading_data['full_name']['status']         = 'yes';
		$user_heading_data['email']['status']             = 'yes';
		$user_heading_data['registration_date']['status'] = 'yes';
		$user_heading_data['wholesalex_role']['status']   = 'yes';
		$user_heading_data['wholesalex_status']['status'] = 'yes';
		$user_heading_data['action']['status']            = 'yes';
		$user_heading_data['transaction']['status']       = 'yes';

		$conversation_slug = apply_filters( 'wholesalex_addon_conversation_endpoint', 'wholesalex-conversation' );
		$users_slug        = apply_filters( 'wholesalex_users_submenu_slug', 'wholesalex-users' );
		$setting_slug      = apply_filters( 'wholesalex_settings_submenu_slug', 'wholesalex-settings' );

		// regis start.
		$is_woo_username = get_option( 'woocommerce_registration_generate_username' );
		wp_enqueue_script( 'wholesalex_form_builder' );
		// Added Password Condition.
		$password_condition_options = array(
			array(
				'value' => 'uppercase_condition',
				'name'  => 'Uppercase',
			),
			array(
				'value' => 'lowercase_condition',
				'name'  => 'Lowercase',
			),
			array(
				'value' => 'special_character_condition',
				'name'  => 'Special Character',
			),
			array(
				'value' => 'min_length_condition',
				'name'  => 'Min 8 Length',
			),
		);

		// Add File Support Type Array.
		$file_condition_options = array(
			array(
				'value' => 'jpg',
				'name'  => 'JPG',
			),
			array(
				'value' => 'jpeg',
				'name'  => 'JPEG',
			),
			array(
				'value' => 'png',
				'name'  => 'PNG',
			),
			array(
				'value' => 'txt',
				'name'  => 'TXT',
			),
			array(
				'value' => 'pdf',
				'name'  => 'PDF',
			),
			array(
				'value' => 'doc',
				'name'  => 'DOC',
			),
			array(
				'value' => 'docx',
				'name'  => 'DOCX',
			),
		);

		$form_data         = WholesaleX_CommonUtils::get_new_form_builder_data();
		$default_form_data = WholesaleX_CommonUtils::get_empty_form();
		// regis end.

		// User Roles.
		$__roles = array_values( wholesalex()->get_roles() );
		if ( empty( $__roles ) ) {
			$__roles = array(
				array(
					'id'    => 1,
					'label' => __( 'New Role', 'wholesalex' ),
				),
			);
		}

		wp_localize_script(
			'wholesalex_overview',
			'wholesalex_overview',
			apply_filters(
				'wholesalex_overview_localize_data',
				array_merge(
					array(
						/**
						 * Wizard Translation Start
						 */
						'wsx_wizard_url'                   => WHOLESALEX_URL,
						'wsx_wizard_nonce'                 => wp_create_nonce( 'wholesalex-setup-wizard' ) ? wp_create_nonce( 'wholesalex-setup-wizard' ) : menu_page_url( 'wholesalex-settings', false ),
						'wsx_wizard_ajax'                  => admin_url( 'admin-ajax.php' ),
						'wsx_wizard_plugin_install_nonce'  => wp_create_nonce( 'updates' ),
						'wsx_wizard_is_pro_active'         => wholesalex()->is_pro_active(),
						'wsx_wizard_addons'                => $this->get_addons(),
						'wsx_wizard_setting_url'           => menu_page_url( 'wholesalex-settings', false ) ? menu_page_url( 'wholesalex-settings', false ) : get_dashboard_url(),
						'wsx_wizard_dashboard_url'         => menu_page_url( 'wholesalex', false ) ? menu_page_url( 'wholesalex', false ) : get_dashboard_url(),
						'wsx_wizard_site_name'             => get_bloginfo( 'name' ),
						// 'wsx_wizard___wholesalex_initial_setup' => get_option( '__wholesalex_initial_setup', false ),
						'wsx_wizard___wholesalex_initial_setup' => 1,
						'wsx_wizard_woocommer_installed'   => file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ),
						'wsx_wizard_productx_installed'    => file_exists( WP_PLUGIN_DIR . '/product-blocks/product-blocks.php' ),
						/**
						* Wizard Translation Stop
						*/

						/**
						* Conversation Translation Start
						*/
						'top_customer_heading'             => $this->prepare_as_heading_data( $this->get_top_customers_columns() ),
						'recent_order_heading'             => $this->prepare_as_heading_data( $this->get_recent_orders_columns() ),
						'pending_registration_heading'     => $this->prepare_as_heading_data( $this->get_pending_registrations_columns() ),
						'wholesalex_conversation'          => menu_page_url( $conversation_slug, false ),
						'wholesalex_users'                 => menu_page_url( $users_slug, false ),
						'wholesalex_user_info'             => $this->get_current_user_info(),
						/**
						 * Conversation Translation Stop
						 */

						/**
						 * Addon Translation Start
						 */
						'addons'                           => $this->get_addons(),
						'setting_url'                      => menu_page_url( $setting_slug, false ),
						/**
						 * Addon Translation Stop
						 */

						/**
						 * RTL Support start
						 */
						'is_rtl_support'                   => is_rtl(),
						/**
							 * RTL Support stop
							 */

							/**
							 * WSX Users Translation Start
							 */
							'whx_users_heading'            => $user_heading_data,
						'whx_users_user_per_page'          => 10,
						'whx_users_bulk_actions'           => \WHOLESALEX\WHOLESALEX_Users::get_wholesalex_users_bulk_actions(),
						'whx_users_statuses'               => wholesalex()->insert_into_array(
							array( '' => __( 'Select Status', 'wholesalex' ) ),
							\WHOLESALEX\WHOLESALEX_Users::get_user_statuses(),
							0
						),
						'whx_users_exportable_columns'     => ImportExport::exportable_user_columns(),
						'whx_users_roles'                  => \WHOLESALEX\WHOLESALEX_Users::get_role_options(),
						/**
						 * WSX Users Translation Start
						 */

						/**
						 * Registration From Translation Start
						 */
						'whx_form_builder_is_woo_username' => $is_woo_username,
						'whx_form_builder_login_form_data' => wp_json_encode( $default_form_data['loginFields'] ),
						'whx_form_builder_form_data'       => wp_json_encode( $form_data ),
						'whx_form_builder_roles'           => wholesalex()->get_roles( 'roles_option' ),
						'whx_form_builder_whitelabel_enabled' => 'yes' === wholesalex()->get_setting( 'wsx_addon_whitelabel' ) && function_exists( 'wholesalex_whitelabel_init' ),
						'whx_form_builder_slug'            => wholesalex()->get_setting( 'registration_form_buidler_submenu_slug' ),
						'whx_form_builder_privacy_policy_text' => wc_get_privacy_policy_text( 'registration' ),
						'whx_form_builder_password_condition_options' => $password_condition_options,
						'whx_form_builder_file_condition_options' => $file_condition_options,
						'whx_form_builder_billing_fields'  => array(
							'' => __( 'No Mapping', 'wholesalex' ),
							'whx_form_builder_billing_first_name' => __( 'Billing	First Name', 'wholesalex' ),
							'whx_form_builder_billing_last_name' => __( 'Billing	Last Name', 'wholesalex' ),
							'whx_form_builder_billing_company' => __( 'Billing	Company', 'wholesalex' ),
							'whx_form_builder_billing_address_1' => __( 'Billing	Address	1', 'wholesalex' ),
							'whx_form_builder_billing_address_2' => __( 'Billing	Address	2', 'wholesalex' ),
							'whx_form_builder_billing_city' => __( 'Billing	City', 'wholesalex' ),
							'whx_form_builder_billing_postcode' => __( 'Billing	Post Code', 'wholesalex' ),
							'whx_form_builder_billing_country' => __( 'Billing	Country', 'wholesalex' ),
							'whx_form_builder_billing_state' => __( 'Billing	State', 'wholesalex' ),
							'whx_form_builder_billing_email' => __( 'Billing	Email', 'wholesalex' ),
							'whx_form_builder_billing_phone' => __( 'Billing	Phone', 'wholesalex' ),
							'whx_form_builder_custom_user_meta_mapping' => __( 'Custom User	Meta Mapping', 'wholesalex' ),
						),
						/**
						 * Registration From Translation Stop
						 */

						/**
						 * Setting Translation Start
						 */
						'whx_settings_fields'              => \WHOLESALEX\Settings::get_option_settings(),
						'whx_settings_data'                => wholesalex()->get_setting(),
						/**
						 * Setting Translation Stop
						 */

						/**
						 * User Role Translation Start
						 */
						'whx_roles_fields'                 => \WHOLESALEX\WHOLESALEX_Role::get_role_fields(),
						'whx_roles_data'                   => $__roles,
						'whx_roles_nonce'                  => wp_create_nonce( 'whx-export-roles' ),
					),
					self::get_dynamic_rules_localize_data( 'wholesalex' )
				),
			),
		);

		wp_set_script_translations( 'wholesalex_overview', 'wholesalex', WHOLESALEX_PATH . 'languages/' );
		wp_enqueue_style( 'wholesalex' );
		wp_enqueue_style( 'wholesalex_public' );
		?>
		<div id="wholesalex-overview"></div>
		<?php
	}
	/**
	 * Get Top Customer Columns.
	 *
	 * @return array
	 */
	public function get_top_customers_columns() {
		$columns = array(
			'name_n_email'    => __( 'Name and Email', 'wholesalex' ),
			'wallet_balance'  => __( 'Wallet Balance', 'wholesalex' ),
			'total_purchase'  => __( 'Total Purchase', 'wholesalex' ),
			/* translators: %s - Plugin Name */
			'wholesalex_role' => wp_sprintf( __( '%s Role', 'wholesalex' ), wholesalex()->get_plugin_name() ),
		);

		if ( ! ( 'yes' === wholesalex()->get_setting( 'wsx_addon_wallet' ) && wholesalex()->is_pro_active() ) ) {
			unset( $columns['wallet_balance'] );
		}

		$columns = apply_filters( 'wholesalex_dashboard_top_customer_columns', $columns );

		return $columns;
	}

	/**
	 * Get Recent Orders Columns
	 *
	 * @return array
	 */
	public function get_recent_orders_columns() {
		$columns = array(
			'ID'            => __( 'Order ID', 'wholesalex' ),
			'customer_name' => __( 'Name', 'wholesalex' ),
			'order_date'    => __( 'Date', 'wholesalex' ),
			'order_status'  => __( 'Status', 'wholesalex' ),
			'view_order'    => __( 'Action', 'wholesalex' ),
		);

		$columns = apply_filters( 'wholesalex_dashboard_recent_orders_columns', $columns );

		return $columns;
	}

	/**
	 * Get Pending Registrations Column.
	 *
	 * @return array
	 */
	public function get_pending_registrations_columns() {
		$columns = array(
			'name_n_email'      => __( 'Name and Email', 'wholesalex' ),
			'user_registered'   => __( 'Regi. Date', 'wholesalex' ),
			'registration_role' => __( 'Regi. Role', 'wholesalex' ),
			'edit_user'         => __( 'Action', 'wholesalex' ),
		);

		$columns = apply_filters( 'wholesalex_dashboard_pending_registrations_columns', $columns );

		return $columns;
	}

	/**
	 * Prepare Column as Heading Data.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function prepare_as_heading_data( $columns ) {
		$user_heading_data = array();
		foreach ( $columns as $key => $value ) {
			$data          = array();
			$data['name']  = $key;
			$data['title'] = $value;
			switch ( $key ) {
				case 'wallet_balance':
					$data['type'] = 'html';
					break;
				case 'total_purchase':
					$data['type'] = 'html';
					break;
				case 'name_n_email':
					$data['type'] = 'name_n_email';
					break;
				case 'view_order':
					$data['type'] = 'view_order';
					break;

				case 'edit_user':
					$data['type'] = 'edit_user';
					break;
				default:
					$data['type'] = 'text';
					break;
			}

			$user_heading_data[ $key ] = $data;
		}

		return $user_heading_data;
	}

	/**
	 * Add Go Pro Menu Page
	 *
	 * @return void
	 * @since 1.1.2
	 */
	public function go_pro_menu_page() {
		if ( ! Xpo::is_lc_active() || Xpo::is_lc_expired() ) {

			$now = new \DateTime( 'now', wp_timezone() );

			$start_date = new \DateTime( '2026-01-01 00:00:00', wp_timezone() );
			$end_date   = new \DateTime( '2026-02-15 23:59:59', wp_timezone() );

			if ( $now >= $start_date && $now <= $end_date ) {
				$button_text = esc_html__( 'New Year Sale!', 'wholesalex' );
			} else {
				$button_text = esc_html__( 'Upgrade to Pro', 'wholesalex' );
			}
			$title = sprintf(
				'<div class="wsx-d-flex wsx-item-center wsx-gap-8 wsx-color-lime "><div class="wsx-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" stroke="currentColor" viewBox="0 0 32 32"><path fill="currentColor" d="m3.488 13.184 6.272 6.112-1.472 8.608L16 23.84l7.712 4.064-1.472-8.608 6.272-6.112-8.64-1.248L16 4.128l-3.872 7.808z"/></svg></div>%s</div>',
				Xpo::is_lc_expired() ? __( 'Renew License', 'wholesalex' ) : $button_text
			);

			add_submenu_page(
				'wholesalex',
				'',
				$title,
				apply_filters( 'wholesalex_capability_access', 'manage_options' ),
				'go_wholesalex_pro',
				array( $this, 'go_pro_redirect' )
			);
		}
	}

	/**
	 * Go Pro Redirect From Dashboard
	 *
	 * @since 1.1.2
	 */
	public function go_pro_redirect() {
		$license_key = Xpo::get_lc_key();
		$pro_link    = Xpo::is_lc_expired() ? 'https://account.wpxpo.com/checkout/?edd_license_key=' . $license_key : Xpo::generate_utm_link( array( 'utmKey' => 'summer_db' ) );
		if (isset($_GET['page']) && 'go_wholesalex_pro' === sanitize_text_field($_GET['page'])) { //phpcs:ignore
			wp_redirect($pro_link); //phpcs:ignore
			die();
		} else {
			return;
		}
	}


	/**
	 * Get top customers
	 *
	 * @param integer $limit Limit.
	 * @return array
	 */
	public function get_top_customers( $limit = 10 ) {
		// For Getting Top B2B Users Data. (Based on Sales).
		$active_user_query = new WP_User_Query(
			array(
				'fields'     => array( 'display_name', 'user_email', 'ID' ),
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => '__wholesalex_status',
						'value'   => 'active',
						'compare' => '=',
					),
					array(
						'key'     => '__wholesalex_role',
						'value'   => array( '', 'wholesalex_guest', 'wholesalex_b2c_users' ),
						'compare' => '!=',
					),

				),
				'number'     => $limit,

			)
		);

		$active_users_data = (array) $active_user_query->get_results();

		foreach ( $active_users_data as $key => $value ) {
			$__temp                   = (array) $value;
			$__id                     = $__temp['id'];
			$__temp['avatar_url']     = get_avatar_url( $__id );
			$__temp['total_purchase'] = wc_get_customer_total_spent( $__id );
			if ( wholesalex()->is_pro_active() && function_exists( 'wholesalex_wallet' ) ) {
				$__temp['wallet_balance'] = wholesalex_wallet()->get_wholesalex_balance( $__id );
			}
			$__temp['wholesalex_role'] = wholesalex()->get_role_name_by_role_id( get_user_meta( $__id, '__wholesalex_role', true ) );
			$active_users_data[ $key ] = $__temp;
		}

		$__sort_colum = array_column( $active_users_data, 'total_purchase' );
		array_multisort( $__sort_colum, SORT_DESC, $active_users_data );

		return $active_users_data;
	}

	/**
	 * Get b2b recent orders
	 *
	 * @param integer $limit limit.
	 * @return array
	 */
	public function get_b2b_recent_orders( $limit = 10 ) {
		$args = array(
			'status'     => array( 'completed', 'on-hold' ),
			'limit'      => 10,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'meta_query' => array(
				array(
					'key'     => '__wholesalex_order_type',
					'value'   => 'b2b',
					'compare' => '=',
				),
			),
		);

		$orders            = wc_get_orders( $args );
		$b2b_recent_orders = array();
		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			// Get order details.
			$order_data = array(
				'ID'           => $order->get_id(),
				'customer_id'  => $order->get_user_id(),
				'order_date'   => $order->get_date_created()->date( 'Y-m-d' ),
				'order_status' => $order->get_status(),
			);

			$__user_data                 = get_userdata( $order_data['customer_id'] );
			$order_data['customer_name'] = $__user_data ? $__user_data->display_name : 'Guest';
			$order_data['order_status']  = wc_get_order_status_name( $order_data['order_status'] );
			$order_data['view_order']    = admin_url( 'post.php?post=' . $order_data['ID'] . '&action=edit' );
			$b2b_recent_orders[]         = $order_data;
		}
		return $b2b_recent_orders;
	}

	/**
	 * Get pending users.
	 *
	 * @param integer $limit limit.
	 * @return array
	 */
	public function get_pending_users( $limit = 10 ) {
		$pending_user_query = new WP_User_Query(
			array(
				'meta_key'     => '__wholesalex_status',
				'meta_value'   => 'pending',
				'meta_compare' => '=',
				'fields'       => array( 'display_name', 'user_email', 'ID', 'user_registered' ),
				'count_total'  => true,
				'number'       => $limit,
			)
		);

		$pending_user_data = (array) $pending_user_query->get_results();

		foreach ( $pending_user_data as $key => $value ) {
			$__temp                      = (array) $value;
			$__id                        = $__temp['id'];
			$__temp['avatar_url']        = get_avatar_url( $__id );
			$__temp['registration_role'] = wholesalex()->get_role_name_by_role_id( get_user_meta( $__id, '__wholesalex_registration_role', true ) );
			$__temp['edit_user']         = get_edit_user_link( $__id );
			$pending_user_data[ $key ]   = $__temp;
		}

		return $pending_user_data;
	}

	/**
	 * Get new registration count.
	 *
	 * @return int
	 */
	public function get_new_registrations_count() {
		$pending_user_query = new WP_User_Query(
			array(
				'meta_key'     => '__wholesalex_status',
				'meta_value'   => 'pending',
				'meta_compare' => '=',
				'fields'       => array( 'display_name', 'user_email', 'ID', 'user_registered' ),
				'count_total'  => true,
			)
		);

		return $pending_user_query->get_total();
	}

	/**
	 * Get new messages count.
	 *
	 * @return int
	 */
	public function get_new_messages_count() {
		$messages_query = new WP_Query(
			array(
				'post_type'    => 'wsx_conversation',
				'post_status'  => 'publish',
				'meta_key'     => '__conversation_status',
				'meta_value'   => 'open',
				'meta_compare' => '=',
			)
		);

		return $messages_query->found_posts;
	}

	/**
	 * Get Previews Growth orders.
	 *
	 * @param string $current_value Current value.
	 * @param string $previous_value Previous value.
	 * @return float|int
	 */
	public function calculate_growth_rate( $current_value, $previous_value ) {
		$current_value  = (float) $current_value;
		$previous_value = (float) $previous_value;
		if ( 0.0 === (float) $previous_value ) {
			// Avoid division by zero; return 100% growth if previous sales are 0.
			return $current_value > 0 ? 100 : 0;
		}
		// Calculate growth percentage.
		$growth_percentage = ( ( $current_value - $previous_value ) / $previous_value ) * 100;
		return (int) round( $growth_percentage );
	}
	/**
	 * Get Previews Growth orders.
	 *
	 * @param mixed $number Current value.
	 * @return float|int
	 */
	private function format_number( $number ) {
		return number_format( $number, 2, '.', '' );
	}

	/**
	 * Determine the period based on the number of days between two dates.
	 *
	 * @param string $start_date The start date.
	 * @param string $end_date The end date.
	 * @return string The period.
	 */
	public function determine_period( $start_date, $end_date ) {
		// Convert dates to DateTime objects.
		$start_date_obj = new DateTime( $start_date );
		$end_date_obj   = new DateTime( $end_date );

		// Calculate the difference in days.
		$interval = $start_date_obj->diff( $end_date_obj );
		$days     = $interval->days;

		// Determine the period based on the number of days.
		if ( 7 === $days ) {
			return 'Week';
		} elseif ( 30 === $days || 31 === $days ) {
			return 'Month';
		} elseif ( 90 === $days || 91 === $days || 92 === $days ) {
			return 'Quarter';
		} elseif ( 365 === $days ) {
			return 'Year';
		} elseif ( $days > 365 ) {
			return $days . ' Days';
		} else {
			return $days . ' Days';
		}
	}

	/**
	 * Get b2b order data.
	 *
	 * @param string $start_date start date.
	 * @param string $end_date end data.
	 * @param string $period period.
	 * @return array
	 */
	public function get_b2b_order_data( $start_date = '', $end_date = '', $period = '' ) {
		global $wpdb;

		$start_date = gmdate( 'Y-m-d H:i:s', strtotime( $start_date ) );
		$end_date   = gmdate( 'Y-m-d H:i:s', strtotime( '+1 DAY', strtotime( $end_date ) ) );

		$period = $this->determine_period( $start_date, $end_date );

		// Calculate the previous period date range.
		$start_date_obj = new DateTime( $start_date );
		$end_date_obj   = new DateTime( $end_date );
		$interval       = $start_date_obj->diff( $end_date_obj );

		$prev_start_date = $start_date_obj->sub( $interval )->format( 'Y-m-d H:i:s' );
		$prev_end_date   = $end_date_obj->sub( $interval )->format( 'Y-m-d H:i:s' );

		$graph_data   = array();
		$graph_legend = array();

		$start_new_date = new DateTime( $start_date );
		$end_new_date   = new DateTime( $end_date );

		$current_date = clone $start_new_date;

		while ( $current_date < $end_new_date ) {
			$formatted_date                = $current_date->format( 'Y-m-d' );
			$graph_data[ $formatted_date ] = 0;
			$graph_legend[]                = $formatted_date;
			$current_date->modify( '+1 day' );
		}

		$args = array(
			'status'     => array( 'wc-completed', 'wc-refunded' ),
			'date_paid'  => $start_date . '...' . $end_date,
			'meta_key'   => '__wholesalex_order_type',
			'meta_value' => 'b2b',
			'limit'      => -1,
		);

		$orders = wc_get_orders( $args );

		$total_sales        = 0;
		$gross_sales        = 0;
		$net_revenue        = 0;
		$total_orders       = 0;
		$graph_data         = array();
		$gross_graph_data   = array();
		$revenue_graph_data = array();

		// Initialize the date range.
		$date_range = new \DatePeriod(
			new \DateTime( $start_date ),
			new \DateInterval( 'P1D' ),
			( new \DateTime( $end_date ) )->modify( '+1 day' )
		);

		// Prepare the graph data for each date in the range.
		foreach ( $date_range as $date ) {
			$formatted_date                        = $date->format( 'Y-m-d' );
			$graph_data[ $formatted_date ]         = 0;
			$gross_graph_data[ $formatted_date ]   = 0;
			$revenue_graph_data[ $formatted_date ] = 0;
		}

		// Process each order.
		foreach ( $orders as $order ) {
			++$total_orders;
			$total_sales += $order->get_total();
			$gross_sales += $order->get_subtotal();
			$net_revenue += $order->get_total() - $order->get_total_refunded();

			$paid_date = $order->get_date_paid()->date( 'Y-m-d' );

			// Update the respective graph data if the paid date exists in the date range.
			if ( isset( $graph_data[ $paid_date ] ) ) {
				$graph_data[ $paid_date ] += $order->get_total();
				$graph_data[ $paid_date ]  = number_format( $graph_data[ $paid_date ], 2, '.', '' );
			}

			if ( isset( $gross_graph_data[ $paid_date ] ) ) {
				$gross_graph_data[ $paid_date ] += $order->get_subtotal();
				$gross_graph_data[ $paid_date ]  = number_format( $gross_graph_data[ $paid_date ], 2, '.', '' );
			}

			if ( isset( $revenue_graph_data[ $paid_date ] ) ) {
				$revenue_graph_data[ $paid_date ] += $order->get_total() - $order->get_total_refunded();
				$revenue_graph_data[ $paid_date ]  = number_format( $revenue_graph_data[ $paid_date ], 2, '.', '' );
			}
		}

		// Fetch previous period data.
		$prev_args = array(
			'status'     => array( 'wc-completed', 'wc-refunded' ),
			'date_paid'  => $prev_start_date . '...' . $prev_end_date,
			'meta_key'   => '__wholesalex_order_type',
			'meta_value' => 'b2b',
			'limit'      => -1,
		);

		$prev_orders = wc_get_orders( $prev_args );

		$prev_total_sales  = 0;
		$prev_gross_sales  = 0;
		$prev_net_revenue  = 0;
		$prev_total_orders = 0;

		// Process each previous period order.
		foreach ( $prev_orders as $order ) {
			++$prev_total_orders;
			$prev_total_sales += $order->get_total();
			$prev_gross_sales += $order->get_subtotal();
			$prev_net_revenue += $order->get_total() - $order->get_total_refunded();
		}
		$data = array(
			'total_sales'         => wc_price( $total_sales ),
			'gross_sales'         => wc_price( $gross_sales ),
			'net_revenue'         => wc_price( $net_revenue ),
			'average_order_value' => 0 !== $total_orders ? wc_price( round( $total_sales / $total_orders, 2 ) ) : wc_price( 0 ),
			'total_orders'        => $total_orders,
			'sales_graph'         => $graph_data,
			'gross_graph'         => $gross_graph_data,
			'revenue_graph'       => $revenue_graph_data,
			'order_graph'         => $graph_data, // Assuming you still want the sales graph for orders.
			'graph_legend'        => $graph_legend,
			'prev_average_order'  => 0 !== $prev_total_orders && 0 !== $total_orders ? $this->calculate_growth_rate( ( $total_sales / $total_orders ), ( $prev_total_sales / $prev_total_orders ) ) : 0,
			'prev_total_sales'    => $this->calculate_growth_rate( $total_sales, $this->format_number( $prev_total_sales ) ),
			'prev_gross_sales'    => $this->calculate_growth_rate( $gross_sales, $this->format_number( $prev_gross_sales ) ),
			'prev_net_revenue'    => $this->calculate_growth_rate( $net_revenue, $this->format_number( $prev_net_revenue ) ),
			'prev_total_order'    => $this->calculate_growth_rate( $total_orders, $this->format_number( $prev_total_orders ) ),
			'period'              => $period,
		);

		return $data;
	}
}
