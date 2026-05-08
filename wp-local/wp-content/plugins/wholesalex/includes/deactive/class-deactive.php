<?php //phpcs:ignore
/**
 * Plugin Deactivation Handler.
 *
 * @since
 */

namespace WHOLESALEX;

use WHOLESALEX\DurbinClient;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation feedback and reporting.
 */
class Deactive {

	private $plugin_slug = 'wholesalex';

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $pagenow;
		if ( 'plugins.php' === $pagenow ) {
			add_action( 'admin_footer', array( $this, 'get_source_data_callback' ) );
		}
		add_action( 'wp_ajax_wsx_deactive_plugin', array( $this, 'send_plugin_data' ) );
	}

	/**
	 * Send plugin deactivation data to remote server.
	 *
	 * @param string|null $type Optional. Unused for now.
	 * @return void
	 */
	public function send_plugin_data() {
		DurbinClient::send( DurbinClient::DEACTIVATE_ACTION );
	}

	/**
	 * Output deactivation modal markup, CSS, and JS.
	 *
	 * @return void
	 */
	public function get_source_data_callback() {
		$this->deactive_container_css();
		$this->deactive_container_js();
		$this->deactive_html_container();
	}

	/**
	 * Get deactivation reasons and field settings.
	 *
	 * @return array[] List of deactivation options.
	 */
	public function get_deactive_settings() {
		return array(
			array(
				'id'    => 'not-working',
				'input' => false,
				'text'  => __( 'The plugin isn’t working properly.', 'wholesalex' ),
			),
			array(
				'id'    => 'limited-features',
				'input' => false,
				'text'  => __( 'Limited features on the free version.', 'wholesalex' ),
			),
			array(
				'id'          => 'better-plugin',
				'input'       => true,
				'text'        => __( 'I found a better plugin.', 'wholesalex' ),
				'placeholder' => __( 'Please share which plugin.', 'wholesalex' ),
			),
			array(
				'id'    => 'temporary-deactivation',
				'input' => false,
				'text'  => __( "It's a temporary deactivation.", 'wholesalex' ),
			),
			array(
				'id'          => 'other',
				'input'       => true,
				'text'        => __( 'Other.', 'wholesalex' ),
				'placeholder' => __( 'Please share the reason.', 'wholesalex' ),
			),
		);
	}

	/**
	 * Output HTML for the deactivation modal.
	 *
	 * @return void
	 */
	public function deactive_html_container() {
		?>
		<div class="wsx-modal" id="wsx-deactive-modal">
			<div class="wsx-modal-wrap">
			
				<div class="wsx-modal-header">
					<h2><?php esc_html_e( 'Quick Feedback', 'wholesalex' ); ?></h2>
					<button class="wsx-modal-cancel"><span class="dashicons dashicons-no-alt"></span></button>
				</div>

				<div class="wsx-modal-body">
					<h3><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating WholesaleX:', 'wholesalex' ); ?></h3>
					<ul class="wsx-modal-input">
						<?php foreach ( $this->get_deactive_settings() as $key => $setting ) { ?>
							<li>
								<label>
									<input type="radio" <?php echo 0 == $key ? 'checked="checked"' : ''; ?> id="<?php echo esc_attr( $setting['id'] ); ?>" name="<?php echo esc_attr( $this->plugin_slug ); ?>" value="<?php echo esc_attr( $setting['text'] ); ?>">
									<div class="wsx-reason-text"><?php echo esc_html( $setting['text'] ); ?></div>
									<?php if ( isset( $setting['input'] ) && $setting['input'] ) { ?>
										<textarea placeholder="<?php echo esc_attr( $setting['placeholder'] ); ?>" class="wsx-reason-input <?php echo $key == 0 ? 'wsx-active' : ''; ?> <?php echo esc_html( $setting['id'] ); ?>"></textarea>
									<?php } ?>
								</label>
							</li>
						<?php } ?>
					</ul>
				</div>

				<div class="wsx-modal-footer">
					<a class="wsx-modal-submit wsx-btn wsx-btn-primary" href="#"><?php esc_html_e( 'Submit & Deactivate', 'wholesalex' ); ?><span class="dashicons dashicons-update rotate"></span></a>
					<a class="wsx-modal-deactive" href="#"><?php esc_html_e( 'Skip & Deactivate', 'wholesalex' ); ?></a>
				</div>
				
			</div>
		</div>
		<?php
	}

	/**
	 * Output inline CSS for the modal.
	 *
	 * @return void
	 */
	public function deactive_container_css() {
		?>
		<style type="text/css">
			.wsx-modal {
				position: fixed;
				z-index: 99999;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				background: rgba(0,0,0,0.5);
				display: none;
				box-sizing: border-box;
				overflow: scroll;
			}
			.wsx-modal * {
				box-sizing: border-box;
			}
			.wsx-modal.modal-active {
				display: block;
			}
			.wsx-modal-wrap {
				max-width: 870px;
				width: 100%;
				position: relative;
				margin: 10% auto;
				background: #fff;
			}
			.wsx-reason-input{
				display: none;
			}
			.wsx-reason-input.wsx-active{
				display: block;
			}
			.rotate{
				animation: rotate 1.5s linear infinite; 
			}
			@keyframes rotate{
				to{ transform: rotate(360deg); }
			}
			.wsx-popup-rotate{
				animation: popupRotate 1s linear infinite; 
			}
			@keyframes popupRotate{
				to{ transform: rotate(360deg); }
			}
			#wsx-deactive-modal {
				background: rgb(0 0 0 / 85%);
				overflow: hidden;
			}
			#wsx-deactive-modal .wsx-modal-wrap {
				max-width: 570px;
				border-radius: 5px;
				margin: 5% auto;
				overflow: hidden
			}
			#wsx-deactive-modal .wsx-modal-header {
				padding: 17px 30px;
				border-bottom: 1px solid #ececec;
				display: flex;
				align-items: center;
				background: #f5f5f5;
			}
			#wsx-deactive-modal .wsx-modal-header .wsx-modal-cancel {
				padding: 0;
				border-radius: 100px;
				border: 1px solid #b9b9b9;
				background: none;
				color: #b9b9b9;
				cursor: pointer;
				transition: 400ms;
			}
			#wsx-deactive-modal .wsx-modal-header .wsx-modal-cancel:focus {
				color: red;
				border: 1px solid red;
				outline: 0;
			}
			#wsx-deactive-modal .wsx-modal-header .wsx-modal-cancel:hover {
				color: red;
				border: 1px solid red;
			}
			#wsx-deactive-modal .wsx-modal-header h2 {
				margin: 0;
				padding: 0;
				flex: 1;
				line-height: 1;
				font-size: 20px;
				text-transform: uppercase;
				color: #8e8d8d;
			}
			#wsx-deactive-modal .wsx-modal-body {
				padding: 25px 30px;
			}
			#wsx-deactive-modal .wsx-modal-body h3{
				padding: 0;
				margin: 0;
				line-height: 1.4;
				font-size: 15px;
			}
			#wsx-deactive-modal .wsx-modal-body ul {
				margin: 25px 0 10px;
			}
			#wsx-deactive-modal .wsx-modal-body ul li {
				display: flex;
				margin-bottom: 10px;
				color: #807d7d;
			}
			#wsx-deactive-modal .wsx-modal-body ul li:last-child {
				margin-bottom: 0;
			}
			#wsx-deactive-modal .wsx-modal-body ul li label {
				align-items: center;
				width: 100%;
			}
			#wsx-deactive-modal .wsx-modal-body ul li label input {
				padding: 0 !important;
				margin: 0;
				display: inline-block;
			}
			#wsx-deactive-modal .wsx-modal-body ul li label textarea {
				margin-top: 8px;
				width: 100% !important;
			}
			#wsx-deactive-modal .wsx-modal-body ul li label .wsx-reason-text {
				margin-left: 8px;
				display: inline-block;
			}
			#wsx-deactive-modal .wsx-modal-footer {
				padding: 0 30px 30px 30px;
				display: flex;
				align-items: center;
			}
			#wsx-deactive-modal .wsx-modal-footer .wsx-modal-submit {
				display: flex;
				align-items: center;
				padding: 12px 22px;
				border-radius: 3px;
				background: #6C6CFF;
				color: #fff;
				font-size: 16px;
				font-weight: 600;
				text-decoration: none;
			}
			#wsx-deactive-modal .wsx-modal-footer .wsx-modal-submit span {
				margin-left: 4px;
				display: none;
			}
			#wsx-deactive-modal .wsx-modal-footer .wsx-modal-submit.loading span {
				display: block;
			}
			#wsx-deactive-modal .wsx-modal-footer .wsx-modal-deactive {
				margin-left: auto;
				color: #c5c5c5;
				text-decoration: none;
			}
			.wpxpo-btn-tracking-notice {
				display: flex;
				align-items: center;
				flex-wrap: wrap;
				padding: 5px 0;
			}
			.wpxpo-btn-tracking-notice .wpxpo-btn-tracking {
				margin: 0 5px;
				text-decoration: none;
			}
		</style>
		<?php
	}

	/**
	 * Output inline JavaScript for the modal logic.
	 *
	 * @return void
	 */
	public function deactive_container_js() {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				'use strict';

				// Modal Radio Input Click Action
				$('.wsx-modal-input input[type=radio]').on( 'change', function(e) {
					$('.wsx-reason-input').removeClass('wsx-active');
					$('.wsx-modal-input').find( '.'+$(this).attr('id') ).addClass('wsx-active');
				});

				// Modal Cancel Click Action
				$( document ).on( 'click', '.wsx-modal-cancel', function(e) {
					$( '#wsx-deactive-modal' ).removeClass( 'modal-active' );
				});
				
				$(document).on('click', function(event) {
					const $popup = $('#wsx-deactive-modal');
					const $modalWrap = $popup.find('.wsx-modal-wrap');

					if ( !$modalWrap.is(event.target) && $modalWrap.has(event.target).length === 0 && $popup.hasClass('modal-active')) {
						$popup.removeClass('modal-active');
					}
				});

				// Deactivate Button Click Action
				$( document ).on( 'click', '#deactivate-wholesalex', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$( '#wsx-deactive-modal' ).addClass( 'modal-active' );
					$( '.wsx-modal-deactive' ).attr( 'href', $(this).attr('href') );
					$( '.wsx-modal-submit' ).attr( 'href', $(this).attr('href') );
				});

				// Submit to Remote Server
				$( document ).on( 'click', '.wsx-modal-submit', function(e) {
					e.preventDefault();
					
					$(this).addClass('loading');
					const url = $(this).attr('href')

					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: { 
							action: 'wsx_deactive_plugin',
							cause_id: $('#wsx-deactive-modal input[type=radio]:checked').attr('id'),
							cause_title: $('#wsx-deactive-modal .wsx-modal-input input[type=radio]:checked').val(),
							cause_details: $('#wsx-deactive-modal .wsx-reason-input.wsx-active').val()
						},
						success: function (data) {
							$( '#wsx-deactive-modal' ).removeClass( 'modal-active' );
							window.location.href = url;
						},
						error: function(xhr) {
							console.log( 'Error occured. Please try again' + xhr.statusText + xhr.responseText );
						},
					});

				});

			});
		</script>
		<?php
	}
}
