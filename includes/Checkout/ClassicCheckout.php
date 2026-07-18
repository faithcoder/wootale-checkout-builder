<?php
/**
 * Classic shortcode checkout integration.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * Applies WooTale workflow settings to WooCommerce classic checkout.
 */
final class ClassicCheckout {
	/**
	 * Workflow repository.
	 *
	 * @var Workflow
	 */
	private $workflow;

	public function __construct( Workflow $workflow ) {
		$this->workflow = $workflow;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_checkout_fields' ), 20 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_custom_fields' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ) );
	}

	/**
	 * Modify classic checkout fields from the saved builder config.
	 *
	 * @param array<string,array<string,array<string,mixed>>> $checkout_fields Checkout fields.
	 * @return array<string,array<string,array<string,mixed>>>
	 */
	public function filter_checkout_fields( array $checkout_fields ): array {
		$enabled_native_fields = array();

		foreach ( $this->workflow->fields() as $position => $field ) {
			if ( 'component' === $field['type'] ) {
				continue;
			}

			$section = (string) $field['section'];
			$key     = (string) $field['key'];

			if ( 'custom' === $field['type'] ) {
				if ( empty( $field['enabled'] ) ) {
					continue;
				}

				$checkout_fields[ $section ][ $key ] = $this->custom_checkout_field( $field, $position );
				continue;
			}

			if ( isset( $checkout_fields[ $section ][ $key ] ) ) {
				if ( empty( $field['enabled'] ) ) {
					unset( $checkout_fields[ $section ][ $key ] );
					continue;
				}

				$enabled_native_fields[ $section ][ $key ]          = true;
				$checkout_fields[ $section ][ $key ]['label']    = $field['label'];
				$checkout_fields[ $section ][ $key ]['required'] = ! empty( $field['required'] );
				$checkout_fields[ $section ][ $key ]['priority'] = (int) $field['priority'];
				$checkout_fields[ $section ][ $key ]['class']    = $this->field_classes( $checkout_fields[ $section ][ $key ]['class'] ?? array(), $field );
				$checkout_fields[ $section ][ $key ]['default']  = (string) ( $field['default'] ?? '' );

				if ( '' !== (string) ( $field['placeholder'] ?? '' ) ) {
					$checkout_fields[ $section ][ $key ]['placeholder'] = (string) $field['placeholder'];
				}

				if ( ! empty( $field['validation'] ) && is_array( $field['validation'] ) ) {
					$checkout_fields[ $section ][ $key ]['validate'] = array_values( array_unique( $field['validation'] ) );
				}
			}
		}

		foreach ( $this->native_checkout_keys() as $section => $keys ) {
			foreach ( $keys as $key ) {
				if ( isset( $checkout_fields[ $section ][ $key ] ) && empty( $enabled_native_fields[ $section ][ $key ] ) ) {
					unset( $checkout_fields[ $section ][ $key ] );
				}
			}
		}

		return $checkout_fields;
	}

	/**
	 * Save custom fields to order meta.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $data  Posted checkout data.
	 */
	public function save_custom_fields( $order, array $data ): void {
		unset( $data );

		foreach ( $this->workflow->fields() as $field ) {
			if ( 'custom' !== $field['type'] || empty( $field['enabled'] ) ) {
				continue;
			}

			$key = (string) $field['key'];

			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

			$order->update_meta_data( FieldRegistry::meta_key( $key ), $value );
			$order->update_meta_data( FieldRegistry::meta_key( $key ) . '_label', (string) $field['label'] );
			$order->update_meta_data( FieldRegistry::meta_key( $key ) . '_display_order_details', ! empty( $field['display']['orderDetails'] ) ? 'yes' : 'no' );
			$order->update_meta_data( FieldRegistry::meta_key( $key ) . '_display_emails', ! empty( $field['display']['emails'] ) ? 'yes' : 'no' );
			$order->update_meta_data( FieldRegistry::meta_key( $key ) . '_display_thank_you', ! empty( $field['display']['thankYou'] ) ? 'yes' : 'no' );
		}
	}

	/**
	 * Enqueue frontend step controller for the classic shortcode checkout.
	 */
	public function enqueue_checkout_assets(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}

		$workflow = $this->workflow->get();

		wp_register_script( 'wtcb-classic-checkout', false, array(), WTCB_VERSION, true );
		wp_enqueue_script( 'wtcb-classic-checkout' );
		wp_add_inline_script( 'wtcb-classic-checkout', 'window.wtcbClassicWorkflow = ' . wp_json_encode( $workflow ) . ';' . $this->frontend_script() );

		wp_register_style( 'wtcb-classic-checkout', false, array(), WTCB_VERSION );
		wp_enqueue_style( 'wtcb-classic-checkout' );
		wp_add_inline_style( 'wtcb-classic-checkout', $this->frontend_styles() );
	}

	/**
	 * Build a custom WooCommerce checkout field definition.
	 *
	 * @param array<string,mixed> $field    WooTale field.
	 * @param int                 $position Position.
	 * @return array<string,mixed>
	 */
	private function custom_checkout_field( array $field, int $position ): array {
		$type = in_array( $field['fieldType'], array( 'text', 'email', 'tel', 'number', 'textarea', 'select', 'radio', 'checkbox' ), true ) ? $field['fieldType'] : 'text';

		$checkout_field = array(
			'type'     => $type,
			'label'    => (string) $field['label'],
			'required' => ! empty( $field['required'] ),
			'class'    => $this->field_classes( array( 'form-row-wide', 'wtcb-custom-checkout-field' ), $field ),
			'default'  => (string) ( $field['default'] ?? '' ),
			'placeholder' => (string) ( $field['placeholder'] ?? '' ),
			'validate' => ! empty( $field['validation'] ) && is_array( $field['validation'] ) ? array_values( array_unique( $field['validation'] ) ) : array(),
			'priority' => (int) $field['priority'] + $position,
		);

		if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			$checkout_field['options'] = $this->checkout_field_options( (string) ( $field['options'] ?? '' ) );
		}

		return $checkout_field;
	}

	/**
	 * Get WooCommerce field wrapper classes for a WooTale field.
	 *
	 * @param mixed               $classes Existing classes.
	 * @param array<string,mixed> $field   WooTale field.
	 * @return array<int,string>
	 */
	private function field_classes( $classes, array $field ): array {
		$classes = is_array( $classes ) ? array_values( $classes ) : array();
		$width   = isset( $field['width'] ) ? (int) $field['width'] : 2;

		$classes = array_values(
			array_filter(
				$classes,
				static function ( $class ): bool {
					return is_string( $class ) && 0 !== strpos( $class, 'wtcb-width-' );
				}
			)
		);

		$classes[] = 'wtcb-classic-field';
		$classes[] = 'wtcb-width-' . ( in_array( $width, array( 1, 2 ), true ) ? $width : 2 );

		return array_values( array_unique( $classes ) );
	}

	/**
	 * @return array<string,array<int,string>>
	 */
	private function native_checkout_keys(): array {
		return array(
			'billing'  => array(
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_country',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_state',
				'billing_postcode',
				'billing_phone',
				'billing_email',
			),
			'shipping' => array(
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_country',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_state',
				'shipping_postcode',
			),
			'order'    => array(
				'order_comments',
			),
		);
	}

	/**
	 * @return array<string,string>
	 */
	private function checkout_field_options( string $options ): array {
		$parsed = array();
		$lines  = preg_split( '/\r\n|\r|\n/', $options );

		foreach ( false === $lines ? array() : $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$key            = sanitize_title( $line );
			$parsed[ $key ] = $line;
		}

		if ( empty( $parsed ) ) {
			return array(
				''       => __( 'Select an option', 'wootale-checkout-builder' ),
				'option' => __( 'Option', 'wootale-checkout-builder' ),
			);
		}

		return $parsed;
	}

	private function frontend_script(): string {
		return <<<'JS'
(function () {
	function ready(callback) {
		if (document.readyState !== 'loading') {
			callback();
			return;
		}
		document.addEventListener('DOMContentLoaded', callback);
	}

	function fieldElement(field) {
		if (field.type === 'component') {
			if (field.key === 'order_review') {
				return document.querySelector('#order_review');
			}
			if (field.key === 'payment_methods' || field.key === 'place_order' || field.key === 'terms') {
				return document.querySelector('#payment');
			}
			return null;
		}

		return document.getElementById(field.key + '_field');
	}

	function cleanupEmptyNativeSections() {
		Array.prototype.forEach.call(document.querySelectorAll('.woocommerce-additional-fields'), function(section) {
			if (!section.querySelector('.form-row, p.form-row, #order_comments_field')) {
				section.style.display = 'none';
			}
		});
	}

	function mountCheckoutSteps() {
		var workflow = window.wtcbClassicWorkflow || {};
		var form = document.querySelector('form.checkout');

		if (!form || !workflow.steps || form.dataset.wtcbClassicMounted === 'true') {
			return;
		}

		var host = document.createElement('div');
		var nav = document.createElement('ol');
		var panels = document.createElement('div');
		var state = { active: 0 };
		var visibleSteps = (workflow.steps || []).filter(function (step) {
			return (step.fields || []).some(function (field) { return field.enabled !== false; });
		});
		var isMultiStep = workflow.multiStepEnabled !== false && visibleSteps.length > 1;

		host.className = 'wtcb-classic-checkout wtcb-orientation-' + (workflow.orientation || 'horizontal') + ' wtcb-indicator-' + (workflow.indicator || 'number') + ' wtcb-connector-' + (workflow.connector || 'solid') + (isMultiStep ? '' : ' is-single-step');
		nav.className = 'wtcb-classic-steps';
		panels.className = 'wtcb-classic-panels';
		if (isMultiStep) {
			host.append(nav);
		}
		host.append(panels);
		form.prepend(host);

		visibleSteps.forEach(function (step, index) {
			var item = document.createElement('li');
			var button = document.createElement('button');
			var panel = document.createElement('section');
			var heading = document.createElement('h3');
			var description = document.createElement('p');

			if (isMultiStep) {
				button.type = 'button';
				button.textContent = step.title || ('Step ' + (index + 1));
				button.dataset.wtcbStep = String(index);
				button.style.setProperty('--wtcb-active-color', workflow.activeColor || '#2563eb');
				item.append(button);
				nav.append(item);
			}

			panel.className = 'wtcb-classic-panel';
			panel.dataset.wtcbStep = String(index);
			panel.style.setProperty('--wtcb-step-color', step.color || '#2563eb');
			if (isMultiStep) {
				heading.textContent = step.title || ('Step ' + (index + 1));
				description.textContent = step.description || '';
				panel.append(heading, description);
			}

			(step.fields || []).forEach(function (field) {
				if (field.enabled === false) {
					return;
				}

				var element = fieldElement(field);

				if (!element || element.dataset.wtcbMounted === 'true') {
					return;
				}

				element.classList.remove('form-row-first', 'form-row-last', 'form-row-wide', 'wtcb-width-1', 'wtcb-width-2', 'wtcb-width-3');
				element.classList.add('wtcb-classic-field', 'wtcb-width-' + (field.width || 2));
				element.dataset.wtcbMounted = 'true';
				panel.append(element);
			});

			panels.append(panel);
		});

		var actions = document.createElement('div');
		var prev = document.createElement('button');
		var next = document.createElement('button');

		if (isMultiStep) {
			actions.className = 'wtcb-classic-actions';
			prev.type = 'button';
			next.type = 'button';
			prev.textContent = 'Previous';
			next.textContent = 'Continue';
			actions.append(prev, next);
			host.append(actions);
		}

		function render() {
			if (!isMultiStep) {
				Array.prototype.forEach.call(panels.children, function (panel) {
					panel.hidden = false;
				});
				return;
			}
			Array.prototype.forEach.call(nav.querySelectorAll('button'), function (button, index) {
				button.setAttribute('aria-current', index === state.active ? 'step' : 'false');
			});
			Array.prototype.forEach.call(panels.children, function (panel, index) {
				panel.hidden = index !== state.active;
			});
			prev.hidden = state.active === 0;
			next.hidden = state.active >= panels.children.length - 1;
		}

		function go(nextIndex) {
			state.active = Math.max(0, Math.min(nextIndex, panels.children.length - 1));
			render();
		}

		nav.addEventListener('click', function (event) {
			var button = event.target.closest('button[data-wtcb-step]');
			if (button) {
				go(Number(button.dataset.wtcbStep));
			}
		});
		prev.addEventListener('click', function () { go(state.active - 1); });
		next.addEventListener('click', function () { go(state.active + 1); });

		form.dataset.wtcbClassicMounted = 'true';
		cleanupEmptyNativeSections();
		render();
	}

	ready(mountCheckoutSteps);
	document.body.addEventListener('updated_checkout', mountCheckoutSteps);
})();
JS;
	}

	private function frontend_styles(): string {
		return <<<'CSS'
.wtcb-classic-checkout{--wtcb-active-color:#2563eb;border:1px solid #dbeafe;border-radius:8px;margin-bottom:24px;padding:16px}
.wtcb-classic-checkout.is-single-step{border:0;margin-bottom:0;padding:0}
.wtcb-classic-steps{display:flex;flex-wrap:wrap;gap:8px;list-style:none;margin:0 0 16px;padding:0}
.wtcb-classic-steps button{background:#fff;border:1px solid #dbeafe;border-radius:6px;color:#1f2937;cursor:pointer;padding:8px 12px}
.wtcb-classic-steps button[aria-current=step]{background:var(--wtcb-active-color,#2563eb);border-color:var(--wtcb-active-color,#2563eb);color:#fff}
.wtcb-orientation-vertical{display:grid;gap:16px;grid-template-columns:minmax(160px,220px) 1fr}
.wtcb-orientation-vertical .wtcb-classic-steps{display:grid;margin:0}
.wtcb-classic-panel{border:1px solid var(--wtcb-step-color,#2563eb);border-radius:8px;display:grid!important;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr));padding:16px}
.wtcb-classic-panel[hidden]{display:none!important}
.wtcb-classic-checkout.is-single-step .wtcb-classic-panel{border:0;padding:0}
.wtcb-classic-panel h3{margin-top:0}
.wtcb-classic-panel h3,.wtcb-classic-panel>p{grid-column:1/-1}
.wtcb-classic-panel .wtcb-classic-field{box-sizing:border-box!important;clear:none!important;float:none!important;flex:none!important;margin:0!important;max-width:100%!important;width:auto!important}
.wtcb-classic-panel .wtcb-width-1{grid-column:span 1!important}
.wtcb-classic-panel .wtcb-width-2{grid-column:1/-1!important}
.wtcb-connector-none .wtcb-classic-steps button{border-color:#e5e7eb}
.wtcb-indicator-tab .wtcb-classic-steps button{border-radius:0;border-width:0 0 2px}
.wtcb-classic-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:16px}
#customer_details .woocommerce-billing-fields,#customer_details .woocommerce-shipping-fields{display:none}
.wtcb-classic-panel #order_review{grid-column:1/-1!important}
@media(max-width:700px){.wtcb-orientation-vertical{display:block}.wtcb-classic-panel{grid-template-columns:1fr}.wtcb-classic-panel .wtcb-width-1,.wtcb-classic-panel .wtcb-width-2{grid-column:1/-1!important}}
CSS;
	}
}
