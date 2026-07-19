<?php
/**
 * WooTale checkout field registry.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Checkout;

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
				'metaPrefix' => '_wtcb_',
			);
		}

		/**
		 * Filters available WooTale field types.
		 *
		 * @param array<string,array<string,mixed>> $types Field type definitions.
		 */
		return apply_filters( 'wtcb_available_field_types', $types );
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
			'text'     => __( 'Text', 'wootale-checkout-builder' ),
			'email'    => __( 'Email', 'wootale-checkout-builder' ),
			'tel'      => __( 'Phone', 'wootale-checkout-builder' ),
			'number'   => __( 'Number', 'wootale-checkout-builder' ),
			'textarea' => __( 'Textarea', 'wootale-checkout-builder' ),
			'select'   => __( 'Select', 'wootale-checkout-builder' ),
			'radio'    => __( 'Radio', 'wootale-checkout-builder' ),
			'checkbox' => __( 'Checkbox', 'wootale-checkout-builder' ),
		);
	}

	/**
	 * Get a meta key for a field key.
	 *
	 * @param string $field_key Field key.
	 */
	public static function meta_key( string $field_key ): string {
		return '_wtcb_' . $field_key;
	}
}
