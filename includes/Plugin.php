<?php
/**
 * Plugin bootstrap.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder;

use WooTale\CheckoutBuilder\Admin\Diagnostics;
use WooTale\CheckoutBuilder\Admin\Builder;
use WooTale\CheckoutBuilder\Checkout\ClassicCheckout;
use WooTale\CheckoutBuilder\Checkout\Display;
use WooTale\CheckoutBuilder\Checkout\Workflow;
use WooTale\CheckoutBuilder\Routing\BuyNow;
use WooTale\CheckoutBuilder\Routing\Settings;
use WooTale\CheckoutBuilder\Routing\SkipCart;
use WooTale\CheckoutBuilder\Compatibility\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates plugin services.
 */
final class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		$woocommerce = new WooCommerce();
		$workflow    = new Workflow();

		$woocommerce->register();
		( new Builder( $workflow ) )->register();
		( new ClassicCheckout( $workflow ) )->register();
		( new Diagnostics( $woocommerce ) )->register();
		( new Display() )->register();
		$settings = new Settings();
		$settings->register();
		( new SkipCart( $settings ) )->register();
		( new BuyNow( $settings ) )->register();
	}
}
