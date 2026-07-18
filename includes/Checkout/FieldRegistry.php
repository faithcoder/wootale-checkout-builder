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
 * Defines supported custom checkout fields for the current phase.
 */
final class FieldRegistry {
	/**
	 * Get supported field types.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function types(): array {
		$types = array(
			'text' => array(
				'label'      => __( 'Text', 'wootale-checkout-builder' ),
				'maxLength'  => 200,
				'metaPrefix' => '_wtcb_',
			),
		);

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
	 * Get a meta key for a field key.
	 *
	 * @param string $field_key Field key.
	 */
	public static function meta_key( string $field_key ): string {
		return '_wtcb_' . $field_key;
	}
}
