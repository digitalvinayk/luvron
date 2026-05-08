<?php //phpcs:ignore
namespace WHOLESALEX;


defined( 'ABSPATH' ) || exit;

/**
 * Plugin Notice
 */
class Notice {

	/**
	 * Notice Constructor
	 */

	private $notice_version        = 'v233';
	private $notice_js_css_applied = false;
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices_callback' ) );
		add_action( 'admin_init', array( $this, 'set_dismiss_notice_callback' ) );

		// REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );

		// Woocommerce Install Action.
		add_action( 'wp_ajax_wsx_install', array( $this, 'install_activate_plugin' ) );
	}

	/**
	 * Registers REST API endpoints.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		$routes = array(
			// Hello Bar.
			array(
				'endpoint'            => 'hello_bar',
				'methods'             => 'POST',
				'callback'            => array( $this, 'hello_bar_callback' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		);

		foreach ( $routes as $route ) {
			register_rest_route(
				'wsx/v1',
				$route['endpoint'],
				array(
					array(
						'methods'             => $route['methods'],
						'callback'            => $route['callback'],
						'permission_callback' => $route['permission_callback'],
					),
				)
			);
		}
	}

	/**
	 * Hellobar config
	 *
	 * @return array
	 */
	public static function get_hellobar_config() {
		return array(
			'wsx_helloBar_flash_sale_2026' => Xpo::get_transient_without_cache( 'wsx_helloBar_flash_sale_2026' ),
			'wsx_helloBar_surprise_sale_2026' => Xpo::get_transient_without_cache( 'wsx_helloBar_surprise_sale_2026' ),
			'wsx_helloBar_massive_sale_2026' => Xpo::get_transient_without_cache( 'wsx_helloBar_massive_sale_2026' ),
			'wsx_helloBar_final_hour_sale_2026' => Xpo::get_transient_without_cache( 'wsx_helloBar_final_hour_sale_2026' ),
		);
	}

	/**
	 * Handles Hello Bar dismissal action via REST API .
	 *
	 * @param \WP_REST_Request $request REST request object .
	 * @return \WP_REST_Response
	 */
	public function hello_bar_callback( \WP_REST_Request $request ) {
		$request_params = $request->get_params();
		$type           = isset( $request_params['type'] ) ? $request_params['type'] : '';
		$id             = isset( $request_params['id'] ) ? $request_params['id'] : '';

		if ( 'hello_bar' === $type && ! empty( $id ) ) {
			Xpo::set_transient_without_cache( $id, 'hide', 1296000 );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Hello Bar Action performed', 'wow-table-rate-shipping' ),
			),
			200
		);
	}

	/**
	 * Set Notice Dismiss Callback
	 *
	 * @return void
	 */
	public function set_dismiss_notice_callback() {

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['wpnonce'] ?? '' ) ), 'wsx-nonce' ) ) {
			return;
		}

		$durbin_key = sanitize_text_field( wp_unslash( $_GET['wsx_durbin_key'] ?? '' ) );

		// Durbin notice dismiss.
		if ( ! empty( $durbin_key ) ) {
			Xpo::set_transient_without_cache( 'wsx_durbin_notice_' . $durbin_key, 'off' );

			if ( 'get' === sanitize_text_field( wp_unslash( $_GET['wsx_get_durbin'] ?? '' ) ) ) {
				DurbinClient::send( DurbinClient::ACTIVATE_ACTION );
			}
		}

		// Install notice dismiss.
		$install_key = sanitize_text_field( wp_unslash( $_GET['wsx_install_key'] ?? '' ) );
		if ( ! empty( $install_key ) ) {
			Xpo::set_transient_without_cache( 'wsx_install_notice_' . $install_key, 'off' );
		}

		$notice_key = sanitize_text_field( wp_unslash( $_GET['disable_wsx_notice'] ?? '' ) );
		if ( ! empty( $notice_key ) ) {
			$interval = (int) sanitize_text_field( wp_unslash( $_GET['wsx_interval'] ?? '' ) );
			if ( ! empty( $interval ) ) {
				Xpo::set_transient_without_cache( 'wsx_get_pro_notice_' . $notice_key, 'off', $interval );
			} else {
				Xpo::set_transient_without_cache( 'wsx_get_pro_notice_' . $notice_key, 'off' );
			}
		}
	}

	/**
	 * Admin Notices Callback
	 *
	 * @return void
	 */
	public function admin_notices_callback() {
		$this->wsx_dashboard_notice_callback();
		$this->wsx_dashboard_durbin_notice_callback();
	}

	/**
	 * Admin Dashboard Notice Callback
	 *
	 * @return void
	 */
	public function wsx_dashboard_notice_callback() {
		$this->wsx_dashboard_banner_notice();
		$this->wsx_dashboard_content_notice();
	}

	/**
	 * Dashboard Banner Notice
	 *
	 * @return void
	 */
	public function wsx_dashboard_banner_notice() {
		$wsx_db_nonce   = wp_create_nonce( 'wsx-nonce' );
		$banner_notices = array(
			array(
				'key'                => 'wsx_banner_flash_sale_2026_1',
				'start'              => '2026-05-18 00:00 Asia/Dhaka', //testing purpose
				'end'                => '2026-05-21 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#6c6cff',
				'left_image'         => WHOLESALEX_URL . 'assets/img/banners/flash_sale/left_image.png',
				'right_image'        => WHOLESALEX_URL . 'assets/img/banners/flash_sale/right_image.png',
				'bg_image'           => WHOLESALEX_URL . 'assets/img/banners/flash_sale/bg.png',
				'text'               => 'Hurry Before It Ends!',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash_sale',
					)
				),

				'visibility'         => ! Xpo::is_lc_active(),
			),
			array(
				'key'                => 'wsx_banner_surprise_sale_2026_2',
				'start'              => '2026-05-29 00:00 Asia/Dhaka', //testing purpose
				'end'                => '2026-06-01 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#6c6cff',
				'left_image'         => WHOLESALEX_URL . 'assets/img/banners/surprise_sale/left_image.png',
				'right_image'        => WHOLESALEX_URL . 'assets/img/banners/flash_sale/right_image.png',
				'bg_image'           => WHOLESALEX_URL . 'assets/img/banners/flash_sale/bg.png',
				'text'               => 'Hurry Before It Ends!',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'surprise_sale',
					)
				),

				'visibility'         => ! Xpo::is_lc_active(),
			),
			array(
				'key'                => 'wsx_banner_massive_sale_2026_1',
				'start'              => '2026-06-17 00:00 Asia/Dhaka', //testing purpose
				'end'                => '2026-06-20 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#6c6cff',
				'left_image'         => WHOLESALEX_URL . 'assets/img/banners/massive_sale/left_image.png',
				'right_image'        => WHOLESALEX_URL . 'assets/img/banners/flash_sale/right_image.png',
				'bg_image'           => WHOLESALEX_URL . 'assets/img/banners/flash_sale/bg.png',
				'text'               => 'Hurry Before It Ends!',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'massive_sale',
					)
				),

				'visibility'         => ! Xpo::is_lc_active(),
			),
			array(
				'key'                => 'wsx_banner_final_hour_sale_2026_2',
				'start'              => '2026-06-28 00:00 Asia/Dhaka', //testing purpose
				'end'                => '2026-06-30 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#6c6cff',
				'left_image'         => WHOLESALEX_URL . 'assets/img/banners/final_hour_sale/left_image.png',
				'right_image'        => WHOLESALEX_URL . 'assets/img/banners/flash_sale/right_image.png',
				'bg_image'           => WHOLESALEX_URL . 'assets/img/banners/flash_sale/bg.png',
				'text'               => 'Hurry Before It Ends!',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final_hour_sale',
					)
				),

				'visibility'         => ! Xpo::is_lc_active(),
			),
		);

		foreach ( $banner_notices as $notice ) {
			$notice_key = isset( $notice['key'] ) ? $notice['key'] : $this->notice_version;
			if ( isset( $_GET['disable_wsx_notice'] ) && $notice_key === sanitize_text_field(wp_unslash($_GET['disable_wsx_notice'])) ) { // phpcs:ignore
				continue;
			}

			$current_time = gmdate( 'U' );
			$notice_start = gmdate( 'U', strtotime( $notice['start'] ) );
			$notice_end   = gmdate( 'U', strtotime( $notice['end'] ) );
			if ( $current_time >= $notice_start && $current_time <= $notice_end && $notice['visibility'] ) {

				$notice_transient = Xpo::get_transient_without_cache( 'wsx_get_pro_notice_' . $notice_key );

				if ( 'off' === $notice_transient ) {
					continue;
				}

				if ( ! $this->notice_js_css_applied ) {
					$this->wsx_banner_notice_js();
					$this->notice_js_css_applied = true;
				}
				$query_args = array(
					'disable_wsx_notice' => $notice_key,
					'wpnonce'            => $wsx_db_nonce,
				);
				if ( isset( $notice['repeat_interval'] ) && $notice['repeat_interval'] ) {
					$query_args['wsx_interval'] = $notice['repeat_interval'];
				}
				?>
				<style type="text/css">
					.wsx-notice-wrapper.wsx-banner-notice {
						height: auto !important;
						min-height: 90px;
						padding: 0 !important;
						position: relative;
						box-sizing: border-box;
						background-repeat: no-repeat;
						background-size: cover;
						background-position: center;
					}

					.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-link {
						width: 100%;
						text-decoration: none;
						display: block;
					}

					.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-content {
						display: flex;
						justify-content: space-between;
						align-items: center;
						max-width: 1358px;
						margin: 0 auto;
						padding: 10px 16px;
						gap: 16px;
					}
					.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-side-image {
						display: block;
						max-width: 100%;
						height: auto;
					}
					.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-main {
						display: flex;
						flex-direction: column;
						gap: 4px;
						align-items: center;
						justify-content: center;
						font-weight: 700;
						font-size: 28px;
						color: #ffffff;
						line-height: 32px;
						text-align: center;
					}

					@media screen and (max-width: 1100px) {
						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-content {
							flex-direction: column;
						}
					}
					@media screen and (max-width: 782px) {
						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-content {
							justify-content: center;
							padding: 12px 32px 12px 12px;
						}
						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-main {
							font-size: 22px;
							line-height: 28px;
						}
					}
					@media screen and (max-width: 480px) {
						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-content {
							padding: 10px 32px 10px 10px;
						}
						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-main {
							font-size: 18px;
							line-height: 24px;
						}
					}
				</style>
				<div 
					class="wsx-notice-wrapper wsx-banner-notice notice" 
					style="
						border-left: 3px solid <?php echo esc_attr( $notice['brand_color'] ); ?>;
						background-image: url('<?php echo esc_attr( $notice['bg_image'] ); ?>');
				">
					<a 
						class="wc-dismiss-notice dashicons dashicons-no-alt" 
						style="
							position: absolute;
							top: 1px;
							right: 1px;
							border-radius: 50%;
							background-color: black;
							color: white;
							font-size: 14px;
							display: flex;
							align-items: center;
							justify-content: center;
						"
						aria-label="<?php esc_html_e( 'Close Banner', 'wholesalex' ); ?>"
						href="<?php echo esc_url( add_query_arg( $query_args ) ); ?>">
					</a>

					<a class="wsx-banner-link" target="_blank" href="<?php echo esc_url( $notice['url'] ); ?>">
						<div class="wsx-banner-content">
							<img class="wsx-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice['left_image'] ); ?>" />
							<div class="wsx-banner-main">
								<span>
									<?php echo esc_html( $notice['text'] ); ?>
								</span>	
								<div 
									class="wsx-notice-countdown" 
									style="
										color: <?php echo esc_attr( $notice['countdown_color'] ); ?>;
									"
									data-notice-key="<?php echo esc_attr( $notice_key . '-countdown' ); ?>" 
									data-duration="<?php echo esc_attr( $notice['countdown_duration'] ); ?>">
									00:00:00:00
								</div>
							</div>
							<img class="wsx-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice['right_image'] ); ?>" />
						</div>
					</a>
				</div>
				
				<?php
			}
		}
	}

	/**
	 * Dashboard Content Notice
	 *
	 * @return void
	 */
	public function wsx_dashboard_content_notice() {

		$content_notices = array(
			array(
				'key'                => 'wsx_dashboard_content_notice_flash_sale_2026_1',
				'start'              => '2026-05-07 00:00 Asia/Dhaka',
				'end'                => '2026-05-12 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Flash Sale:', 'wholesalex' ),
				'content_subheading' => __( 'Enjoy %s off on WholesaleX Pro.', 'wholesalex' ),
				'discount_content'   => ' up to 50% OFF',
				'border_color'       => '#6c6cff',
				'icon'               => WHOLESALEX_URL . 'assets/img/banners/wholesalex_logo.svg',
				'button_text'        => __( 'Upgrade Now →', 'wholesalex' ),
				'is_discount_logo'   => false,
			),
			array(
				'key'                => 'wsx_dashboard_content_notice_flash_sale_2026_2',
				'start'              => '2026-05-13 00:00 Asia/Dhaka',
				'end'                => '2026-05-17 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Surprise Sale:', 'wholesalex' ),
				'content_subheading' => __( 'Enjoy %s off on WholesaleX Pro.', 'wholesalex' ),
				'discount_content'   => ' up to 50% OFF',
				'border_color'       => '#6c6cff',
				'icon'               => WHOLESALEX_URL . 'assets/img/banners/wholesalex_discount2.svg',
				'button_text'        => __( 'Upgrade Now →', 'wholesalex' ),
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wsx_dashboard_content_notice_surprise_sale_2026_1',
				'start'              => '2026-05-22 00:00 Asia/Dhaka',
				'end'                => '2026-05-25 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'surprise_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Surprise Sale:', 'wholesalex' ),
				'content_subheading' => __( 'Enjoy %s off on WholesaleX Pro.', 'wholesalex' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#6c6cff',
				'icon'               => WHOLESALEX_URL . 'assets/img/banners/wholesalex_logo.svg',
				'button_text'        => __( 'Upgrade Now →', 'wholesalex' ),
				'is_discount_logo'   => false,
			),
			array(
				'key'                => 'wsx_dashboard_content_notice_surprise_sale_2026_2',
				'start'              => '2026-05-26 00:00 Asia/Dhaka',
				'end'                => '2026-05-28 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'surprise_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Surprise Sale:', 'wholesalex' ),
				'content_subheading' => __( 'Enjoy %s off on WholesaleX Pro.', 'wholesalex' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#6c6cff',
				'icon'               => WHOLESALEX_URL . 'assets/img/banners/wholesalex_discount.svg',
				'button_text'        => __( 'Upgrade Now →', 'wholesalex' ),
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wsx_dashboard_content_notice_massive_sale_2026_1',
				'start'              => '2026-06-02 00:00 Asia/Dhaka',
				'end'                => '2026-06-10 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'massive_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Massive Sale:', 'wholesalex' ),
				'content_subheading' => __( 'Enjoy %s off on WholesaleX Pro.', 'wholesalex' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#6c6cff',
				'icon'               => WHOLESALEX_URL . 'assets/img/banners/wholesalex_logo.svg',
				'button_text'        => __( 'Upgrade Now →', 'wholesalex' ),
				'is_discount_logo'   => false,
			),
			array(
				'key'                => 'wsx_dashboard_content_notice_massive_sale_2026_2',
				'start'              => '2026-06-11 00:00 Asia/Dhaka',
				'end'                => '2026-06-16 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'massive_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Massive Sale:', 'wholesalex' ),
				'content_subheading' => __( 'Enjoy %s off on WholesaleX Pro.', 'wholesalex' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#6c6cff',
				'icon'               => WHOLESALEX_URL . 'assets/img/banners/wholesalex_discount.svg',
				'button_text'        => __( 'Upgrade Now →', 'wholesalex' ),
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wsx_dashboard_content_notice_final_hour_sale_2026_1',
				'start'              => '2026-06-21 00:00 Asia/Dhaka',
				'end'                => '2026-06-24 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final_hour_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Final Hour Sale:', 'wholesalex' ),
				'content_subheading' => __( 'Enjoy %s off on WholesaleX Pro.', 'wholesalex' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#6c6cff',
				'icon'               => WHOLESALEX_URL . 'assets/img/banners/wholesalex_logo.svg',
				'button_text'        => __( 'Upgrade Now →', 'wholesalex' ),
				'is_discount_logo'   => false,
			),
			array(
				'key'                => 'wsx_dashboard_content_notice_final_hour_sale_2026_2',
				'start'              => '2026-06-25 00:00 Asia/Dhaka',
				'end'                => '2026-06-27 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final_hour_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Final Hour Sale:', 'wholesalex' ),
				'content_subheading' => __( 'Enjoy %s off on WholesaleX Pro.', 'wholesalex' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#6c6cff',
				'icon'               => WHOLESALEX_URL . 'assets/img/banners/wholesalex_discount.svg',
				'button_text'        => __( 'Upgrade Now →', 'wholesalex' ),
				'is_discount_logo'   => true,
			),

		);

		$wsx_db_nonce = wp_create_nonce( 'wsx-dashboard-nonce' );

		foreach ( $content_notices as $key => $notice ) {
			$notice_key = isset( $notice['key'] ) ? $notice['key'] : $this->notice_version;
			if ( isset( $_GET['disable_wsx_notice'] ) && $notice_key === $_GET['disable_wsx_notice'] ) {
				continue;
			} else {
				$border_color = $notice['border_color'];

				$current_time = gmdate( 'U' );
				$notice_start = gmdate( 'U', strtotime( $notice['start'] ) );
				$notice_end   = gmdate( 'U', strtotime( $notice['end'] ) );
				if ( $current_time >= $notice_start && $current_time <= $notice_end && $notice['visibility'] ) {
					$notice_transient = Xpo::get_transient_without_cache( 'wsx_get_pro_notice_' . $notice_key );

					if ( 'off' !== $notice_transient ) {

						$query_args = array(
							'disable_wsx_notice' => $notice_key,
							'wsx_db_nonce'       => $wsx_db_nonce,
						);
						if ( isset( $notice['repeat_interval'] ) && $notice['repeat_interval'] ) {
							$query_args['wsx_interval'] = $notice['repeat_interval'];
						}

						$url = isset( $notice['url'] ) ? $notice['url'] : Xpo::generate_utm_link(
							array(
								'utmKey' => 'content_notice',
							)
						);

						?>

						<style id="wsx-notice-css" type="text/css">
							.wsx-content-notice-wrapper {
								border: 1px solid #c3c4c7;
								border-left: 3px solid #037fff;
								margin: 15px 0 !important;
								display: flex;
								align-items: center;
								background: #e8e8f0;
								width: 100%;
								padding: 10px 0;
								position: relative;
								box-sizing: border-box;
							}

							.wsx-content-notice-wrapper.notice {
								margin: 10px 0;
								width: calc(100% - 20px);
							}

							.wrap .wsx-content-notice-wrapper.notice {
								width: 100%;
							}

							.wsx-content-notice-icon {
								margin-left: 15px;
							}

							.wsx-content-notice-discout-icon {
								margin-left: 10px;
							}

							.wsx-content-notice-icon img {
								max-width: 42px;
								height: 70px;
							}

							.wsx-content-notice-discout-icon img {
								height: 70px;
								width: 70px;
							}

							.wsx-notice-content-wrapper {
								display: flex;
								flex-direction: column;
								gap: 8px;
								font-size: 14px;
								line-height: 20px;
								margin-left: 15px;
							}

							.wsx-content-notice-buttons {
								display: flex;
								align-items: center;
								gap: 15px;
							}

							.wsx-content-notice-btn {
								font-weight: 600;
								text-transform: uppercase !important;
								padding: 2px 10px !important;
								background-color: #6c6cff;
								border: none !important;
							}

							.wsx-content-discount_btn {
								background-color: #ffffff;
								text-decoration: none;
								border: 1px solid #6c6cff;
								padding: 5px 10px;
								border-radius: 5px;
								font-weight: 500;
								text-transform: uppercase;
								color: #6c6cff !important;
							}

							.wsx-content-notice-close {
								position: absolute;
								right: 2px;
								top: 5px;
								text-decoration: none;
								color: #b6b6b6;
								font-family: dashicons;
								font-size: 16px;
								line-height: 20px;
							}

							.wsx-content-notice-close-icon {
								font-size: 14px;
							}
						</style>
					<div class="wsx-content-notice-wrapper notice data_collection_notice" 
					style="border-left: 3px solid <?php echo esc_attr( $border_color ); ?>;"
					> 
						<?php
						if ( $notice['is_discount_logo'] ) {
							?>
								<div class="wsx-content-notice-discout-icon"> <img src="<?php echo esc_url( $notice['icon'] ); ?>"/>  </div>
							<?php
						} else {
							?>
								<div class="wsx-content-notice-icon"> <img src="<?php echo esc_url( $notice['icon'] ); ?>"/>  </div>
							<?php
						}
						?>
						
						<div class="wsx-notice-content-wrapper">
							<div class="">
								<strong><?php printf( esc_html( $notice['content_heading'] ) ); ?> </strong>
						<?php
						printf(
							wp_kses_post( $notice['content_subheading'] ),
							'<strong>' . esc_html( $notice['discount_content'] ) . '</strong>'
						);
						?>
							</div>
							<div class="wsx-content-notice-buttons">
							<?php if ( isset( $notice['is_discount_logo'] ) && $notice['is_discount_logo'] ) : ?>
									<a class="wsx-content-discount_btn" href="<?php echo esc_url( $url ); ?>" target="_blank">
										<?php echo esc_html( $notice['button_text'] ); ?>
									</a>
								<?php else : ?>
									<a class="wsx-content-notice-btn button button-primary" href="<?php echo esc_url( $url ); ?>" target="_blank" style="background-color: <?php echo ! empty( $notice['background_color'] ) ? esc_attr( $notice['background_color'] ) : '#6c6cff'; ?>;">
									<?php echo esc_html( $notice['button_text'] ); ?>
										
									</a>
								<?php endif; ?>
							</div>
						</div>
						<a href=
							<?php
							echo esc_url(
								add_query_arg(
									$query_args
								)
							);
							?>
						class="wsx-content-notice-close"><span class="wsx-content-notice-close-icon dashicons dashicons-dismiss"> </span></a>
					</div>
								<?php
					}
				}
			}
		}
	}


	/**
	 * Banner JS
	 *
	 * @return void
	 */
	public function wsx_banner_notice_js() {
		?>
		<script type="text/javascript">
			jQuery(function($) {
				'use strict';

				const storagePrefix = 'wsx_notice_countdown_';

				const formatCountdown = function(seconds) {
					const days = Math.floor(seconds / 86400);
					const hours = Math.floor((seconds % 86400) / 3600);
					const minutes = Math.floor((seconds % 3600) / 60);
					const secs = seconds % 60;

					return String(days).padStart(2, '0') + ':' + String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
				};

				const parseDurationToSeconds = function(duration) {
					if (typeof duration === 'number' && Number.isFinite(duration) && duration > 0) {
						return Math.floor(duration);
					}

					const durationString = String(duration || '').trim();
					if (/^\d+$/.test(durationString)) {
						return parseInt(durationString, 10);
					}

					return 0;
				};

				const nowInSeconds = function() {
					return Math.floor(Date.now() / 1000);
				};

				$('.wsx-notice-countdown').each(function() {
					const countdownElement = $(this);
					const noticeKey = String(countdownElement.data('noticeKey') || '');
					const duration = parseDurationToSeconds(countdownElement.data('duration'));

					if (!noticeKey || duration <= 0) {
						return;
					}

					const storageKey = storagePrefix + noticeKey;
					let endAt = 0;

					try {
						const storedDataRaw = window.localStorage.getItem(storageKey);
						if (storedDataRaw) {
							const storedData = JSON.parse(storedDataRaw);
							if (storedData && parseInt(storedData.duration, 10) === duration) {
								endAt = parseInt(storedData.endAt, 10) || 0;
							}
						}
					} catch (error) {
						endAt = 0;
					}

					const saveTimerState = function(nextEndAt) {
						try {
							window.localStorage.setItem(
								storageKey,
								JSON.stringify({
									endAt: nextEndAt,
									duration: duration,
								})
							);
						} catch (error) {
							// No-op.
						}
					};

					const resetTimer = function(currentTime) {
						endAt = currentTime + duration;
						saveTimerState(endAt);
					};

					const tick = function() {
						const currentTime = nowInSeconds();

						if (endAt <= currentTime) {
							resetTimer(currentTime);
						}

						const remaining = Math.max(endAt - currentTime, 0);
						countdownElement.text(formatCountdown(remaining));
					};

					if (endAt <= nowInSeconds()) {
						resetTimer(nowInSeconds());
					}

					tick();
					window.setInterval(tick, 1000);
				});
			});
		</script>
		<?php
	}


	/**
	 * The Durbin Html
	 *
	 * @return string | HTML
	 */
	public function wsx_dashboard_durbin_notice_callback() {
		$durbin_key = 'wsx_durbin_dc2x';

		if (
			isset( $_GET['wsx_durbin_key'] ) || // phpcs:ignore
			'off' === Xpo::get_transient_without_cache( 'wsx_durbin_notice_' . $durbin_key )
		) {
			return;
		}

		if ( ! $this->notice_js_css_applied ) {
			$this->notice_js_css_applied = true;
		}

		$wsx_db_nonce = wp_create_nonce( 'wsx-nonce' );

		?>
		<style>
				.wsx-consent-box {
					width: 656px;
					padding: 16px !important;
					border: 1px solid #070707;
					border-left-width: 4px;
					border-radius: 4px;
					background-color: #fff;
					position: relative;
					width: 100%;
					box-sizing: border-box;
				}
				.wsx-consent-content {
					display: flex;
					justify-content: flex-start;
					align-items: flex-end;
					gap: 26px;
				}
 
				.wsx-consent-text-first {
					font-size: 14px;
					font-weight: 600;
					color: #000000;
				}
				.wsx-consent-text-last {
					margin: 4px 0 0;
					font-size: 14px;
					color: #070707;
				}
 
				.wsx-consent-accept {
					background-color: #070707;
					color: #fff !important;
					border: none;
					padding: 6px 10px;
					border-radius: 4px;
					cursor: pointer;
					font-size: 12px;
					font-weight: 600;
					text-decoration: none;
				}
				.wsx-consent-accept:hover {
					background-color:rgb(38, 38, 38);
					color: #fff;
				}
			</style>
			<div class="wsx-consent-box wsx-notice-wrapper notice data_collection_notice">
			<div class="wsx-consent-content">
			<div class="wsx-consent-text">
			<div class="wsx-consent-text-first"><?php esc_html_e( 'Want to help make Wholesalex even more awesome?', 'wholesalex' ); ?></div>
			<div class="wsx-consent-text-last">
					<?php esc_html_e( 'Allow us to collect diagnostic data and usage information. see ', 'wholesalex' ); ?>
			<a href="https://www.wpxpo.com/data-collection-policy/" target="_blank" ><?php esc_html_e( 'what we collect.', 'wholesalex' ); ?></a>
			</div>
			</div>
			<a
					class="wsx-consent-accept"
					href=
					<?php
									echo esc_url(
										add_query_arg(
											array(
												'wsx_durbin_key' => $durbin_key,
												'wsx_get_durbin' => 'get',
												'wpnonce' => $wsx_db_nonce,
											)
										)
									);
					?>
									class="wsx-notice-close"
			><?php esc_html_e( 'Accept & Close', 'wholesalex' ); ?></a>
			</div>
			<a href=
				<?php
							echo esc_url(
								add_query_arg(
									array(
										'wsx_durbin_key' => $durbin_key,
										'wpnonce'        => $wsx_db_nonce,
									)
								)
							);
				?>
				class="wsx-notice-close"
				style="
					position: absolute;
					right: 2px;
					top: 5px;
					text-decoration: unset;
					color: #b6b6b6;
					font-family: dashicons;
					font-size: 16px;
					font-style: normal;
					font-weight: 400;
					line-height: 20px;
					"
			>
				<span 
				style="font-size: 14px;"
				class="wsx-notice-close-icon dashicons dashicons-dismiss"> </span></a>
			</div>
		<?php
	}
}
