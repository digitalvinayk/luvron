<?php
/**
 * WholesaleX Dynamic Rules - REST API
 *
 * Handles all REST API endpoints for dynamic rules:
 * CRUD operations, bulk actions, pagination, and AJAX data sub-actions.
 *
 * Route names MUST match the frontend JS paths exactly:
 *   - /dynamic_rule_action        (main CRUD + AJAX search)
 *   - /dynamic_rule_per_page_action (pagination GET/POST)
 *   - /dynamic_bulk_action         (bulk enable/disable/delete/export)
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic Rules REST API
 */
class Dynamic_Rules_Rest_Api {

	/**
	 * Data Provider instance.
	 *
	 * @var Dynamic_Rules_Data_Provider
	 */
	private $data_provider;

	/**
	 * Constructor.
	 *
	 * NOTE: Hook registration is done here. The orchestrator must NOT
	 * duplicate these add_action calls.
	 */
	public function __construct() {
		$this->data_provider = new Dynamic_Rules_Data_Provider();

		add_action( 'rest_api_init', array( $this, 'dynamic_rule_restapi_callback' ) );
		add_action( 'rest_api_init', array( $this, 'dynamic_rule_restapi_pagination_callback' ) );
		add_action( 'rest_api_init', array( $this, 'dynamic_rule_rest_api_bulk_action_callback' ) );
	}

	// ─── Route Registration ──────────────────────────────────────

	/**
	 * Register main CRUD + AJAX search route.
	 * Frontend path: /wholesalex/v1/dynamic_rule_action
	 *
	 * @return void
	 */
	public function dynamic_rule_restapi_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/dynamic_rule_action',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'dynamic_rule_restapi_action' ),
					'permission_callback' => array( $this, 'dynamic_rule_restapi_permission' ),
					'args'                => array(),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'dynamic_rule_restapi_action' ),
					'permission_callback' => array( $this, 'dynamic_rule_restapi_permission' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Register pagination route (GET to read, POST to save).
	 * Frontend path: /wholesalex/v1/dynamic_rule_per_page_action
	 *
	 * @return void
	 */
	public function dynamic_rule_restapi_pagination_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/dynamic_rule_per_page_action',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'dynamic_rules_per_page_get' ),
					'permission_callback' => array( $this, 'dynamic_rule_restapi_permission' ),
					'args'                => array(),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'dynamic_rules_per_page_post' ),
					'permission_callback' => array( $this, 'dynamic_rule_restapi_permission' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Register bulk action route.
	 * Frontend path: /wholesalex/v1/dynamic_bulk_action
	 *
	 * @return void
	 */
	public function dynamic_rule_rest_api_bulk_action_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/dynamic_bulk_action',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle_bulk_action' ),
					'permission_callback' => array( $this, 'dynamic_rule_restapi_permission' ),
					'args'                => array(),
				),
			)
		);
	}

	// ─── Permission ──────────────────────────────────────────────

	/**
	 * REST API Permission Callback.
	 *
	 * @return bool
	 */
	public function dynamic_rule_restapi_permission() {
		return current_user_can( 'manage_options' );
	}

	// ─── Main Handler: /dynamic_rule_action ──────────────────────

	/**
	 * Main REST API handler.
	 *
	 * Dispatches based on the `type` field in the POST body:
	 *   - type=get + ajax_action  → AJAX data search (MultiSelect)
	 *   - type=get (no ajax_action) → fetch all rules + field definitions
	 *   - type=post + delete=true → delete a rule
	 *   - type=post + check       → toggle rule status
	 *   - type=post + id + rule   → save/update a rule
	 *
	 * @param \WP_REST_Request $server Full details about the request.
	 * @return array|\WP_REST_Response
	 */
	public function dynamic_rule_restapi_action( $server ) {
		$post = $server->get_params();
		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		// ── AJAX data search (MultiSelect component) ─────────────
		// Frontend sends: type=get, ajax_action=users|products|…, query=…
		if ( 'get' === $type && isset( $post['ajax_action'] ) ) {
			return $this->handle_ajax_action( $post );
		}

		// ── Fetch all rules + field definitions ──────────────────
		// Frontend sends: type=get (no ajax_action)
		if ( 'get' === $type ) {
			$per_page = get_option( '_wholesalex_dynamic_rules_per_page', 10 );
			return array(
				'success'  => true,
				'data'     => array(
					'default' => Dynamic_Rules_Data_Provider::get_dynamic_rules_field(),
					'value'   => Dynamic_Rules::dynamic_rules_get(),
				),
				'per_page' => $per_page,
			);
		}

		// ── POST actions (save / delete / toggle) ────────────────
		if ( 'post' === $type ) {
			return $this->handle_post_action( $post );
		}

		return array(
			'success' => false,
			'data'    => array( 'message' => __( 'Invalid request type.', 'wholesalex' ) ),
		);
	}

	// ─── AJAX Data Search ────────────────────────────────────────

	/**
	 * Handle AJAX data sub-actions for MultiSelect dropdowns.
	 *
	 * Frontend sends: ajax_action (action type), query (search term),
	 * depends (e.g. zone_id for shipping methods, product_id for variations).
	 *
	 * Response uses `status` key (not `success`) – matches MultiSelect.js expectation.
	 *
	 * @param array $post Request parameters.
	 * @return array
	 */
	private function handle_ajax_action( $post ) {
		$ajax_action = sanitize_text_field( $post['ajax_action'] );
		$query       = isset( $post['query'] ) ? sanitize_text_field( $post['query'] ) : '';
		$depends     = isset( $post['depends'] ) ? $post['depends'] : '';

		$data = array();

		switch ( $ajax_action ) {
			case 'get_users':
				$data = $this->data_provider->get_users( $query );
				break;
			case 'get_products':
				$data = $this->data_provider->get_products( $query );
				break;
			case 'get_categories':
				$data = $this->data_provider->get_categories( $query );
				break;
			case 'get_brands':
				$data = $this->data_provider->get_brands( $query );
				break;
			case 'get_attributes':
				$data = $this->data_provider->get_attributes( $query );
				break;
			case 'get_variation_products':
				$data = $this->data_provider->get_variation_products( $query, $depends ? $depends : false );
				break;
			case 'productsWithVariation':
			case 'get_products_with_variations':
				$data = $this->data_provider->get_products_with_variations( $query );
				break;
			case 'get_payment_gateways':
				$data = $this->data_provider->get_payment_gateways();
				break;
			case 'tax_classes':
				$data = $this->data_provider->get_tax_classes();
				break;
			case 'get_roles':
				$data = $this->data_provider->get_roles();
				break;
			case 'get_shipping_zones':
				$data = $this->data_provider->get_shipping_zones();
				break;
			case 'get_shipping_methods':
				$data = $this->data_provider->get_shipping_methods( $depends );
				break;
			case 'get_shipping_country':
			case 'shipping_country':
				$data = $this->data_provider->get_shipping_country( $query );
				break;
			default:
				$data = apply_filters( 'wholesalex_dynamic_rules_rest_action', array(), $post );
				break;
		}

		return array(
			'status' => true,
			'data'   => $data,
		);
	}

	// ─── POST Action (Save / Delete / Toggle) ────────────────────

	/**
	 * Handle save, delete, or status-toggle for a single rule.
	 *
	 * Frontend sends:
	 *   - id: rule ID
	 *   - rule: JSON-encoded rule data
	 *   - delete: (optional) true to delete
	 *   - check: (optional) '_rule_status' to toggle
	 *
	 * @param array $post Request parameters.
	 * @return array
	 */
	private function handle_post_action( $post ) {
		$rule_id = isset( $post['id'] ) ? sanitize_text_field( $post['id'] ) : '';

		if ( empty( $rule_id ) ) {
			return array(
				'success' => false,
				'data'    => array( 'message' => __( 'Missing rule ID.', 'wholesalex' ) ),
			);
		}

		// HANDLE DELETE DYNAMIC RULES.
		if ( isset( $post['delete'] ) && $post['delete'] ) {

			$existing_rule = wholesalex()->get_dynamic_rules( $rule_id );
			if ( ! empty( $existing_rule ) ) {
				wholesalex()->set_dynamic_rules( $rule_id, $existing_rule, 'delete' );
			}

			return array(
				'success' => true,
				'data'    => array( 'message' => __( 'Rule deleted successfully.', 'wholesalex' ) ),
			);
		}

		// handle Save/Update Rules.
		$rule_json = isset( $post['rule'] ) ? $post['rule'] : '';
		if ( empty( $rule_json ) ) {
			return array(
				'success' => false,
				'data'    => array( 'message' => __( 'Missing rule data.', 'wholesalex' ) ),
			);
		}

		$data = json_decode( wp_unslash( $rule_json ), true );
		if ( ! is_array( $data ) ) {
			return array(
				'success' => false,
				'data'    => array( 'message' => __( 'Invalid rule data.', 'wholesalex' ) ),
			);
		}

		// Status toggle only – just flip _rule_status and save.
		if ( isset( $post['check'] ) && '_rule_status' === $post['check'] ) {
			$existing = wholesalex()->get_dynamic_rules( $rule_id );
			if ( ! empty( $existing ) ) {
				$existing['_rule_status'] = isset( $data['_rule_status'] ) ? $data['_rule_status'] : '';
				wholesalex()->set_dynamic_rules( $rule_id, $existing );
			}
			$status_label = ! empty( $data['_rule_status'] ) ? __( 'Rule activated successfully.', 'wholesalex' ) : __( 'Rule deactivated successfully.', 'wholesalex' );
			return array(
				'success' => true,
				'data'    => array( 'message' => $status_label ),
			);
		}

		// Full save.
		$data        = apply_filters( 'wholesalex_dynamic_rule_data_before_save', $data );
		$is_frontend = isset( $post['isFrontend'] ) ? (bool) $post['isFrontend'] : false;
		wholesalex()->set_dynamic_rules( $rule_id, $data, '', $is_frontend );
		do_action( 'wholesalex_dynamic_rule_data_after_save', $data );

		return array(
			'success' => true,
			'data'    => array( 'message' => __( 'Rule saved successfully.', 'wholesalex' ) ),
		);
	}

	// ─── Bulk Action: /dynamic_bulk_action ───

	/**
	 * Handle bulk enable/disable/delete/export.
	 *
	 * Frontend sends: { ruleIds: [...], action: 'enable'|'disable'|'delete'|'export' }
	 *
	 * @param \WP_REST_Request $server Full details about the request.
	 * @return array|\WP_REST_Response
	 */
	public function handle_bulk_action( $server ) {
		$post     = $server->get_params();
		$action   = isset( $post['action'] ) ? sanitize_text_field( $post['action'] ) : '';
		$rule_ids = isset( $post['ruleIds'] ) ? array_map( 'sanitize_text_field', $post['ruleIds'] ) : array();

		if ( empty( $rule_ids ) || ! in_array( $action, array( 'enable', 'disable', 'delete', 'export' ), true ) ) {
			return array(
				'status'  => false,
				'message' => __( 'Invalid data.', 'wholesalex' ),
			);
		}

		if ( 'export' === $action ) {
			$rules = array();
			foreach ( $rule_ids as $rid ) {
				$existing = wholesalex()->get_dynamic_rules( $rid );
				if ( $existing ) {
					$rules[] = $existing;
				}
			}
			return array(
				'status' => true,
				'data'   => $rules,
			);
		}

		foreach ( $rule_ids as $rid ) {
			if ( 'delete' === $action ) {
				$existing = wholesalex()->get_dynamic_rules( $rid );
				if ( ! empty( $existing ) ) {
					wholesalex()->set_dynamic_rules( $rid, $existing, 'delete' );
				}
			} else {
				$existing = wholesalex()->get_dynamic_rules( $rid );
				if ( $existing ) {
					$existing['_rule_status'] = ( 'enable' === $action ) ? 'yes' : 'no';
					wholesalex()->set_dynamic_rules( $rid, $existing );
				}
			}
		}

		return array(
			'status'  => true,
			'message' => __( 'Done', 'wholesalex' ),
		);
	}

	// ─── Pagination: /dynamic_rule_per_page_action ───────────────

	/**
	 * GET handler – return current rules_per_page setting.
	 *
	 * @param \WP_REST_Request $server Full details about the request.
	 * @return array
	 */
	public function dynamic_rules_per_page_get( $server ) {
		$per_page = get_option( '_wholesalex_dynamic_rules_per_page', 10 );
		return array(
			'success' => true,
			'data'    => array( 'rules_per_page' => (int) $per_page ),
		);
	}

	/**
	 * POST handler – save rules_per_page setting.
	 *
	 * @param \WP_REST_Request $server Full details about the request.
	 * @return array
	 */
	public function dynamic_rules_per_page_post( $server ) {
		$post = $server->get_params();
		if ( isset( $post['rules_per_page'] ) ) {
			update_option( '_wholesalex_dynamic_rules_per_page', absint( $post['rules_per_page'] ) );
			return array(
				'success' => true,
			);
		}
		return array(
			'success' => false,
			'data'    => array( 'message' => __( 'Missing rules_per_page.', 'wholesalex' ) ),
		);
	}
}
