<?php
/**
 * Plugin bootstrap.
 *
 * @package Checkoutly\\CheckoutBuilder
 */

declare(strict_types=1);

namespace Checkoutly\CheckoutBuilder;

use Checkoutly\CheckoutBuilder\Admin\Diagnostics;
use Checkoutly\CheckoutBuilder\Admin\Builder;
use Checkoutly\CheckoutBuilder\Checkout\ClassicCheckout;
use Checkoutly\CheckoutBuilder\Checkout\Display;
use Checkoutly\CheckoutBuilder\Checkout\Workflow;
use Checkoutly\CheckoutBuilder\Routing\BuyNow;
use Checkoutly\CheckoutBuilder\Routing\Settings;
use Checkoutly\CheckoutBuilder\Routing\SkipCart;
use Checkoutly\CheckoutBuilder\Compatibility\WooCommerce;

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
