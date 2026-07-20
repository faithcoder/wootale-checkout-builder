<?php
/**
 * Standalone workflow tests.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

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
require_once __DIR__ . '/../../includes/Checkout/Workflow.php';

use WooTale\CheckoutBuilder\Checkout\Workflow;

$workflow = new Workflow();
$default  = $workflow->defaults();
$fields   = $workflow->fields( $default );

assert_same( 3, count( $default['steps'] ), 'Default workflow contains three steps.' );
assert_true( count( $default['steps'][0]['fields'] ) > 10, 'Default first step contains native contact, billing, and shipping fields.' );
assert_true( in_array( 'billing_email', array_column( $default['steps'][0]['fields'], 'key' ), true ), 'Billing email is in the first step.' );
assert_true( in_array( 'shipping_address_1', array_column( $default['steps'][0]['fields'], 'key' ), true ), 'Shipping address is in the first step.' );
assert_same( 1, $default['steps'][0]['fields'][0]['width'], 'First default field starts as half width.' );
assert_same( 'order_payment', $default['steps'][2]['fields'][0]['key'], 'Default payment step uses the grouped order and payment component.' );
assert_true( count( $fields ) >= count( $default['steps'][0]['fields'] ), 'Fields flatten across workflow steps.' );

$sanitized = $workflow->sanitize(
	array(
		'steps' => array(
			array(
				'id'     => 'My Step',
				'title'  => '<b>Custom</b>',
				'fields' => array(
					array(
						'key'      => 'custom_note',
						'type'     => 'custom',
						'section'  => 'order',
						'label'    => '<b>Note</b>',
						'enabled'  => true,
						'required' => true,
						'width'    => 2,
					),
					array(
						'key'  => '1_bad',
						'type' => 'custom',
					),
				),
			),
		),
	)
);

assert_same( 'Custom', $sanitized['steps'][0]['title'], 'Step title is sanitized.' );
assert_same( 1, count( $sanitized['steps'][0]['fields'] ), 'Invalid custom keys are dropped.' );
assert_same( 2, $sanitized['steps'][0]['fields'][0]['width'], 'Field width is sanitized and preserved.' );

echo "WorkflowTest passed.\n";

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
