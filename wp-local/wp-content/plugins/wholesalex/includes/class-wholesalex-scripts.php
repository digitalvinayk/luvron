<?php
/**
 * WholesaleX Scripts
 *
 * @package wholesalex
 * @since 1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WholesaleX Scripts Class
 */
class Scripts {

	/**
	 * Read a wp-scripts generated .asset.php file.
	 *
	 * @param string $asset_path Absolute path to the .asset.php file.
	 * @return array{dependencies: array, version: string}
	 */
	private static function read_asset_file( $asset_path ) {
		$default = array(
			'dependencies' => array(),
			'version'      => WHOLESALEX_VER,
		);

		if ( ! is_string( $asset_path ) || '' === $asset_path || ! file_exists( $asset_path ) ) {
			return $default;
		}

		$asset = require $asset_path;
		if ( ! is_array( $asset ) ) {
			return $default;
		}

		return array(
			'dependencies' => isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
			'version'      => isset( $asset['version'] ) && is_string( $asset['version'] ) ? $asset['version'] : WHOLESALEX_VER,
		);
	}

	/**
	 * Contains all wholesalex pages slug
	 *
	 * @var array
	 */
	public $wholesalex_pages = array(
		'wholesalex-settings'      => 'wholesalex_settings',
		'wholesalex-users'         => 'wholesalex_header',
		'wholesalex-addons'        => 'wholesalex_header',
		'wholesalex_role'          => 'wholesalex_roles',
		'wholesalex-email'         => 'wholesalex_header',
		'wholesalex_dynamic_rules' => 'wholesalex_dynamic_rules',
		'wholesalex-registration'  => 'wholesalex_form_builder',
		'wsx_conversation'         => 'wholesalex_header',
		'wholesalex'               => 'wholesalex_overview',
		'wholesalex-setup-wizard'  => 'wholesalex_wizard',
	);
	/**
	 * Register all scripts
	 */
	public static function register_backend_scripts() {
		$register_scripts = apply_filters(
			'wholesalex_register_backend_scripts',
			array(
				'wholesalex_category'     => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_cat.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_cat.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex_overview'     => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_overview.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_overview.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex_product'      => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_product.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_product.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex_all_products' => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_all_products.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_all_products.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex_profile'      => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_profile.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_profile.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex-builder'      => array(
					'src'       => WHOLESALEX_URL . 'assets/js/wholesalex_wallet.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/wholesalex_wallet.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex_dashboard_widget' => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_dashboard_widget.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_dashboard_widget.asset.php',
					'deps'      => array( 'react', 'react-dom' ),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'whx_migration_tools' => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_migration_tools.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_migration_tools.asset.php',
					'deps'      => array( 'react', 'react-dom' ),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
			)
		);
		foreach ( $register_scripts as $handle => $args ) {
			$asset = array(
				'dependencies' => array(),
				'version'      => isset( $args['ver'] ) ? $args['ver'] : WHOLESALEX_VER,
			);
			if ( isset( $args['asset'] ) ) {
				$asset = self::read_asset_file( $args['asset'] );
			}

			$deps = isset( $args['deps'] ) && is_array( $args['deps'] ) ? $args['deps'] : array();
			$deps = array_values( array_unique( array_merge( $asset['dependencies'], $deps ) ) );

			$ver = $asset['version'];
			wp_register_script( $handle, $args['src'], $deps, $ver, $args['in_footer'] );

			$css_src_candidates = array(
				preg_replace( '/\\.js$/', '.css', $args['src'] ),
				preg_replace( '/\\.js$/', '.css', str_replace( 'assets/js/', 'assets/css/', $args['src'] ) ),
			);

			$css_src  = '';
			$css_path = '';
			foreach ( $css_src_candidates as $candidate_src ) {
				$candidate_path = str_replace( WHOLESALEX_URL, WHOLESALEX_PATH, $candidate_src );
				if ( is_string( $candidate_path ) && file_exists( $candidate_path ) ) {
					$css_src  = $candidate_src;
					$css_path = $candidate_path;
					break;
				}
			}

			if ( '' !== $css_src ) {
				wp_register_style( $handle, $css_src, array(), $ver );
				$rtl_css_path = preg_replace( '/\\.css$/', '-rtl.css', $css_path );
				if ( is_string( $rtl_css_path ) && file_exists( $rtl_css_path ) ) {
					wp_style_add_data( $handle, 'rtl', 'replace' );
				}
			}

			wp_set_script_translations( $handle, 'wholesalex', WHOLESALEX_PATH . 'languages' );
		}

		// Register a virtual 'wholesalex' handle (no JS file) so that
		// wp_localize_script( 'wholesalex', ... ) can attach the global
		// configuration object used by every backend React component.
		wp_register_script( 'wholesalex', false, array( 'jquery', 'wp-i18n' ), WHOLESALEX_VER, true );
	}
	/**
	 * Register all scripts
	 */
	public static function register_frontend_scripts() {
		$register_scripts = apply_filters(
			'wholesalex_register_frontend_scripts',
			array(
				'wholesalex_category'     => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_cat.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_cat.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex_overview'     => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_overview.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_overview.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex_product'      => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_product.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_product.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'whx_integration' => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_integration.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_integration.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex_profile'      => array(
					'src'       => WHOLESALEX_URL . 'assets/js/whx_profile.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/whx_profile.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex-builder'      => array(
					'src'       => WHOLESALEX_URL . 'assets/js/wholesalex_wallet.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/wholesalex_wallet.asset.php',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex'              => array(
					'src'       => WHOLESALEX_URL . 'assets/js/wholesalex-public.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/wholesalex-public.asset.php',
					'deps'      => array( 'jquery', 'wp-i18n' ),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
				'wholesalex_price_table'  => array(
					'src'       => WHOLESALEX_URL . 'assets/js/wholesalex-price-table.js',
					'asset'     => WHOLESALEX_PATH . 'assets/js/wholesalex-price-table.asset.php',
					'deps'      => array( 'jquery', 'wp-i18n' ),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => true,
				),
			)
		);
		foreach ( $register_scripts as $handle => $args ) {
			$asset = array(
				'dependencies' => array(),
				'version'      => isset( $args['ver'] ) ? $args['ver'] : WHOLESALEX_VER,
			);
			if ( isset( $args['asset'] ) ) {
				$asset = self::read_asset_file( $args['asset'] );
			}

			$deps = isset( $args['deps'] ) && is_array( $args['deps'] ) ? $args['deps'] : array();
			$deps = array_values( array_unique( array_merge( $asset['dependencies'], $deps ) ) );

			$ver = $asset['version'];
			wp_register_script( $handle, $args['src'], $deps, $ver, $args['in_footer'] );

			$css_src_candidates = array(
				preg_replace( '/\\.js$/', '.css', $args['src'] ),
				preg_replace( '/\\.js$/', '.css', str_replace( 'assets/js/', 'assets/css/', $args['src'] ) ),
			);

			$css_src  = '';
			$css_path = '';
			foreach ( $css_src_candidates as $candidate_src ) {
				$candidate_path = str_replace( WHOLESALEX_URL, WHOLESALEX_PATH, $candidate_src );
				if ( is_string( $candidate_path ) && file_exists( $candidate_path ) ) {
					$css_src  = $candidate_src;
					$css_path = $candidate_path;
					break;
				}
			}

			if ( '' !== $css_src ) {
				wp_register_style( $handle, $css_src, array(), $ver );
				$rtl_css_path = preg_replace( '/\\.css$/', '-rtl.css', $css_path );
				if ( is_string( $rtl_css_path ) && file_exists( $rtl_css_path ) ) {
					wp_style_add_data( $handle, 'rtl', 'replace' );
				}
			}

			wp_set_script_translations( $handle, 'wholesalex', WHOLESALEX_PATH . 'languages/' );
		}
	}

	/**
	 * Register All Styles
	 *
	 * @return void
	 */
	public static function register_styles() {
		$register_scripts = apply_filters(
			'wholesalex_register_styles',
			array(
				'wholesalex'        => array(
					'src'       => WHOLESALEX_URL . 'assets/css/wholesalex-admin.css',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => false,
				),
				'wholesalex_public' => array(
					'src'       => WHOLESALEX_URL . 'assets/css/wholesalex-public.css',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => false,
				),

			)
		);
		foreach ( $register_scripts as $handle => $args ) {
			wp_register_style( $handle, $args['src'], $args['deps'], $args['ver'], $args['in_footer'] );
		}
	}
	/**
	 * Register Frontend Styles
	 *
	 * @return void
	 */
	public static function register_fronend_style() {
		$register_scripts = apply_filters(
			'wholesalex_register_frontend_styles',
			array(
				'wholesalex' => array(
					'src'       => WHOLESALEX_URL . 'assets/css/wholesalex-public.css',
					'deps'      => array(),
					'ver'       => WHOLESALEX_VER,
					'in_footer' => false,
				),
			)
		);
		foreach ( $register_scripts as $handle => $args ) {
			wp_register_style( $handle, $args['src'], $args['deps'], $args['ver'], $args['in_footer'] );
		}
	}

	/**
	 * Register Backend Style
	 *
	 * @return void
	 */
	public static function register_backend_style() {
		$styles = array(
			'wholesalex' => array(
				'src'       => WHOLESALEX_URL . 'assets/css/wholesalex-admin.css',
				'deps'      => array(),
				'ver'       => WHOLESALEX_VER,
				'in_footer' => false,
			),
		);

		$dataviews_css_path = WHOLESALEX_PATH . 'assets/css/vendor/dataviews/style.css';
		if ( file_exists( $dataviews_css_path ) ) {
			$styles['wholesalex_dataviews'] = array(
				'src'       => WHOLESALEX_URL . 'assets/css/vendor/dataviews/style.css',
				'deps'      => array( 'wp-components' ),
				'ver'       => WHOLESALEX_VER,
				'in_footer' => false,
			);
		}

		$register_scripts = apply_filters( 'wholesalex_register_backend_styles', $styles );
		foreach ( $register_scripts as $handle => $args ) {
			wp_register_style( $handle, $args['src'], $args['deps'], $args['ver'], $args['in_footer'] );
			// Support RTL variant for vendored DataViews stylesheet.
			if ( 'wholesalex_dataviews' === $handle ) {
				$css_path     = str_replace( WHOLESALEX_URL, WHOLESALEX_PATH, $args['src'] );
				$rtl_css_path = preg_replace( '/\\.css$/', '-rtl.css', $css_path );
				if ( is_string( $rtl_css_path ) && file_exists( $rtl_css_path ) ) {
					wp_style_add_data( $handle, 'rtl', 'replace' );
				}
			}
		}
	}
}
