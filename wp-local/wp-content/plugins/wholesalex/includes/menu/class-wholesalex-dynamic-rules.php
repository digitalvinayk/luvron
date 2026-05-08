<?php
/**
 * WholesaleX Dynamic Rules - Facade
 *
 * Thin wrapper that loads the refactored dynamic-rules module and delegates
 * all functionality to \WHOLESALEX\Dynamic_Rules.
 *
 * Kept at the original path so existing require_once and class references
 * (WHOLESALEX_Dynamic_Rules) continue to work.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Facade class - preserves the original WHOLESALEX_Dynamic_Rules name so that
 * external code (\WHOLESALEX\WHOLESALEX_Dynamic_Rules::$cu_total_spent, etc.)
 * continues to work without changes.
 */
class WHOLESALEX_Dynamic_Rules {

	/**
	 * Static properties kept here for backward-compat. The orchestrator syncs
	 * these after it sets its own copies in get_valid_dynamic_rules().
	 */
	public static $cu_order_counts           = 0;
	public static $cu_total_spent            = 0;
	public static $total_cart_counts         = '';
	public static $total_unique_item_on_cart = '';

	/**
	 * Whether module dependencies have been loaded.
	 *
	 * @var bool
	 */
	private static $dependencies_loaded = false;

	/**
	 * Orchestrator instance.
	 *
	 * @var Dynamic_Rules
	 */
	private $orchestrator;

	/**
	 * REST API instance.
	 *
	 * @var Dynamic_Rules_Rest_Api
	 */
	private $rest_api;

	/**
	 * Constructor - load dependencies once, then instantiate the orchestrator and REST API.
	 */
	public function __construct() {
		self::load_dependencies();
		$this->orchestrator = new Dynamic_Rules();
		$this->rest_api = new Dynamic_Rules_Rest_Api();
	}

	/**
	 * Load all refactored module files exactly once.
	 *
	 * Kept inside the class to avoid file-scope side effects and ensure the
	 * namespace declaration is unambiguously the first statement in this file.
	 *
	 * @return void
	 */
	private static function load_dependencies() {
		if ( self::$dependencies_loaded ) {
			return;
		}
		self::$dependencies_loaded = true;

		$dr_dir = WHOLESALEX_PATH . 'includes/dynamic-rules/';

		// Infrastructure.
		require_once $dr_dir . 'class-dynamic-rules-data-provider.php';
		require_once $dr_dir . 'class-dynamic-rules-condition-engine.php';
		require_once $dr_dir . 'class-dynamic-rules-rest-api.php';

		// Rule handlers.
		$rules_dir = $dr_dir . 'rules/';
		require_once $rules_dir . 'class-rule-tax.php';
		require_once $rules_dir . 'class-rule-shipping.php';
		require_once $rules_dir . 'class-rule-payment-gateway.php';
		require_once $rules_dir . 'class-rule-buy-x-get-one.php';
		require_once $rules_dir . 'class-rule-cart-discount.php';
		require_once $rules_dir . 'class-rule-payment-discount.php';
		require_once $rules_dir . 'class-rule-min-order-qty.php';
		// Orchestrator.
		require_once $dr_dir . 'class-dynamic-rules.php';
	}

	// -- Static API methods referenced externally -----------------

	/**
	 * Used by class-wholesalex-overview.php
	 *
	 * @return array
	 */
	public static function get_dynamic_rules_field() {
		self::load_dependencies();
		return Dynamic_Rules_Data_Provider::get_dynamic_rules_field();
	}

	/**
	 * Used by class-wholesalex-overview.php and REST API
	 *
	 * @return array
	 */
	public static function dynamic_rules_get() {
		self::load_dependencies();
		return Dynamic_Rules::dynamic_rules_get();
	}
}
