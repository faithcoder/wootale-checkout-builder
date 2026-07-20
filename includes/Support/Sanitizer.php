<?php
/**
 * Shared sanitization helpers.
 *
 * @package Checkoutly\CheckoutBuilder
 */

declare(strict_types=1);

namespace Checkoutly\CheckoutBuilder\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitization helpers for future workflow attributes and diagnostics.
 */
final class Sanitizer {
	/**
	 * Sanitize a Checkoutly stable ID.
	 *
	 * @param string $value Raw value.
	 */
	public static function id( string $value ): string {
		$value = sanitize_key( $value );

		if ( 1 === preg_match( '/^checkoutly_(workflow|step|section|field)_[a-z0-9_]+$/', $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Sanitize a field key candidate.
	 *
	 * @param string $value Raw value.
	 */
	public static function field_key( string $value ): string {
		$value = sanitize_key( $value );

		if ( 1 === preg_match( '/^[a-z][a-z0-9_]{1,63}$/', $value ) ) {
			return $value;
		}

		return '';
	}
}
