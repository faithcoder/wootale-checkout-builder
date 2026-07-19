<?php
/**
 * Admin diagnostics screen.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Admin;

use WooTale\CheckoutBuilder\Compatibility\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal diagnostics screen.
 */
final class Diagnostics {
	/**
	 * WooCommerce compatibility helper.
	 *
	 * @var WooCommerce
	 */
	private $woocommerce;

	/**
	 * Constructor.
	 *
	 * @param WooCommerce $woocommerce WooCommerce compatibility helper.
	 */
	public function __construct( WooCommerce $woocommerce ) {
		$this->woocommerce = $woocommerce;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Register diagnostics submenu.
	 */
	public function register_menu(): void {
		add_submenu_page(
			'tools.php',
			__( 'WooTale Checkout Builder Diagnostics', 'wootale-checkout-builder' ),
			__( 'WooTale Diagnostics', 'wootale-checkout-builder' ),
			'manage_options',
			'wtcb-diagnostics',
			array( $this, 'render' )
		);
	}

	/**
	 * Render the diagnostics page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wootale-checkout-builder' ) );
		}

		$rows = $this->get_rows();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'WooTale Checkout Builder Diagnostics', 'wootale-checkout-builder' ) . '</h1>';
		echo '<table class="widefat striped"><tbody>';

		foreach ( $rows as $label => $value ) {
			printf(
				'<tr><th scope="row">%s</th><td><code>%s</code></td></tr>',
				esc_html( $label ),
				esc_html( $value )
			);
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * Get diagnostic rows.
	 *
	 * @return array<string,string>
	 */
	public function get_rows(): array {
		global $wp_version;

		return array(
			'Plugin version'                 => WTCB_VERSION,
			'WordPress version'              => isset( $wp_version ) ? (string) $wp_version : '',
			'PHP version'                    => PHP_VERSION,
			'WooCommerce active'             => $this->woocommerce->is_active() ? 'yes' : 'no',
			'WooCommerce version'            => $this->woocommerce->version(),
			'Checkout mode'                  => 'classic shortcode',
		);
	}
}
