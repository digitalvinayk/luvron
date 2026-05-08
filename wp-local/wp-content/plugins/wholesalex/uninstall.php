<?php
/**
 * WholesaleX Uninstaller
 *
 * @link              https://www.wpxpo.com/
 * @since             1.4.3
 * @package           WholesaleX
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove Plugin Data
 *
 * Only runs if user has enabled the data deletion setting.
 *
 * @since 1.4.3
 * @return void
 */
function wholesalex_uninstall_plugin_data_remove() {
	// Get setting directly from database - no plugin dependencies.
	$settings              = get_option( 'wholesalex_settings', array() );
	$is_plugin_data_delete = isset( $settings['_settings_access_delete_wholesalex_plugin_data'] ) ? $settings['_settings_access_delete_wholesalex_plugin_data'] : '';

	// Only delete data if user explicitly enabled this setting.
	if ( 'yes' !== $is_plugin_data_delete ) {
		return;
	}

	global $wpdb;

	$option_keys = array(
		'wholesalex_settings',
		'wholesalex_installation_date',
		'__wholesalex_customer_import_export_stats',
		'wholesalex_notice',
		'__wholesalex_single_product_settings',
		'__wholesalex_single_product_db_update_v2',
		'__wholesalex_category_settings',
		'__wholesalex_dynamic_rules',
		'_wholesalex_roles',
		'__wholesalex_registration_form',
		'__wholesalex_email_templates',
		'__wholesalex_initial_setup',
		'woocommerce_wholesalex_new_user_approval_required_settings',
		'woocommerce_wholesalex_new_user_approved_settings',
		'woocommerce_wholesalex_new_user_auto_approve_settings',
		'woocommerce_wholesalex_new_user_email_verified_settings',
		'woocommerce_wholesalex_registration_pending_settings',
		'woocommerce_wholesalex_new_user_registered_settings',
		'woocommerce_wholesalex_registration_rejected_settings',
		'woocommerce_wholesalex_new_user_email_verification_settings',
		'woocommerce_wholesalex_user_profile_update_notify_settings',
	);

	// Delete options from the wp_options table.
	$placeholders = array_fill( 0, count( $option_keys ), '%s' );
	$query        = "DELETE FROM $wpdb->options WHERE option_name IN (" . implode( ', ', $placeholders ) . ')';
	$wpdb->query( $wpdb->prepare( $query, ...$option_keys ) ); //phpcs:ignore

	$post_meta_keys = array(
		'wholesalex_b2b_stock_status',
		'wholesalex_b2b_stock',
		'wholesalex_b2b_backorders',
		'wholesalex_b2b_separate_stock_status',
		'wholesalex_b2b_variable_stock',
		'wholesalex_b2b_variable_backorders',
		'wholesalex_b2b_variable_separate_stock_status',
	);

	// Delete post meta keys from the wp_postmeta table.
	$placeholders = array_fill( 0, count( $post_meta_keys ), '%s' );
	$query        = "DELETE FROM $wpdb->postmeta WHERE meta_key IN (" . implode( ', ', $placeholders ) . ')';
	$wpdb->query( $wpdb->prepare( $query, ...$post_meta_keys ) ); //phpcs:ignore

	$dynamic_prefix = 'wholesalex_';
	$suffixes       = array( '_base_price', '_sale_price' );
	foreach ( $suffixes as $suffix ) {
		$wpdb->query( //phpcs:ignore
			$wpdb->prepare(
				"DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s",
				$wpdb->esc_like( $dynamic_prefix ) . '%' . $wpdb->esc_like( $suffix )
			)
		);
	}

	// Delete all keys with prefix 'wholesalex_'.
	$wpdb->query( //phpcs:ignore
		$wpdb->prepare(
			"DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s",
			$wpdb->esc_like( $dynamic_prefix ) . '%'
		)
	);

	$wpdb->query( //phpcs:ignore
		$wpdb->prepare(
			"DELETE FROM $wpdb->termmeta WHERE meta_key LIKE %s",
			$wpdb->esc_like( $dynamic_prefix ) . '%'
		)
	);

	$wpdb->query( //phpcs:ignore
		$wpdb->prepare(
			"DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s",
			$wpdb->esc_like( '__wholesalex_' ) . '%'
		)
	);

	$user_meta_keys = array(
		'__wholesalex_status',
		'__wholesalex_account_confirmed',
		'wholesalex_notice',
		'__wholesalex_role',
		'__wholesalex_profile_discounts',
		'__wholesalex_profile_settings',
		'__wholesalex_email_confirmation_code',
	);

	// Delete user meta keys from the wp_usermeta table.
	$placeholders = array_fill( 0, count( $user_meta_keys ), '%s' );
	$query        = "DELETE FROM $wpdb->usermeta WHERE meta_key IN (" . implode( ', ', $placeholders ) . ')';
	$wpdb->query( $wpdb->prepare( $query, ...$user_meta_keys ) ); //phpcs:ignore

	$user_option_keys = array(
		'wholesalex_dynamic_rule_import_mapping',
		'wholesalex_role_import_mapping',
		'wholesalex_role_import_error_log',
	);

	// Delete user option keys from the wp_usermeta table for specific users.
	$placeholders = array_fill( 0, count( $user_option_keys ), '%s' );
	$query        = "DELETE FROM $wpdb->usermeta WHERE meta_key IN (" . implode( ', ', $placeholders ) . ')'; //phpcs:ignore
	$wpdb->query( $wpdb->prepare( $query, ...$user_option_keys ) ); //phpcs:ignore
}

// Run the uninstall process.
wholesalex_uninstall_plugin_data_remove();
