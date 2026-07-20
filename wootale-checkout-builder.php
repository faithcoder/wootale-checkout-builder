<?php
/**
 * Plugin Name: Checkoutly – Custom Checkout Builder for WooCommerce
 * Plugin URI: https://checkoutly.com/
 * Description: Dashboard checkout workflow builder for the WooCommerce classic checkout shortcode.
 * Version: 0.7.2
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: Checkoutly
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: checkoutly
 * Domain Path: /languages
 *
 * @package Checkoutly\CheckoutBuilder
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'CHECKOUTLY_VERSION', '0.7.2' );
define( 'CHECKOUTLY_PLUGIN_FILE', __FILE__ );
define( 'CHECKOUTLY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CHECKOUTLY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Plugin.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Admin/Builder.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Admin/Diagnostics.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Support/Sanitizer.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Compatibility/HPOS.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Compatibility/WooCommerce.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Checkout/ClassicCheckout.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Checkout/Display.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Checkout/FieldRegistry.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Checkout/Workflow.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Routing/Settings.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Routing/Router.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Routing/SkipCart.php';
require_once CHECKOUTLY_PLUGIN_DIR . 'includes/Routing/BuyNow.php';

( new \Checkoutly\CheckoutBuilder\Compatibility\HPOS() )->register();

add_action(
	'plugins_loaded',
	static function (): void {
		\Checkoutly\CheckoutBuilder\Plugin::instance()->register();
	}
);
