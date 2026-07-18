<?php
/**
 * Classic checkout workflow configuration.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Checkout;

use WooTale\CheckoutBuilder\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Stores and sanitizes WooTale classic checkout builder configuration.
 */
class Workflow {
	public const OPTION = 'wtcb_classic_checkout_workflow';
	public const MAX_FREE_STEPS = 3;

	/**
	 * Get the saved workflow, falling back to the default starter layout.
	 *
	 * @return array<string,mixed>
	 */
	public function get(): array {
		$workflow = get_option( self::OPTION, array() );

		if ( ! is_array( $workflow ) || empty( $workflow['steps'] ) ) {
			return $this->defaults();
		}

		return $this->sanitize( $workflow );
	}

	/**
	 * Save a workflow.
	 *
	 * @param array<string,mixed> $workflow Workflow config.
	 */
	public function save( array $workflow ): void {
		update_option( self::OPTION, $this->sanitize( $workflow ), false );
	}

	/**
	 * Starter workflow: native contact, billing, and shipping fields begin in step 1.
	 *
	 * @return array<string,mixed>
	 */
	public function defaults(): array {
		return array(
			'version'       => 1,
			'multiStepEnabled' => true,
			'orientation'   => 'horizontal',
			'indicator'     => 'number',
			'connector'     => 'solid',
			'navigation'    => 'tabs',
			'activeColor'   => '#2563eb',
			'completedColor'=> '#16a34a',
			'steps'         => array(
				array(
					'id'          => 'wtcb_step_customer_details',
					'title'       => __( 'Customer Details', 'wootale-checkout-builder' ),
					'description' => __( 'Collect customer, billing, and shipping information.', 'wootale-checkout-builder' ),
					'color'       => '#2563eb',
					'fields'      => array(
						$this->native_field( 'billing', 'billing_first_name', __( 'First name', 'wootale-checkout-builder' ), true, 1 ),
						$this->native_field( 'billing', 'billing_last_name', __( 'Last name', 'wootale-checkout-builder' ), true, 1 ),
						$this->native_field( 'billing', 'billing_email', __( 'Email address', 'wootale-checkout-builder' ), true, 1 ),
						$this->native_field( 'billing', 'billing_phone', __( 'Phone', 'wootale-checkout-builder' ), false ),
						$this->native_field( 'billing', 'billing_company', __( 'Company name', 'wootale-checkout-builder' ), false ),
						$this->native_field( 'billing', 'billing_country', __( 'Billing country / region', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'billing', 'billing_address_1', __( 'Billing street address', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'billing', 'billing_address_2', __( 'Billing apartment, suite, unit, etc.', 'wootale-checkout-builder' ), false ),
						$this->native_field( 'billing', 'billing_city', __( 'Billing town / city', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'billing', 'billing_state', __( 'Billing state / county', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'billing', 'billing_postcode', __( 'Billing postcode / ZIP', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'shipping', 'shipping_first_name', __( 'Shipping first name', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'shipping', 'shipping_last_name', __( 'Shipping last name', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'shipping', 'shipping_company', __( 'Shipping company', 'wootale-checkout-builder' ), false ),
						$this->native_field( 'shipping', 'shipping_country', __( 'Shipping country / region', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'shipping', 'shipping_address_1', __( 'Shipping street address', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'shipping', 'shipping_address_2', __( 'Shipping apartment, suite, unit, etc.', 'wootale-checkout-builder' ), false ),
						$this->native_field( 'shipping', 'shipping_city', __( 'Shipping town / city', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'shipping', 'shipping_state', __( 'Shipping state / county', 'wootale-checkout-builder' ), true ),
						$this->native_field( 'shipping', 'shipping_postcode', __( 'Shipping postcode / ZIP', 'wootale-checkout-builder' ), true ),
					),
				),
				array(
					'id'          => 'wtcb_step_shipping_details',
					'title'       => __( 'Shipping Details', 'wootale-checkout-builder' ),
					'description' => __( 'Collect delivery preferences and notes.', 'wootale-checkout-builder' ),
					'color'       => '#16a34a',
					'fields'      => array(
						$this->native_field( 'order', 'order_comments', __( 'Order notes', 'wootale-checkout-builder' ), false ),
					),
				),
				array(
					'id'          => 'wtcb_step_payment',
					'title'       => __( 'Payment', 'wootale-checkout-builder' ),
					'description' => __( 'Complete payment and place the order.', 'wootale-checkout-builder' ),
					'color'       => '#7c3aed',
					'fields'      => array(
						$this->component( 'payment_methods', __( 'Payment Methods', 'wootale-checkout-builder' ) ),
						$this->component( 'terms', __( 'Terms & Conditions', 'wootale-checkout-builder' ) ),
						$this->component( 'place_order', __( 'Place Order Button', 'wootale-checkout-builder' ) ),
					),
				),
			),
		);
	}

	/**
	 * Sanitize a workflow config.
	 *
	 * @param array<string,mixed> $workflow Workflow config.
	 * @return array<string,mixed>
	 */
	public function sanitize( array $workflow ): array {
		$steps = isset( $workflow['steps'] ) && is_array( $workflow['steps'] ) ? array_values( $workflow['steps'] ) : array();
		$steps = array_slice( $steps, 0, self::MAX_FREE_STEPS );
		$clean = array(
			'version'        => 1,
			'multiStepEnabled' => ! array_key_exists( 'multiStepEnabled', $workflow ) || ! empty( $workflow['multiStepEnabled'] ),
			'orientation'    => $this->sanitize_choice( isset( $workflow['orientation'] ) ? (string) $workflow['orientation'] : 'horizontal', array( 'horizontal', 'vertical' ), 'horizontal' ),
			'indicator'      => $this->sanitize_choice( isset( $workflow['indicator'] ) ? (string) $workflow['indicator'] : 'number', array( 'number', 'icon', 'tab' ), 'number' ),
			'connector'      => $this->sanitize_choice( isset( $workflow['connector'] ) ? (string) $workflow['connector'] : 'solid', array( 'solid', 'line', 'arrow', 'none' ), 'solid' ),
			'navigation'     => $this->sanitize_choice( isset( $workflow['navigation'] ) ? (string) $workflow['navigation'] : 'tabs', array( 'tabs', 'buttons' ), 'tabs' ),
			'activeColor'    => $this->sanitize_color( isset( $workflow['activeColor'] ) ? (string) $workflow['activeColor'] : '#2563eb' ),
			'completedColor' => $this->sanitize_color( isset( $workflow['completedColor'] ) ? (string) $workflow['completedColor'] : '#16a34a' ),
			'steps'          => array(),
		);

		foreach ( $steps as $index => $step ) {
			$step = is_array( $step ) ? $step : array();
			$id   = isset( $step['id'] ) ? sanitize_key( (string) $step['id'] ) : '';

			if ( '' === $id ) {
				$id = 'wtcb_step_' . ( $index + 1 );
			}

			$clean['steps'][] = array(
				'id'          => $id,
				'title'       => isset( $step['title'] ) ? sanitize_text_field( (string) $step['title'] ) : sprintf( __( 'Step %d', 'wootale-checkout-builder' ), $index + 1 ),
				'description' => isset( $step['description'] ) ? sanitize_text_field( (string) $step['description'] ) : '',
				'color'       => $this->sanitize_color( isset( $step['color'] ) ? (string) $step['color'] : '#2563eb' ),
				'fields'      => $this->sanitize_fields( isset( $step['fields'] ) && is_array( $step['fields'] ) ? $step['fields'] : array() ),
			);
		}

		return $clean;
	}

	/**
	 * Flatten workflow fields.
	 *
	 * @param array<string,mixed>|null $workflow Workflow config.
	 * @return array<int,array<string,mixed>>
	 */
	public function fields( ?array $workflow = null ): array {
		$workflow = null === $workflow ? $this->get() : $workflow;
		$fields   = array();

		foreach ( $workflow['steps'] as $step_index => $step ) {
			foreach ( $step['fields'] as $field_index => $field ) {
				$field['stepId']   = $step['id'];
				$field['priority'] = ( $step_index + 1 ) * 100 + $field_index;
				$fields[]          = $field;
			}
		}

		return $fields;
	}

	private function native_field( string $section, string $key, string $label, bool $required, int $width = 2 ): array {
		return array(
			'id'          => $key,
			'key'         => $key,
			'section'     => $section,
			'type'        => 'native',
			'fieldType'   => 'text',
			'label'       => $label,
			'required'    => $required,
			'enabled'     => true,
			'width'       => $width,
			'default'     => '',
			'placeholder' => '',
			'options'     => '',
			'validation'  => array(),
			'display'     => $this->default_display_options(),
		);
	}

	private function component( string $key, string $label ): array {
		return array(
			'id'          => 'component_' . $key,
			'key'         => $key,
			'section'     => 'component',
			'type'        => 'component',
			'fieldType'   => 'component',
			'label'       => $label,
			'required'    => false,
			'enabled'     => true,
			'width'       => 2,
			'default'     => '',
			'placeholder' => '',
			'options'     => '',
			'validation'  => array(),
			'display'     => $this->default_display_options(),
		);
	}

	/**
	 * @param array<int,mixed> $fields Fields.
	 * @return array<int,array<string,mixed>>
	 */
	private function sanitize_fields( array $fields ): array {
		$clean = array();

		foreach ( $fields as $field ) {
			$field = is_array( $field ) ? $field : array();
			$type  = isset( $field['type'] ) ? sanitize_key( (string) $field['type'] ) : 'native';
			$key   = isset( $field['key'] ) ? sanitize_key( (string) $field['key'] ) : '';

			if ( 'custom' === $type ) {
				$key = Sanitizer::field_key( $key );
			}

			if ( '' === $key ) {
				continue;
			}

			$section = isset( $field['section'] ) ? sanitize_key( (string) $field['section'] ) : 'billing';

			if ( ! in_array( $section, array( 'billing', 'shipping', 'order', 'component' ), true ) ) {
				$section = 'billing';
			}

			$clean[] = array(
				'id'          => isset( $field['id'] ) ? sanitize_key( (string) $field['id'] ) : $key,
				'key'         => $key,
				'section'     => $section,
				'type'        => in_array( $type, array( 'native', 'custom', 'component' ), true ) ? $type : 'native',
				'fieldType'   => $this->sanitize_field_type( isset( $field['fieldType'] ) ? (string) $field['fieldType'] : 'text' ),
				'label'       => isset( $field['label'] ) ? sanitize_text_field( (string) $field['label'] ) : $key,
				'required'    => ! empty( $field['required'] ),
				'enabled'     => ! isset( $field['enabled'] ) || ! empty( $field['enabled'] ),
				'width'       => $this->sanitize_width( isset( $field['width'] ) ? (int) $field['width'] : 2 ),
				'default'     => isset( $field['default'] ) ? sanitize_text_field( (string) $field['default'] ) : '',
				'placeholder' => isset( $field['placeholder'] ) ? sanitize_text_field( (string) $field['placeholder'] ) : '',
				'options'     => isset( $field['options'] ) ? $this->sanitize_textarea( (string) $field['options'] ) : '',
				'validation'  => $this->sanitize_validation( isset( $field['validation'] ) && is_array( $field['validation'] ) ? $field['validation'] : array() ),
				'display'     => $this->sanitize_display( isset( $field['display'] ) && is_array( $field['display'] ) ? $field['display'] : array() ),
			);
		}

		return $clean;
	}

	private function sanitize_color( string $color ): string {
		return 1 === preg_match( '/^#[0-9a-f]{6}$/i', $color ) ? $color : '#2563eb';
	}

	/**
	 * @param array<int,string> $allowed Allowed values.
	 */
	private function sanitize_choice( string $value, array $allowed, string $fallback ): string {
		$value = sanitize_key( $value );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private function sanitize_textarea( string $value ): string {
		if ( function_exists( 'sanitize_textarea_field' ) ) {
			return sanitize_textarea_field( $value );
		}

		return sanitize_text_field( $value );
	}

	private function sanitize_width( int $width ): int {
		if ( in_array( $width, array( 1, 2 ), true ) ) {
			return $width;
		}

		return 2;
	}

	private function sanitize_field_type( string $type ): string {
		$type = sanitize_key( $type );

		return in_array( $type, array( 'text', 'email', 'tel', 'number', 'textarea', 'select', 'radio', 'checkbox', 'component' ), true ) ? $type : 'text';
	}

	/**
	 * @param array<int,mixed> $validation Validation rules.
	 * @return array<int,string>
	 */
	private function sanitize_validation( array $validation ): array {
		$allowed = array( 'email', 'phone', 'postcode' );
		$clean   = array();

		foreach ( $validation as $rule ) {
			$rule = sanitize_key( (string) $rule );

			if ( in_array( $rule, $allowed, true ) ) {
				$clean[] = $rule;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * @param array<string,mixed> $display Display options.
	 * @return array<string,bool>
	 */
	private function sanitize_display( array $display ): array {
		$defaults = $this->default_display_options();

		foreach ( $defaults as $key => $value ) {
			$defaults[ $key ] = array_key_exists( $key, $display ) ? ! empty( $display[ $key ] ) : $value;
		}

		return $defaults;
	}

	/**
	 * @return array<string,bool>
	 */
	private function default_display_options(): array {
		return array(
			'orderDetails' => true,
			'emails'       => true,
			'thankYou'     => true,
		);
	}
}
