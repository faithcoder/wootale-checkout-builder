<?php
/**
 * Standalone classic checkout tests.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, $callback, int $priority = 10 ): void {
		unset( $hook, $callback, $priority );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, $callback, int $priority = 10 ): void {
		unset( $hook, $callback, $priority );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) ) ?: '';
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $value ): string {
		return trim( strip_tags( $value ) );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = '' ): string {
		unset( $domain );
		return $text;
	}
}

require_once __DIR__ . '/../../includes/Support/Sanitizer.php';
require_once __DIR__ . '/../../includes/Checkout/FieldRegistry.php';
require_once __DIR__ . '/../../includes/Checkout/Workflow.php';
require_once __DIR__ . '/../../includes/Checkout/ClassicCheckout.php';

use WooTale\CheckoutBuilder\Checkout\ClassicCheckout;
use WooTale\CheckoutBuilder\Checkout\Workflow;

$workflow = new class() extends Workflow {
	public function fields( ?array $workflow = null ): array {
		unset( $workflow );
		return array(
			array(
				'type'     => 'native',
				'section'  => 'billing',
				'key'      => 'billing_email',
				'label'    => 'Email',
				'required' => false,
				'enabled'  => true,
				'priority' => 10,
				'width'    => 1,
			),
			array(
				'type'      => 'custom',
				'section'   => 'order',
				'key'       => 'delivery_reference',
				'label'     => 'Delivery reference',
				'fieldType' => 'text',
				'required'  => true,
				'enabled'   => true,
				'priority'  => 20,
				'width'     => 2,
			),
			array(
				'type'     => 'native',
				'section'  => 'billing',
				'key'      => 'billing_phone',
				'label'    => 'Phone',
				'required' => false,
				'enabled'  => false,
				'priority' => 30,
				'width'    => 2,
			),
		);
	}
};

$classic = new ClassicCheckout( $workflow );
$fields  = $classic->filter_checkout_fields(
	array(
		'billing' => array(
			'billing_email' => array(
				'label'    => 'Old email',
				'required' => true,
			),
			'billing_phone' => array(
				'label' => 'Phone',
			),
		),
		'order'   => array(),
	)
);

assert_same( false, $fields['billing']['billing_email']['required'], 'Native required state can be changed.' );
assert_true( in_array( 'wtcb-width-1', $fields['billing']['billing_email']['class'], true ), 'Native field width class is applied.' );
assert_true( ! isset( $fields['billing']['billing_phone'] ), 'Disabled native field is removed.' );
assert_true( isset( $fields['order']['delivery_reference'] ), 'Custom fields are added to classic checkout.' );
assert_same( true, $fields['order']['delivery_reference']['required'], 'Custom required state is preserved.' );
assert_true( in_array( 'wtcb-width-2', $fields['order']['delivery_reference']['class'], true ), 'Custom field width class is applied.' );

echo "ClassicCheckoutTest passed.\n";

function assert_true( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "Failed: {$message}\n" );
		exit( 1 );
	}
}

function assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, "Failed: {$message}\n" );
		exit( 1 );
	}
}
