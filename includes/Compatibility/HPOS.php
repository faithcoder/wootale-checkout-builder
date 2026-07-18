<?php
/**
 * WooCommerce HPOS compatibility declaration.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Declares compatibility without touching checkout order persistence in Phase 0.
 */
final class HPOS {
	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
	}

	/**
	 * Declare HPOS compatibility when WooCommerce exposes the feature utility.
	 */
	public function declare_compatibility(): void {
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WTCB_PLUGIN_FILE, true );
	}
}
