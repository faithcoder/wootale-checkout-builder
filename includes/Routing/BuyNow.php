<?php
/**
 * Buy Now routing.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Routing;

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

		printf(
			'<button type="submit" name="wtcb_buy_now" value="1" class="button wtcb-buy-now-button">%s</button>',
			esc_html( $this->settings->buy_now_label() )
		);
	}

	/**
	 * Handle Buy Now submissions.
	 */
	public function handle_request(): void {
		if ( empty( $_REQUEST['wtcb_buy_now'] ) || empty( $_REQUEST['add-to-cart'] ) || ! function_exists( 'wc_get_product' ) || ! function_exists( 'WC' ) ) {
			return;
		}

		$product_id = absint( wp_unslash( $_REQUEST['add-to-cart'] ) );
		$product    = wc_get_product( $product_id );

		if ( ! Router::supports_buy_now_product( $product ) ) {
			return;
		}

		$quantity = isset( $_REQUEST['quantity'] ) ? Router::sanitize_quantity( wp_unslash( $_REQUEST['quantity'] ) ) : 1;

		if ( WC()->cart ) {
			WC()->cart->add_to_cart( $product_id, $quantity );
		}

		wp_safe_redirect( Router::checkout_url() );
		exit;
	}
}
