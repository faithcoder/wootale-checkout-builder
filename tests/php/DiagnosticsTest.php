<?php
/**
 * Standalone diagnostics tests.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );
define( 'WTCB_VERSION', '0.7.2' );

$wp_version = '7.0.2';

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Minimal add_action stub.
	 *
	 * @param string $hook     Hook name.
	 * @param mixed  $callback Callback.
	 */
	function add_action( string $hook, $callback ): void {
		unset( $hook, $callback );
	}
}

require_once __DIR__ . '/../../includes/Compatibility/WooCommerce.php';
require_once __DIR__ . '/../../includes/Admin/Diagnostics.php';

use WooTale\CheckoutBuilder\Admin\Diagnostics;
use WooTale\CheckoutBuilder\Compatibility\WooCommerce;

$diagnostics = new Diagnostics( new WooCommerce() );
$rows        = $diagnostics->get_rows();

assert_true( isset( $rows['Plugin version'] ), 'Plugin version row exists.' );
assert_true( $rows['Plugin version'] === '0.7.2', 'Plugin version is reported.' );
assert_true( isset( $rows['WooCommerce active'] ), 'WooCommerce active row exists.' );
assert_true( $rows['Checkout mode'] === 'classic shortcode', 'Classic checkout mode is reported.' );

echo "DiagnosticsTest passed.\n";

/**
 * Assert a condition.
 *
 * @param bool   $condition Condition.
 * @param string $message   Message.
 */
function assert_true( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "Failed: {$message}\n" );
		exit( 1 );
	}
}
