<?php
/**
 * Plugin Name: WooTale Checkout Builder
 * Plugin URI: https://wootale.com/
 * Description: Dashboard checkout workflow builder for the WooCommerce classic checkout shortcode.
 * Version: 0.7.2
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Author: WooTale
 * Text Domain: wootale-checkout-builder
 * Domain Path: /languages
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'WTCB_VERSION', '0.7.2' );
define( 'WTCB_PLUGIN_FILE', __FILE__ );
define( 'WTCB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WTCB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WTCB_PLUGIN_DIR . 'includes/Plugin.php';
require_once WTCB_PLUGIN_DIR . 'includes/Admin/Builder.php';
require_once WTCB_PLUGIN_DIR . 'includes/Admin/Diagnostics.php';
require_once WTCB_PLUGIN_DIR . 'includes/Support/Sanitizer.php';
require_once WTCB_PLUGIN_DIR . 'includes/Compatibility/HPOS.php';
require_once WTCB_PLUGIN_DIR . 'includes/Compatibility/WooCommerce.php';
require_once WTCB_PLUGIN_DIR . 'includes/Checkout/ClassicCheckout.php';
require_once WTCB_PLUGIN_DIR . 'includes/Checkout/Display.php';
require_once WTCB_PLUGIN_DIR . 'includes/Checkout/FieldRegistry.php';
require_once WTCB_PLUGIN_DIR . 'includes/Checkout/Workflow.php';
require_once WTCB_PLUGIN_DIR . 'includes/Routing/Settings.php';
require_once WTCB_PLUGIN_DIR . 'includes/Routing/Router.php';
require_once WTCB_PLUGIN_DIR . 'includes/Routing/SkipCart.php';
require_once WTCB_PLUGIN_DIR . 'includes/Routing/BuyNow.php';

( new \WooTale\CheckoutBuilder\Compatibility\HPOS() )->register();

add_action(
	'plugins_loaded',
	static function (): void {
		\WooTale\CheckoutBuilder\Plugin::instance()->register();
	}
);
