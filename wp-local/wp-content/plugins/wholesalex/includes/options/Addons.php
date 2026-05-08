<?php
/**
 * Addons Page
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Setting Class
 */
class Addons {

	/**
	 * Setting Constructor
	 */
	public function __construct() {
		add_filter( 'wholesalex_addons_config', array( $this, 'pro_addons_config' ), 1 );

		add_action( 'rest_api_init', array( $this, 'register_addons_restapi' ) );
	}

	/**
	 * Register addon restapi route
	 *
	 * @return void
	 */
	public function register_addons_restapi() {
		register_rest_route(
			'wholesalex/v1',
			'/addons/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'addon_restapi_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Addon RestAPI Callback
	 *
	 * @param object $server Server.
	 * @return void
	 */
	public function addon_restapi_callback( $server ) {
		$post = $server->get_params();
		if ( ! ( isset( $post['nonce'] ) && wp_verify_nonce( sanitize_key( $post['nonce'] ), 'wholesalex-registration' ) ) ) {
			return;
		}

		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		$response = array(
			'status' => false,
			'data'   => array(),
		);

		switch ( $type ) {
			case 'get':
				$response['status'] = true;
				$response['data']   = $this->get_addons();
				break;
			case 'post':
				$request_for = isset( $post['request_for'] ) ? sanitize_text_field( $post['request_for'] ) : '';
				$addon       = isset( $post['addon'] ) ? sanitize_text_field( $post['addon'] ) : '';
				if ( 'install_plugin' === $request_for ) {
					$response['status'] = true;

					$response['data'] = $this->install_and_active_plugin( $addon );

					return wp_send_json( $response );
				}

				$addon_name  = isset( $post['addon'] ) ? sanitize_text_field( $post['addon'] ) : '';
				$addon_value = isset( $post['status'] ) ? sanitize_text_field( $post['status'] ) : '';
				if ( 'wsx_addon_dokan_integration' === $addon_name ) {
					if ( 'no' === $addon_value ) {
						$activate_status = deactivate_plugins( 'multi-vendor-marketplace-b2b-for-wholesalex-dokan/multi-vendor-marketplace-b2b-for-wholesalex-dokan.php', true );
						$message         = 'Success';
					} else {
						$activate_status = activate_plugin( 'multi-vendor-marketplace-b2b-for-wholesalex-dokan/multi-vendor-marketplace-b2b-for-wholesalex-dokan.php', '', false, true );
						if ( is_wp_error( $activate_status ) ) {
							$message = $activate_status->get_error_message();
						} else {
							$message = 'Successfully Installed and Activated';
						}
					}
					wp_send_json(
						array(
							'status' => true,
							'data'   => $message,
						)
					);
				}

				if ( 'wsx_addon_wcfm_integration' === $addon_name ) {
					if ( 'no' === $addon_value ) {
						$activate_status = deactivate_plugins( 'wholesalex-wcfm-b2b-multivendor-marketplace/wholesalex-wcfm-b2b-multivendor-marketplace.php', true );
						$message         = 'Success';
					} else {
						$activate_status = activate_plugin( 'wholesalex-wcfm-b2b-multivendor-marketplace/wholesalex-wcfm-b2b-multivendor-marketplace.php', '', false, true );
						if ( is_wp_error( $activate_status ) ) {
							$message = $activate_status->get_error_message();
						} else {
							$message = 'Successfully Installed and Activated';
						}
					}
					wp_send_json(
						array(
							'status' => true,
							'data'   => $message,
						)
					);
				}
				if ( 'wsx_addon_migration_integration' === $addon_name ) {
					if ( 'no' === $addon_value ) {
						$activate_status = deactivate_plugins( 'wholesalex-migration-tool/wholesalex-migration-tool.php', true );
						$message         = 'Success';
					} else {
						$activate_status = activate_plugin( 'wholesalex-migration-tool/wholesalex-migration-tool.php', '', false, true );
						if ( is_wp_error( $activate_status ) ) {
							$message = $activate_status->get_error_message();
						} else {
							$message = 'Successfully Installed and Activated';
						}
					}
					wp_send_json(
						array(
							'status' => true,
							'data'   => $message,
						)
					);
				}
				if ( 'wsx_addon_recaptcha' === $addon_name ) {
					$__site_key   = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
					$__secret_key = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
					if ( empty( $__site_key ) || empty( $__secret_key ) ) {
						$response['status'] = false;
						/* translators: %s Plugin Name */
						$response['data'] = sprintf( __( 'Please Set Site Key and Secret Key Before Enable Recaptcha (Path: Dashboard > %s > Settings > Recaptcha)', 'wholesalex' ), wholesalex()->get_plugin_name() );
					}
				}
				do_action( 'wholesalex_' . $addon_name . '_before_status_update', $addon_value );
				$error = apply_filters( 'wholesalex_' . $addon_name . '_error', '', $addon_value );
				if ( $addon_name && current_user_can( 'administrator' ) && '' === $error ) {
					$addon_data                                    = wholesalex()->get_setting();
					$addon_data[ $addon_name ]                     = $addon_value;
					$GLOBALS['wholesalex_settings'][ $addon_name ] = $addon_value;
					update_option( 'wholesalex_settings', $addon_data );
					do_action( 'wholesalex_' . $addon_name . '_after_status_update', $addon_value );
					$response['status'] = true;
					$response['data']   = __( 'Successfully Updated!', 'wholesalex' );

				} else {
					$response['status'] = false;
					$response['data']   = __( 'Update Failed!', 'wholesalex' );
				}
				break;

			default:
				// code...
				break;
		}

		wp_send_json( $response );
	}

	/**
	 * Install and Active Addon Plugin
	 *
	 * @param string $addon AddonId.
	 * @return string
	 */
	public function install_and_active_plugin( $addon ) {

		$message    = '';
		$plugin_url = '';
		if ( 'wsx_addon_dokan_integration' === $addon ) {
			$plugin_url = 'https://downloads.wordpress.org/plugin/multi-vendor-marketplace-b2b-for-wholesalex-dokan.zip';
		} elseif ( 'wsx_addon_wcfm_integration' === $addon ) {
			$plugin_url = 'https://downloads.wordpress.org/plugin/wholesalex-wcfm-b2b-multivendor-marketplace.zip';
		} elseif ( 'wsx_addon_migration_integration' === $addon ) {
			$plugin_url = 'https://downloads.wordpress.org/plugin/wholesalex-migration-tool.zip';
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $plugin_url );

		if ( 'wsx_addon_dokan_integration' === $addon ) {
			$plugin_file = 'dokan-lite/dokan.php';
			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
				$upgrader->install( 'https://downloads.wordpress.org/plugin/dokan-lite.zip' );
			}
			activate_plugin( $plugin_file );
		} elseif ( 'wsx_addon_wcfm_integration' === $addon ) {
			$plugin_file = 'wc-multivendor-marketplace/wc-multivendor-marketplace.php';
			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
				$upgrader->install( 'https://downloads.wordpress.org/plugin/wc-multivendor-marketplace.zip' );
			}
			activate_plugin( $plugin_file );
		}

		if ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();
		} else {
			if ( 'wsx_addon_dokan_integration' === $addon ) {
				deactivate_plugins( 'wholesalex-dokan-b2b-multi-vendor-marketplace/wholesalex-dokan-b2b-multi-vendor-marketplace.php', true );
				$activate_status = activate_plugin( 'multi-vendor-marketplace-b2b-for-wholesalex-dokan/multi-vendor-marketplace-b2b-for-wholesalex-dokan.php', '', false, true );
			} elseif ( 'wsx_addon_wcfm_integration' === $addon ) {
				$activate_status = activate_plugin( 'wholesalex-wcfm-b2b-multivendor-marketplace/wholesalex-wcfm-b2b-multivendor-marketplace.php', '', false, true );
			} elseif ( 'wsx_addon_migration_integration' === $addon ) {
				$activate_status = activate_plugin( 'wholesalex-migration-tool/wholesalex-migration-tool.php', '', false, true );
			}
			if ( is_wp_error( $activate_status ) ) {
				$message = $activate_status->get_error_message();
			} else {
				$message = 'Successfully Installed and Activated';
			}
		}

		return $message;
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
	 * Check if Dokan Plugin is Active
	 *
	 * @param string $plugin_name Plugin Name.
	 * @return bool
	 */
	public function check_required_plugins( $plugin_name ) {
		// Ensure the `is_plugin_active` function is available.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Define plugin paths for each supported plugin.
		$plugin_paths = array(
			'dokan'     => array(
				'dokan-lite/dokan.php',
			),
			'wcfm'      => array(
				'wc-frontend-manager/wc_frontend_manager.php',
			),
			'migration' => array(
				'wholesalex-migration-tool/wholesalex-migration-tool.php',
			),
		);

		// Check if the plugin name exists in the paths array.
		if ( ! isset( $plugin_paths[ $plugin_name ] ) ) {
			return false;
		}

		// Loop through plugin paths for the requested plugin and check if any are active.
		foreach ( $plugin_paths[ $plugin_name ] as $plugin_path ) {
			if ( is_plugin_active( $plugin_path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Pro Addons Config
	 *
	 * @param object $config Addon Configuration.
	 * @return object $config .
	 * @since 1.0.0
	 * @since 1.0.4 Add Bulk Order.
	 */
	public function pro_addons_config( $config ) {
		$config['wsx_addon_bulkorder'] = array(
			'name'                => __( 'Bulk Order Form', 'wholesalex' ),
			'desc'                => __( 'Help buyers place large orders quickly. They can build purchase lists, update them at any time, and complete the order directly from their “My Account” page.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/bulkorder.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/bulk-order/?utm_source=wholesalex-menu&utm_medium=addons-docs&utm_campaign=wholesalex-DB',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'         => 'https://getwholesalex.com/bulk-order/?utm_source=wholesalex-menu&utm_medium=addons-more_features&utm_campaign=wholesalex-DB',
			'video'               => 'https://www.youtube.com/embed/uwHojBY0lZk',
			'status'              => wholesalex()->get_setting( 'wsx_addon_bulkorder' ),
			'setting_id'          => '#bulkorder',
			'lock_status'         => ! ( wholesalex()->is_pro_active() ),
		);

		$config['wsx_addon_subaccount'] = array(
			'name'                => __( 'Subaccounts ', 'wholesalex' ),
			'desc'                => __( 'Allow registered users to create subaccounts with customized permissions. Subaccount holders can perform allowed tasks on behalf of the main account owner.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/subaccount.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/subaccounts/?utm_source=wholesalex-menu&utm_medium=addons-docs&utm_campaign=wholesalex-DB',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'         => 'https://getwholesalex.com/subaccounts-in-woocommerce-b2b-stores/?utm_source=wholesalex-menu&utm_medium=addons-more_features&utm_campaign=wholesalex-DB',
			'video'               => 'https://www.youtube.com/embed/cO4AYwkXyco',
			'status'              => wholesalex()->get_setting( 'wsx_addon_subaccount' ),
			'setting_id'          => '#subaccounts',
			'lock_status'         => ! ( wholesalex()->is_pro_active() ),
		);

		$config['wsx_addon_raq'] = array(
			'name'                   => __( 'Request a Quote', 'wholesalex' ),
			'desc'                   => __( 'Allow buyers to request custom quotes for the products they want. You can set personalized pricing, negotiate, and finalize purchase terms with ease.', 'wholesalex' ),
			'img'                    => WHOLESALEX_URL . 'assets/img/addons/raq.svg',
			'docs'                   => 'https://getwholesalex.com/request-a-quote/?utm_source=wholesalex-menu&utm_medium=addons-docs&utm_campaign=wholesalex-DB',
			'live'                   => '',
			'is_pro'                 => true,
			'is_different_plugin'    => false,
			'depends_on'             => apply_filters( 'wholesalex_addon_raq_depends_on', array( 'wsx_addon_conversation' => __( 'Conversation', 'wholesalex' ) ) ),
			'eligible_price_ids'     => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'            => 'https://getwholesalex.com/request-a-quote/?utm_source=wholesalex-menu&utm_medium=addons-more_features&utm_campaign=wholesalex-DB',
			'video'                  => 'https://www.youtube.com/embed/jOIdNj18OEI',
			'status'                 => wholesalex()->get_setting( 'wsx_addon_raq' ),
			'setting_id'             => '#conversation',
			'lock_status'            => ! ( wholesalex()->is_pro_active() ),
			'is_conversation_active' => wholesalex()->get_setting( 'wsx_addon_conversation' ),
		);

		$config['wsx_addon_conversation'] = array(
			'name'                => __( 'Conversations', 'wholesalex' ),
			'desc'                => __( 'Enable messaging so customers can communicate with one another directly from their “My Account” dashboard and keep all conversations centralized.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/conversation.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/conversation/?utm_source=wholesalex-menu&utm_medium=addons-docs&utm_campaign=wholesalex-DB',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'         => 'https://getwholesalex.com/conversation/?utm_source=wholesalex-menu&utm_medium=addons-more_features&utm_campaign=wholesalex-DB',
			'video'               => 'https://www.youtube.com/embed/-g9t44AhSRw?si=LO4EmZX87MaNyFjl',
			'status'              => wholesalex()->get_setting( 'wsx_addon_conversation' ),
			'setting_id'          => '#conversation',
			'lock_status'         => ! ( wholesalex()->is_pro_active() ),
		);

		$config['wsx_addon_wallet'] = array(
			'name'                => __( 'WholesaleX Wallet', 'wholesalex' ),
			/* translators: %s Plugin Name */
			'desc'                => __( 'Activate a store wallet that buyers can use as a payment method. They can deposit, store a balance, and check out faster with wallet funds.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/wallet.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/wallet/?utm_source=wholesalex-menu&utm_medium=addons-docs&utm_campaign=wholesalex-DB',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'         => 'https://getwholesalex.com/wallet/?utm_source=wholesalex-menu&utm_medium=addons-more_features&utm_campaign=wholesalex-DB',
			'video'               => 'https://www.youtube.com/embed/r4_V2ZW4p4I?si=ZO3-iZaffU8s7Cfw',
			'status'              => wholesalex()->get_setting( 'wsx_addon_wallet' ),
			'setting_id'          => '#wallet',
			'lock_status'         => ! ( wholesalex()->is_pro_active() ),
		);

		$config['wsx_addon_whitelabel'] = array(
			'name'                => __( 'White Label', 'wholesalex' ),
			'desc'                => __( 'Add your own branding while working on client sites using WholesaleX. Customize the plugin’s appearance to match your brand identity.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/whitelabel.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/white-label/?utm_source=wholesalex-menu&utm_medium=addons-docs&utm_campaign=wholesalex-DB',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '3', '6', '7' ),
			'status'              => wholesalex()->get_setting( 'wsx_addon_whitelabel' ),
			'lock_status'         => ! ( wholesalex()->is_pro_active() && in_array( wholesalex()->get_price_id(), array( '3', '6', '7' ) ) ),
			'setting_id'          => '#whitelabel',
			'video'               => 'https://www.youtube.com/embed/xMTJYQFbWEw',
		);

		$config['wsx_addon_recaptcha'] = array(
			'name'        => __( 'reCAPTCHA', 'wholesalex' ),
			'desc'        => __( 'Protect your website from suspicious login attempts by adding an extra layer of security with Google reCAPTCHA v3.', 'wholesalex' ),
			'img'         => WHOLESALEX_URL . 'assets/img/addons/recaptcha.svg',
			'docs'        => 'https://getwholesalex.com/docs/wholesalex/add-on/recaptcha/?utm_source=wholesalex-menu&utm_medium=addons-docs&utm_campaign=wholesalex-DB',
			'live'        => '',
			'is_pro'      => false,
			'moreFeature' => 'https://getwholesalex.com/docs/wholesalex/add-on/recaptcha/?utm_source=wholesalex-menu&utm_medium=addons-docs&utm_campaign=wholesalex-DB',
			'video'       => 'https://www.youtube.com/',
			'status'      => wholesalex()->get_setting( 'wsx_addon_recaptcha' ),
			'setting_id'  => '#recaptcha',
			'lock_status' => false,
		);

		$config['wsx_addon_dokan_integration'] = array(
			'name'                 => __( 'WholesaleX for Dokan', 'wholesalex' ),
			'desc'                 => __( 'Turn your store into a B2B multi-vendor marketplace. Create and manage wholesale discounts, dynamic rules, and user roles. Easily handle conversations between vendors and customers.', 'wholesalex' ),
			'img'                  => WHOLESALEX_URL . 'assets/img/addons/dokan_integration.svg',
			'docs'                 => 'https://getwholesalex.com/docs/wholesalex/wholesalex-for-dokan/',
			'live'                 => '',
			'is_pro'               => false,
			'is_different_plugin'  => true,
			'eligible_price_ids'   => array( 1, 2, 3, 4, 5, 6, 7 ),
			// 'status'               => is_plugin_active( 'multi-vendor-marketplace-b2b-for-wholesalex-dokan/multi-vendor-marketplace-b2b-for-wholesalex-dokan.php' ),
			'status'               => function_exists( 'wholesalex_dokan_run' ),
			'lock_status'          => false,
			'setting_id'           => '#dokan_wholesalex',
			'is_installed'         => file_exists( WP_PLUGIN_DIR . '/multi-vendor-marketplace-b2b-for-wholesalex-dokan/multi-vendor-marketplace-b2b-for-wholesalex-dokan.php' ),
			// 'is_active'            => is_plugin_active( 'multi-vendor-marketplace-b2b-for-wholesalex-dokan/multi-vendor-marketplace-b2b-for-wholesalex-dokan.php' ),
			'download_link'        => 'https://downloads.wordpress.org/plugin/multi-vendor-marketplace-b2b-for-wholesalex-dokan.zip',
			'video'                => 'https://www.youtube.com/embed/4UatlL2-XXo',
			// Translators: %s is the name of the required plugin.
			'depends_message'      => sprintf( __( 'This addon require %s plugin', 'wholesalex' ), '<a href="https://wordpress.org/plugins/dokan-lite/" target="_blank">Dokan</a>' ),
			'is_dependency_active' => $this->check_required_plugins( 'dokan' ),
		);

		$config['wsx_addon_wcfm_integration'] = array(
			'name'                 => __( 'WholesaleX for WCFM', 'wholesalex' ),
			'desc'                 => __( 'Enable vendors to set wholesale prices and discounts in your WCFM-powered marketplace. Manage user conversations and streamline your B2B multi-vendor store operations.', 'wholesalex' ),
			'img'                  => WHOLESALEX_URL . 'assets/img/addons/wcfm_integration.svg',
			'docs'                 => 'https://getwholesalex.com/docs/wholesalex/wcfm-marketplace-integration/',
			'live'                 => '',
			'is_pro'               => false,
			'is_different_plugin'  => true,
			'eligible_price_ids'   => array( 1, 2, 3, 4, 5, 6, 7 ),
			// 'status'               => is_plugin_active( 'wholesalex-wcfm-b2b-multivendor-marketplace/wholesalex-wcfm-b2b-multivendor-marketplace.php' ),
			'status'               => function_exists( 'wholesalex_wcfm_run' ),
			'lock_status'          => false,
			'setting_id'           => '#wcfm_wholesalex',
			'is_installed'         => file_exists( WP_PLUGIN_DIR . '/wholesalex-wcfm-b2b-multivendor-marketplace/wholesalex-wcfm-b2b-multivendor-marketplace.php' ),
			// 'is_active'            => is_plugin_active( 'wholesalex-wcfm-b2b-multivendor-marketplace/wholesalex-wcfm-b2b-multivendor-marketplace.php' ),
			'download_link'        => 'https://downloads.wordpress.org/plugin/wholesalex-wcfm-b2b-multivendor-marketplace.zip',
			'video'                => 'https://www.youtube.com/embed/2OLOqyvv5rE',
			// Translators: %s is the name of the required plugin with an HTML link.
			'depends_message'      => sprintf( __( 'This addon require %s plugin', 'wholesalex' ), '<a href="https://wordpress.org/plugins/wc-frontend-manager/" target="_blank">WCFM – Frontend Manager</a>' ),
			'is_dependency_active' => $this->check_required_plugins( 'wcfm' ),
		);
		$config['wsx_addon_migration_integration'] = array(
			'name'                 => __( 'WholesaleX Migration Tool', 'wholesalex' ),
			'desc'                 => __( 'Import your data from B2BKing and Wholesale Suite into WholesaleX with the built-in migration tool. Move everything over smoothly and continue selling without disruption.', 'wholesalex' ),
			'img'                  => WHOLESALEX_URL . 'assets/img/addons/migration_tool_icon.png',
			'docs'                 => 'https://getwholesalex.com/docs/wholesalex/wholesalex-migration-tool/',
			'live'                 => '',
			'is_pro'               => false,
			'is_different_plugin'  => true,
			'eligible_price_ids'   => array( 1, 2, 3, 4, 5, 6, 7 ),
			// 'status'               => is_plugin_active( 'wholesalex-migration-tool/wholesalex-migration-tool.php' ),
			'status'               => function_exists( 'wholesalex_migration_run' ),
			'lock_status'          => false,
			'setting_id'           => '#migration_wholesalex',
			'is_installed'         => file_exists( WP_PLUGIN_DIR . '/wholesalex-migration-tool/wholesalex-migration-tool.php' ),
			// 'is_active'            => is_plugin_active( 'wholesalex-migration-tool/wholesalex-migration-tool.php' ),
			'download_link'        => 'https://downloads.wordpress.org/plugin/wholesalex-migration-tool.zip',
			'video'                => 'https://www.youtube.com/embed/KVRg10OcfTI',
			// Translators: %s is the name of the required plugin with an HTML link.
			'is_dependency_active' => $this->check_required_plugins( 'migration' ),
		);
		return $config;
	}
}
