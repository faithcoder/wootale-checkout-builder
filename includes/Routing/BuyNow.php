<?php
/**
 * Buy Now routing.
 *
 * @package Checkoutly\CheckoutBuilder
 */

declare(strict_types=1);

namespace Checkoutly\CheckoutBuilder\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a separate Buy Now path for simple products.
 */
final class BuyNow {
	/**
	 * Routing settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_button' ) );
		add_action( 'template_redirect', array( $this, 'handle_request' ), 9 );
	}

	/**
	 * Render the Buy Now button on supported product forms.
	 */
	public function render_button(): void {
		global $product;

		if ( ! $this->settings->buy_now_enabled() || ! Router::supports_buy_now_product( $product ) ) {
			return;
		}

		wp_nonce_field( 'checkoutly_buy_now', 'checkoutly_buy_now_nonce', false );

		printf(
			'<button type="submit" name="checkoutly_buy_now" value="1" class="button checkoutly-buy-now-button">%s</button>',
			esc_html( $this->settings->buy_now_label() )
		);
	}

	/**
	 * Handle Buy Now submissions.
	 */
	public function handle_request(): void {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

		if ( 'post' !== $request_method || empty( $_POST['checkoutly_buy_now'] ) || empty( $_POST['add-to-cart'] ) || empty( $_POST['checkoutly_buy_now_nonce'] ) || ! function_exists( 'wc_get_product' ) || ! function_exists( 'WC' ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['checkoutly_buy_now_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'checkoutly_buy_now' ) ) {
			return;
		}

		$product_id = absint( wp_unslash( $_POST['add-to-cart'] ) );
		$product    = wc_get_product( $product_id );

		if ( ! Router::supports_buy_now_product( $product ) ) {
			return;
		}

		$quantity = isset( $_POST['quantity'] ) ? Router::sanitize_quantity( absint( wp_unslash( $_POST['quantity'] ) ) ) : 1;

		if ( WC()->cart ) {
			WC()->cart->add_to_cart( $product_id, $quantity );
		}

		wp_safe_redirect( Router::checkout_url() );
		exit;
	}
}
