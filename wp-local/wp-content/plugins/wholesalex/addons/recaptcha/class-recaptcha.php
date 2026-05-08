<?php
/**
 * WholesaleX Recaptcha
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

use Exception;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WholesaleX Recaptcha Class
 *
 * @since 1.0.0
 */
class Recaptcha {


	/**
	 * Shortcodes Constructor
	 */
	public function __construct() {
		add_filter( 'wholesalex_register_scripts', array( $this, 'register_recaptcha' ) );
		add_action( 'wholesalex_before_process_user_registration', array( $this, 'add_recaptcha_on_registration_form' ), 10 );
		add_action( 'wholesalex_before_process_user_login', array( $this, 'add_recaptcha_on_login_form' ), 10 );
		add_action( 'wholesalex_before_process_conversation', array( $this, 'add_recaptcha' ), 10, 1 );
		add_action( 'wholesalex_before_registration_form_render', array( $this, 'add_recaptcha_script' ) );
		add_action( 'wholesalex_conversation_metabox_content', array( $this, 'add_recaptcha_script' ) );
		add_action( 'wholesalex_conversation_metabox_content_account_page', array( $this, 'add_recaptcha_script' ) );
		add_action( 'wholesalex_before_conversation_create', array( $this, 'add_recaptcha' ) );
		add_action( 'wholesalex_before_conversation_reply', array( $this, 'add_recaptcha' ) );
		/**
		 * WooCommerce Login Recaptcha Verification
		 *
		 * @since 1.0.1
		 */
		add_action( 'woocommerce_login_form', array( $this, 'process_recaptcha_script' ) );
		add_filter( 'wp_authenticate_user', array( $this, 'recaptcha_on_wc_login' ) );
		add_action( 'woocommerce_login_form', array( $this, 'add_recaptcha_v2_to_login_form' ) );
		add_action( 'woocommerce_register_form', array( $this, 'add_recaptcha_v2_to_registration_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_recaptcha_v2_script_for_registration' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'add_recaptcha_v2_login' ) );
		add_action( 'login_form', array( $this, 'add_recaptcha_v2_widget' ) );
		add_action( 'wholesalex_login_form', array( $this, 'add_recaptcha_v2_widget' ) );
		add_action( 'wholesalex_login_form', array( $this, 'add_recaptcha_wholesalex_login' ) );
		add_action( 'wholesalex_registration_form', array( $this, 'add_recaptcha_v2_to_registration_form' ) );
	}

	public function add_recaptcha_wholesalex_login() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v2_site_key' ); // Use v2 site key

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			wp_enqueue_script(
				'google-recaptcha',
				'https://www.google.com/recaptcha/api.js',
				array(),
				null,
				true
			);
		}

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
				$inline_script = <<<EOT
                jQuery(document).ready(function($) {
                    // Add reCAPTCHA to WooCommerce registration form
                    $('form.woocommerce-form.woocommerce-form-register.register').each(function() {
                        $(this).find('.woocommerce-form__input').last().after(
                            '<div class="g-recaptcha" data-sitekey="' + '$site_key' + '"></div>'
                        );
                    });

                    // Prevent form submission if reCAPTCHA is not completed
                    $('form.woocommerce-form.woocommerce-form-register.register').submit(function(e) {
                        if ($(this).find('.g-recaptcha-response').val() === '') {
                            e.preventDefault();
                            alert('Please complete the reCAPTCHA checkbox.');
                            return false;
                        }
                        $('<input>')
                            .attr({
                                name: 'register',
                                id: 'register',
                                type: 'hidden',
                                value: 'Register'
                            })
                            .appendTo(this);
                    });
                });
EOT;

				// Add inline script to the enqueued reCAPTCHA script.
				wp_add_inline_script( 'google-recaptcha', $inline_script );
		}
	}



	public function add_recaptcha_v2_login() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			wp_enqueue_script(
				'google-recaptcha',
				'https://www.google.com/recaptcha/api.js',
				array(),
				null,
				true
			);
		}
	}

	public function add_recaptcha_v2_widget() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			?>
			<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<br>
			<?php
		}
	}

	public function add_recaptcha_v2_to_login_form() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			?>
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
				<br>
			<?php
		}
	}

	public function enqueue_recaptcha_v2_script_for_registration() {
		if ( is_page( 'my-account' ) || is_wc_endpoint_url( 'register' ) ) {
			$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
			$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

			if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
				$inline_script = <<<EOT
                jQuery(document).ready(function($) {
                    // Add reCAPTCHA to WooCommerce registration form
                    $('form.woocommerce-form.woocommerce-form-register.register').each(function() {
                        $(this).find('.woocommerce-form__input').last().after(
                            '<div class="g-recaptcha" data-sitekey="' + '$site_key' + '"></div>'
                        );
                    });

                    // Prevent form submission if reCAPTCHA is not completed
                    $('form.woocommerce-form.woocommerce-form-register.register').submit(function(e) {
                        if ($(this).find('.g-recaptcha-response').val() === '') {
                            e.preventDefault();
                            alert('Please complete the reCAPTCHA checkbox.');
                            return false;
                        }
                        $('<input>')
                            .attr({
                                name: 'register',
                                id: 'register',
                                type: 'hidden',
                                value: 'Register'
                            })
                            .appendTo(this);
                    });
                });
EOT;

				// Add inline script to the enqueued reCAPTCHA script.
				wp_add_inline_script( 'google-recaptcha', $inline_script );
			}
		}
	}

	public function add_recaptcha_v2_to_registration_form() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			?>
			<div style="margin-top:15px;" class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<br>
			<?php
		}
	}
	/**
	 * Contains instance of this class.
	 *
	 * @var Recaptcha
	 * @since 1.0.0
	 */

	protected static $_instance = null; //phpcs:ignore


	/**
	 * Recaptcha instance
	 *
	 * @return class object
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * Parse recaptcha Response
	 *
	 * @param string $response reCaptcha response token.
	 * @return array
	 * @since 1.0.0
	 */
	private function parse_recaptcha_response( $response ) {
		$secret_key           = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
		$recaptcha_verify_api = sprintf( 'https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s', $secret_key, $response );
		$response             = (array) wp_remote_get( $recaptcha_verify_api );
		$error                = array(
			'success'     => false,
			'error-codes' => array( 'unknown' ),
		);

		return isset( $response['body'] ) ? json_decode( $response['body'], true ) : $error;
	}

	/**
	 * Recaptcha Error Code
	 *
	 * @param string $code recaptcha error code.
	 * @return string Error Message.
	 * @since 1.0.0
	 */
	private function recaptcha_error_message( $code ) {
		switch ( $code ) {
			case 'missing-input-secret':
				return __( 'The secret parameter is missing.', 'wholesalex' );
			case 'invalid-input-secret':
				return __( 'The secret parameter is invalid or malformed.', 'wholesalex' );
			case 'missing-input-response':
				return __( 'The response parameter is missing.', 'wholesalex' );
			case 'invalid-input-response':
				return __( 'The response parameter is invalid or malformed.', 'wholesalex' );
			case 'bad-request':
				return __( 'The request is invalid or malformed.', 'wholesalex' );
			case 'timeout-or-duplicate':
				return __( 'The response is no longer valid: either is too old or has been used previously.', 'wholesalex' );
			default:
				return __( 'Unknown!', 'wholesalex' );
		}
	}

	/**
	 * WholesaleX Process Recaptcha in user registration form
	 *
	 * @param object $post_data User Input Data.
	 * @throws Exception Recaptcha Error.
	 * @since 1.0.0
	 */
	public function add_recaptcha( $post_data ) {
		$__site_key        = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
		$__secret_key      = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
		$__token           = isset( $post_data['token'] ) ? $post_data['token'] : '';
		$__minimum_score   = apply_filters( 'wholesalex_recaptcha_minimum_score_allow', 0.5 );
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		if ( isset( $__token ) && $__site_key && $__secret_key && 'recaptcha_v3' === $recaptcha_version ) {
			$parsed_response = $this->parse_recaptcha_response( $__token );
			if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] && $parsed_response['score'] >= $__minimum_score ) ) {
				$error_header = __( 'reCAPTCHA v3:', 'wholesalex' );
				if ( function_exists( 'wc_add_notice' ) && DOING_AJAX ) {
					try {
						wc_add_notice( $error_header . $this->recaptcha_error_message( $parsed_response['error-codes'][0] ), 'error' );
						wp_send_json_error( array( 'messages' => wc_print_notices( true ) ) );
					} catch ( Exception $e ) {
						throw new Exception( 'reCAPTCHA v3 Error!' );
					}
				} else {
					wp_safe_redirect( esc_url_raw( $post_data['_wp_http_referer'] ) );
					exit();
				}
			}
		}
	}

	/**
	 * WholesaleX Process Recaptcha in user registration form
	 *
	 * @since 1.0.0
	 */
	public function add_recaptcha_on_registration_form() {
		if ( isset( $_POST['token'], $_POST['wholesalex-registration-nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wholesalex-registration-nonce'] ), 'wholesalex-registration' ) ) {
			$data              = array(
				'error_messages' => array(),
			);
			$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
			$__site_key        = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
			$__secret_key      = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
			$__token           = sanitize_text_field( wp_unslash( $_POST['token'] ) );
			$__minimum_score   = apply_filters( 'wholesalex_recaptcha_minimum_score_allow', 0.5 );

			if ( 'recaptcha_v3' === $recaptcha_version ) {

				if ( isset( $__token ) && $__site_key && $__secret_key ) {
					$parsed_response = $this->parse_recaptcha_response( $__token );
					if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] && $parsed_response['score'] >= $__minimum_score ) ) {
						$error_header                        = __( 'reCAPTCHA v3:', 'wholesalex' );
						$data['error_messages']['recaptcha'] = wc_print_notice( $error_header . $this->recaptcha_error_message( $parsed_response['error-codes'][0] ), 'error', array(), true );
						wp_send_json_success( $data );
					}
				}
			} elseif ( 'recaptcha_v2' === $recaptcha_version ) {
				$__site_key   = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
				$__secret_key = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
				$__response   = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '';

				if ( $__response && $__site_key && $__secret_key ) {
					$verify_response = wp_remote_post(
						'https://www.google.com/recaptcha/api/siteverify',
						array(
							'body' => array(
								'secret'   => $__secret_key,
								'response' => $__response,
								// 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
							),
						)
					);

					$parsed_response = json_decode( wp_remote_retrieve_body( $verify_response ), true );
					if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] ) ) {
						return new WP_Error(
							'recaptcha_error',
							__( '<strong>reCAPTCHA v2:</strong> Invalid reCAPTCHA. Please check the box.', 'wholesalex' )
						);
					}

					if ( empty( $parsed_response['success'] ) ) {
						$error_header                        = __( 'reCAPTCHA v2:', 'wholesalex' );
						$data['error_messages']['recaptcha'] = wc_print_notice(
							$error_header . $this->recaptcha_error_message( $parsed_response['error-codes'][0] ?? '' ),
							'error',
							array(),
							true
						);
						wp_send_json_success( $data );
						return;
					}
				} else {
					$error_header                        = __( 'reCAPTCHA v2:', 'wholesalex' );
					$data['error_messages']['recaptcha'] = wc_print_notice(
						$error_header . __( 'Please complete the reCAPTCHA.', 'wholesalex' ),
						'error',
						array(),
						true
					);
					wp_send_json_success( $data );
					return;
				}
			}
		}
	}

	/**
	 * Add Recaptcha on Login Form
	 *
	 * @since 1.0.0
	 */
	public function add_recaptcha_on_login_form() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );

		if ( 'recaptcha_v3' === $recaptcha_version ) {
			if ( isset( $_POST['token'], $_POST['wholesalex-login-nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wholesalex-login-nonce'] ), 'wholesalex-login' ) ) {
				$data = array(
					'error_messages' => array(),
				);

				$__site_key      = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
				$__secret_key    = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
				$__token         = sanitize_text_field( wp_unslash( $_POST['token'] ) );
				$__minimum_score = apply_filters( 'wholesalex_recaptcha_minimum_score_allow', 1 );

				if ( isset( $__token ) && $__site_key && $__secret_key ) {
					$parsed_response = $this->parse_recaptcha_response( $__token );
					if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] && $parsed_response['score'] >= $__minimum_score ) ) {
						$error_header                        = __( 'reCAPTCHA v3:', 'wholesalex' );
						$data['error_messages']['recaptcha'] = wc_add_notice( $error_header . $this->recaptcha_error_message( $parsed_response['error-codes'][0] ), 'error' );
						wp_send_json_success( $data );
					}
				}
			}
		} elseif ( isset( $_POST['g-recaptcha-response'], $_POST['wholesalex-login-nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wholesalex-login-nonce'] ), 'wholesalex-login' ) ) {
			$data = array(
				'error_messages' => array(),
			);

			$site_key   = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
			$secret_key = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
			$token      = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) );
			if ( ! empty( $token ) && $site_key && $secret_key ) {
				$response = wp_remote_post(
					'https://www.google.com/recaptcha/api/siteverify',
					array(
						'body' => array(
							'secret'   => $secret_key,
							'response' => $token,
						),
					)
				);

				if ( is_wp_error( $response ) ) {
					$error_header                        = __( 'reCAPTCHA v2:', 'wholesalex' );
					$data['error_messages']['recaptcha'] = wc_add_notice( $error_header . __( 'Verification failed. Please try again.', 'wholesalex' ), 'error' );
					wp_send_json_success( $data );
				}

				$current_url = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

				$is_wp_login     = strpos( $current_url, 'wp-login.php' ) !== false;
				$is_my_account   = strpos( $current_url, 'my-account' ) !== false || strpos( $current_url, 'myaccount' ) !== false;
				$parsed_response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( $is_wp_login || $is_my_account ) {
					if ( isset( $_POST['log'] ) ) {
						$username = sanitize_user( wp_unslash( $_POST['log'] ) );
						$user     = get_user_by( 'login', $username );

						if ( $user instanceof \WP_User && in_array( 'administrator', (array) $user->roles, true ) ) {
							return $user;
						}
					}
					$parsed_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] ) ) {
						return new WP_Error(
							'recaptcha_error',
							__( '<strong>reCAPTCHA v2:</strong> Invalid reCAPTCHA. Please check the box.', 'wholesalex' )
						);
					}
				}
			} else {
				$error_header                        = __( 'reCAPTCHA v2:', 'wholesalex' );
				$data['error_messages']['recaptcha'] = wc_add_notice( $error_header . __( 'Please complete the reCAPTCHA checkbox.', 'wholesalex' ), 'error' );
				wp_send_json_success( $data );
			}
		}
	}

	/**
	 * Add Recaptcha Script in user registration form
	 *
	 * @since 1.0.0
	 */
	public function add_recaptcha_script() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v3' === $recaptcha_version ) {
			wp_enqueue_script( 'wholesalex-google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $site_key, array(), WHOLESALEX_VER, true );
			wp_add_inline_script( 'wholesalex-google-recaptcha-v3', 'var recaptchaSiteKey=' . wp_json_encode( $site_key ) );
		} else {
			if ( ! $site_key ) {
				return;
			}

			wp_enqueue_script(
				'wholesalex-google-recaptcha-v2',
				'https://www.google.com/recaptcha/api.js',
				array(),
				WHOLESALEX_VER,
				true
			);
			wp_add_inline_script(
				'wholesalex-google-recaptcha-v2',
				'var recaptchaSiteKey=' . wp_json_encode( $site_key )
			);
		}
	}

	/**
	 * Register Google Recaptcha Script
	 *
	 * @param array $scripts Scripts Array.
	 * @since 1.0.0
	 */
	public function register_recaptcha( $scripts ) {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
		if ( ! $site_key ) {
			return $scripts;
		}
		if ( 'recaptcha_v3' === $recaptcha_version ) {

			$scripts['wholesalex-google-recaptcha-v3'] = array(
				'src'       => 'https://www.google.com/recaptcha/api.js?render=' . $site_key,
				'deps'      => array(),
				'ver'       => WHOLESALEX_VER,
				'in_footer' => true,
			);
		} else {
			$scripts['wholesalex-google-recaptcha-v2'] = array(
				'src'       => 'https://www.google.com/recaptcha/api.js',
				'deps'      => array(),
				'ver'       => WHOLESALEX_VER,
				'in_footer' => true,
			);

			$inline_script = <<<EOT
				jQuery(document).ready(function($) {
					$('form.woocommerce-form.woocommerce-form-login.login').each(function() {
						$(this).find('.woocommerce-form__input').last().after(
							'<div class="g-recaptcha" data-sitekey="' + recaptchaSiteKey + '"></div>'
						);
					});

					// Prevent form submission if reCAPTCHA is not completed
					$('form.woocommerce-form.woocommerce-form-login.login').submit(function(e) {
						if ($(this).find('.g-recaptcha-response').val() === '') {
							e.preventDefault();
							alert('Please complete the reCAPTCHA checkbox.');
							return false;
						}
						$('<input>')
							.attr({
								name: 'login',
								id: 'login',
								type: 'hidden',
								value: 'Login'
							})
							.appendTo(this);
					});
				});
			EOT;

			wp_add_inline_script( 'wholesalex-google-recaptcha-v2', $inline_script );
		}

		return $scripts;
	}

	/**
	 * Process Recaptcha on WooCommerce Login
	 *
	 * @param WP_User|WP_Error $user     WP_User or WP_Error object if a previous
	 *                                   callback failed authentication.
	 * @since 1.0.1
	 */
	public function recaptcha_on_wc_login( $user ) {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$__site_key        = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
		$__secret_key      = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
		$__token         = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : ''; //phpcs:ignore
		$__minimum_score   = apply_filters( 'wholesalex_recaptcha_minimum_score_allow', 0.5 );

		if ( 'recaptcha_v3' === $recaptcha_version ) {
			if ( ! empty( $__token ) && $__site_key && $__secret_key ) {
				$parsed_response = $this->parse_recaptcha_response( $__token );

				if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] && $parsed_response['score'] >= $__minimum_score ) ) {

					return new WP_Error(
						'recaptcha_error',
						__( '<strong>reCAPTCHA v3:</strong> Error!', 'wholesalex' )
					);
				}
			}
		} else {
			$token = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( $_POST['g-recaptcha-response'] ) : '';
			if ( $__site_key && $__secret_key ) {
				$response = wp_remote_post(
					'https://www.google.com/recaptcha/api/siteverify',
					array(
						'body' => array(
							'secret'   => $__secret_key,
							'response' => $token,
						),
					)
				);

				if ( is_wp_error( $response ) ) {
					return new WP_Error(
						'recaptcha_error',
						__( '<strong>reCAPTCHA v2:</strong> Verification failed. Please try again.', 'wholesalex' )
					);
				}

				$current_url = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

				$is_wp_login     = strpos( $current_url, 'wp-login.php' ) !== false;
				$is_my_account   = strpos( $current_url, 'my-account' ) !== false || strpos( $current_url, 'myaccount' ) !== false;
				$parsed_response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( $is_wp_login || $is_my_account ) {
					if ( isset( $_POST['log'] ) ) {
						$username = sanitize_user( wp_unslash( $_POST['log'] ) );
						$user     = get_user_by( 'login', $username );

						if ( $user instanceof \WP_User && in_array( 'administrator', (array) $user->roles, true ) ) {
							return $user;
						}
					}
					$parsed_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] ) ) {
						return new WP_Error(
							'recaptcha_error',
							__( '<strong>reCAPTCHA v2:</strong> Invalid reCAPTCHA. Please check the box.', 'wholesalex' )
						);
					}
				}
			} else {
				return new WP_Error(
					'recaptcha_error',
					__( '<strong>reCAPTCHA v2:</strong> Please complete the reCAPTCHA.', 'wholesalex' )
				);
			}
		}

		return $user;
	}

	/**
	 * Process Recaptcha Script
	 */
	public function process_recaptcha_script() {
		$this->add_recaptcha_script();

		if ( 'yes' === wholesalex()->get_setting( 'wsx_addon_recaptcha' ) ) {
			$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
			$__site_key        = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

			if ( 'recaptcha_v3' === $recaptcha_version ) {
				?>
				<script type="text/javascript">
					(function($) {
						$(document).ready(function() {
							$("form.woocommerce-form.woocommerce-form-login.login").submit(function(e) {
								e.preventDefault();
								let curState = this;
								if(typeof grecaptcha !== 'undefined') {
									grecaptcha.ready(function() {
									try {
										grecaptcha
										.execute('<?php echo esc_html( $__site_key ); ?>', {
											action: "submit",
										})
										.then(function(token) {
											$("<input>")
												.attr({
													name: "token",
													id: "token",
													type: "hidden",
													value: token,
												})
												.appendTo("form");
											$("<input>")
												.attr({
													name: "login",
													id: "login",
													type: "hidden",
													value: 'Login',
												})
												.appendTo("form");
											curState.submit();
										});
									} catch (error) {
										// error
									}
									});
								} else {
									$("<input>")
									.attr({
										name: "login",
										id: "login",
										type: "hidden",
										value: 'Login',
									})
									.appendTo("form");
									
									curState.submit();
								}
							});
						});
					})(jQuery);
				</script>
				<?php

			} else {
				$site_key = wholesalex()->get_setting( '_settings_google_recaptcha_v2_site_key' );
				if ( ! $site_key ) {
					return;
				}
				?>
				<script type="text/javascript">
					(function($) {
						$(document).ready(function() {
							// Add reCAPTCHA v2 widget to the form
							$('form.woocommerce-form.woocommerce-form-login.login').each(function() {
								$(this).find('.woocommerce-form__input').last().after(
									'<div class="g-recaptcha" data-sitekey="<?php echo esc_html( $site_key ); ?>"></div>'
								);
							});

							// Optional: Add hidden input for login form submission
							$('form.woocommerce-form.woocommerce-form-login.login').submit(function(e) {
								if ($(this).find('.g-recaptcha-response').val() === '') {
									e.preventDefault();
									alert('Please complete the reCAPTCHA.');
									return false;
								}
								$('<input>')
									.attr({
										name: 'login',
										id: 'login',
										type: 'hidden',
										value: 'Login',
									})
									.appendTo(this);
							});
						});
					})(jQuery);
				</script>
				<?php
			}
		}
	}
}
