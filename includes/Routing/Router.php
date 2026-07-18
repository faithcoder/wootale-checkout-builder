<?php
/**
 * Routing helpers.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Shared routing decisions.
 */
final class Router {
	/**
	 * Get the current checkout URL.
	 */
	public static function checkout_url(): string {
		if ( function_exists( 'wc_get_checkout_url' ) ) {
			return wc_get_checkout_url();
		}

		return function_exists( 'home_url' ) ? home_url( '/checkout/' ) : '/checkout/';
	}

	/**
	 * Whether a product can use the basic Free Buy Now path.
	 *
	 * @param mixed $product Product object.
	 */
	public static function supports_buy_now_product( $product ): bool {
		if ( ! is_object( $product ) || ! method_exists( $product, 'is_purchasable' ) || ! method_exists( $product, 'is_type' ) ) {
			return false;
		}

		if ( ! $product->is_purchasable() ) {
			return false;
		}

		return (bool) $product->is_type( 'simple' );
	}

	/**
	 * Sanitize a posted quantity.
	 *
	 * @param mixed $quantity Raw quantity.
	 */
	public static function sanitize_quantity( $quantity ): int {
		$quantity = is_numeric( $quantity ) ? (int) $quantity : 1;

		return max( 1, $quantity );
	}
}
