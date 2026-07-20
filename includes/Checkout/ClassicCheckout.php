<?php
/**
 * Classic shortcode checkout integration.
 *
 * @package Checkoutly\\CheckoutBuilder
 */

declare(strict_types=1);

namespace Checkoutly\CheckoutBuilder\Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * Applies Checkoutly workflow settings to WooCommerce classic checkout.
 */
final class ClassicCheckout {
	/**
	 * Workflow repository.
	 *
	 * @var Workflow
	 */
	private $workflow;

	public function __construct( Workflow $workflow ) {
		$this->workflow = $workflow;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_checkout_fields' ), 20 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_custom_fields' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ) );
	}

	/**
	 * Modify classic checkout fields from the saved builder config.
	 *
	 * @param array<string,array<string,array<string,mixed>>> $checkout_fields Checkout fields.
	 * @return array<string,array<string,array<string,mixed>>>
	 */
	public function filter_checkout_fields( array $checkout_fields ): array {
		$enabled_native_fields = array();

		foreach ( $this->workflow->fields() as $position => $field ) {
			if ( 'component' === $field['type'] ) {
				continue;
			}

			$section = (string) $field['section'];
			$key     = (string) $field['key'];

			if ( 'custom' === $field['type'] ) {
				if ( empty( $field['enabled'] ) ) {
					continue;
				}

				$checkout_fields[ $section ][ $key ] = $this->custom_checkout_field( $field, $position );
				continue;
			}

			if ( isset( $checkout_fields[ $section ][ $key ] ) ) {
				if ( empty( $field['enabled'] ) ) {
					unset( $checkout_fields[ $section ][ $key ] );
					continue;
				}

				$enabled_native_fields[ $section ][ $key ]          = true;
				$checkout_fields[ $section ][ $key ]['label']    = $field['label'];
				$checkout_fields[ $section ][ $key ]['required'] = ! empty( $field['required'] );
				$checkout_fields[ $section ][ $key ]['priority'] = (int) $field['priority'];
				$checkout_fields[ $section ][ $key ]['class']    = $this->field_classes( $checkout_fields[ $section ][ $key ]['class'] ?? array(), $field );
				$checkout_fields[ $section ][ $key ]['default']  = (string) ( $field['default'] ?? '' );

				if ( '' !== (string) ( $field['placeholder'] ?? '' ) ) {
					$checkout_fields[ $section ][ $key ]['placeholder'] = (string) $field['placeholder'];
				}

				if ( ! empty( $field['validation'] ) && is_array( $field['validation'] ) ) {
					$checkout_fields[ $section ][ $key ]['validate'] = array_values( array_unique( $field['validation'] ) );
				}
			}
		}

		foreach ( $this->native_checkout_keys() as $section => $keys ) {
			foreach ( $keys as $key ) {
				if ( isset( $checkout_fields[ $section ][ $key ] ) && empty( $enabled_native_fields[ $section ][ $key ] ) ) {
					unset( $checkout_fields[ $section ][ $key ] );
				}
			}
		}

		return $checkout_fields;
	}

	/**
	 * Save custom fields to order meta.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $data  Posted checkout data.
	 */
	public function save_custom_fields( $order, array $data ): void {
		foreach ( $this->workflow->fields() as $field ) {
			if ( 'custom' !== $field['type'] || empty( $field['enabled'] ) ) {
				continue;
			}

			$key = (string) $field['key'];

			if ( ! array_key_exists( $key, $data ) && 'checkbox' !== (string) ( $field['fieldType'] ?? '' ) ) {
				continue;
			}

			$value = $this->sanitize_posted_value_for_field( $field, array_key_exists( $key, $data ) ? $data[ $key ] : '' );

			$order->update_meta_data( FieldRegistry::meta_key( $key ), $value );
			$order->update_meta_data( FieldRegistry::meta_key( $key ) . '_label', (string) $field['label'] );
			$order->update_meta_data( FieldRegistry::meta_key( $key ) . '_display_order_details', ! empty( $field['display']['orderDetails'] ) ? 'yes' : 'no' );
			$order->update_meta_data( FieldRegistry::meta_key( $key ) . '_display_emails', ! empty( $field['display']['emails'] ) ? 'yes' : 'no' );
			$order->update_meta_data( FieldRegistry::meta_key( $key ) . '_display_thank_you', ! empty( $field['display']['thankYou'] ) ? 'yes' : 'no' );
		}
	}

	/**
	 * Sanitize a posted checkout value for order meta display.
	 *
	 * @param mixed $value Posted value.
	 */
	private function sanitize_posted_value( $value ): string {
		if ( is_array( $value ) ) {
			$clean = array_map(
				function ( $item ): string {
					return sanitize_text_field( (string) $item );
				},
				$value
			);

			return implode( ', ', array_filter( $clean, 'strlen' ) );
		}

		if ( function_exists( 'sanitize_textarea_field' ) ) {
			return sanitize_textarea_field( (string) $value );
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize and normalize a posted value based on its Checkoutly field type.
	 *
	 * @param array<string,mixed> $field Field definition.
	 * @param mixed               $value Posted value.
	 */
	private function sanitize_posted_value_for_field( array $field, $value ): string {
		$type = isset( $field['fieldType'] ) ? (string) $field['fieldType'] : 'text';

		if ( 'checkbox' === $type ) {
			return $this->is_checked_value( $value ) ? __( 'Yes', 'checkoutly' ) : __( 'No', 'checkoutly' );
		}

		$value = $this->sanitize_posted_value( $value );

		if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			$options = $this->checkout_field_options( (string) ( $field['options'] ?? '' ) );

			return isset( $options[ $value ] ) ? $options[ $value ] : $value;
		}

		return $value;
	}

	/**
	 * Determine whether a posted checkbox value represents a checked state.
	 *
	 * @param mixed $value Posted value.
	 */
	private function is_checked_value( $value ): bool {
		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		$value = strtolower( trim( (string) $value ) );

		return in_array( $value, array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Enqueue frontend step controller for the classic shortcode checkout.
	 */
	public function enqueue_checkout_assets(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}

		$workflow = $this->workflow->get();

		wp_enqueue_script(
			'checkoutly-classic-checkout',
			CHECKOUTLY_PLUGIN_URL . 'assets/js/classic-checkout.js',
			array(),
			CHECKOUTLY_VERSION,
			true
		);

		wp_add_inline_script(
			'checkoutly-classic-checkout',
			'window.checkoutlyClassicWorkflow = ' . $this->encode_json( $workflow ) . ';',
			'before'
		);

		wp_enqueue_style(
			'checkoutly-classic-checkout',
			CHECKOUTLY_PLUGIN_URL . 'assets/css/classic-checkout.css',
			array(),
			CHECKOUTLY_VERSION
		);
	}

	/**
	 * Build a custom WooCommerce checkout field definition.
	 *
	 * @param array<string,mixed> $field    Checkoutly field.
	 * @param int                 $position Position.
	 * @return array<string,mixed>
	 */
	private function custom_checkout_field( array $field, int $position ): array {
		$type = in_array( $field['fieldType'], array( 'text', 'email', 'tel', 'number', 'textarea', 'select', 'radio', 'checkbox' ), true ) ? $field['fieldType'] : 'text';

		$checkout_field = array(
			'type'     => $type,
			'label'    => (string) $field['label'],
			'required' => ! empty( $field['required'] ),
			'class'    => $this->field_classes( array( 'form-row-wide', 'checkoutly-custom-checkout-field' ), $field ),
			'default'  => (string) ( $field['default'] ?? '' ),
			'placeholder' => (string) ( $field['placeholder'] ?? '' ),
			'validate' => ! empty( $field['validation'] ) && is_array( $field['validation'] ) ? array_values( array_unique( $field['validation'] ) ) : array(),
			'priority' => (int) $field['priority'] + $position,
		);

		if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			$checkout_field['options'] = $this->checkout_field_options( (string) ( $field['options'] ?? '' ) );
		}

		return $checkout_field;
	}

	/**
	 * Get WooCommerce field wrapper classes for a Checkoutly field.
	 *
	 * @param mixed               $classes Existing classes.
	 * @param array<string,mixed> $field   Checkoutly field.
	 * @return array<int,string>
	 */
	private function field_classes( $classes, array $field ): array {
		$classes = is_array( $classes ) ? array_values( $classes ) : array();
		$width   = isset( $field['width'] ) ? (int) $field['width'] : 2;

		$classes = array_values(
			array_filter(
				$classes,
				static function ( $class ): bool {
					return is_string( $class ) && 0 !== strpos( $class, 'checkoutly-width-' );
				}
			)
		);

		$classes[] = 'checkoutly-classic-field';
		$classes[] = 'checkoutly-width-' . ( in_array( $width, array( 1, 2 ), true ) ? $width : 2 );

		return array_values( array_unique( $classes ) );
	}

	/**
	 * @return array<string,array<int,string>>
	 */
	private function native_checkout_keys(): array {
		return array(
			'billing'  => array(
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_country',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_state',
				'billing_postcode',
				'billing_phone',
				'billing_email',
			),
			'shipping' => array(
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_country',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_state',
				'shipping_postcode',
			),
			'order'    => array(
				'order_comments',
			),
		);
	}

	/**
	 * @return array<string,string>
	 */
	private function checkout_field_options( string $options ): array {
		$parsed = array();
		$lines  = preg_split( '/\r\n|\r|\n/', $options );

		foreach ( false === $lines ? array() : $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$key            = sanitize_title( $line );
			$parsed[ $key ] = $line;
		}

		if ( empty( $parsed ) ) {
			return array(
				''       => __( 'Select an option', 'checkoutly' ),
				'option' => __( 'Option', 'checkoutly' ),
			);
		}

		return $parsed;
	}


	/**
	 * Encode workflow data for the inline checkout controller.
	 *
	 * @param array<string,mixed> $data Data to encode.
	 */
	private function encode_json( array $data ): string {
		$encoded = wp_json_encode( $data );

		return is_string( $encoded ) ? $encoded : '{}';
	}
}
