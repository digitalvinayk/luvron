<?php
/**
 * Abstract WholesaleX Role Importer
 * Inspired By WooCommerce Core Product Import
 *
 * @package  WholesaleX
 * @version  1.2.9
 */

namespace WHOLESALEX;

use WC_Importer_Interface;
use Exception;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WHOLESALEX_Importer_Interface', false ) ) {
	include_once WHOLESALEX_PATH . 'includes/import/class-wholesalex-importer-interface.php';
}

/**
 * WHOLESALEX_Role_Importer Class.
 */
abstract class WHOLESALEX_Role_Importer implements WHOLESALEX_Importer_Interface {

	/**
	 * CSV file.
	 *
	 * @var string
	 */
	protected $file = '';

	/**
	 * The file position after the last read.
	 *
	 * @var int
	 */
	protected $file_position = 0;

	/**
	 * Importer parameters.
	 *
	 * @var array
	 */
	protected $params = array();

	/**
	 * Raw keys - CSV raw headers.
	 *
	 * @var array
	 */
	protected $raw_keys = array();

	/**
	 * Mapped keys - CSV headers.
	 *
	 * @var array
	 */
	protected $mapped_keys = array();

	/**
	 * Raw data.
	 *
	 * @var array
	 */
	protected $raw_data = array();

	/**
	 * Raw data.
	 *
	 * @var array
	 */
	protected $file_positions = array();

	/**
	 * Parsed data.
	 *
	 * @var array
	 */
	protected $parsed_data = array();

	/**
	 * Start time of current import.
	 *
	 * (default value: 0)
	 *
	 * @var int
	 */
	protected $start_time = 0;

	/**
	 * Get file raw headers.
	 *
	 * @return array
	 */
	public function get_raw_keys() {
		return $this->raw_keys;
	}

	/**
	 * Get file mapped headers.
	 *
	 * @return array
	 */
	public function get_mapped_keys() {
		return ! empty( $this->mapped_keys ) ? $this->mapped_keys : $this->raw_keys;
	}

	/**
	 * Get raw data.
	 *
	 * @return array
	 */
	public function get_raw_data() {
		return $this->raw_data;
	}

	/**
	 * Get parsed data.
	 *
	 * @return array
	 */
	public function get_parsed_data() {
		/**
		 * Filter wholesalex roles importer parsed data.
		 *
		 * @param array $parsed_data Parsed data.
		 * @param WHOLESALEX_Role_Importer $importer Importer instance.
		 */
		return apply_filters( 'wholesalex_role_importer_parsed_data', $this->parsed_data, $this );
	}

	/**
	 * Get importer parameters.
	 *
	 * @return array
	 */
	public function get_params() {
		return $this->params;
	}

	/**
	 * Get file pointer position from the last read.
	 *
	 * @return int
	 */
	public function get_file_position() {
		return $this->file_position;
	}

	/**
	 * Get file pointer position as a percentage of file size.
	 *
	 * @return int
	 */
	public function get_percent_complete() {
		$size = filesize( $this->file );
		if ( ! $size ) {
			return 0;
		}

		return absint( min( floor( ( $this->file_position / $size ) * 100 ), 100 ) );
	}

	/**
	 * Process a single item and save.
	 *
	 * @throws Exception If item cannot be processed.
	 * @param  array $data Raw CSV data.
	 * @return array|WP_Error
	 */
	protected function process_item( $data ) {
		try {
			do_action( 'wholesalex_role_import_before_process_item', $data );
			$data = apply_filters( 'wholesalex_role_import_process_item_data', $data );

			$existing_role = wholesalex()->get_roles( 'by_id', $data['id'] );
			$updating      = false;

			if ( ! empty( $existing_role ) ) {
				$updating = true;
				// Merge imported data with existing role data to preserve fields marked "Do not import".
				$role = $this->merge_role_data( $existing_role, $data );
			} else {
				$role = $data;
				// If role id is numeric, add a string prefix.
				if ( is_numeric( $role['id'] ) ) {
					$role_id_prefix = apply_filters( 'wholesalex_role_importer_role_id_prefix', 'wholesalex_', $data );
					$role['id']     = $role_id_prefix . $role['id'];
				}
			}

			// Validate role data before saving.
			$validation_error = $this->validate_role_data( $role, $updating );
			if ( is_wp_error( $validation_error ) ) {
				return $validation_error;
			}

			$role = apply_filters( 'wholesalex_role_import_pre_insert_role_data', $role, $data );

			// Role Inserted/Updated.
			wholesalex()->set_roles( $role['id'], $role );

			do_action( 'wholesalex_role_import_inserted_role', $role, $data );

			return array(
				'id'      => $role['id'],
				'updated' => $updating,
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'wholesalex_role_importer_error', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Merge existing role data with imported data.
	 * Only updates fields that are present in the imported data.
	 * Preserves existing values for critical fields when imported value is empty.
	 *
	 * @param array $existing_role Existing role data.
	 * @param array $imported_data Imported data from CSV.
	 * @return array Merged role data.
	 */
	protected function merge_role_data( $existing_role, $imported_data ) {
		$merged = $existing_role;

		foreach ( $imported_data as $key => $value ) {
			// For critical fields, only update if the imported value is not empty.
			if ( $this->is_critical_field( $key ) ) {
				if ( ! empty( $value ) || ( is_string( $value ) && strlen( trim( $value ) ) > 0 ) ) {
					$merged[ $key ] = $value;
				}
				// Otherwise, keep the existing value.
			} else {
				// For non-critical fields, update with the imported value.
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Check if a field is critical and should not be overwritten with empty values.
	 *
	 * @param string $field_key Field key to check.
	 * @return bool True if field is critical.
	 */
	protected function is_critical_field( $field_key ) {
		$critical_fields = array(
			'_role_title', // Role title is required to identify the role.
			'id',          // ID is required for role identification.
		);

		return in_array( $field_key, $critical_fields, true );
	}

	/**
	 * Validate role data before saving.
	 * Ensures that critical fields are present and valid.
	 *
	 * @param array $role     The role data to validate.
	 * @param bool  $updating Whether this is an update operation.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	protected function validate_role_data( $role, $updating = false ) {
		$role_id = isset( $role['id'] ) ? $role['id'] : '';

		// Validate role ID.
		if ( empty( $role_id ) ) {
			return new WP_Error(
				'wholesalex_role_importer_error',
				__( 'Role ID is missing or invalid.', 'wholesalex' ),
				array( 'id' => $role_id )
			);
		}

		// Validate role title.
		if ( empty( $role['_role_title'] ) || ( is_string( $role['_role_title'] ) && strlen( trim( $role['_role_title'] ) ) === 0 ) ) {
			return new WP_Error(
				'wholesalex_role_importer_error',
				__( 'Role Title is missing. This field is required.', 'wholesalex' ),
				array( 'id' => $role_id )
			);
		}

		return true;
	}




	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;
		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}
		return apply_filters( 'wholesalex_role_importer_memory_exceeded', $return );
	}

	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}
		return intval( $memory_limit ) * 1024 * 1024;
	}

	/**
	 * Time exceeded.
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		$finish = $this->start_time + apply_filters( 'wholesalex_role_importer_default_time_limit', 20 ); // 20 seconds
		$return = false;
		if ( time() >= $finish ) {
			$return = true;
		}
		return apply_filters( 'wholesalex_role_importer_time_exceeded', $return );
	}

	/**
	 * Explode CSV cell values using commas by default, and handling escaped
	 * separators.
	 *
	 * @since 1.2.9
	 * @param  string $value     Value to explode.
	 * @param  string $separator Separator separating each value. Defaults to comma.
	 * @return array
	 */
	protected function explode_values( $value, $separator = ',' ) {
		$value  = str_replace( '\\,', '::separator::', $value );
		$values = explode( $separator, $value );
		$values = array_map( array( $this, 'explode_values_formatter' ), $values );

		return $values;
	}

	/**
	 * Remove formatting and trim each value.
	 *
	 * @since  1.2.9
	 * @param  string $value Value to format.
	 * @return string
	 */
	protected function explode_values_formatter( $value ) {
		return trim( str_replace( '::separator::', ',', $value ) );
	}

	/**
	 * The exporter prepends a ' to escape fields that start with =, +, - or @.
	 * Remove the prepended ' character preceding those characters.
	 *
	 * @since 1.2.9
	 * @param  string $value A string that may or may not have been escaped with '.
	 * @return string
	 */
	protected function unescape_data( $value ) {
		$active_content_triggers = array( "'=", "'+", "'-", "'@" );

		if ( in_array( mb_substr( $value, 0, 2 ), $active_content_triggers, true ) ) {
			$value = mb_substr( $value, 1 );
		}

		return $value;
	}
}
