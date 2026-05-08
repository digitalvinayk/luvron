<?php
/**
 * WholesaleX Registration
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

use Exception;
use WHOLESALEX\WholesaleX_CommonUtils;
use WP_Error;

/**
 * WholesaleX Registration class
 */
class WHOLESALEX_Registration {

	/**
	 * Custom Fields
	 *
	 * @var array
	 */
	public $woo_custom_fields = array();

	/**
	 * Registration Fields
	 *
	 * @var array
	 */
	public $registration_fields = array();

	/**
	 * Registration Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'registration_form_builder_restapi_callback' ) );
		add_filter( 'wp_authenticate_user', array( $this, 'check_status' ), 10, 2 );
		add_filter( 'wholesalex_registration_form_user_login_option', array( $this, 'user_login_option' ) );
		add_filter( 'wholesalex_registration_form_user_status_option', array( $this, 'user_status_option' ), 10, 3 );

		add_filter( 'wholesalex_registration_form_after_registration_redirect_url', array( $this, 'after_registration_redirect' ), 10, 3 );
		add_filter( 'wholesalex_registration_form_after_registration_success_message', array( $this, 'after_registration_success_message' ) );
		add_action( 'wholesalex_registration_form_user_status_email_confirmation_require', array( $this, 'confirmation_email_after_registration' ), 10, 2 );
		add_action( 'wholesalex_registration_form_user_status_auto_approve', array( $this, 'auto_approve_after_registration' ), 10, 3 );
		add_action( 'wholesalex_registration_form_user_auto_login', array( $this, 'auto_login_after_registration' ) );
		add_filter( 'woocommerce_login_redirect', array( $this, 'login_redirect' ), 10, 2 );
		add_action( 'wholesalex_registration_form_user_status_admin_approve', array( $this, 'user_registration_admin_approval_need' ) );

		add_action( 'init', array( $this, 'register_block' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		add_action( 'woocommerce_register_form', array( $this, 'add_custom_field_on_woo_registration' ) );

		add_action( 'woocommerce_process_registration_errors', array( $this, 'process_woo_registration_validation' ), 10, 4 );

		add_action( 'woocommerce_created_customer', array( $this, 'add_custom_woo_field_to_user_meta' ) );

		add_action( 'wp_ajax_nopriv_wholesalex_process_registration', array( $this, 'process_registration' ) );
		add_action( 'wp_ajax_wholesalex_process_registration', array( $this, 'process_registration' ) );

		add_action( 'template_redirect', array( $this, 'show_wholesalex_notice' ) );

		add_action( 'woocommerce_register_form_tag', array( $this, 'allow_file_upload_on_woo_registration' ) );
	}

	/**
	 * Allow File Upload on WooCommerce Registration
	 *
	 * @since 1.0.0
	 */
	public function allow_file_upload_on_woo_registration() {
		echo 'enctype="multipart/form-data"';
	}


	/**
	 * Enqueue Block Editor Assets
	 *
	 * @since 1.0.0
	 */
	public function enqueue_block_editor_assets() {
		$slug = apply_filters( 'wholesalex_registration_form_builder_submenu_slug', 'wholesalex-registration' );

		wp_enqueue_script( 'wholesalex_forms_block', WHOLESALEX_URL . 'assets/js/wholesalex_forms_block.js', array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components' ), WHOLESALEX_VER, true );
		wp_enqueue_style( 'wholesalex_forms_block', WHOLESALEX_URL . 'assets/js/wholesalex_forms_block.css', array(), WHOLESALEX_VER );
		wp_localize_script(
			'wholesalex_forms_block',
			'wholesalex_block_data',
			array(
				'form_builder_url' => admin_url( 'admin.php?page=wholesalex#/registration' ),
				'url'              => WHOLESALEX_URL,
			)
		);
	}

	/**
	 * Register Block
	 *
	 * @since 1.0.0
	 */
	public function register_block() {
		register_block_type(
			'wholesalex/form',
			array(
				'editor_script'   => 'wholesalex_forms_block',
				'editor_style'    => 'wholesalex_forms_block',
				'render_callback' => array( $this, 'render_block' ),
			)
		);
		$this->set_custom_fields();
	}

	/**
	 * Render Block
	 *
	 * @since 1.0.0
	 */
	public function render_block() {
		return '';
	}

	/**
	 * User Registration Form Output
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function output() {
		/**
		 * Enqueue Script
		 *
		 * @since 1.1.0 Enqueue Script (Reconfigure Build File)
		 */
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

		// Add File Support Type.
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

		$form_regi_data    = WholesaleX_CommonUtils::get_default_registration_form_fields();
		$default_form_data = WholesaleX_CommonUtils::get_empty_form();
		wp_localize_script(
			'wholesalex_form_builder',
			'whx_form_builder',
			array(
				'is_woo_username'            => $is_woo_username,
				'login_form_data'            => wp_json_encode( $default_form_data['loginFields'] ),
				'form_data'                  => wp_json_encode( $form_regi_data ),
				'roles'                      => wholesalex()->get_roles( 'roles_option' ),
				'whitelabel_enabled'         => 'yes' === wholesalex()->get_setting( 'wsx_addon_whitelabel' ) && function_exists( 'wholesalex_whitelabel_init' ),
				'slug'                       => wholesalex()->get_setting( 'registration_form_buidler_submenu_slug' ),
				'privacy_policy_text'        => wc_get_privacy_policy_text( 'registration' ),
				'password_condition_options' => $password_condition_options,
				'file_condition_options'     => $file_condition_options,
				'billing_fields'             => array(
					''                         => __( 'No Mapping', 'wholesalex' ),
					'billing_first_name'       => __( 'Billing First Name', 'wholesalex' ),
					'billing_last_name'        => __( 'Billing Last Name', 'wholesalex' ),
					'billing_company'          => __( 'Billing Company', 'wholesalex' ),
					'billing_address_1'        => __( 'Billing Address 1', 'wholesalex' ),
					'billing_address_2'        => __( 'Billing Address 2', 'wholesalex' ),
					'billing_city'             => __( 'Billing City', 'wholesalex' ),
					'billing_postcode'         => __( 'Billing Post Code', 'wholesalex' ),
					'billing_country'          => __( 'Billing Country', 'wholesalex' ),
					'billing_state'            => __( 'Billing State', 'wholesalex' ),
					'billing_email'            => __( 'Billing Email', 'wholesalex' ),
					'billing_phone'            => __( 'Billing Phone', 'wholesalex' ),
					'custom_user_meta_mapping' => __( 'Custom User Meta Mapping', 'wholesalex' ),
				),
			)
		);
		?>
		<div id="wholesalex_registration_form_builder__root"></div>
		<?php
	}

	/**
	 * WholesaleX Registration Form Builder Rest Api Callback
	 *
	 * @return void
	 */
	public function registration_form_builder_restapi_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/builder_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'builder_action_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			'wholesalex/v1',
			'/blockPreview/forms',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_form_preview' ),
					'permission_callback' => '__return_true',
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Get Form Preview
	 *
	 * @param object $server Server.
	 * @since 1.0.0
	 */
	public function get_form_preview( $server ) {
		$post = $server->get_params();

		$role = isset( $post['role'] ) ? sanitize_text_field( $post['role'] ) : '';

		$form_regi_data = WholesaleX_CommonUtils::get_new_form_builder_data();

		wp_send_json_success(
			array(
				'formdata' => wp_json_encode( $form_regi_data ),
				'role'     => $post,
			)
		);
	}

	/**
	 * Registration Form Builder Action
	 *
	 * @param object $server Server.
	 * @return mixed
	 */
	public function builder_action_callback( $server ) {
		$post = $server->get_params();
		if ( ! ( isset( $post['nonce'] ) && wp_verify_nonce( sanitize_key( $post['nonce'] ), 'wholesalex-registration' ) ) ) {
			return;
		}

		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		if ( 'post' === $type ) {

			if ( isset( $post['data'] ) ) {
				update_option( 'wholesalex_registration_form', sanitize_text_field( wp_unslash( $post['data'] ) ) );

				$GLOBALS['wholesalex_registration_fields'] = WholesaleX_CommonUtils::get_form_fields();

				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		} elseif ( 'get' === $type ) {
			$__roles_options = wholesalex()->get_roles( 'roles_option' );

			wp_send_json_success(
				array(
					'roles'     => $__roles_options,
					'form_data' => array(),
				)
			);
		}
	}





	/**
	 * Email Confirmation After Registration
	 *
	 * @param int|string $user_id User ID.
	 * @param string     $registration_role Registration Role.
	 * @return void
	 */
	public function confirmation_email_after_registration( $user_id, $registration_role ) {
		update_user_meta( $user_id, '__wholesalex_status', 'pending' );

		$confirmation_code = bin2hex( random_bytes( 16 ) );
		update_user_meta( $user_id, '__wholesalex_email_confirmation_code', $confirmation_code );
		update_user_meta( $user_id, '__wholesalex_account_confirmed', false );
		do_action( 'wholesalex_user_email_confirmation', $user_id, $confirmation_code );
	}


	/**
	 * Auto Approve After Registration
	 *
	 * @param int|string $user_id User ID.
	 * @param string     $registration_role Registration Role.
	 * @param string     $password Password.
	 * @return void
	 */
	public function auto_approve_after_registration( $user_id, $registration_role, $password = '' ) {
		wholesalex()->change_role( $user_id, $registration_role );
		update_user_meta( $user_id, '__wholesalex_status', 'active' );
		do_action( 'wholesalex_set_status_active', $user_id, $password );
		do_action( 'wholesalex_user_auto_approval', $user_id );
	}
	/**
	 * Auto Login After Registration
	 *
	 * Auto Login Will Work only if user status is auto approved.
	 *
	 * @param int|string $user_id User ID.
	 */
	public function auto_login_after_registration( $user_id ) {
		$__user_status = wholesalex()->get_user_status( $user_id );
		if ( 'pending' === $__user_status ) {
			$user_login_option = wholesalex()->get_setting( '_settings_user_status_option', 'admin_approve' );
			switch ( $user_login_option ) {
				case 'admin_approve':
					/* translators: %s: Account Status */
					$message = sprintf( esc_html__( 'Your account Status is %s. Please Contact with Site Administration to approve your account.', 'wholesalex' ), $__user_status );
					return new WP_Error( 'admin_approval_pending', $message );
				case 'email_confirmation_require':
					$message = esc_html__( 'Please confirm your account by clicking the confirmation link, that already sent your registered email.', 'wholesalex' );
					return new WP_Error( 'email_confirmation_require', $message );

				default:
					// code...
					break;
			}
		}
		wc_set_customer_auth_cookie( $user_id );
		do_action( 'wholesalex_user_auto_login', $user_id );
	}

	/**
	 * Generate Verification Code
	 *
	 * @param int $len length of verification code.
	 * @throws Exception If the generated string does not meet the criteria.
	 */
	public function generate_confirmation_code( $len ) {
		$characters        = '0123456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_-[]{}!@$';
		$characters_length = strlen( $characters );
		$random_string     = '';
		for ( $i = 0; $i < $len; $i++ ) {
			$random_character = $characters[ wp_rand( 0, $characters_length - 1 ) ];
			$random_string   .= $random_character;
		}
		$random_string = sanitize_user( $random_string );
		if ( ( preg_match( '([a-zA-Z].*[0-9]|[0-9].*[a-zA-Z].*[_\W])', $random_string ) === 1 ) && ( strlen( $random_string ) === $len ) ) {
			return $random_string;
		} else {
			return call_user_func( array( $this, 'generate_confirmation_code' ), $len );
		}
	}

	/**
	 * Check User Approval Status
	 *
	 * @param WP_User $user user object.
	 * @since 1.0.0
	 * @since 1.1.10 Admin Approval Require Restriction Remove For WooCommerce Registration Form Users.
	 */
	public function check_status( $user ) {
		// Check if $user is a WP_User object.
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( user_can( $user->ID, 'manage_options' ) ) {
			return $user;
		}

		$user_login_option = wholesalex()->get_setting( '_settings_user_status_option', 'admin_approve' );
		$status            = get_the_author_meta( '__wholesalex_status', $user->ID );

		if ( 'admin_approve' === $user_login_option ) {
			$message = '';
			switch ( $status ) {
				case 'pending':
					/* translators: %s: Account Status */
					$message = sprintf( esc_html__( 'Your account Status is %s. Please Contact with Site Administration to approve your account.', 'wholesalex' ), $status );
					break;
				case 'inactive':
					/* translators: %s: Account Status */
					$message = sprintf( esc_html__( 'Your account Status is %s. Please Contact with Site Administration to Active your account.', 'wholesalex' ), $status );
					break;
				case 'reject':
					/* translators: %s: Account Status */
					$message = sprintf( esc_html__( 'Your account Status is %s. Please Contact with Site Administration to Active your account.', 'wholesalex' ), $status );
					break;

				default:
					// code...
					break;
			}
			if ( '' !== $message ) {
				return new WP_Error( 'admin_approval_pending', $message );
			}
		} elseif ( 'email_confirmation_require' === $user_login_option && 'pending' === $status ) {
			$message = esc_html__( 'Please confirm your account by clicking the confirmation link, that already sent your registered email.', 'wholesalex' );
			return new WP_Error( 'email_confirmation_require', $message );
		}
		return $user;
	}

	/**
	 * Returns the login redirect URL.
	 *
	 * @param string  $redirect Default redirect URL.
	 * @param WP_User $user WP_User object.
	 * @return string Redirect URL.
	 * @since 1.2.4 login to view prices redirect url check added
	 */
	public function login_redirect( $redirect, $user ) {
		$view_price_product_list   = wholesalex()->get_setting( '_settings_login_to_view_price_product_list', 'no' );
		$view_price_product_single = wholesalex()->get_setting( '_settings_login_to_view_price_product_page', 'no' );
		$url                       = esc_url_raw( wholesalex()->get_setting( '_settings_redirect_url_login', get_permalink( get_option( 'woocommerce_shop_page_id' ) ) ) );
		$role_content              = wholesalex()->get_roles( 'by_id', get_user_meta( $user->ID, '__wholesalex_role', true ) );
		if ( isset( $role_content['after_login_redirect'] ) && esc_url_raw( $role_content['after_login_redirect'] ) === $role_content['after_login_redirect'] ) {
			$redirect_url = esc_url_raw( $role_content['after_login_redirect'] );
			if ( $redirect_url ) {
				return $redirect_url;
			}
		}

		if ( 'yes' === $view_price_product_list || 'yes' === $view_price_product_single ) {
			$product_redirect = isset( $_POST['redirect'] ) ? wp_unslash( $_POST['redirect'] ) : $url;//phpcs:ignore
			$product_redirect = remove_query_arg( array( 'wc_error', 'password-reset' ), $product_redirect );

			if ( ! empty( $product_redirect ) ) {
				$parsed_url          = wp_parse_url( $product_redirect );
				$query_string        = isset( $parsed_url['query'] ) ? $parsed_url['query'] : '';
				$redirect_url_prices = '';

				if ( ! empty( $query_string ) ) {
					parse_str( $query_string, $query_params );
					$redirect_url_prices = isset( $query_params['redirect'] ) ? $query_params['redirect'] : '';
				}

				$redirect_url_view_prices = isset( $_GET['redirect'] ) ? esc_url( $_GET['redirect'] ) : '';//phpcs:ignore
				$redirect_url_view_prices = empty( $redirect_url_view_prices ) ? $product_redirect : $redirect_url_view_prices;

				if ( $redirect_url_view_prices ) {
					return $redirect_url_view_prices;
				}
			}
		}

		$redirect = wc_get_page_permalink( 'myaccount' );
		return ( $url ) ? $url : $redirect;
	}


	/**
	 * User Login Option Settings
	 *
	 * @param string $option Option.
	 * @since 1.0.0 Reposition on v1.0..7
	 */
	public function user_login_option( $option ) {
		$__user_login_option = wholesalex()->get_setting( '_settings_user_login_option', 'manual_login' );
		if ( ! empty( $__user_login_option ) ) {
			return $__user_login_option;
		}
		return $option;
	}

	/**
	 * User Status Options
	 *
	 * @param string $option Option.
	 * @param int    $user_id User ID.
	 * @param string $regi_role Registration Role.
	 * @since 1.0.0 Reposition on v1.0..7
	 */
	public function user_status_option( $option, $user_id, $regi_role ) {
		$__user_status_option = wholesalex()->get_setting( '_settings_user_status_option', 'admin_approve' );

		$role_content = wholesalex()->get_roles( 'by_id', $regi_role );

		if ( isset( $role_content['user_status'] ) && 'global_setting' != $role_content['user_status'] ) {
			return $role_content['user_status'];
		}
		if ( ! empty( $__user_status_option ) ) {
			return $__user_status_option;
		}
		return $option;
	}


	/**
	 * After Registration Form Redirect Settings.
	 *
	 * @param string $redirect_url Redirect Url.
	 * @param int    $user_id User ID.
	 * @param string $registration_role Registration Role.
	 * @since 1.0.0 Reposition on v1.0..7
	 */
	public function after_registration_redirect( $redirect_url, $user_id, $registration_role ) {
		$__redirect_url = wholesalex()->get_setting( '_settings_redirect_url_registration', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
		$role_content   = wholesalex()->get_roles( 'by_id', $registration_role );

		if ( ! empty( $__redirect_url ) ) {
			$redirect_url = esc_url_raw( $__redirect_url ); //phpcs:ignore
		}

		if ( isset( $role_content['after_registration_redirect'] ) && esc_url_raw( $role_content['after_registration_redirect'] ) === $role_content['after_registration_redirect'] ) {
			$redirect_url = esc_url_raw( $role_content['after_registration_redirect'] );
		}

		return $redirect_url;
	}

	/**
	 * After Registration Success Message
	 *
	 * @param string $message Message.
	 * @since 1.0.0 Reposition on v1.0..7
	 */
	public function after_registration_success_message( $message ) {
		$__registration_success_message = wholesalex()->get_setting( '_settings_registration_success_message', __( 'Thank you for registering. Your account will be reviewed by us & approve manually. Please wait to be approved.', 'wholesalex' ) );
		if ( ! empty( $__registration_success_message ) ) {
			$__registration_success_message = esc_html( $__registration_success_message ); //phpcs:ignore
			return $__registration_success_message;
		}

		return $message;
	}


	/**
	 * Process User Registration Admin Approval Need Settings
	 *
	 * @param string $user_id User ID.
	 * @return void
	 * @since 1.1.6
	 */
	public function user_registration_admin_approval_need( $user_id ) {
		add_user_meta( $user_id, '__wholesalex_status', 'pending' );
		set_transient( 'wholesalex_registration_approval_required_email_status_' . $user_id, true );
		do_action( 'wholesalex_set_user_approval_needed', $user_id );
	}

	/**
	 * Disable WooCommerce new user email for the users who registered throw the wholesalex registration form.
	 *
	 * @return void
	 * @since 1.1.6
	 */
	public function disable_woocommerce_new_user_email() {
		$is_disable = wholesalex()->get_setting( '_settings_disable_woocommerce_new_user_email' );
		if ( 'yes' === $is_disable ) {
			add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false' );
		}
	}

	/**
	 * Check User Registration Status
	 *
	 * @param mixed $field Field.
	 * @return string
	 * @since 1.1.6
	 */
	public function check_depends( $field ) {
		$exclude_string = '';
		if ( isset( $field['excludeRoles'] ) && ! empty( $field['excludeRoles'] ) && is_array( $field['excludeRoles'] ) ) {
			foreach ( $field['excludeRoles'] as $role ) {
				$exclude_string .= $role['value'] . ' ';
			}
		}
		return $exclude_string;
	}

	/**
	 * Generate custom Fields for displaying on woo registration form
	 *
	 * @param array $field Field.
	 * @return void
	 */
	public function generate_field_for_woo_registration( $field ) {
		$depends     = $this->check_depends( $field );
		$is_required = isset( $field['required'] ) ? $field['required'] : false;

		// Check to Guest User Shouldn't Be Show In WooCommerce Registration Form.
		if ( 'select' === $field['type'] && 'wholesalex_registration_role' === $field['name'] ) {
			$filtered_role_options = array_filter(
				$field['option'],
				function ( $item ) {
					return 'wholesalex_guest' !== $item['value'];
				}
			);
			$field['option']       = $filtered_role_options;
		}
		switch ( $field['type'] ) {
			case 'text':
			case 'password':
			case 'url':
			case 'tel':
				?>
					<p data-wsx-exclude="<?php echo esc_attr( $depends ); ?>" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wholesalex-custom-field wsx-field" style="<?php echo esc_attr( $depends ? 'display: none;' : '' ); ?>">
						<?php
						if ( ! ( isset( $field['isLabelHide'] ) && $field['isLabelHide'] ) ) {
							?>
							<label class="wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>&nbsp;
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span class="required">*</span>
								<?php
							}

							?>
								</label>
							<?php
						}
						?>
						<input type="<?php echo esc_attr($field['type']);?>" class="wsx-input woocommerce-Input woocommerce-Input--text input-text <?php echo esc_attr(isset($field['required']) && $field['required']?'wsx-field-required':'');  ?>" name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field['name']); ?>"  value="<?php echo ( isset($_POST[$field['name']]) && ! empty( $_POST[$field['name']] ) ) ? esc_attr( wp_unslash( $_POST[$field['name']] ) ) : ''; ?>" <?php if (!empty($is_required)) echo 'required'; ?> /><?php // @codingStandardsIgnoreLine ?>
						<span class="wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>"> </span>
						<?php
						if ( isset( $field['help_message'] ) && $field['help_message'] ) {
							?>
								<span class="description"><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						}
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
					</p>
				<?php
				// code...
				break;

			case 'number':
			case 'date':
				?>
					<p data-wsx-exclude="<?php echo esc_attr( $depends ); ?>" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wholesalex-custom-field wsx-field" style="<?php echo esc_attr( $depends ? 'display: none;' : '' ); ?>">
						<?php
						if ( ! ( isset( $field['isLabelHide'] ) && $field['isLabelHide'] ) ) {
							?>
							<label class="wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>&nbsp;
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span class="required">*</span>
								<?php
							}

							?>
								</label>
							<?php
						}
						?>
						<input type="<?php echo esc_attr($field['type']);?>" class="wsx-input woocommerce-Input woocommerce-Input--text input-text <?php echo esc_attr(isset($field['required']) && $field['required']?'wsx-field-required':'');  ?>" name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field['name']); ?>"  value="<?php echo ( isset($_POST[$field['name']]) && ! empty( $_POST[$field['name']] ) ) ? esc_attr( wp_unslash( $_POST[$field['name']] ) ) : ''; ?>" <?php if (!empty($is_required)) echo 'required'; ?> /><?php // @codingStandardsIgnoreLine ?>
						<span class="wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>"> </span>
						<?php
						if ( isset( $field['help_message'] ) && $field['help_message'] ) {
							?>
								<span class="description"><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						}
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
					</p>
				<?php
				// code...
				break;
			case 'file':
				?>
					<p data-wsx-exclude="<?php echo esc_attr( $depends ); ?>" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wholesalex-custom-field wsx-field" style="<?php echo esc_attr( $depends ? 'display: none;' : '' ); ?>">
						<?php
						if ( ! ( isset( $field['isLabelHide'] ) && $field['isLabelHide'] ) ) {
							?>
							<label class="wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>&nbsp;
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
										<span class="required">*</span>
								<?php
							}
							?>
								</label>
							<?php
						}
						?>
						<input type="<?php echo esc_attr($field['type']);?>" class="wsx-input woocommerce-Input  <?php echo esc_attr(isset($field['required']) && $field['required']?'wsx-field-required':'');  ?>" name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field['name']); ?>"  value="<?php echo ( ! empty( $_POST[$field['name']] ) ) ? esc_attr( wp_unslash( $_POST[$field['name']] ) ) : ''; ?>" <?php if (!empty($is_required)) echo 'required'; ?> /><?php // @codingStandardsIgnoreLine ?>
						<span class="wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>"> </span>
						<?php
						if ( isset( $field['help_message'] ) && $field['help_message'] ) {
							?>
								<span class="description"><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						}
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
					</p>
				<?php
				break;
			case 'select':
				?>
					<p data-wsx-exclude="<?php echo esc_attr( $depends ); ?>" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wholesalex-custom-field wsx-field" style="<?php echo esc_attr( $depends ? 'display: none;' : '' ); ?>">
						<?php
						if ( ! ( isset( $field['isLabelHide'] ) && $field['isLabelHide'] ) ) {
							?>
							<label class="wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>&nbsp;
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span class="required">*</span>
								<?php
							}
							?>
								</label>
							<?php
						}
						?>
						<select 
							class="wsx-select <?php echo esc_attr( isset( $field['required'] ) && $field['required'] ? 'wsx-field-required' : '' ); ?>" 
							name="<?php echo esc_attr( $field['name'] ); ?>" 
							id="<?php echo esc_attr( $field['name'] ); ?>"
							<?php
							if ( ! empty( $is_required ) ) {
								echo 'required';}
							?>
													>
							<option value="" disabled <?php echo empty( $_POST[ $field['name'] ] ) ? 'selected' : ''; ?>>Please select</option>

							<?php
							foreach ( $field['option'] as $option ) {
								$selected = ( isset( $_POST[ $field['name'] ] ) && $_POST[ $field['name'] ] == $option['value'] ) ? 'selected' : '';
								?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php echo $selected; ?>>
									<?php echo esc_html( $option['name'] ); ?>
								</option>
								<?php
							}
							?>
						</select>
						<?php
						if ( isset( $field['help_message'] ) && $field['help_message'] ) {
							?>
								<span class="description"><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						}
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
					</p>
				<?php
				// code...
				break;
			case 'radio':
				?>
				<p data-wsx-exclude="<?php echo esc_attr( $depends ); ?>" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wholesalex-custom-field wsx-field" style="<?php echo esc_attr( $depends ? 'display: none;' : '' ); ?>">
					<?php
					if ( ! ( isset( $field['isLabelHide'] ) && $field['isLabelHide'] ) ) {
						?>
						<label class="wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>&nbsp;
						<?php
						if ( isset( $field['required'] ) && $field['required'] ) {
							?>
								<span class="required">*</span>
							<?php
						}
						?>
							</label>
						<?php
					}
					?>
					<span>
					<?php
					$index = 0;
					foreach ( $field['option'] as $option ) {
						?>
						<input 
							type="radio" 
							class="wsx-radio woocommerce-form__input" 
							name="<?php echo esc_attr( $field['name'] ); ?>" 
							value="<?php echo esc_attr( $option['value'] ); ?>" 
							id="<?php echo esc_attr( $option['value'] ); ?>"
							<?php
							if ( 0 === $index && ! empty( $is_required ) ) {
								echo 'required';}
							?>
													/>
						<span><?php echo esc_html( $option['name'] ); ?></span>
						<?php
						++$index;
					}
					?>
					</span>
					<?php
					if ( isset( $field['help_message'] ) && $field['help_message'] ) {
						?>
								<span class="description"><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
					}
					?>
				</p>
				<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
				<?php
				break;

			case 'checkbox':
				?>
				<p data-wsx-exclude="<?php echo esc_attr( $depends ); ?>" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wholesalex-custom-field wsx-field" style="<?php echo esc_attr( $depends ? 'display: none;' : '' ); ?>">
					<?php
					if ( ! ( isset( $field['isLabelHide'] ) && $field['isLabelHide'] ) ) {
						?>
						<label class="wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>&nbsp;
						<?php
						if ( isset( $field['required'] ) && $field['required'] ) {
							?>
								<span class="required">*</span>
							<?php
						}
						?>
							</label>
						<?php
					}
					?>
					<span>
					<?php
					foreach ( $field['option'] as $option ) {
						?>
								<input type="checkbox" class="wsx-checkbox woocommerce-form__input woocommerce-form__input-checkbox" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" id="<?php echo esc_attr( $option['value'] ); ?>" />
								<span><?php echo esc_html( $option['name'] ); ?></span>
							<?php
					}
					?>
					</span>
					<?php
					if ( isset( $field['help_message'] ) && $field['help_message'] ) {
						?>
								<span class="description"><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
					}
					?>
					<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
				</p>
				<?php
				break;
			case 'textarea':
				?>
					<p data-wsx-exclude="<?php echo esc_attr( $depends ); ?>" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wholesalex-custom-field wsx-field" style="<?php echo esc_attr( $depends ? 'display: none;' : '' ); ?>" required="<?php echo esc_attr( $is_required ); ?>">
						<?php
						if ( ! ( isset( $field['isLabelHide'] ) && $field['isLabelHide'] ) ) {
							?>
							<label class="wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>&nbsp;
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span class="required">*</span>
								<?php
							}
							?>
								</label>
							<?php
						}
						?>
						<textarea name="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-textarea input-text <?php echo esc_attr( isset( $field['required'] ) && $field['required'] ? 'wsx-field-required' : '' ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>" rows="2" cols="5" > </textarea>
						<?php
						if ( isset( $field['help_message'] ) && $field['help_message'] ) {
							?>
								<span class="description"><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						}
						?>
					</p>
					<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>

				<?php
				break;
			default:
				// code...
				break;
		}
	}

	/**
	 * Add Custom Field on Default WooCommerce Registration Form
	 *
	 * @return void
	 */
	public function add_custom_field_on_woo_registration() {
		$fields = $this->woo_custom_fields;

		foreach ( $fields as $field ) {
			$this->generate_field_for_woo_registration( $field );
		}
	}

	/**
	 * Set Custom Fields
	 *
	 * @return void
	 */
	public function set_custom_fields() {
		$GLOBALS['wholesalex_registration_fields'] = WholesaleX_CommonUtils::get_form_fields();
		$this->woo_custom_fields                   = $GLOBALS['wholesalex_registration_fields']['woo_custom_fields'];
		$this->registration_fields                 = $GLOBALS['wholesalex_registration_fields']['wholesalex_fields'];
	}

	/**
	 * Password and Confirm Password Validation
	 *
	 * @param WP_Error $validation_error Validation Error.
	 * @param string   $username Username.
	 * @param string   $password Password.
	 * @param string   $email Email.
	 * @return WP_Error
	 */
	public function process_woo_registration_validation( $validation_error, $username, $password, $email ) {
		$nonce_value = isset( $_POST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-register' ) ) {
			return $validation_error;
		}
		if ( isset( $_POST['password'] ) && isset( $_POST['user_confirm_pass'] ) && ! ( sanitize_text_field( wp_unslash( $_POST['password'] ) ) === sanitize_text_field( wp_unslash( $_POST['user_confirm_pass'] ) ) ) ) {
			return new WP_Error( '201', __( 'Password and Confirm password does not match!', 'wholesalex' ) );
		}

		$is_rolewise = isset( $_POST['wholesalex_registration_role'] ) ? sanitize_text_field( wp_unslash( $_POST['wholesalex_registration_role'] ) ) : false;

		foreach ( $this->woo_custom_fields as $field ) {

			if ( 'file' === $field['type'] ) {
				$is_valid_file = isset( $_FILES[ $field['name'] ] ) && ! empty( $_FILES[ $field['name'] ] ) && $_FILES[ $field['name'] ]['name'] && ( $_FILES[ $field['name'] ]['size'] > 0 );
				if ( $is_valid_file ) {
					// Allowed file types.
					if ( isset( $field['allowed_file_types'] ) && ! empty( $field['allowed_file_types'] ) ) {
						$create_file_types = array();
						foreach ( $field['allowed_file_types'] as $item ) {
							$create_file_types[] = $item['value'];
						}
						$create_file_types  = implode( ',', $create_file_types );
						$allowed_file_types = explode( ',', $create_file_types );
						$allowed_file_types = wholesalex()->sanitize( $allowed_file_types );
					} else {
						$allowed_file_types = array( 'jpg', 'jpeg', 'png', 'txt', 'pdf', 'doc', 'docx' );
					}

					// Allowed file size.
					if ( isset( $field['maximum_file_size'] ) && ! empty( $field['maximum_file_size'] ) ) {
						$allowed_file_size = sanitize_text_field( $field['maximum_file_size'] );
					} else {
						$allowed_file_size = 5000000; // 5MB Default
					}

					// Allowed file size -> 5MB.
					$allowed_file_size_in_mb = $allowed_file_size / 1000000;
					$file_extension          = strtolower( pathinfo( $_FILES[ $field['name'] ]['name'], PATHINFO_EXTENSION ) );

					if ( ! in_array( $file_extension, $allowed_file_types, true ) ) {
						// translators: %s Field Name.
						// translators: %s Allowed File Types.
						return new WP_Error( '201', sprintf( __( 'File Type Does not support for %1$s Field! Supported File Types is %2$s.', 'wholesalex' ), $field['label'], implode( ',', $allowed_file_types ) ) );
					}
					if ( $_FILES[ $field['name'] ]['size'] > $allowed_file_size ) {
						/* translators: 1: Field Label, 2: Allowed Size */
						return new WP_Error( '201', sprintf( __( 'File is too large! Max Upload Size For %1$s field is %2$s.', 'wholesalex' ), $field['label'], $allowed_file_size_in_mb . 'MB' ) );
					}

					if ( isset( $field['migratedFromOldBuilder'] ) && $field['migratedFromOldBuilder'] ) {
						$files[ 'file_' . $field['name'] ]  = $_FILES[ $field['name'] ];
						$_FILES[ 'file_' . $field['name'] ] = $_FILES[ $field['name'] ];

					} elseif ( isset( $field['custom_field'] ) && $field['custom_field'] ) {
						$files[ 'wholesalex_cf_' . $field['name'] ]  = $_FILES[ $field['name'] ];
						$_FILES[ 'wholesalex_cf_' . $field['name'] ] = $_FILES[ $field['name'] ];
					}
				}
				if ( ! $is_rolewise && ( isset( $field['required'] ) && $field['required'] && ! $is_valid_file ) ) {
					return new WP_Error( '201', $field['label'] . __( ' is Required!', 'wholesalex' ) );
				}
				if ( $is_rolewise ) {
					$is_field_excluded = ( isset( $field['excludeRoles'] ) && ! empty( $field['excludeRoles'] ) && is_array( $field['excludeRoles'] ) ) && in_array( $is_rolewise, $this->get_multiselect_values( $field['excludeRoles'] ) );
					if ( ! $is_field_excluded && ( isset( $field['required'] ) && $field['required'] && ! $is_valid_file ) ) {
						return new WP_Error( '201', $field['label'] . __( ' is Required!', 'wholesalex' ) );
					}
				}
			} else {

				if ( ! $is_rolewise && ( isset( $field['required'] ) && $field['required'] && ( ! isset( $_POST[ $field['name'] ] ) || empty( $_POST[ $field['name'] ] ) ) ) ) {
					return new WP_Error( '201', $field['label'] . __( ' is Required!', 'wholesalex' ) );
				}
				if ( $is_rolewise ) {
					$is_field_excluded = ( isset( $field['excludeRoles'] ) && ! empty( $field['excludeRoles'] ) && is_array( $field['excludeRoles'] ) ) && in_array( $is_rolewise, $this->get_multiselect_values( $field['excludeRoles'] ) );
					if ( ! $is_field_excluded && ( isset( $field['required'] ) && $field['required'] && ( ! isset( $_POST[ $field['name'] ] ) || empty( $_POST[ $field['name'] ] ) ) ) ) {
						return new WP_Error( '201', $field['label'] . __( ' is Required!', 'wholesalex' ) );
					}
				}
			}
		}

		return $validation_error;
	}

	/**
	 * Get Multiselect Values
	 *
	 * @param array $user_id Array.
	 * @return array
	 */
	public function add_custom_woo_field_to_user_meta( $user_id ) {
		// Nonce is already checked woocommerce registration form.
		// $nonce_value = isset( $_POST['_wpnonce'] ) ? sanitize_key(wp_unslash( $_POST['_wpnonce'] )) : '';.
		if ( empty( $this->woo_custom_fields ) ) {
			return;
		}
		$files = array();
		foreach ( $this->woo_custom_fields as $field ) {
			if ( isset( $_POST[ $field['name'] ] ) && ! empty( $_POST[ $field['name'] ] ) ) {
				$value = '';
				switch ( $field['type'] ) {
					case 'text':
					case 'password':
					case 'select':
					case 'date':
					case 'radio':
					case 'number':
						$value = sanitize_text_field( $_POST[ $field['name'] ] );
						break;
					case 'textarea':
						$value = sanitize_textarea_field( $_POST[ $field['name'] ] );
						break;
					case 'url':
						$value = sanitize_url( $_POST[ $field['name'] ] );
						break;
					case 'checkbox':
						$value = wholesalex()->sanitize( $_POST[ $field['name'] ] );
						break;
					case 'email':
						$value = sanitize_email( $_POST[ $field['name'] ] );
						break;
					default:
						break;
				}

				if ( '' != $value ) {
					$key = $field['name'];
					if ( 'wholesalex_registration_role' === $key ) {
						$key = '__wholesalex_registration_role';
					}
					if ( 'user_confirm_pass' !== $key && 'user_confirm_email' !== $key ) {

						if ( isset( $field['migratedFromOldBuilder'] ) && $field['migratedFromOldBuilder'] ) {
						} elseif ( isset( $field['custom_field'] ) && $field['custom_field'] ) {
							$key = 'wholesalex_cf_' . $field['name'];
						}

						update_user_meta( $user_id, $key, $value );
					}
				}
			}

			if ( isset( $_FILES[ $field['name'] ] ) && ! empty( $_FILES[ $field['name'] ] ) ) {
				if ( 'file' == $field['type'] ) {
					if ( isset( $field['migratedFromOldBuilder'] ) && $field['migratedFromOldBuilder'] ) {
						$files[ 'file_' . $field['name'] ]  = $_FILES[ $field['name'] ];
						$_FILES[ 'file_' . $field['name'] ] = $_FILES[ $field['name'] ];

					} elseif ( isset( $field['custom_field'] ) && $field['custom_field'] ) {
						$files[ 'wholesalex_cf_' . $field['name'] ]  = $_FILES[ $field['name'] ];
						$_FILES[ 'wholesalex_cf_' . $field['name'] ] = $_FILES[ $field['name'] ];
					}
				}
			}
		}

		if ( ! empty( $files ) ) {
			/**
			* Process File Upload
			*/
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			foreach ( $files as $key => $file ) {
				// Upload File.
				$file_id = media_handle_upload( $key, 0 );

				if ( $file_id ) {
					// Set file registered user as file author.
					wp_update_post(
						array(
							'ID'          => $file_id,
							'post_author' => $user_id,
						)
					);

					// Update file id in user meta.
					update_user_meta( $user_id, $key, $file_id );
				}
			}
		}

		if ( isset( $_POST['wholesalex_registration_role'] ) && ! empty( $_POST['wholesalex_registration_role'] ) ) {
			$regi_role            = sanitize_text_field( wp_unslash( $_POST['wholesalex_registration_role'] ) );
			$__user_status_option = apply_filters( 'wholesalex_registration_form_user_status_option', 'admin_approve', $user_id, $regi_role );

			do_action( 'wholesalex_registration_form_user_status_' . $__user_status_option, $user_id, $regi_role );

			$__user_login_option = apply_filters( 'wholesalex_registration_form_user_login_option', 'manual_login' );
			do_action( 'wholesalex_registration_form_user_' . $__user_login_option, $user_id, $regi_role );

			if ( 'admin_approve' === $__user_status_option ) {
				add_filter( 'woocommerce_registration_auth_new_customer', '__return_false' );
			}
		}
	}


	/**
	 * Process Registration
	 *
	 * @return void
	 * @since 1.0.0 Reposition on v1.0..7
	 * @throws \Exception If the generated string does not meet the criteria.
	 */
	public function process_registration() {

		if ( isset( $_POST['wholesalex-registration-nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wholesalex-registration-nonce'] ), 'wholesalex-registration' ) ) {
			$data = array(
				'error_messages' => array(),
			);
			if ( isset( $_POST['user_email'], $_POST['user_pass'] ) ) {

				try {

					if ( isset( $_POST['user_pass'] ) && isset( $_POST['user_confirm_pass'] ) && ! ( sanitize_text_field( wp_unslash( $_POST['user_pass'] ) ) === sanitize_text_field( wp_unslash( $_POST['user_confirm_pass'] ) ) ) ) {
						$data['error_messages']['user_pass'] = __( 'Password and Confirm password does not match!', 'wholesalex' );
						wp_send_json_success( $data );
					}
					do_action( 'wholesalex_before_process_user_registration' );

					$user_email = trim( wp_unslash( $_POST['user_email'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$password   = sanitize_text_field( wp_unslash( $_POST['user_pass'] ) );
					$user_name  = '';

					if ( empty( $user_email ) && ! is_email( $user_email ) ) {
						$data['error_messages']['user_email'] = __( 'Email is Required!', 'wholesalex' );
					}
					if ( empty( $password ) ) {
						$data['error_messages']['user_pass'] = __( 'Password is Required!', 'wholesalex' );
					}

					// Disable WooCommerce Account Creation Email For wholesalex users.
					add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false' );

					/**
					 * Remove All Third Party Plugin Registration Errors Filter.
					 *
					 * @since 1.0.2
					 */
					remove_all_filters( 'woocommerce_registration_errors' );
					remove_filter( 'woocommerce_created_customer', array( $this, 'add_custom_woo_field_to_user_meta' ) );

					$userdata            = array();
					$usermeta            = array();
					$__registration_role = '';
					$files               = array();

					foreach ( $this->registration_fields as $field ) {
						if ( 'file' !== $field['type'] && isset( $_POST[ $field['name'] ] ) && ! empty( $_POST[ $field['name'] ] ) ) {
							$value = '';
							switch ( $field['type'] ) {
								case 'text':
								case 'password':
								case 'select':
								case 'date':
								case 'radio':
								case 'number':
									$value = sanitize_text_field( wp_unslash( $_POST[ $field['name'] ] ) );
									break;
								case 'textarea':
									$value = sanitize_textarea_field( wp_unslash( $_POST[ $field['name'] ] ) );
									break;
								case 'url':
									$value = sanitize_url( wp_unslash( $_POST[ $field['name'] ] ) );
									break;
								case 'checkbox':
									$value = wholesalex()->sanitize( sanitize_text_field( wp_unslash( $_POST[ $field['name'] ] ) ) );
									break;
								case 'email':
									$value = sanitize_email( wp_unslash( $_POST[ $field['name'] ] ) );
									break;

								default:
									break;
							}

							if ( 'user_login' === $field['name'] ) {
								$user_name = $value;
								continue;
							}
							if ( 'user_pass' === $field['name'] || 'display_name' === $field['name'] || 'nickname' === $field['name'] || 'first_name' === $field['name'] || 'last_name' === $field['name'] ) {
								if ( 'user_pass' === $field['name'] ) {
									$password = $value;
									continue;
								}
								$userdata[ $field['name'] ] = $value;
								continue;
							}
							if ( 'description' === $field['name'] ) {
								$userdata[ $field['name'] ] = $value;
								continue;
							}
							if ( 'url' === $field['name'] ) {
                            $userdata['user_url'] =  $value ;// phpcs:ignore
								continue;
							}
							if ( 'user_email' === $field['name'] ) {
								$user_email = $value;
								continue;
							}

							if ( 'wholesalex_registration_role' === $field['name'] ) {
								$usermeta['__wholesalex_registration_role'] = $value;
								$__registration_role                        = $value;
							} elseif ( isset( $field['migratedFromOldBuilder'] ) && $field['migratedFromOldBuilder'] ) {
									$usermeta[ $field['name'] ] = $value;
							} elseif ( isset( $field['custom_field'] ) && $field['custom_field'] ) {
								$usermeta[ 'wholesalex_cf_' . $field['name'] ] = $value;
							}
						}

						if ( 'file' == $field['type'] && isset( $_FILES[ $field['name'] ] ) && ! empty( $_FILES[ $field['name'] ] ) && $_FILES[ $field['name'] ]['name'] && ( $_FILES[ $field['name'] ]['size'] > 0 ) ) {
							// Allowed file types.
							if ( isset( $field['allowed_file_types'] ) && ! empty( $field['allowed_file_types'] ) ) {

								$create_file_types = array();
								foreach ( $field['allowed_file_types'] as $item ) {
									$create_file_types[] = $item['value'];
								}
								$create_file_types  = implode( ',', $create_file_types );
								$allowed_file_types = explode( ',', $create_file_types );
								$allowed_file_types = wholesalex()->sanitize( $allowed_file_types );
							} else {
								$allowed_file_types = array( 'jpg', 'jpeg', 'png', 'txt', 'pdf', 'doc', 'docx' );
							}

							// Allowed file size.
							if ( isset( $field['maximum_file_size'] ) && ! empty( $field['maximum_file_size'] ) ) {
								$allowed_file_size = sanitize_text_field( $field['maximum_file_size'] );
							} else {
								$allowed_file_size = 5000000; // 5MB Default
							}

							// Allowed file size -> 5MB.
							$allowed_file_size_in_mb = $allowed_file_size / 1000000;
							$file_extension          = strtolower( pathinfo( $_FILES[ $field['name'] ]['name'], PATHINFO_EXTENSION ) );

							if ( ! in_array( $file_extension, $allowed_file_types, true ) ) {
								// translators: %s Field Name.
								// translators: %s Allowed File Types.

								$data['error_messages'][ $field['name'] ] = sprintf( __( 'File Type Does not support for %1$s Field! Supported File Types is %2$s.', 'wholesalex' ), $field['label'], implode( ',', $allowed_file_types ) );
								throw new \Exception();
							}
							if ( $_FILES[ $field['name'] ]['size'] > $allowed_file_size ) {
								/* translators: 1: Field Label, 2: Allowed Size */
								$data['error_messages'][ $field['name'] ] = sprintf( __( 'File is too large! Max Upload Size For %1$s field is %2$s.', 'wholesalex' ), $field['label'], $allowed_file_size_in_mb . 'MB' );
								throw new \Exception();
							}

							if ( isset( $field['migratedFromOldBuilder'] ) && $field['migratedFromOldBuilder'] ) {
								$files[ 'file_' . $field['name'] ]  = $_FILES[ $field['name'] ];
								$_FILES[ 'file_' . $field['name'] ] = $_FILES[ $field['name'] ];

							} elseif ( isset( $field['custom_field'] ) && $field['custom_field'] ) {
								$files[ 'wholesalex_cf_' . $field['name'] ]  = $_FILES[ $field['name'] ];
								$_FILES[ 'wholesalex_cf_' . $field['name'] ] = $_FILES[ $field['name'] ];
							}
						}
					}
					if ( ! $__registration_role ) {
						if ( ( isset( $_POST['wholesalex_registration_role'] ) && ! empty( $_POST['wholesalex_registration_role'] ) ) ) {
							$usermeta['__wholesalex_registration_role'] = sanitize_text_field( wp_unslash( $_POST['wholesalex_registration_role'] ) );
							$__registration_role                        = $usermeta['__wholesalex_registration_role'];
						}
					}

					if ( ! $__registration_role ) {
						if ( ( isset( $_POST['wholesalex_registration_role'] ) && ! empty( $_POST['wholesalex_registration_role'] ) ) ) {
							$usermeta['__wholesalex_registration_role'] = sanitize_text_field( wp_unslash( $_POST['wholesalex_registration_role'] ) );
							$__registration_role                        = $usermeta['__wholesalex_registration_role'];
						}
					}

					$registered_user_id = wc_create_new_customer( $user_email, $user_name, $password, $userdata );
					if ( is_wp_error( $registered_user_id ) ) {

						$errors = $registered_user_id->get_error_codes();
						foreach ( $errors as $error_code ) {
							switch ( $error_code ) {
								case 'registration-error-invalid-email':
								case 'registration-error-email-exists':
									$data['error_messages']['user_email'] = $registered_user_id->get_error_message( $error_code );
									break;
								case 'registration-error-invalid-username':
								case 'registration-error-username-exists':
									$data['error_messages']['user_login'] = $registered_user_id->get_error_message( $error_code );
									break;
								case 'registration-error-missing-password':
									$data['error_messages']['user_pass'] = $registered_user_id->get_error_message( $error_code );
									break;
								default:
									$data['error_messages']['other_error'] = $registered_user_id->get_error_message( $error_code );
									break;
							}
						}

						throw new \Exception();

					} else {
						if ( is_array( $usermeta ) ) {
							foreach ( $usermeta as $key => $value ) {
								if ( 'user_confirm_email' === $key || 'user_confirm_password' === $key ) {
									continue;
								}
								add_user_meta( $registered_user_id, $key, $value );
							}
						}

						/**
						* Process File Upload
						*/
						require_once ABSPATH . 'wp-admin/includes/image.php';
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/media.php';

						foreach ( $files as $key => $file ) {
							// Upload File.
							$file_id = media_handle_upload( $key, 0 );

							// Set file registered user as file author.
							wp_update_post(
								array(
									'ID'          => $file_id,
									'post_author' => $registered_user_id,
								)
							);

							// Update file id in user meta.
							update_user_meta( $registered_user_id, $key, $file_id );
						}

						$__user_status_option = apply_filters( 'wholesalex_registration_form_user_status_option', 'admin_approve', $registered_user_id, $__registration_role );

						do_action( 'wholesalex_registration_form_user_status_' . $__user_status_option, $registered_user_id, $__registration_role );

						$__user_login_option = apply_filters( 'wholesalex_registration_form_user_login_option', 'manual_login' );
						do_action( 'wholesalex_registration_form_user_' . $__user_login_option, $registered_user_id, $__registration_role );

						$__redirect_url = apply_filters( 'wholesalex_registration_form_after_registration_redirect_url', wc_get_page_permalink( 'myaccount' ), $registered_user_id, $__registration_role );

						$__redirect_url = add_query_arg( 'wsx-notice', 'regi_success', $__redirect_url );
						$__redirect_url = add_query_arg( 'wsx-nonce', wp_create_nonce( 'wsx_notice' ), $__redirect_url );

						$data['redirect'] = wp_validate_redirect( apply_filters( 'woocommerce_registration_redirect', $__redirect_url, $registered_user_id ), wc_get_page_permalink( 'myaccount' ) );

						wp_send_json_success( $data );

					}
				} catch ( \Exception $th ) {
					wp_send_json_success( $data );
				}
			} else {
				if ( ! isset( $_POST['user_email'] ) ) {
					$data['error_messages']['user_email'] = __( 'Email is Required!', 'wholesalex' );
				}
				if ( ! isset( $_POST['user_pass'] ) ) {
					$data['error_messages']['user_pass'] = __( 'Password is Required!', 'wholesalex' );
				}
				wp_send_json_success( $data );
			}
		}
	}


	/**
	 * Show Wholesalex Notice
	 *
	 * @return void
	 * @since 1.0.0 Reposition on v1.0..7
	 * @throws \Exception If the generated string does not meet the criteria.
	 */
	public function show_wholesalex_notice() {

		if ( isset( $_GET['wsx-notice'], $_GET['wsx-nonce'] ) && wp_verify_nonce( sanitize_key( $_GET['wsx-nonce'] ), 'wsx_notice' ) ) {
			$notice_type = sanitize_text_field( wp_unslash( $_GET['wsx-notice'] ) );
			switch ( $notice_type ) {
				case 'regi_success':
					$__success_message = wholesalex()->get_setting( '_settings_registration_success_message', __( 'Thank you for registering. Your account will be reviewed by us & approve manually. Please wait to be approved.', 'wholesalex' ) );
					wc_add_notice( $__success_message, 'success' );

					break;

				default:
					// code...
					break;
			}
		}
	}

	/**
	 * Get Multiselect Values
	 *
	 * @param array $data Array.
	 * @return array
	 */
	public function get_multiselect_values( $data ) {
		$allowed_methods = array();
		foreach ( $data as $method ) {
			$allowed_methods[] = $method['value'];
		}
		return $allowed_methods;
	}
}
