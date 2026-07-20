<?php
/**
 * Standalone routing tests.
 *
 * @package Checkoutly\CheckoutBuilder
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

$GLOBALS['checkoutly_test_options'] = array();

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		unset( $hook, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		unset( $hook, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = '' ): string {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, $default = false ) {
		return $GLOBALS['checkoutly_test_options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'wc_get_checkout_url' ) ) {
	function wc_get_checkout_url(): string {
		return 'https://example.test/checkout/';
	}
}

require_once __DIR__ . '/../../includes/Routing/Settings.php';
require_once __DIR__ . '/../../includes/Routing/Router.php';
require_once __DIR__ . '/../../includes/Routing/SkipCart.php';

use Checkoutly\CheckoutBuilder\Routing\Router;
use Checkoutly\CheckoutBuilder\Routing\Settings;
use Checkoutly\CheckoutBuilder\Routing\SkipCart;

$settings = new Settings();

assert_same( Settings::ROUTING_STANDARD, $settings->routing_mode(), 'Default routing mode is standard.' );
assert_same( false, $settings->skip_cart_enabled(), 'Skip Cart is disabled by default.' );
assert_same( false, $settings->buy_now_enabled(), 'Buy Now is disabled by default.' );
assert_same( 'Buy Now', $settings->buy_now_label(), 'Default Buy Now label is used.' );

$GLOBALS['checkoutly_test_options'][ Settings::OPTION_ROUTING_MODE ] = Settings::ROUTING_SKIP_CART;
$skip_cart = new SkipCart( $settings );

assert_same( true, $settings->skip_cart_enabled(), 'Skip Cart setting is detected.' );
assert_same( 'https://example.test/checkout/', $skip_cart->redirect_to_checkout( 'https://example.test/cart/' ), 'Skip Cart redirects to checkout.' );
assert_same( 1, Router::sanitize_quantity( 0 ), 'Quantity lower bound is one.' );
assert_same( 3, Router::sanitize_quantity( '3' ), 'Numeric quantity is preserved.' );

$simple_product = new class() {
	public function is_purchasable(): bool {
		return true;
	}

	public function is_type( string $type ): bool {
		return 'simple' === $type;
	}
};
$variable_product = new class() {
	public function is_purchasable(): bool {
		return true;
	}

	public function is_type( string $type ): bool {
		unset( $type );
		return false;
	}
};

assert_same( true, Router::supports_buy_now_product( $simple_product ), 'Simple purchasable products support Buy Now.' );
assert_same( false, Router::supports_buy_now_product( $variable_product ), 'Non-simple products are blocked in Free Buy Now.' );

echo "RoutingTest passed.\n";

function assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, "Failed: {$message}\n" );
		exit( 1 );
	}
}
