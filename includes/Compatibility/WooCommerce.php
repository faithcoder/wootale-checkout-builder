<?php
/**
 * WooCommerce dependency checks.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Detects WooCommerce availability and version details.
 */
final class WooCommerce {
	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render_missing_notice' ) );
	}

	/**
	 * Whether WooCommerce is loaded.
	 */
	public function is_active(): bool {
		return class_exists( '\WooCommerce' ) || function_exists( 'WC' );
	}

	/**
	 * Get the detected WooCommerce version.
	 */
	public function version(): string {
		if ( defined( 'WC_VERSION' ) ) {
			return (string) WC_VERSION;
		}

		if ( defined( 'WC_PLUGIN_FILE' ) && function_exists( 'get_plugin_data' ) ) {
			$data = get_plugin_data( WC_PLUGIN_FILE, false, false );

			if ( ! empty( $data['Version'] ) ) {
				return (string) $data['Version'];
			}
		}

		return '';
	}

	/**
	 * Render an admin notice when WooCommerce is missing.
	 */
	public function render_missing_notice(): void {
		if ( $this->is_active() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__(
				'WooTale Checkout Builder requires WooCommerce and the classic checkout shortcode.',
				'wootale-checkout-builder'
			)
		);
	}
}
