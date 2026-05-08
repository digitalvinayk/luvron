<?php

namespace WHOLESALEX;

class OurPlugins {


	/**
	 * Constructor. Hooks into various WordPress actions.
	 */
	public function __construct() {
		// Our Plugin Activation Hooks.
		add_action( 'wp_ajax_wsx_install_plugin', array( $this, 'wsx_install_plugin_callback' ) );
	}

	/**
	 * Handles plugin installation and activation via AJAX.
	 *
	 * @return void
	 */
	public function wsx_install_plugin_callback() {

		$nonce  = isset( $_POST['wpnonce'] ) ? sanitize_key( wp_unslash( $_POST['wpnonce'] ) ) : '';
		$plugin = isset( $_POST['plugin'] ) ? $_POST['plugin'] : '';

		if ( ! wp_verify_nonce( $nonce, 'wholesalex-registration' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No plugin specified', 'wholesalex' ) ) );

		}

		$res = array( 'message' => 'false' );

		if ( $plugin ) {
			$res = Xpo::install_and_active_plugin( $plugin );
		}

		wp_send_json_success( array( 'message' => $res ) );

		die();
	}
}
