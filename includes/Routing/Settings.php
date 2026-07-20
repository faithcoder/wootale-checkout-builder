<?php
/**
 * Checkout routing settings.
 *
 * @package Checkoutly\CheckoutBuilder
 */

declare(strict_types=1);

namespace Checkoutly\CheckoutBuilder\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Stores global Free routing options.
 */
final class Settings {
	public const OPTION_ROUTING_MODE     = 'checkoutly_routing_mode';
	public const OPTION_BUY_NOW_ENABLED  = 'checkoutly_buy_now_enabled';
	public const OPTION_BUY_NOW_LABEL    = 'checkoutly_buy_now_label';
	public const ROUTING_STANDARD        = 'standard';
	public const ROUTING_SKIP_CART       = 'skip_cart';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_get_sections_products', array( $this, 'register_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'register_settings' ), 10, 2 );
	}

	/**
	 * Register product settings section.
	 *
	 * @param array<string,string> $sections Existing sections.
	 * @return array<string,string>
	 */
	public function register_section( array $sections ): array {
		$sections['checkoutly_checkout_routing'] = __( 'Checkoutly Checkout Routing', 'checkoutly' );

		return $sections;
	}

	/**
	 * Register section settings.
	 *
	 * @param array<int,array<string,mixed>> $settings Existing settings.
	 * @param string                         $section  Current section.
	 * @return array<int,array<string,mixed>>
	 */
	public function register_settings( array $settings, string $section ): array {
		if ( 'checkoutly_checkout_routing' !== $section ) {
			return $settings;
		}

		return array(
			array(
				'title' => __( 'Checkoutly Checkout Routing', 'checkoutly' ),
				'type'  => 'title',
				'id'    => 'checkoutly_checkout_routing_options',
			),
			array(
				'title'    => __( 'Customer flow', 'checkoutly' ),
				'id'       => self::OPTION_ROUTING_MODE,
				'type'     => 'select',
				'default'  => self::ROUTING_STANDARD,
				'options'  => array(
					self::ROUTING_STANDARD  => __( 'Standard: Product > Cart > Checkout', 'checkoutly' ),
					self::ROUTING_SKIP_CART => __( 'Skip Cart: Product > Checkout', 'checkoutly' ),
				),
				'desc_tip' => __( 'Routes add-to-cart requests to the configured WooCommerce checkout page. Does not replace WooCommerce checkout.', 'checkoutly' ),
			),
			array(
				'title'   => __( 'Buy Now button', 'checkoutly' ),
				'id'      => self::OPTION_BUY_NOW_ENABLED,
				'type'    => 'checkbox',
				'default' => 'no',
				'desc'    => __( 'Show a separate Buy Now button on simple product pages.', 'checkoutly' ),
			),
			array(
				'title'   => __( 'Buy Now label', 'checkoutly' ),
				'id'      => self::OPTION_BUY_NOW_LABEL,
				'type'    => 'text',
				'default' => __( 'Buy Now', 'checkoutly' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'checkoutly_checkout_routing_options',
			),
		);
	}

	/**
	 * Get routing mode.
	 */
	public function routing_mode(): string {
		$mode = get_option( self::OPTION_ROUTING_MODE, self::ROUTING_STANDARD );

		if ( ! in_array( $mode, array( self::ROUTING_STANDARD, self::ROUTING_SKIP_CART ), true ) ) {
			return self::ROUTING_STANDARD;
		}

		return (string) $mode;
	}

	/**
	 * Whether Skip Cart is enabled.
	 */
	public function skip_cart_enabled(): bool {
		return self::ROUTING_SKIP_CART === $this->routing_mode();
	}

	/**
	 * Whether Buy Now is enabled.
	 */
	public function buy_now_enabled(): bool {
		return 'yes' === get_option( self::OPTION_BUY_NOW_ENABLED, 'no' );
	}

	/**
	 * Get Buy Now label.
	 */
	public function buy_now_label(): string {
		$label = trim( (string) get_option( self::OPTION_BUY_NOW_LABEL, __( 'Buy Now', 'checkoutly' ) ) );

		return '' !== $label ? $label : __( 'Buy Now', 'checkoutly' );
	}
}
