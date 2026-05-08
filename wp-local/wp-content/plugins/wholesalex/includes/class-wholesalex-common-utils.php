<?php
/**
 * WholesaleX Initialization. Initialize All Files And Dependencies
 *
 * @link              https://www.wpxpo.com/
 * @since             1.0.0
 * @package           WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;


/**
 * WholesaleX_Initialization Class
 */
class WholesaleX_CommonUtils {
		/**
		 * Get Empty Form
		 *
		 * @return array
		 */
	public static function get_empty_form() {
		$default_form = array(
			'registrationFormHeader' =>
			array(
				'isShowFormTitle'   => true,
				'isHideDescription' => false,
				'title'             => 'Register',
				'description'       => "Don't have an account? Sign up now!",
				'styles'            =>
				array(
					'title'       =>
						array(
							'color'     => '#343A46',
							'size'      => 24,
							'weight'    => 500,
							'transform' => '',
							'padding'   => '',
						),
					'description' =>
						array(
							'color'     => '#343A46',
							'size'      => 14,
							'weight'    => 400,
							'transform' => '',
							'padding'   => '',
						),
				),
			),
			'loginFormHeader'        =>
			array(
				'isShowFormTitle'   => true,
				'isHideDescription' => false,
				'title'             => 'Login',
				'description'       => 'Sign In to Your Account',
				'styles'            =>
				array(
					'title'       =>
					array(
						'color'     => '#343A46',
						'size'      => 24,
						'weight'    => 500,
						'transform' => '',
						'padding'   => '',
					),
					'description' =>
					array(
						'color'     => '#343A46',
						'size'      => 14,
						'weight'    => 400,
						'transform' => '',
						'padding'   => '',
					),
				),
			),
			'settings'               =>
			array(
				'inputVariation'  => 'variation_1',
				'isShowLoginForm' => false,
			),
			'fieldsName'             =>
			array(),
			'loginFields'            =>
			array(
				0 =>
				array(
					'id'            => 'login_row_1',
					'type'          => 'row',
					'columns'       =>
					array(
						0 =>
							array(
								'type'           => 'text',
								'label'          => __( 'Username or email address', 'wholesalex' ),
								'name'           => 'username',
								'isLabelHide'    => false,
								'placeholder'    => 'Username or Email',
								'columnPosition' => 'left',
								'parent'         => 'login_row_1',
								'isRequired'     => true,
							),
					),
					'isMultiColumn' => false,
				),
				1 =>
				array(
					'id'            => 'login_row_2',
					'type'          => 'row',
					'columns'       =>
					array(
						0 =>
							array(
								'type'           => 'password',
								'label'          => __( 'Password', 'wholesalex' ),
								'name'           => 'password',
								'isLabelHide'    => false,
								'placeholder'    => 'Password',
								'columnPosition' => 'left',
								'parent'         => 'login_row_2',
								'isRequired'     => true,
							),
					),
					'isMultiColumn' => false,
				),
				2 =>
				array(
					'id'            => 'login_row_3',
					'type'          => 'row',
					'columns'       =>
					array(
						0 =>
						array(
							'type'           => 'checkbox',
							'label'          => '',
							'name'           => 'rememberme',
							'isLabelHide'    => true,
							'columnPosition' => 'left',
							'option'         =>
							array(
								0 =>
									array(
										'name'  => __( 'Remember me', 'wholesalex' ),
										'value' => 'rememberme',
									),
							),
							'parent'         => 'row_3438998',
							'excludeRoles'   =>
							array(),
						),
					),
					'isMultiColumn' => false,
				),
			),
			'registrationFields'     => self::get_default_registration_form_fields(),
			'registrationFormButton' =>
			array(
				'title' => 'Register',
			),
			'loginFormButton'        =>
			array(
				'title' => 'Log in',
			),
			'style'                  =>
			array(
				'color'       =>
				array(
					'field'     =>
					array(
						'signIn' =>
						array(
							'normal'  =>
							array(
								'label'       => '#343A46',
								'text'        => '#343A46',
								'background'  => '#FFF',
								'border'      => '#E9E9F0',
								'placeholder' => '#6C6E77',
							),
							'active'  =>
							array(
								'label'       => '#343A46',
								'text'        => '#343A46',
								'background'  => '#FFF',
								'border'      => '#6C6CFF',
								'placeholder' => '#6C6E77',
							),
							'warning' =>
							array(
								'label'       => '#343A46',
								'text'        => '#FF6C6C',
								'background'  => '#FFF',
								'border'      => '#FF6C6C',
								'placeholder' => '#6C6E77',
							),
						),
						'signUp' =>
						array(
							'normal'  =>
							array(
								'label'       => '#343A46',
								'text'        => '#343A46',
								'background'  => '#FFF',
								'border'      => '#E9E9F0',
								'placeholder' => '#6C6E77',
							),
							'active'  =>
							array(
								'label'       => '#343A46',
								'text'        => '#343A46',
								'background'  => '#FFF',
								'border'      => '#6C6CFF',
								'placeholder' => '#6C6E77',
							),
							'warning' =>
							array(
								'label'       => '#343A46',
								'text'        => '#FF6C6C',
								'background'  => '#FFF',
								'border'      => '#FF6C6C',
								'placeholder' => '#6C6E77',
							),
						),
					),
					'button'    =>
					array(
						'signIn' =>
						array(
							'normal' =>
							array(
								'text'       => '#fff',
								'background' => '#6C6CFF',
								'border'     => '',
							),
							'hover'  =>
							array(
								'text'       => '#fff',
								'background' => '#1a1ac3',
								'border'     => '',
							),
						),
						'signUp' =>
						array(
							'normal' =>
							array(
								'text'       => '#fff',
								'background' => '#6C6CFF',
								'border'     => '',
							),
							'hover'  =>
							array(
								'text'       => '#fff',
								'background' => '#1a1ac3',
								'border'     => '',
							),
						),
					),
					'container' =>
					array(
						'main'   =>
						array(
							'background' => '#FFF',
							'border'     => '#E9E9F0',
						),
						'signIn' =>
						array(
							'background' => '#FFF',
							'border'     => '',
						),
						'signUp' =>
						array(
							'background' => '#FFF',
							'border'     => '',
						),
					),
				),
				'typography'  =>
				array(
					'field'  =>
					array(
						'label' =>
						array(
							'size'      => 14,
							'weight'    => 500,
							'transform' => '',
						),
						'input' =>
						array(
							'size'      => 14,
							'weight'    => 400,
							'transform' => '',
						),
					),
					'button' =>
					array(
						'size'      => 14,
						'weight'    => 500,
						'transform' => '',
					),
				),
				'sizeSpacing' =>
				array(
					'input'     =>
					array(
						'width'        => 395,
						'border'       => 1,
						'borderRadius' => 2,
						'padding'      => 16,
					),
					'button'    =>
					array(
						'width'        => 50,
						'border'       => 0,
						'borderRadius' => 2,
						'padding'      => 13,
						'align'        => 'left',
					),
					'container' =>
					array(
						'main'   =>
						array(
							'width'        => '1200',
							'border'       => 1,
							'borderRadius' => 16,
							'padding'      => 0,
							'align'        => '',
							'separator'    => 1,
						),
						'signIn' =>
						array(
							'width'        => '',
							'border'       => 0,
							'borderRadius' => 16,
							'padding'      => 54,
							'align'        => '',
							'separator'    => '',
						),
						'signUp' =>
						array(
							'width'        => '',
							'border'       => 0,
							'borderRadius' => 16,
							'padding'      => 54,
							'align'        => '',
							'separator'    => '',
						),
					),
				),
			),
		);
		return $default_form;
	}

	/**
	 * Get Default Registration Form Fields
	 *
	 * @return array
	 */
	public static function get_new_form_builder_data() {
		$form_data = '';

		$old_form = get_option( '__wholesalex_registration_form' );

		$new_form = get_option( 'wholesalex_registration_form' );

		$default_form = self::get_empty_form();
		if ( ! $new_form && $old_form ) {
			$old_form = json_decode( $old_form, true );
			foreach ( $old_form as $field ) {
				$field['columnPosition'] = 'left';
				$field['parent']         = wp_unique_id( 'whx_form' );
				$field['label']          = $field['title'];
				$field['status']         = true;
				$field['conditions']     = array(
					'status'   => 'show',
					'relation' => 'all',
					'tiers'    => array(
						array(
							'_id'       => '1',
							'condition' => '',
							'value'     => '',
							'field'     => '',
							'src'       => 'registration_form',
						),
					),
				);
				if ( ! isset( $field['option'] ) ) {
					$field['option'] = array(
						array(
							'name'  => 'Select Option',
							'value' => '',
						),
					);
				}
				unset( $field['title'] );
				$field['migratedFromOldBuilder']      = true;
				$default_form['registrationFields'][] =
				array(
					'id'            => $field['parent'],
					'type'          => 'row',
					'columns'       => array( $field ),
					'isMultiColumn' => false,
				);

			}

			$form_data = $default_form;

		} else {
			$form_data = json_decode( $new_form, true );
		}

		return is_array( $form_data ) ? $form_data : $default_form;
	}

	/**
	 * Get Default Registration Form Fields
	 */
	public static function get_default_registration_form_fields() {
		$is_woo_username     = get_option( 'woocommerce_registration_generate_username' );
		$registration_fields = array(
			...( isset( $is_woo_username ) && 'no' === $is_woo_username ? array(
				array(
					'id'            => 'regi_3',
					'type'          => 'row',
					'columns'       => array(
						array(
							'status'         => true,
							'type'           => 'text',
							'label'          => 'Username',
							'name'           => 'user_login',
							'isLabelHide'    => false,
							'placeholder'    => '',
							'columnPosition' => 'left',
							'parent'         => 'regi_3',
							'required'       => true,
							'conditions'     => array(
								'status'   => 'show',
								'relation' => 'all',
								'tiers'    => array(
									array(
										'_id'       => strval( time() ),
										'condition' => '',
										'field'     => '',
										'value'     => '',
										'src'       => 'registration_form',
									),
								),
							),
						),
					),
					'isMultiColumn' => false,
				),
			) : array() ),
			array(
				'id'            => 'regi_1',
				'type'          => 'row',
				'columns'       => array(
					array(
						'status'         => true,
						'type'           => 'email',
						'label'          => 'Email',
						'name'           => 'user_email',
						'isLabelHide'    => false,
						'placeholder'    => '',
						'columnPosition' => 'left',
						'parent'         => 'regi_1',
						'required'       => true,
						'conditions'     => array(
							'status'   => 'show',
							'relation' => 'all',
							'tiers'    => array(
								array(
									'_id'       => strval( time() ),
									'condition' => '',
									'field'     => '',
									'value'     => '',
									'src'       => 'registration_form',
								),
							),
						),
					),
				),
				'isMultiColumn' => false,
			),
			array(
				'id'            => 'regi_2',
				'type'          => 'row',
				'columns'       => array(
					array(
						'status'         => true,
						'type'           => 'password',
						'label'          => 'Password',
						'name'           => 'user_pass',
						'isLabelHide'    => false,
						'placeholder'    => '',
						'columnPosition' => 'left',
						'parent'         => 'regi_2',
						'required'       => true,
						'conditions'     => array(
							'status'   => 'show',
							'relation' => 'all',
							'tiers'    => array(
								array(
									'_id'       => strval( time() ),
									'condition' => '',
									'field'     => '',
									'value'     => '',
									'src'       => 'registration_form',
								),
							),
						),
					),
				),
				'isMultiColumn' => false,
			),
		);

		return $registration_fields;
	}

	/**
	 * Get New Form Builder Data
	 *
	 * @return array
	 */
	public static function get_form_fields() {
		$woo_custom_fields   = array();
		$registration_fields = array();
		$billing_fields      = array();
		$myaccount_fields    = array();

		$fields = self::get_new_form_builder_data();

		if ( isset( $fields['registrationFields'] ) && is_array( $fields['registrationFields'] ) ) {
			foreach ( $fields['registrationFields'] as $row ) {
				if ( isset( $row['columns'] ) && is_array( $row['columns'] ) ) {
					foreach ( $row['columns'] as $field ) {
						if ( ( isset( $field['status'] ) && $field['status'] ) ) {
							if ( isset( $field['isAddToWooCommerceRegistration'] ) && $field['isAddToWooCommerceRegistration'] ) {
								$woo_custom_fields[] = $field;
							}
							if ( isset( $field['enableForBillingForm'] ) && $field['enableForBillingForm'] ) {
								$billing_fields[] = $field;
							}
							if ( isset( $field['isEditableByUser'] ) && $field['isEditableByUser'] ) {
								$myaccount_fields[] = $field;
							}
							$registration_fields[] = $field;
						}
					}
				}
			}
		}

		return array(
			'woo_custom_fields' => $woo_custom_fields,
			'wholesalex_fields' => $registration_fields,
			'billing_fields'    => $billing_fields,
			'myaccount_fields'  => $myaccount_fields,
		);
	}
}
