<?php
/**
 * Checkoutly checkout field registry.
 *
 * @package Checkoutly\CheckoutBuilder
 */

declare(strict_types=1);

namespace Checkoutly\CheckoutBuilder\Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * Defines supported custom checkout fields.
 */
final class FieldRegistry {
	/**
	 * Get supported field types.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function types(): array {
		$types = array();

		foreach ( self::core_types() as $type => $label ) {
			$types[ $type ] = array(
				'label'      => $label,
				'maxLength'  => 'textarea' === $type ? 1000 : 200,
				'metaPrefix' => '_checkoutly_',
			);
		}

		/**
		 * Filters available Checkoutly field types.
		 *
		 * @param array<string,array<string,mixed>> $types Field type definitions.
		 */
		return apply_filters( 'checkoutly_available_field_types', $types );
	}

	/**
	 * Whether a field type is supported.
	 *
	 * @param string $type Field type.
	 */
	public static function is_supported_type( string $type ): bool {
		return array_key_exists( $type, self::types() );
	}

	/**
	 * Get built-in free field types.
	 *
	 * @return array<string,string>
	 */
	private static function core_types(): array {
		return array(
			'text'     => __( 'Text', 'checkoutly' ),
			'email'    => __( 'Email', 'checkoutly' ),
			'tel'      => __( 'Phone', 'checkoutly' ),
			'number'   => __( 'Number', 'checkoutly' ),
			'textarea' => __( 'Textarea', 'checkoutly' ),
			'select'   => __( 'Select', 'checkoutly' ),
			'radio'    => __( 'Radio', 'checkoutly' ),
			'checkbox' => __( 'Checkbox', 'checkoutly' ),
		);
	}

	/**
	 * Get a meta key for a field key.
	 *
	 * @param string $field_key Field key.
	 */
	public static function meta_key( string $field_key ): string {
		return '_checkoutly_' . $field_key;
	}
}
