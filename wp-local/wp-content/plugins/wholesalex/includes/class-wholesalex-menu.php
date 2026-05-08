<?php
/**
 * WholesaleX Menu
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

/**
 * WholesaleX Menu Class.
 */
class WHOLESALEX_Menu {

	/**
	 * Menu Constructor
	 */
	public function __construct() {
		add_filter( 'plugin_row_meta', array( $this, 'plugin_settings_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . WHOLESALEX_BASE, array( $this, 'plugin_action_links_callback' ) );
	}

	/**
	 * Settings Pro Update Link
	 *
	 * @param ARRAY $links Plugin Action Links.
	 * @since v.1.0.0
	 * @return ARRAY
	 */
	public function plugin_action_links_callback( $links ) {

		$offer_config = array(
			array(
				'start'  => '2026-05-07 00:00 Asia/Dhaka',
				'end'    => '2026-05-21 23:59 Asia/Dhaka',
				'text'   => __(
					'Flash Sale - Up to 50% OFF',
					'wholesalex'
				),
				'utmKey' => 'plugin_meta',
			),
			array(
				'start'  => '2026-05-22 00:00 Asia/Dhaka',
				'end'    => '2026-06-01 23:59 Asia/Dhaka',
				'text'   => __(
					'Surprise Sale - Up to 55% OFF',
					'wholesalex'
				),
				'utmKey' => 'plugin_meta',
			),
			array(
				'start'  => '2026-06-02 00:00 Asia/Dhaka',
				'end'    => '2026-06-20 23:59 Asia/Dhaka',
				'text'   => __(
					'Massive Sale - Up to 55% OFF',
					'wholesalex'
				),
				'utmKey' => 'plugin_meta',
			),
			array(
				'start'  => '2026-06-21 00:00 Asia/Dhaka',
				'end'    => '2026-06-30 23:59 Asia/Dhaka',
				'text'   => __(
					'Final Hour Sale - Up to 55% OFF',
					'wholesalex'
				),
				'utmKey' => 'plugin_meta',
			),
		);

		$upgrade_link = array();
		$setting_link = array();
		if ( ! defined( 'WHOLESALEX_PRO_VER' ) || Xpo::is_lc_expired() ) {
			if ( Xpo::is_lc_expired() ) {
				$text = esc_html__( 'Renew License', 'wholesalex' );
				$url  = 'https://account.wpxpo.com/checkout/?edd_license_key=' . Xpo::get_lc_key() . '&renew=1';
			} else {

				$text = esc_html__( 'Go Pro', 'wholesalex' );
				$url  = Xpo::generate_utm_link(
					array(
						'utmKey' => 'plugin_meta',
					)
				);

				foreach ( $offer_config as $offer ) {
					$current_time = gmdate( 'U' );
					$notice_start = gmdate( 'U', strtotime( $offer['start'] ) );
					$notice_end   = gmdate( 'U', strtotime( $offer['end'] ) );
					if ( $current_time >= $notice_start && $current_time <= $notice_end ) {
						$url  = Xpo::generate_utm_link(
							array(
								'utmKey' => $offer['utmKey'],
							)
						);
						$text = $offer['text'];
						break;
					}
				}
			}

			$upgrade_link = array(
				'wholesalex_pro' => '<a href="' . esc_url( $url ) . '" target="_blank" style="color: #e83838;font-weight: bold;">' . $text . '</a>',
			);
		}
		$setting_link['wholesalex_settings'] = '<a href="' . esc_url( admin_url( 'admin.php?page=wholesalex#/settings' ) ) . '">' . esc_html__( 'Settings', 'wholesalex' ) . '</a>';
		return array_merge( $setting_link, $links, $upgrade_link );
	}

	/**
	 * Plugin Page Menu Add
	 *
	 * @param ARRAY  $links Plugin Action Links.
	 * @param STRING $file Plugin File.
	 * @since v.1.0.0
	 * @return ARRAY
	 */
	public function plugin_settings_meta( $links, $file ) {
		if ( strpos( $file, 'wholesalex.php' ) !== false ) {
			$new_links = array(
				'wholesalex_docs'    => '<a href="https://getwholesalex.com/documentation/?utm_source=wholesalex_plugin&utm_medium=support&utm_campaign=wholesalex-DB" target="_blank">' . esc_html__( 'Docs', 'wholesalex' ) . '</a>',
				'wholesalex_support' => '<a href="' . esc_url( 'https://getwholesalex.com/contact/?utm_source=wholesalex_plugin&utm_medium=support&utm_campaign=wholesalex-DB' ) . '" target="_blank">' . esc_html__( 'Support', 'wholesalex' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}
}
