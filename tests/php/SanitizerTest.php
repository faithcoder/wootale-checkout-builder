<?php
/**
 * Standalone sanitizer tests.
 *
 * @package Checkoutly\CheckoutBuilder
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Minimal sanitize_key stub.
	 *
	 * @param string $key Raw key.
	 */
	function sanitize_key( string $key ): string {
		$key = strtolower( $key );

		return preg_replace( '/[^a-z0-9_]/', '', $key ) ?: '';
	}
}

require_once __DIR__ . '/../../includes/Support/Sanitizer.php';

use Checkoutly\CheckoutBuilder\Support\Sanitizer;

assert_same(
	'checkoutly_step_checkout_1',
	Sanitizer::id( 'checkoutly_step_checkout_1' ),
	'Valid step ID is preserved.'
);
assert_same(
	'',
	Sanitizer::id( 'checkout_step_1' ),
	'Invalid stable ID is rejected.'
);
assert_same(
	'delivery_reference',
	Sanitizer::field_key( 'delivery_reference' ),
	'Valid field key is preserved.'
);
assert_same(
	'',
	Sanitizer::field_key( '1_delivery_reference' ),
	'Field key must start with a letter.'
);

echo "SanitizerTest passed.\n";

/**
 * Assert same.
 *
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 * @param string $message  Message.
 */
function assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, "Failed: {$message}\n" );
		exit( 1 );
	}
}
