<?php
/**
 * Standalone display tests.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		unset( $hook, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		unset( $hook, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = '' ): string {
		unset( $domain );
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

require_once __DIR__ . '/../../includes/Checkout/FieldRegistry.php';
require_once __DIR__ . '/../../includes/Checkout/Display.php';

use WooTale\CheckoutBuilder\Checkout\Display;
use WooTale\CheckoutBuilder\Checkout\FieldRegistry;

$meta_key = FieldRegistry::meta_key( 'delivery_reference' );
$order    = new FakeOrder(
	array(
		$meta_key                            => 'Leave at reception',
		$meta_key . '_label'                 => 'Delivery reference',
		$meta_key . '_display_order_details' => 'yes',
		$meta_key . '_display_emails'        => 'no',
		$meta_key . '_display_thank_you'     => 'yes',
	)
);
$display = new Display();

$order_fields = $display->get_order_fields( $order, 'orderDetails' );
$email_fields = $display->get_order_fields( $order, 'emails' );
$thank_fields = $display->get_order_fields( $order, 'thankYou' );

assert_same( 1, count( $order_fields ), 'Order details enabled field is returned.' );
assert_same( 'Leave at reception', $order_fields[0]['value'], 'Order details receives saved value.' );
assert_same( 0, count( $email_fields ), 'Email-disabled field is hidden from emails.' );
assert_same( 1, count( $thank_fields ), 'Thank-you enabled field is returned.' );

ob_start();
$display->render_order_details_fields( $order );
$order_html = ob_get_clean();

ob_start();
$display->render_email_fields( $order, false, false );
$email_html = ob_get_clean();

ob_start();
$display->render_thank_you_fields( $order );
$thank_html = ob_get_clean();

assert_true( false !== strpos( $order_html, 'Leave at reception' ), 'Order details output includes saved value.' );
assert_same( '', $email_html, 'Email output is empty when email display is disabled.' );
assert_true( false !== strpos( $thank_html, 'Leave at reception' ), 'Thank-you output includes saved value.' );

echo "DisplayTest passed.\n";

final class FakeOrderMeta {
	/**
	 * @var array<string,mixed>
	 */
	private $data;

	/**
	 * @param array<string,mixed> $data Meta data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_data(): array {
		return $this->data;
	}
}

final class FakeOrder {
	/**
	 * @var array<string,string>
	 */
	private $meta;

	/**
	 * @param array<string,string> $meta Meta values.
	 */
	public function __construct( array $meta ) {
		$this->meta = $meta;
	}

	/**
	 * @return array<int,FakeOrderMeta>
	 */
	public function get_meta_data(): array {
		$items = array();

		foreach ( $this->meta as $key => $value ) {
			$items[] = new FakeOrderMeta(
				array(
					'key'   => $key,
					'value' => $value,
				)
			);
		}

		return $items;
	}

	public function get_meta( string $key ): string {
		return $this->meta[ $key ] ?? '';
	}
}

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
