<?php
/**
 * Skip Cart routing.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Redirects add-to-cart requests to native WooCommerce checkout.
 */
final class SkipCart {
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
		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'redirect_to_checkout' ) );
	}

	/**
	 * Redirect add-to-cart to checkout when enabled.
	 *
	 * @param string $url Original redirect URL.
	 */
	public function redirect_to_checkout( string $url ): string {
		if ( ! $this->settings->skip_cart_enabled() ) {
			return $url;
		}

		return Router::checkout_url();
	}
}
