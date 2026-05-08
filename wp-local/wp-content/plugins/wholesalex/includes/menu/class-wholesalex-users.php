<?php
/**
 * WholesaleX Users
 *
 * @package WHOLESALEX
 * @since v.1.0.0
 */

namespace WHOLESALEX;

use WP_User_Query;

/**
 * WholesaleX Users Class
 */
class WHOLESALEX_Users {

	/**
	 * WholesaleX User Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_users_restapi' ) );
		add_action( 'rest_api_init', array( $this, 'register_users_filters_restapi' ) );
		add_action( 'admin_init', array( $this, 'initialize_default_user_filters' ) );
		add_action( 'rest_api_init', array( $this, 'get_initial_user_filters_data' ) );
	}

	/**
	 * Get Initial User Filters Data
	 *
	 * @return void
	 */
	public function get_initial_user_filters_data() {
		register_rest_route(
			'wholesalex/v1',
			'/get-users-filters',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_user_filters' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get User Filters
	 *
	 * @return array
	 */
	public function get_user_filters() {
		$filters  = get_option( 'wholesalex_settings', array() );
		$response = isset( $filters['_settings_show_users_filters'] ) ? $filters['_settings_show_users_filters'] : array();
		return new \WP_REST_Response( array( 'filters' => $response ), 200 );
	}

	/**
	 * Initialize Default User Filters
	 *
	 * @return void
	 */
	public function initialize_default_user_filters() {
		$default_filters = array(
			'_settings_show_users_filters' => array(
				'users_filter_user_id'           => true,
				'users_filter_username'          => false,
				'users_filter_full_name'         => true,
				'users_filter_account_type'      => true,
				'users_filter_email'             => true,
				'users_filter_registration_date' => false,
				'users_filter_wholesalex_role'   => true,
				'users_filter_wholesalex_status' => true,
				'users_filter_transaction'       => true,
				'users_filter_wallet_balance'    => true,
				'users_filter_action'            => true,
			),
		);

		// Get existing filters.
		$existing_filters = get_option( 'wholesalex_settings', false );

		// If no filters exist, initialize them.
		if ( ! $existing_filters ) {
			add_option( 'wholesalex_settings', $default_filters );
		} elseif ( ! isset( $existing_filters['_settings_show_users_filters'] ) ) {
				$existing_filters['_settings_show_users_filters'] = $default_filters['_settings_show_users_filters'];
				update_option( 'wholesalex_settings', $existing_filters );
		}
	}

	/**
	 * Register Users Filters RestAPI
	 *
	 * @return void
	 */
	public function register_users_filters_restapi() {
		register_rest_route(
			'wholesalex/v1',
			'/save-users-filters/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_user_filters' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' ); // Ensure only admins can use this.
					},
				),
			)
		);
	}

	/**
	 * Save User Filters
	 *
	 * @param object $request Request.
	 * @return array
	 */
	public function save_user_filters( $request ) {
		// Get the filters from the request. We expect a 'filters' key to be passed.
		$filters = $request->get_param( 'filters' );

		if ( empty( $filters ) || ! is_array( $filters ) ) {
			return new \WP_REST_Response( array( 'message' => __( 'Invalid data', 'wholesalex' ) ), 400 );
		}

		// Retrieve the existing settings.
		$existing_settings = get_option( 'wholesalex_settings', array() );

		if ( ! isset( $existing_settings['_settings_show_users_filters'] ) ) {
			$existing_settings['_settings_show_users_filters'] = array();
		}

		// Merge the new filters into the existing ones.
		$existing_settings['_settings_show_users_filters'] = array_merge(
			$existing_settings['_settings_show_users_filters'],
			$filters
		);

		// Save updated settings to the database.
		update_option( 'wholesalex_settings', $existing_settings );

		return new \WP_REST_Response( array( 'message' => __( 'Filters saved successfully', 'wholesalex' ) ), 200 );
	}

	/**
	 * Users Submenu Page Content
	 *
	 * @return void
	 */
	public static function users_page_content() {
		wp_enqueue_script( 'whx_users' );
		wp_enqueue_style( 'whx_users' );

		$heading_data = array();

		// Prepare as heading data.
		foreach ( self::get_wholesalex_users_columns() as $key => $value ) {
			$data               = array();
			$data['all_select'] = '';
			$data['name']       = $key;
			$data['title']      = $value;
			if ( 'action' === $key ) {
				$data['type'] = '3dot';
			} elseif ( 'wallet_balance' === $key || 'account_type' === $key ) {
				$data['type'] = 'html';
			} else {
				$data['type'] = 'text';
			}

			$heading_data[ $key ] = $data;
		}

		$heading_data['user_id']['status']           = 'yes';
		$heading_data['username']['status']          = 'yes';
		$heading_data['full_name']['status']         = 'yes';
		$heading_data['email']['status']             = 'yes';
		$heading_data['registration_date']['status'] = 'yes';
		$heading_data['wholesalex_role']['status']   = 'yes';
		$heading_data['wholesalex_status']['status'] = 'yes';
		$heading_data['action']['status']            = 'yes';

		wp_localize_script(
			'whx_users',
			'whx_users',
			array(
				'heading'            => $heading_data,
				'user_per_page'      => 10,
				'bulk_actions'       => self::get_wholesalex_users_bulk_actions(),
				'statuses'           => wholesalex()->insert_into_array(
					array( '' => __( 'Select Status', 'wholesalex' ) ),
					self::get_user_statuses(),
					0
				),
				'exportable_columns' => ImportExport::exportable_user_columns(),
				'roles'              => self::get_role_options(),
				'i18n'               => array(
					// 'whx_users_users'                   => __( 'Users', 'wholesalex' ),
					// 'whx_users_edit'                    => __( 'Edit', 'wholesalex' ),
					// 'whx_users_active'                  => __( 'Active', 'wholesalex' ),
					// 'whx_users_reject'                  => __( 'Reject', 'wholesalex' ),
					// 'whx_users_pending'                 => __( 'Pending', 'wholesalex' ),
					// 'whx_users_delete'                  => __( 'Delete', 'wholesalex' ),
					// 'whx_users_selected_users'          => __( 'Selected Users', 'wholesalex' ),
					// 'whx_users_apply'                   => __( 'Apply', 'wholesalex' ),
					// 'whx_users_import'                  => __( 'Import', 'wholesalex' ),
					// 'whx_users_export'                  => __( 'Export', 'wholesalex' ),
					// 'whx_users_columns'                 => __( 'Columns', 'wholesalex' ),
					// 'whx_users_no_users_found'          => __( 'No Users Found!', 'wholesalex' ),
					// 'whx_users_showing'                 => __( 'Showing', 'wholesalex' ),
					// 'whx_users_pages'                   => __( 'Pages', 'wholesalex' ),
					// 'whx_users_of'                      => __( 'of', 'wholesalex' ),
					// 'whx_users_please_select_valid_csv_file' => __( 'Please Select a valid csv file to process import!', 'wholesalex' ),
					// 'whx_users_please_wait_to_complete_existing_import_request' => __( 'Please Wait to complete existing import request!', 'wholesalex' ),
					// 'whx_users_error_occured'           => __( 'Error Occured!', 'wholesalex' ),
					// 'whx_users_import_successful'       => __( 'Import Sucessful', 'wholesalex' ),
					// 'whx_users_users_updated'           => __( 'Users Updated', 'wholesalex' ),
					// 'whx_users_users_inserted'          => __( 'Users Inserted', 'wholesalex' ),
					// 'whx_users_users_skipped'           => __( 'Users Skipped', 'wholesalex' ),
					// 'whx_users_download'                => __( 'Download', 'wholesalex' ),
					// 'whx_users_log_for_more_info'       => __( 'Log For More Info', 'wholesalex' ),
					// 'whx_users_close'                   => __( 'Close', 'wholesalex' ),
					// 'whx_users_username'                => __( 'Username', 'wholesalex' ),
					// 'whx_users_email'                   => __( 'Email', 'wholesalex' ),
					// 'whx_users_upload_csv'              => __( 'Upload CSV', 'wholesalex' ),
					// 'whx_users_you_can_upload_only_csv_file' => __( 'You can upload only csv file format', 'wholesalex' ),
					// 'whx_users_update_existing_users'   => __( 'Update Existing Users', 'wholesalex' ),
					// 'whx_users_update_existing_users_message' => __( 'Selecting "Update Existing Users" will only update existing users. No new user will be added.', 'wholesalex' ),
					// 'whx_users_find_existing_user_by'   => __( 'Find Existing Users By:', 'wholesalex' ),
					// 'whx_users_option_to_detect_user'   => __( "Option to detect user from the uploaded CSV's email or username field.", 'wholesalex' ),
					// 'whx_users_process_per_iteration'   => __( 'Process Per Iteration', 'wholesalex' ),
					// 'whx_users_low_process_ppi'         => __( "Low process per iteration (PPI) increases the import's accuracy and success rate. A (PPI) higher than your server's maximum execution time might fail the import.", 'wholesalex' ),
					// 'whx_users_import_users'            => __( 'Import Users', 'wholesalex' ),
					// 'whx_users_select_fields_to_export' => __( 'Select Fields to Export', 'wholesalex' ),
					// 'whx_users_csv_comma_warning'       => __( 'Warning: If any of the fields contain a comma (,), it might break the CSV file. Ensure the selected column value contains no comma(,).', 'wholesalex' ),
					// 'whx_users_download_csv'            => __( 'Download CSV', 'wholesalex' ),
					// 'whx_users_export_users'            => __( 'Export Users', 'wholesalex' ),
				),
			)
		);

		?>
		<div id="wholeslex_users_root"></div>
		<?php
	}

	/**
	 * Get Wholesale Users
	 *
	 * @return array
	 */
	public static function get_role_options() {
		$roles        = get_option( '_wholesalex_roles', array() );
		$roles_option = array( '' => __( '--Select Role--', 'wholesalex' ) );
		foreach ( $roles as $role ) {
			if ( ! is_array( $role ) ) {
				continue;
			}
			$role_id    = isset( $role['id'] ) ? $role['id'] : '';
			$role_title = isset( $role['_role_title'] ) ? $role['_role_title'] : '';
			if ( '' === $role_id || 'wholesalex_guest' === $role_id ) {
				continue;
			}
			$roles_option[ $role_id ] = $role_title;
		}
		return $roles_option;
	}


	/**
	 * Register Users RestAPI Scripts
	 *
	 * @return void
	 */
	public function register_users_restapi() {
		register_rest_route(
			'wholesalex/v1',
			'/users/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'users_restapi_callback' ),
					'permission_callback' => function () {
						return current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Users RestAPI Callback
	 *
	 * @param object $server Server.
	 * @return void
	 */
	public function users_restapi_callback( $server ) {
		$post = $server->get_params();

		// Nonce validation.
		if ( ! ( isset( $post['nonce'] ) && wp_verify_nonce( sanitize_key( $post['nonce'] ), 'wholesalex-registration' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request. Please refresh the page and try again.', 'wholesalex' ) ) );
			return;
		}

		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		$response = array(
			'status' => false,
			'data'   => array(),
		);

		switch ( $type ) {
			case 'get':
				$page           = isset( $post['page'] ) ? sanitize_text_field( $post['page'] ) : 1;
				$items_per_page = isset( $post['itemsPerPage'] ) ? (int) $post['itemsPerPage'] : 10;
				$user_status    = isset( $post['status'] ) ? sanitize_text_field( $post['status'] ) : '';
				$user_role      = isset( $post['role'] ) ? sanitize_text_field( $post['role'] ) : '';
				$search_query   = isset( $post['search'] ) ? sanitize_text_field( $post['search'] ) : '';
					$sort_field     = isset( $post['sortField'] ) ? sanitize_text_field( $post['sortField'] ) : 'ID';
					$sort_direction = isset( $post['sortDirection'] ) ? sanitize_text_field( $post['sortDirection'] ) : 'desc';

				$response['status'] = true;
					$response['data']   = $this->get_wholesale_users( $items_per_page, $page, $user_status, $search_query, $user_role, $sort_field, $sort_direction );
				break;

			case 'update_status':
				$action = isset( $post['user_action'] ) ? sanitize_text_field( $post['user_action'] ) : '';
				$id     = isset( $post['id'] ) ? sanitize_text_field( $post['id'] ) : '';

				// Validate action and user ID.
				if ( empty( $action ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid Action!', 'wholesalex' ) ) );
					return;
				}
				if ( empty( $id ) || ! get_userdata( $id ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid User ID!', 'wholesalex' ) ) );
					return;
				}

				// Perform the action.
				$this->handle_user_action( $action, $id );

				$response['status'] = true;
				$response['data']   = ( 'delete' === $action ) ? __( 'Successfully Deleted', 'wholesalex' ) : __( 'Successfully Updated', 'wholesalex' );
				break;

			case 'bulk_action':
				$action = isset( $post['user_action'] ) ? sanitize_text_field( $post['user_action'] ) : '';
				$ids    = isset( $post['ids'] ) ? wholesalex()->sanitize( $post['ids'] ) : array();

				// Validate action.
				if ( empty( $action ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid Action!', 'wholesalex' ) ) );
					return;
				}

				// Validate user IDs.
				if ( empty( $ids ) || ! is_array( $ids ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid User IDs!', 'wholesalex' ) ) );
					return;
				}

				foreach ( $ids as $id ) {
					if ( ! get_userdata( $id ) ) {
						wp_send_json_error( array( 'message' => __( 'One or more User IDs are invalid.', 'wholesalex' ) ) );
						return;
					}
				}

				// Perform bulk actions.
				$this->bulk_actions( $action, $ids );

				$response['status'] = true;
				if ( 'delete' === $action ) {
					$response['data'] = __( 'Successfully Deleted', 'wholesalex' );
				} elseif ( wholesalex()->start_with( $action, 'change_role_to_' ) ) {
					$response['data'] = __( 'Role Successfully Changed.', 'wholesalex' );
				} else {
					$response['data'] = __( 'Successfully Updated', 'wholesalex' );
				}
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid request type.', 'wholesalex' ) ) );
				return;
		}

		wp_send_json( $response );
	}

	/**
	 * Get User Statuses
	 *
	 * @return array
	 */
	public static function get_user_statuses() {
		$statuses = array(
			'active'  => 'Active',
			'pending' => 'Pending',
			'reject'  => 'Reject',
			'decline' => 'Decline',
		);

		$statuses = apply_filters( 'wholesalex_users_statuses', $statuses );

		return $statuses;
	}

	/**
	 * Get Bulk User Actions
	 *
	 * @return array
	 */
	public static function get_wholesalex_users_bulk_actions() {

		$actions = array(
			'delete'     => 'Delete Users',
			'export'     => 'Export',
			'export_all' => 'Export All',
			'pending'    => 'Set Status to Pending',
			'active'     => 'Set Status to Active',
			'reject'     => 'Set Status to Reject',
		);
		$actions = apply_filters( 'wholesalex_users_bulk_actions', $actions );

		$options_groups = array(
			'action' => array(
				'label'   => 'Status',
				'options' => $actions,
			),
		);

		$roles        = get_option( '_wholesalex_roles', array() );
		$roles_option = array();
		foreach ( $roles as $role ) {
			if ( ! is_array( $role ) ) {
				continue;
			}
			$role_id    = isset( $role['id'] ) ? $role['id'] : '';
			$role_title = isset( $role['_role_title'] ) ? $role['_role_title'] : $role_id;
			if ( '' === $role_id || 'wholesalex_guest' === $role_id ) {
				continue;
			}
			$roles_option[ 'change_role_to_' . $role_id ] = $role_title;
		}

		$options_groups['roles_action'] = array(
			'label'   => __( 'Change Role To ', 'wholesalex' ),
			'options' => $roles_option,
		);

		return $options_groups;
	}

	/**
	 * Get Users Columns
	 *
	 * @return array
	 */
	public static function get_wholesalex_users_columns() {
		$columns = array(
			'user_id'           => __( 'ID', 'wholesalex' ),
			'username'          => __( 'Username', 'wholesalex' ),
			'full_name'         => __( 'Full Name', 'wholesalex' ),
			'email'             => __( 'Email', 'wholesalex' ),
			'registration_date' => __( 'Date', 'wholesalex' ),
			'wholesalex_role'   => __( 'Role', 'wholesalex' ),
			'wholesalex_status' => __( 'Status', 'wholesalex' ),
			'transaction'       => __( 'Transaction', 'wholesalex' ),
		);

		$columns = apply_filters( 'wholesalex_users_columns', $columns );

		$columns = wholesalex()->insert_into_array( $columns, array( 'action' => __( 'Action', 'wholesalex' ) ) );

		return $columns;
	}

	/**
	 * Get Wholesale Users
	 *
	 * @param integer $user_per_page User Per Page.
	 * @param integer $page Page.
	 * @param string  $status Account Status.
	 * @param string  $search_query Search Query.
	 * @param string  $role User Role.
	 * @param string  $sort_field Sort field.
	 * @param string  $sort_direction Sort direction.
	 * @return array
	 */
	public function get_wholesale_users( $user_per_page = -1, $page = 1, $status = '', $search_query = '', $role = '', $sort_field = 'ID', $sort_direction = 'desc' ) {
		$normalized_sort_field = strtolower( (string) $sort_field );
		$orderby_map           = array(
			'id'                => 'ID',
			'user_id'           => 'ID',
			'full_name'         => 'display_name',
			'email'             => 'email',
			'registration_date' => 'registered',
		);
		$orderby              = isset( $orderby_map[ $normalized_sort_field ] ) ? $orderby_map[ $normalized_sort_field ] : 'ID';
		$order                = ( 'asc' === strtolower( (string) $sort_direction ) ) ? 'ASC' : 'DESC';

		$user_fields = array( 'ID', 'user_login', 'display_name', 'user_email', 'user_registered' );
		$meta_query  = array(
			'relation' => 'OR',
			array(
				'key'     => '__wholesalex_status',
				'value'   => '',
				'compare' => '!=',
			),
			array(
				'key'     => '__wholesalex_role',
				'value'   => '',
				'compare' => '!=',
			),
		);

		if ( '' !== $status && '' !== $role ) {
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => '__wholesalex_status',
					'value'   => $status,
					'compare' => '=',
				),
				array(
					'key'     => '__wholesalex_role',
					'value'   => $role,
					'compare' => '=',
				),
			);
		}

		if ( '' !== $status && '' === $role ) {
			$meta_query = array(
				array(
					'key'     => '__wholesalex_status',
					'value'   => $status,
					'compare' => '=',
				),
			);
		}
		if ( '' !== $role && '' === $status ) {
			$meta_query = array(
				array(
					'key'     => '__wholesalex_role',
					'value'   => $role,
					'compare' => '=',
				),
			);
		}

		$args = array(
			'meta_query'  => $meta_query,
			'orderby'     => $orderby,
			'order'       => $order,
			'number'      => $user_per_page,
			'paged'       => $page,
			'fields'      => 'all',
			'count_total' => true,
			'search'      => '*' . $search_query . '*',
		);

		$user_search = new WP_User_Query( $args );

		$users = (array) $user_search->get_results();

		$total_users = $user_search->get_total();

		$column_value = array();

		foreach ( $users as $key => $user ) {
			$user_id            = $user->ID;
			$wholesalex_role_id = get_user_meta( $user_id, '__wholesalex_role', true );

			$user_data       = array();
			$user_data['ID'] = $user_id;

			foreach ( $this->get_wholesalex_users_columns() as $column_id => $column_name ) {
				switch ( $column_id ) {
					case 'user_id':
						$user_data[ $column_id ] = $user->ID;
						break;
					case 'username':
						$user_data[ $column_id ] = $user->user_login;
						break;
					case 'full_name':
						$user_data[ $column_id ] = $user->display_name;
						break;
					case 'email':
						$user_data[ $column_id ] = $user->user_email;
						break;
					case 'registration_date':
						$user_data[ $column_id ] = $user->user_registered;
						break;
					case 'wholesalex_role':
						$__user_role             = get_user_meta( $user_id, '__wholesalex_registration_role', true );
						$wholesalex_role_id      = ( ! empty( $wholesalex_role_id ) ? $wholesalex_role_id : $__user_role );
						$user_data[ $column_id ] = wholesalex()->get_role_name_by_role_id( $wholesalex_role_id );
						break;
					case 'wholesalex_status':
						$user_data[ $column_id ] = wholesalex()->get_user_status( $user_id );
						break;

					default:
						$user_data[ $column_id ] = apply_filters( 'wholesalex_users_column_value', $column_id, (array) $user, $column_name );
						break;
				}
			}

			$user_data['edit_profile'] = get_edit_user_link( $user_id );

			$column_value[] = $user_data;

		}

		return array(
			'users'       => $column_value,
			'total_users' => $total_users,
		);
	}


	/**
	 * Users Bulk Actions
	 *
	 * @param string $action Action.
	 * @param array  $ids User IDs.
	 * @return void
	 */
	public function bulk_actions( $action, $ids ) {
		switch ( $action ) {
			case 'active':
			case 'pending':
			case 'reject':
			case 'delete':
				if ( is_array( $ids ) ) {
					foreach ( $ids as $id ) {
						$this->handle_user_action( $action, $id );
					}
				}
				break;
			default:
				// code...
				break;
		}

		if ( wholesalex()->start_with( $action, 'change_role_to_' ) ) {
			$role = str_replace( 'change_role_to_', '', $action );
			if ( wp_roles()->is_role( $role ) ) {
				if ( is_array( $ids ) ) {
					foreach ( $ids as $id ) {
						$__user_role = get_user_meta( $id, '__wholesalex_role', true );
						if ( empty( $__user_role ) ) {
							$__user_role = get_user_meta( $id, '__wholesalex_registration_role', true );
						}
						wholesalex()->change_role( $id, $role, $__user_role );
					}
				}
			}
		}
	}

	/**
	 * Handle User Action
	 *
	 * @param string $action Action.
	 * @param int    $user_id User ID.
	 * @return void
	 */
	public function handle_user_action( $action, $user_id ) {

		switch ( $action ) {
			case 'active':
			case 'pending':
			case 'reject':
				$old_status = get_user_meta( $user_id, '__wholesalex_status', true );

				if ( $old_status !== $action ) {
					// proceed.
					update_user_meta( $user_id, '__wholesalex_status', $action );
					do_action( 'wholesalex_set_status_' . $action, $user_id, $old_status );
				}

				$__user_role = get_user_meta( $user_id, '__wholesalex_role', true );
				if ( empty( $__user_role ) ) {
					$__registration_role = get_user_meta( $user_id, '__wholesalex_registration_role', true );
					if ( ! empty( $__registration_role ) ) {
						wholesalex()->change_role( $user_id, $__registration_role );
					}
				}

				break;
			case 'delete':
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( $user_id );
				break;

			default:
				// code...
				break;
		}
	}
}
