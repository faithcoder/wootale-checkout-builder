<?php
/**
 * Display persisted Checkoutly field data.
 *
 * @package Checkoutly\CheckoutBuilder
 */

declare(strict_types=1);

namespace Checkoutly\CheckoutBuilder\Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * Displays stored field values in early supported locations.
 */
final class Display {
	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'render_admin_order_fields' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'render_order_details_fields' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'render_thank_you_fields' ) );
		add_action( 'woocommerce_email_order_meta', array( $this, 'render_email_fields' ), 20, 3 );
		add_filter( 'woocommerce_privacy_export_order_personal_data', array( $this, 'export_order_personal_data' ), 10, 2 );
		add_filter( 'woocommerce_privacy_erase_order_personal_data', array( $this, 'erase_order_personal_data' ), 10, 2 );
	}

	/**
	 * Render order meta in admin.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function render_admin_order_fields( $order ): void {
		$fields = $this->get_order_fields( $order, 'orderDetails' );

		if ( empty( $fields ) ) {
			return;
		}

		echo '<div class="checkoutly-order-fields"><h3>' . esc_html__( 'Checkoutly Checkout Fields', 'checkoutly' ) . '</h3>';

		foreach ( $fields as $field ) {
			printf(
				'<p><strong>%s:</strong> %s</p>',
				esc_html( $field['label'] ),
				esc_html( $field['value'] )
			);
		}

		echo '</div>';
	}

	/**
	 * Render configured fields on frontend order details.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function render_order_details_fields( $order ): void {
		$this->render_field_table( $order, 'orderDetails', 'checkoutly-order-detail-fields' );
	}

	/**
	 * Render configured fields on the thank-you page.
	 *
	 * @param int|\WC_Order $order Order ID or order object.
	 */
	public function render_thank_you_fields( $order ): void {
		if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( (int) $order );
		}

		if ( ! $order ) {
			return;
		}

		$this->render_field_table( $order, 'thankYou', 'checkoutly-thank-you-fields' );
	}

	/**
	 * Render a frontend field table for a display location.
	 *
	 * @param \WC_Order $order    Order object.
	 * @param string    $location Display location.
	 * @param string    $class    Section class.
	 */
	private function render_field_table( $order, string $location, string $class ): void {
		$fields = $this->get_order_fields( $order, $location );

		if ( empty( $fields ) ) {
			return;
		}

		echo '<section class="' . esc_attr( $class ) . '"><h2>' . esc_html__( 'Additional information', 'checkoutly' ) . '</h2><table class="woocommerce-table shop_table"><tbody>';

		foreach ( $fields as $field ) {
			printf(
				'<tr><th>%s</th><td>%s</td></tr>',
				esc_html( $field['label'] ),
				esc_html( $field['value'] )
			);
		}

		echo '</tbody></table></section>';
	}

	/**
	 * Render configured fields in WooCommerce order emails.
	 *
	 * @param \WC_Order $order         Order object.
	 * @param bool      $sent_to_admin Whether the email is sent to admin.
	 * @param bool      $plain_text    Whether the email is plain text.
	 */
	public function render_email_fields( $order, bool $sent_to_admin, bool $plain_text ): void {
		unset( $sent_to_admin );

		$fields = $this->get_order_fields( $order, 'emails' );

		if ( empty( $fields ) ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Additional information', 'checkoutly' ) . "\n";

			foreach ( $fields as $field ) {
				echo esc_html( $field['label'] . ': ' . $field['value'] ) . "\n";
			}

			return;
		}

		echo '<h2>' . esc_html__( 'Additional information', 'checkoutly' ) . '</h2><table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #e5e5e5" border="1"><tbody>';

		foreach ( $fields as $field ) {
			printf(
				'<tr><th scope="row">%s</th><td>%s</td></tr>',
				esc_html( $field['label'] ),
				esc_html( $field['value'] )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Get saved Checkoutly order fields.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string|null $location Optional display location.
	 * @return array<int,array{key:string,label:string,value:string,display:array<string,bool>}>
	 */
	public function get_order_fields( $order, ?string $location = null ): array {
		$fields = array();

		foreach ( $order->get_meta_data() as $meta ) {
			$data = $meta->get_data();
			$key  = isset( $data['key'] ) ? (string) $data['key'] : '';

			if ( 0 !== strpos( $key, '_checkoutly_' ) || $this->is_private_field_meta( $key ) ) {
				continue;
			}

			$field_key = substr( $key, 6 );
			$label     = $order->get_meta( $key . '_label' );
			$display   = array(
				'orderDetails' => 'no' !== $order->get_meta( $key . '_display_order_details' ),
				'emails'       => 'no' !== $order->get_meta( $key . '_display_emails' ),
				'thankYou'     => 'no' !== $order->get_meta( $key . '_display_thank_you' ),
			);

			if ( null !== $location && empty( $display[ $location ] ) ) {
				continue;
			}

			$fields[] = array(
				'key'     => $field_key,
				'label'   => $label ? (string) $label : $field_key,
				'value'   => (string) $data['value'],
				'display' => $display,
			);
		}

		return $fields;
	}

	private function is_private_field_meta( string $key ): bool {
		foreach ( array( '_label', '_display_order_details', '_display_emails', '_display_thank_you' ) as $suffix ) {
			if ( $suffix === substr( $key, -strlen( $suffix ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Export order personal data.
	 *
	 * @param array<int,array<string,string>> $personal_data Personal data.
	 * @param \WC_Order                       $order         Order object.
	 * @return array<int,array<string,string>>
	 */
	public function export_order_personal_data( array $personal_data, $order ): array {
		foreach ( $this->get_order_fields( $order ) as $field ) {
			$personal_data[] = array(
				'name'  => 'Checkoutly: ' . $field['label'],
				'value' => $field['value'],
			);
		}

		return $personal_data;
	}

	/**
	 * Erase order personal data.
	 *
	 * @param array<string,mixed> $response Eraser response.
	 * @param \WC_Order          $order    Order object.
	 * @return array<string,mixed>
	 */
	public function erase_order_personal_data( array $response, $order ): array {
		foreach ( $this->get_order_fields( $order ) as $field ) {
			$order->delete_meta_data( FieldRegistry::meta_key( $field['key'] ) );
			$order->delete_meta_data( FieldRegistry::meta_key( $field['key'] ) . '_label' );
			$order->delete_meta_data( FieldRegistry::meta_key( $field['key'] ) . '_display_order_details' );
			$order->delete_meta_data( FieldRegistry::meta_key( $field['key'] ) . '_display_emails' );
			$order->delete_meta_data( FieldRegistry::meta_key( $field['key'] ) . '_display_thank_you' );
			$response['items_removed'] = true;
		}

		$order->save();

		return $response;
	}
}
